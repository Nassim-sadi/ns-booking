<?php
if (!defined('ABSPATH')) exit;

class NSBC_Validation {
    public static function sanitize_settings($input) {
        $out = get_option('nsbc_settings', []);
        if (!is_array($input)) return $out;
        $out['currency'] = isset($input['currency']) ? sanitize_text_field($input['currency']) : ($out['currency'] ?? 'EUR');
        $out['admin_emails'] = isset($input['admin_emails']) ? sanitize_text_field($input['admin_emails']) : ($out['admin_emails'] ?? '');
        $out['min_lead_days'] = isset($input['min_lead_days']) ? max(0, (int)$input['min_lead_days']) : 1;
        $out['blackout_dates'] = isset($input['blackout_dates']) ? sanitize_text_field($input['blackout_dates']) : '';
        $out['phone_default_country'] = isset($input['phone_default_country']) ? sanitize_text_field($input['phone_default_country']) : '+33';
        $out['phone_countries'] = isset($input['phone_countries']) ? sanitize_text_field($input['phone_countries']) : ($out['phone_countries'] ?? '');
        $out['email_admin_subject'] = isset($input['email_admin_subject']) ? sanitize_text_field($input['email_admin_subject']) : ($out['email_admin_subject'] ?? '');
        $out['email_customer_subject'] = isset($input['email_customer_subject']) ? sanitize_text_field($input['email_customer_subject']) : ($out['email_customer_subject'] ?? '');
        $currencies = ['EUR','USD','GBP','MAD','TRY','AED','SAR'];
        if (!in_array(strtoupper($out['currency']), $currencies, true)) $out['currency'] = 'EUR';
        return $out;
    }

    /**
     * Validate submission. Returns array [ok=>bool, errors=>[], data=>sanitized]
     */
    public static function validate_submission(array $raw): array {
        $errors = [];
        $settings = get_option('nsbc_settings', function_exists('nsbc_default_settings') ? nsbc_default_settings() : []);
        $minLead = (int)($settings['min_lead_days'] ?? 1);

        $package_id = isset($raw['package_id']) ? (int)$raw['package_id'] : 0;
        $session = isset($raw['session_type']) ? sanitize_key($raw['session_type']) : 'solo';
        if (!in_array($session, ['solo','couple'], true)) $session='solo';

        $extra_ids = [];
        if (isset($raw['extras']) && is_array($raw['extras'])) {
            foreach ($raw['extras'] as $e) $extra_ids[] = (int)$e;
            $extra_ids = array_values(array_unique(array_filter($extra_ids)));
        }

        $date_raw = isset($raw['date']) ? sanitize_text_field($raw['date']) : '';
        $name = isset($raw['name']) ? sanitize_text_field($raw['name']) : '';
        $email = isset($raw['email']) ? sanitize_email($raw['email']) : '';
        $phone_country = isset($raw['phone_country']) ? sanitize_text_field($raw['phone_country']) : '';
        $phone_number = isset($raw['phone']) ? sanitize_text_field($raw['phone']) : (isset($raw['phone_number']) ? sanitize_text_field($raw['phone_number']) : '');
        $message = isset($raw['message']) ? sanitize_textarea_field($raw['message']) : '';
        $honeypot = isset($raw['website']) ? trim((string)$raw['website']) : (isset($raw['nsbc_website']) ? trim((string)$raw['nsbc_website']) : '');

        if ($honeypot !== '') $errors[] = __('Spam detected.','ns-booking');

        if (!$package_id || get_post_type($package_id) !== NSBC_CPT_PACKAGE || get_post_status($package_id) !== 'publish') {
            $errors[] = __('Invalid package.','ns-booking');
        } else {
            $active = get_post_meta($package_id, '_package_active', true);
            if ($active !== '' && !$active && $active !== '1') $errors[] = __('Package not available.','ns-booking');
            // Validate extras belong to package
            $allowed = array_map('intval', (array)get_post_meta($package_id, '_package_extra_ids', true));
            foreach ($extra_ids as $eid) {
                if (!in_array($eid, $allowed, true)) $errors[] = sprintf(__('Extra %d not available for this package.','ns-booking'), $eid);
                elseif (get_post_type($eid) !== NSBC_CPT_EXTRA) $errors[] = __('Invalid extra.','ns-booking');
            }
        }

        // Date: Y-m-d, >= today+minLead, not blackout
        if (empty($date_raw)) $errors[] = __('Date is required.','ns-booking');
        else {
            $d = DateTime::createFromFormat('Y-m-d', $date_raw);
            $valid = $d && $d->format('Y-m-d') === $date_raw;
            if (!$valid) $errors[] = __('Invalid date format.','ns-booking');
            else {
                $today = new DateTime('today');
                $minDate = (clone $today)->modify('+' . $minLead . ' days');
                if ($d < $minDate) $errors[] = sprintf(__('Date must be at least %d day(s) in the future.','ns-booking'), $minLead);
                $blackout = array_filter(array_map('trim', explode(',', (string)($settings['blackout_dates'] ?? ''))));
                if (in_array($date_raw, $blackout, true)) $errors[] = __('Selected date is not available.','ns-booking');
            }
        }

        if (mb_strlen($name) < 2) $errors[] = __('Name is required.','ns-booking');
        if (!is_email($email)) $errors[] = __('Valid email is required.','ns-booking');
        // Phone: country + number
        $phone_country = preg_replace('/\s+/', '', $phone_country);
        if (!preg_match('/^\+\d{1,4}$/', $phone_country)) $errors[] = __('Invalid country code.','ns-booking');
        $digits = preg_replace('/\D+/', '', $phone_number);
        if (strlen($digits) < 6 || strlen($digits) > 15) $errors[] = __('Valid phone number is required.','ns-booking');

        $data = [
            'package_id'=>$package_id,
            'session_type'=>$session,
            'extra_ids'=>$extra_ids,
            'date'=>$date_raw,
            'name'=>$name,
            'email'=>$email,
            'phone_country'=>$phone_country,
            'phone_number'=>$digits,
            'phone_full'=>$phone_country . $digits,
            'message'=>$message,
        ];
        return ['ok'=>empty($errors),'errors'=>$errors,'data'=>$data];
    }
}
