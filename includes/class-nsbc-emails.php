<?php
if (!defined('ABSPATH')) exit;

class NSBC_Emails {
    private static function replace_tags($text, $booking_id) {
        $package_id = (int)get_post_meta($booking_id,'_booking_package_id',true);
        $package = $package_id ? get_the_title($package_id) : '';
        $session = get_post_meta($booking_id,'_booking_session_type',true);
        $date = get_post_meta($booking_id,'_booking_date',true);
        $total = get_post_meta($booking_id,'_booking_total_formatted',true);
        $name = get_post_meta($booking_id,'_booking_customer_name',true);
        $email = get_post_meta($booking_id,'_booking_customer_email',true);
        $phone = get_post_meta($booking_id,'_booking_phone_full',true);
        $extras = get_post_meta($booking_id,'_booking_extras_labels',true);
        if (is_array($extras)) $extras = implode(', ', $extras);
        $map = [
            '{{id}}'=>$booking_id,
            '{{package}}'=>$package,
            '{{session}}'=>$session,
            '{{extras}}'=>$extras,
            '{{date}}'=>$date,
            '{{total}}'=>$total,
            '{{customer_name}}'=>$name,
            '{{customer_email}}'=>$email,
            '{{phone}}'=>$phone,
        ];
        return strtr((string)$text, $map);
    }

    public static function send_admin(int $booking_id) {
        $settings = get_option('nsbc_settings', []);
        $emails_raw = $settings['admin_emails'] ?? get_option('admin_email');
        $emails = array_filter(array_map('trim', explode(',', (string)$emails_raw)));
        if (empty($emails)) return false;
        $subject_tpl = $settings['email_admin_subject'] ?? 'New booking #{{id}} — {{package}} ({{session}})';
        $subject = self::replace_tags($subject_tpl, $booking_id);
        $body = self::admin_body($booking_id);
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent = false;
        foreach ($emails as $em) {
            if (!is_email($em)) continue;
            $sent = wp_mail($em, $subject, $body, $headers) || $sent;
        }
        return $sent;
    }

    public static function send_customer(int $booking_id) {
        $email = get_post_meta($booking_id,'_booking_customer_email',true);
        if (!is_email($email)) return false;
        $settings = get_option('nsbc_settings', []);
        $subject_tpl = $settings['email_customer_subject'] ?? 'Your booking request received — {{package}}';
        $subject = self::replace_tags($subject_tpl, $booking_id);
        $body = self::customer_body($booking_id);
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        return wp_mail($email, $subject, $body, $headers);
    }

    private static function admin_body(int $booking_id): string {
        $package_id = (int)get_post_meta($booking_id,'_booking_package_id',true);
        $package = $package_id ? esc_html(get_the_title($package_id)) : '';
        $session = esc_html(get_post_meta($booking_id,'_booking_session_type',true));
        $date = esc_html(get_post_meta($booking_id,'_booking_date',true));
        $total = esc_html(get_post_meta($booking_id,'_booking_total_formatted',true));
        $name = esc_html(get_post_meta($booking_id,'_booking_customer_name',true));
        $email = esc_html(get_post_meta($booking_id,'_booking_customer_email',true));
        $phone = esc_html(get_post_meta($booking_id,'_booking_phone_full',true));
        $msg = nl2br(esc_html(get_post_meta($booking_id,'_booking_customer_message',true)));
        $extras = get_post_meta($booking_id,'_booking_extras_labels',true);
        $extrasHtml = '';
        if (is_array($extras) && $extras) {
            $extrasHtml = '<ul><li>' . implode('</li><li>', array_map('esc_html',$extras)) . '</li></ul>';
        } else $extrasHtml = '<em>—</em>';
        $adminUrl = esc_url(admin_url('post.php?post='.$booking_id.'&action=edit'));
        ob_start(); include NSBC_PLUGIN_DIR . 'templates/emails/admin-notification.php';
        return ob_get_clean();
    }

    private static function customer_body(int $booking_id): string {
        $package_id = (int)get_post_meta($booking_id,'_booking_package_id',true);
        $package = $package_id ? esc_html(get_the_title($package_id)) : '';
        $session = esc_html(get_post_meta($booking_id,'_booking_session_type',true));
        $date = esc_html(get_post_meta($booking_id,'_booking_date',true));
        $total = esc_html(get_post_meta($booking_id,'_booking_total_formatted',true));
        $name = esc_html(get_post_meta($booking_id,'_booking_customer_name',true));
        $extras = get_post_meta($booking_id,'_booking_extras_labels',true);
        $extrasHtml = '';
        if (is_array($extras) && $extras) $extrasHtml = '<ul><li>' . implode('</li><li>', array_map('esc_html',$extras)) . '</li></ul>';
        else $extrasHtml = '<em>—</em>';
        ob_start(); include NSBC_PLUGIN_DIR . 'templates/emails/customer-confirmation.php';
        return ob_get_clean();
    }
}
