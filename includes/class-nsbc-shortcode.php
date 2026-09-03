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
        $minDate = date('Y-m-d', strtotime('+' . $minLead . ' days'));
        $blackout = array_filter(array_map('trim', explode(',', (string)($settings['blackout_dates'] ?? ''))));

        $packages = get_posts(['post_type'=>NSBC_CPT_PACKAGE,'posts_per_page'=>-1,'post_status'=>'publish','orderby'=>'title','order'=>'ASC','meta_query'=>[['key'=>'_package_active','value'=>'1']]]);
        $extrasAll = get_posts(['post_type'=>NSBC_CPT_EXTRA,'posts_per_page'=>-1,'post_status'=>'publish','orderby'=>'title','order'=>'ASC','meta_query'=>[['key'=>'_extra_active','value'=>'1']]]);

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

        wp_enqueue_style('nsbc-frontend');
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
