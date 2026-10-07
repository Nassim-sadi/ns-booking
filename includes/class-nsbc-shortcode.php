<?php
if (!defined('ABSPATH')) exit;

class NSBC_Shortcode {
    public function register_shortcode() {
        add_shortcode('booking_configurator', [$this,'render']);
        add_shortcode('ns_booking', [$this,'render']);
    }
    public function register_assets() {
        wp_register_style('nsbc-frontend', NSBC_PLUGIN_URL.'assets/css/frontend.css', [], NSBC_VERSION);
        wp_register_script('nsbc-frontend', NSBC_PLUGIN_URL.'assets/js/frontend.js', [], NSBC_VERSION, true);
    }
    public function render($atts=[]) {
        $atts = shortcode_atts(['package'=>''], $atts, 'booking_configurator');
        $settings = get_option('nsbc_settings', function_exists('nsbc_default_settings') ? nsbc_default_settings() : []);
        $currency = $settings['currency'] ?? 'EUR';
        $symbol = NSBC_Pricing::currency_symbol($currency);
        $minLead = (int)($settings['min_lead_days'] ?? 1);
        // WP timezone-aware minDate
        $minDate = function_exists('wp_date') ? wp_date('Y-m-d', strtotime('+' . $minLead . ' days')) : date('Y-m-d', strtotime('+' . $minLead . ' days'));
        $blackout = array_filter(array_map('trim', explode(',', (string)($settings['blackout_dates'] ?? ''))));

        // Include packages/extras where active meta is '1' OR missing (legacy = active)
        $packages = get_posts(['post_type'=>NSBC_CPT_PACKAGE,'posts_per_page'=>-1,'post_status'=>'publish','orderby'=>'title','order'=>'ASC','meta_query'=>['relation'=>'OR',['key'=>'_package_active','value'=>'1'],['key'=>'_package_active','compare'=>'NOT EXISTS']]]);
        $extrasAll = get_posts(['post_type'=>NSBC_CPT_EXTRA,'posts_per_page'=>-1,'post_status'=>'publish','orderby'=>'title','order'=>'ASC','meta_query'=>['relation'=>'OR',['key'=>'_extra_active','value'=>'1'],['key'=>'_extra_active','compare'=>'NOT EXISTS']]]);

        // flag map — emoji best, no extra lib, tourist friendly
        $flagMap = [
            '+90'=>'🇹🇷','+1'=>'🇺🇸','+44'=>'🇬🇧','+49'=>'🇩🇪','+33'=>'🇫🇷','+39'=>'🇮🇹','+34'=>'🇪🇸','+31'=>'🇳🇱','+32'=>'🇧🇪','+41'=>'🇨🇭','+43'=>'🇦🇹','+48'=>'🇵🇱','+7'=>'🇷🇺','+380'=>'🇺🇦','+40'=>'🇷🇴','+30'=>'🇬🇷','+359'=>'🇧🇬','+381'=>'🇷🇸','+966'=>'🇸🇦','+971'=>'🇦🇪','+974'=>'🇶🇦','+965'=>'🇰🇼','+973'=>'🇧🇭','+968'=>'🇴🇲','+962'=>'🇯🇴','+961'=>'🇱🇧','+964'=>'🇮🇶','+98'=>'🇮🇷','+92'=>'🇵🇰','+91'=>'🇮🇳','+86'=>'🇨🇳','+81'=>'🇯🇵','+82'=>'🇰🇷','+998'=>'🇺🇿','+994'=>'🇦🇿','+995'=>'🇬🇪','+374'=>'🇦🇲','+993'=>'🇹🇲','+996'=>'🇰🇬','+61'=>'🇦🇺','+55'=>'🇧🇷','+52'=>'🇲🇽','+54'=>'🇦🇷','+212'=>'🇲🇦','+213'=>'🇩🇿','+216'=>'🇹🇳',
        ];
        $packagesForJs = [];
        $extrasForJs = [];
        foreach ($extrasAll as $ex) {
            $price=(int)get_post_meta($ex->ID,'_extra_price_cents',true);
            $icon_id=(int)get_post_meta($ex->ID,'_extra_icon_id',true);
            $icon_url = $icon_id ? wp_get_attachment_image_url($icon_id,'medium') : '';
            $icon_thumb = $icon_id ? wp_get_attachment_image_url($icon_id,'thumbnail') : '';
            $icon_class = get_post_meta($ex->ID,'_extra_icon_class',true);
            $extrasForJs[$ex->ID] = [
                'id'=>$ex->ID,'label'=>$ex->post_title,'price'=>$price,'priceFormatted'=>NSBC_Pricing::format($price,$currency),
                'iconUrl'=>$icon_url ?: $icon_thumb,'iconClass'=>$icon_class
            ];
        }
        foreach ($packages as $p) {
            $solo=(int)get_post_meta($p->ID,'_package_price_solo',true);
            $couple=(int)get_post_meta($p->ID,'_package_price_couple',true);
            $ids=(array)get_post_meta($p->ID,'_package_extra_ids',true);
            $ids=array_values(array_filter(array_map('intval',$ids), fn($id)=>isset($extrasForJs[$id])));
            $thumb = get_the_post_thumbnail_url($p->ID,'medium_large') ?: get_the_post_thumbnail_url($p->ID,'medium') ?: '';
            $excerpt = has_excerpt($p->ID) ? get_the_excerpt($p->ID) : wp_trim_words($p->post_content, 14);
            $packagesForJs[$p->ID]=[
                'id'=>$p->ID,'label'=>$p->post_title,'prices'=>['solo'=>$solo,'couple'=>$couple],
                'pricesFormatted'=>['solo'=>NSBC_Pricing::format($solo,$currency),'couple'=>NSBC_Pricing::format($couple,$currency)],
                'extraIds'=>$ids,'imageUrl'=>$thumb,'excerpt'=>$excerpt
            ];
        }

        $phoneCountriesRaw = $settings['phone_countries'] ?? $settings['phoneCountries'] ?? '';
        if (is_array($phoneCountriesRaw)) $phoneCountries = $phoneCountriesRaw;
        else $phoneCountries = array_filter(array_map('trim', explode(',', (string)$phoneCountriesRaw)));
        if (empty($phoneCountries)) $phoneCountries = ['+90','+1','+44','+49','+33','+39','+34','+971'];
        $defaultCountry = $settings['phone_default_country'] ?? '+33';
        // build phone options with flags for JS
        $phoneOptions = [];
        foreach ($phoneCountries as $c) {
            $c = trim($c);
            if (!$c) continue;
            $flag = $flagMap[$c] ?? '🌐';
            $phoneOptions[] = ['code'=>$c,'flag'=>$flag,'label'=>"$flag $c"];
        }

        $showImages = !isset($settings['show_images']) || (bool)$settings['show_images'];
        // if disabled, strip image urls server-side
        if (!$showImages) {
            foreach ($packagesForJs as &$pf) $pf['imageUrl'] = '';
            foreach ($extrasForJs as &$ef) { $ef['iconUrl']=''; $ef['iconClass']=''; }
            unset($pf,$ef);
        }

        // dynamic CSS vars for bg / card colors + theme mode — scoped to .nsbc-configurator to avoid :root pollution
        $bg_light = $settings['bg_light'] ?? '#ffffff';
        $bg_dark = $settings['bg_dark'] ?? '#0b0b0c';
        $card_light = $settings['card_light'] ?? '#ffffff';
        $card_dark = $settings['card_dark'] ?? '#17171a';
        $theme_mode = $settings['theme_mode'] ?? 'auto';
        // color settings — hex-guard so invalid values never reach CSS
        $hex = fn($v, $d) => (is_string($v) && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v)) ? $v : $d;
        $text_light = $hex($settings['text_light'] ?? '', '#111827');
        $text_dark = $hex($settings['text_dark'] ?? '', '#f4f4f5');
        $muted_light = $hex($settings['muted_light'] ?? '', '#6b7280');
        $muted_dark = $hex($settings['muted_dark'] ?? '', '#a1a1aa');
        $accent_light = $hex($settings['accent_light'] ?? '', '#111827');
        $accent_dark = $hex($settings['accent_dark'] ?? '', '#fafafa');
        $btnHover_light = $hex($settings['btn_hover_light'] ?? '', '');
        $btnHover_dark = $hex($settings['btn_hover_dark'] ?? '', '');
        $varsFor = function($text, $muted, $accent, $btnHover) {
            $css = sprintf('--nsbc-text:%s;--nsbc-muted:%s;--nsbc-accent:%s;', $text, $muted, $accent);
            if ($btnHover !== '') $css .= '--nsbc-btn-hover:'.$btnHover.';';
            return $css;
        };
        if ($theme_mode === 'light') {
            $inlineCss = sprintf('.nsbc-configurator{--nsbc-bg:%s;--nsbc-card:%s;--nsbc-border:%s;%sbackground:var(--nsbc-bg);color:var(--nsbc-text)}', esc_attr($bg_light), esc_attr($card_light), '#e5e7eb', $varsFor($text_light, $muted_light, $accent_light, $btnHover_light));
        } elseif ($theme_mode === 'dark') {
            $inlineCss = sprintf('.nsbc-configurator{--nsbc-bg:%s;--nsbc-card:%s;--nsbc-border:%s;%sbackground:var(--nsbc-bg);color:var(--nsbc-text)}', esc_attr($bg_dark), esc_attr($card_dark), '#27272a', $varsFor($text_dark, $muted_dark, $accent_dark, $btnHover_dark));
        } else {
            $inlineCss = sprintf(
                '.nsbc-configurator{--nsbc-bg:%s;--nsbc-card:%s;%s}@media(prefers-color-scheme:dark){.nsbc-configurator{--nsbc-bg:%s;--nsbc-card:%s;%s}} .nsbc-configurator{background:var(--nsbc-bg)}',
                esc_attr($bg_light), esc_attr($card_light), $varsFor($text_light, $muted_light, $accent_light, $btnHover_light),
                esc_attr($bg_dark), esc_attr($card_dark), $varsFor($text_dark, $muted_dark, $accent_dark, $btnHover_dark)
            );
        }
        // card shadow preset — hardcoded map, never echo raw input
        $shadowMap = [
            'none'   => 'none',
            'subtle' => '0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04)',
            'medium' => '0 4px 12px rgba(0,0,0,.10), 0 12px 32px rgba(0,0,0,.08)',
            'strong' => '0 8px 24px rgba(0,0,0,.16), 0 24px 64px rgba(0,0,0,.14)',
        ];
        $shadowPreset = $settings['card_shadow'] ?? 'auto';
        if (isset($shadowMap[$shadowPreset])) {
            $inlineCss .= '.nsbc-configurator{--nsbc-shadow:' . $shadowMap[$shadowPreset] . '}';
        }
        wp_register_style('nsbc-frontend', NSBC_PLUGIN_URL.'assets/css/frontend.css', [], NSBC_VERSION);
        wp_enqueue_style('nsbc-frontend');
        wp_add_inline_style('nsbc-frontend', $inlineCss);
        wp_register_script('nsbc-frontend', NSBC_PLUGIN_URL.'assets/js/frontend.js', [], NSBC_VERSION, true);
        wp_enqueue_script('nsbc-frontend');
        wp_localize_script('nsbc-frontend','NSBC',[
            'restUrl'=> esc_url_raw(rest_url('nsbc/v1/bookings')),
            'ajaxUrl'=> esc_url_raw(admin_url('admin-ajax.php')),
            'restNonce'=> wp_create_nonce('wp_rest'),
            'ajaxNonce'=> wp_create_nonce('nsbc_submit'),
            'currency'=> $currency,
            'currencySymbol'=> $symbol,
            'minDate'=> $minDate,
            'blackoutDates'=> array_values($blackout),
            'packages'=> $packagesForJs,
            'extras'=> $extrasForJs,
            'phoneCountries'=> $phoneOptions,
            'phoneDefault'=> $defaultCountry,
            'showImages'=> $showImages,
            'i18n'=> [
                'selectPackage'=>__('Select a package to begin','ns-booking'),
                'total'=>__('Total','ns-booking'),
                'required'=>__('This field is required.','ns-booking'),
                'invalidEmail'=>__('Valid email is required.','ns-booking'),
                'invalidPhone'=>__('Valid phone is required.','ns-booking'),
                'submitError'=>__('Submission failed. Please try again.','ns-booking'),
                'successTitle'=>__('Reservation Received!','ns-booking'),
                'successText'=>__('Thank you! We have received your booking request and will confirm within 24 hours.','ns-booking'),
            ]
        ]);

        ob_start();
        include NSBC_PLUGIN_DIR . 'templates/configurator.php';
        return ob_get_clean();
    }
}
