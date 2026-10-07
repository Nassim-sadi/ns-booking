<?php
if (!defined('ABSPATH')) exit;

class NSBC_Validation {
    public static function get_client_ip(): string {
        // Respect reverse proxies — Cloudflare, nginx, etc.
        $keys = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (empty($_SERVER[$k])) continue;
            $val = trim((string)$_SERVER[$k]);
            // X-Forwarded-For may be comma list — take first
            if (strpos($val, ',') !== false) $val = trim(explode(',', $val)[0]);
            if (filter_var($val, FILTER_VALIDATE_IP)) return $val;
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private static function is_inactive($val): bool {
        // Strict inactive check — meta stored as '0'/'1' or '' (missing = active)
        return $val === '0' || $val === 0 || $val === 0.0 || $val === false;
    }

    public static function sanitize_settings($input) {
        $out = get_option('nsbc_settings', []);
        if (!is_array($input)) return $out;
        $out['currency'] = isset($input['currency']) ? sanitize_text_field($input['currency']) : ($out['currency'] ?? 'EUR');
        // Validate each admin email individually — keep only valid
        if (isset($input['admin_emails'])) {
            $raw = sanitize_text_field($input['admin_emails']);
            $parts = array_filter(array_map('trim', explode(',', $raw)));
            $valid = array_filter($parts, 'is_email');
            $out['admin_emails'] = implode(', ', $valid) ?: ($valid ? implode(', ', $valid) : $raw);
            // Fallback: if none valid keep raw so admin sees error, but booking will skip invalid
            if (empty($valid) && $raw !== '') $out['admin_emails'] = $raw;
        } else {
            $out['admin_emails'] = $out['admin_emails'] ?? '';
        }
        $out['min_lead_days'] = isset($input['min_lead_days']) ? max(0, (int)$input['min_lead_days']) : 1;
        $out['blackout_dates'] = isset($input['blackout_dates']) ? sanitize_text_field($input['blackout_dates']) : '';
        $out['phone_default_country'] = isset($input['phone_default_country']) ? sanitize_text_field($input['phone_default_country']) : '+33';
        // Normalize phone_countries — validate each code, store as comma string
        if (isset($input['phone_countries'])) {
            $raw = sanitize_text_field($input['phone_countries']);
            $codes = array_filter(array_map('trim', explode(',', $raw)));
            $codes = array_filter($codes, fn($c)=> preg_match('/^\+\d{1,4}$/', preg_replace('/\s+/', '', $c)));
            $out['phone_countries'] = implode(',', $codes) ?: $raw;
        } else {
            $out['phone_countries'] = $out['phone_countries'] ?? '';
        }
        $out['enable_message'] = isset($input['enable_message']) ? (int)(bool)$input['enable_message'] : (int)($out['enable_message'] ?? 1);
        $out['show_images'] = isset($input['show_images']) ? (int)(bool)$input['show_images'] : (int)($out['show_images'] ?? 1);
        // support both color picker and text helper (bg_light_text)
        $bgL = $input['bg_light'] ?? $input['bg_light_text'] ?? null;
        $bgD = $input['bg_dark'] ?? $input['bg_dark_text'] ?? null;
        $cL = $input['card_light'] ?? $input['card_light_text'] ?? null;
        $cD = $input['card_dark'] ?? $input['card_dark_text'] ?? null;
        $out['bg_light'] = $bgL !== null ? (sanitize_hex_color($bgL) ?: '#ffffff') : ($out['bg_light'] ?? '#ffffff');
        $out['bg_dark'] = $bgD !== null ? (sanitize_hex_color($bgD) ?: '#0b0b0c') : ($out['bg_dark'] ?? '#0b0b0c');
        $out['card_light'] = $cL !== null ? (sanitize_hex_color($cL) ?: '#ffffff') : ($out['card_light'] ?? '#ffffff');
        $out['card_dark'] = $cD !== null ? (sanitize_hex_color($cD) ?: '#17171a') : ($out['card_dark'] ?? '#17171a');
        $mode = isset($input['theme_mode']) ? strtolower(trim($input['theme_mode'])) : ($out['theme_mode'] ?? 'auto');
        if (!in_array($mode, ['auto','light','dark'], true)) $mode = 'auto';
        $out['theme_mode'] = $mode;
        $shadow = isset($input['card_shadow']) ? strtolower(trim($input['card_shadow'])) : ($out['card_shadow'] ?? 'auto');
        if (!in_array($shadow, ['auto','none','subtle','medium','strong'], true)) $shadow = 'auto';
        $out['card_shadow'] = $shadow;
        // color settings — light/dark pairs; empty btn_hover = auto (CSS color-mix fallback)
        $hexFields = [
            'accent_light'=>'#111827','accent_dark'=>'#fafafa',
            'text_light'=>'#111827','text_dark'=>'#f4f4f5',
            'muted_light'=>'#6b7280','muted_dark'=>'#a1a1aa',
            'btn_hover_light'=>'','btn_hover_dark'=>'',
        ];
        foreach ($hexFields as $key=>$default) {
            $v = $input[$key] ?? $input[$key.'_text'] ?? null;
            if (!is_string($v)) $v = null;
            $clean = $v !== null ? (sanitize_hex_color($v) ?: ($v === '' ? '' : $default)) : ($out[$key] ?? $default);
            if (!is_string($clean) || ($clean !== '' && !sanitize_hex_color($clean))) $clean = $default;
            $out[$key] = $clean;
        }
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
            if (self::is_inactive($active)) $errors[] = __('Package not available.','ns-booking');
            // Validate extras belong to package
            $allowed = array_map('intval', (array)get_post_meta($package_id, '_package_extra_ids', true));
            foreach ($extra_ids as $eid) {
                if (!in_array($eid, $allowed, true)) $errors[] = sprintf(__('Extra %d not available for this package.','ns-booking'), $eid);
                elseif (get_post_type($eid) !== NSBC_CPT_EXTRA) $errors[] = __('Invalid extra.','ns-booking');
            }
        }

        // Date: Y-m-d, >= today+minLead, not blackout — WP timezone aware
        if (empty($date_raw)) $errors[] = __('Date is required.','ns-booking');
        else {
            $tz = function_exists('wp_timezone') ? wp_timezone() : null;
            $d = DateTime::createFromFormat('Y-m-d', $date_raw, $tz);
            $valid = $d && $d->format('Y-m-d') === $date_raw;
            if (!$valid) $errors[] = __('Invalid date format.','ns-booking');
            else {
                $today = new DateTime('today', $tz);
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
