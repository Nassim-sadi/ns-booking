<?php
if (!defined('ABSPATH')) exit;

class NSBC_Ajax {
    public function register_rest() {
        register_rest_route('nsbc/v1','/bookings',[
            'methods'=>'POST',
            'callback'=>[$this,'handle_rest'],
            'permission_callback'=>'__return_true',
            'args'=>[],
        ]);
    }

    public function handle_rest(WP_REST_Request $req) {
        // Nonce via header X-WP-Nonce or _wpnonce
        $nonce = $req->get_header('X-WP-Nonce');
        if (!$nonce) $nonce = $req->get_param('_wpnonce') ?: $req->get_param('nonce');
        if ($nonce && !wp_verify_nonce($nonce,'wp_rest') && !wp_verify_nonce($nonce,'nsbc_submit')) {
            return new WP_Error('nsbc_nonce','Nonce invalid.',['status'=>403]);
        }
        // Rate limit
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'nsbc_rate_' . md5($ip);
        $count = (int)get_transient($key);
        if ($count > 10) return new WP_Error('nsbc_rate','Too many requests.',['status'=>429]);
        set_transient($key, $count+1, 60);

        $body = $req->get_json_params();
        if (empty($body)) $body = $req->get_body_params();
        return $this->process($body);
    }

    public function handle_ajax() {
        $nonce = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? $_SERVER['HTTP_X_WP_NONCE'] ?? '';
        if (!wp_verify_nonce($nonce,'nsbc_submit') && !wp_verify_nonce($nonce,'wp_rest')) {
            wp_send_json_error(['message'=>__('Security check failed.','ns-booking')], 403);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'nsbc_rate_' . md5($ip);
        $count=(int)get_transient($key);
        if ($count>10) wp_send_json_error(['message'=>__('Too many requests.','ns-booking')],429);
        set_transient($key,$count+1,60);

        $raw = $_POST;
        // JSON fallback
        $json = file_get_contents('php://input');
        if ($json) { $j=json_decode($json,true); if (is_array($j)) $raw=array_merge($raw,$j); }
        $res = $this->process($raw);
        if (is_wp_error($res)) {
            wp_send_json_error(['message'=>$res->get_error_message(),'errors'=>$res->get_error_data()], $res->get_error_data()['status'] ?? 400);
        }
        wp_send_json_success($res);
    }

    public function handle_recalc() {
        check_ajax_referer('nsbc_recalc','_ajax_nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Forbidden',403);
        if (!empty($_POST['resend'])) {
            $pid=(int)($_POST['post_id']??0);
            if ($pid) {
                NSBC_Emails::send_admin($pid);
                NSBC_Emails::send_customer($pid);
                wp_send_json_success('Emails resent');
            }
            wp_send_json_error('No booking');
        }
        $pkg=(int)($_POST['package_id']??0);
        $sess=sanitize_key($_POST['session_type']??'solo');
        $extras=isset($_POST['extras']) && is_array($_POST['extras']) ? array_map('intval',$_POST['extras']) : [];
        $cents=NSBC_Pricing::calculate($pkg,$sess,$extras);
        $settings=get_option('nsbc_settings',[]);
        $curr=$settings['currency']??'EUR';
        wp_send_json_success(['cents'=>$cents,'formatted'=>NSBC_Pricing::format($cents,$curr)]);
    }

    private function process(array $raw) {
        // Normalize keys from JS: packageId/package_id, sessionType/session_type, phoneCountry etc.
        $norm = [
            'package_id'=> $raw['package_id'] ?? $raw['packageId'] ?? 0,
            'session_type'=> $raw['session_type'] ?? $raw['sessionType'] ?? 'solo',
            'extras'=> $raw['extras'] ?? $raw['extraIds'] ?? [],
            'date'=> $raw['date'] ?? $raw['preferred_date'] ?? '',
            'name'=> $raw['name'] ?? $raw['full_name'] ?? $raw['customer_name'] ?? '',
            'email'=> $raw['email'] ?? $raw['customer_email'] ?? '',
            'phone_country'=> $raw['phone_country'] ?? $raw['phoneCountry'] ?? '',
            'phone'=> $raw['phone'] ?? $raw['phone_number'] ?? $raw['phoneNumber'] ?? '',
            'message'=> $raw['message'] ?? '',
            'website'=> $raw['website'] ?? $raw['nsbc_website'] ?? '',
        ];
        // Frontend may send total but we ignore it entirely.
        $validation = NSBC_Validation::validate_submission($norm);
        if (!$validation['ok']) {
            return new WP_Error('nsbc_validation', implode(' ', $validation['errors']), ['status'=>400,'errors'=>$validation['errors']]);
        }
        $d = $validation['data'];
        $settings=get_option('nsbc_settings', function_exists('nsbc_default_settings') ? nsbc_default_settings() : []);
        $currency = $settings['currency'] ?? 'EUR';
        // Server-side price
        $cents = NSBC_Pricing::calculate($d['package_id'], $d['session_type'], $d['extra_ids']);
        $formatted = NSBC_Pricing::format($cents, $currency);
        // Resolve labels for extras
        $labels=[];
        foreach($d['extra_ids'] as $eid){ $t=get_the_title($eid); if($t) $labels[]=$t; }
        $packageLabel = get_the_title($d['package_id']);

        $booking_id = wp_insert_post([
            'post_type'=>NSBC_CPT_BOOKING,
            'post_status'=>'pending', // pending post status, meta status also pending
            'post_title'=> sprintf('Booking — %s — %s', $d['name'], $d['date']),
        ], true);
        if (is_wp_error($booking_id)) return $booking_id;

        update_post_meta($booking_id,'_booking_package_id',$d['package_id']);
        update_post_meta($booking_id,'_booking_package_label',$packageLabel);
        update_post_meta($booking_id,'_booking_session_type',$d['session_type']);
        update_post_meta($booking_id,'_booking_extras',$d['extra_ids']);
        update_post_meta($booking_id,'_booking_extras_labels',$labels);
        update_post_meta($booking_id,'_booking_date',$d['date']);
        update_post_meta($booking_id,'_booking_total_cents',$cents);
        update_post_meta($booking_id,'_booking_total_formatted',$formatted);
        update_post_meta($booking_id,'_booking_currency',$currency);
        update_post_meta($booking_id,'_booking_customer_name',$d['name']);
        update_post_meta($booking_id,'_booking_customer_email',$d['email']);
        update_post_meta($booking_id,'_booking_phone_country',$d['phone_country']);
        update_post_meta($booking_id,'_booking_phone_number',$d['phone_number']);
        update_post_meta($booking_id,'_booking_phone_full',$d['phone_full']);
        update_post_meta($booking_id,'_booking_customer_message',$d['message']);
        update_post_meta($booking_id,'_booking_status','pending');

        $snapshot = [
            'package_id'=>$d['package_id'],'package_label'=>$packageLabel,
            'session_type'=>$d['session_type'],'extra_ids'=>$d['extra_ids'],'extra_labels'=>$labels,
            'date'=>$d['date'],'total_cents'=>$cents,'total_formatted'=>$formatted,'currency'=>$currency,
            'customer'=>['name'=>$d['name'],'email'=>$d['email'],'phone_country'=>$d['phone_country'],'phone_number'=>$d['phone_number'],'phone_full'=>$d['phone_full'],'message'=>$d['message']],
            'created_at'=> current_time('mysql'),
            'ip'=> $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        update_post_meta($booking_id,'_booking_snapshot', wp_json_encode($snapshot, JSON_UNESCAPED_UNICODE));

        // Final title
        wp_update_post(['ID'=>$booking_id,'post_title'=> sprintf('Booking #%d — %s — %s', $booking_id, $d['name'], $d['date'])]);

        // Emails (non-blocking failure)
        NSBC_Emails::send_admin($booking_id);
        NSBC_Emails::send_customer($booking_id);

        return ['bookingId'=>$booking_id,'total'=>$formatted,'totalCents'=>$cents,'message'=>__('Booking received.','ns-booking')];
    }
}
