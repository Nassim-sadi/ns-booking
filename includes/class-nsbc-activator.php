<?php
if (!defined('ABSPATH')) exit;

class NSBC_Activator {
    public static function activate() {
        // Ensure CPTs registered before seeding.
        require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-cpt.php';
        $cpt = new NSBC_CPT();
        $cpt->register();
        flush_rewrite_rules();

        // Default settings
        if (false === get_option('nsbc_settings')) {
            add_option('nsbc_settings', self::default_settings());
        }

        // Seed extras if none
        $existing = get_posts(['post_type'=>NSBC_CPT_EXTRA,'posts_per_page'=>1,'post_status'=>'any']);
        if (empty($existing)) self::seed_extras();

        // Seed packages if none
        $pkgs = get_posts(['post_type'=>NSBC_CPT_PACKAGE,'posts_per_page'=>1,'post_status'=>'any']);
        if (empty($pkgs)) self::seed_packages();
    }

    public static function default_settings() {
        return [
            'currency'            => 'EUR',
            'admin_emails'        => get_option('admin_email'),
            'min_lead_days'       => 1,
            'blackout_dates'      => '',
            'phone_default_country'=> '+33',
            'phone_countries'     => ['+90','+1','+44','+49','+33','+39','+34','+31','+32','+41','+43','+48','+7','+380','+40','+30','+359','+381','+966','+971','+974','+965','+973','+968','+962','+961','+964','+98','+92','+91','+86','+81','+82','+61','+55','+52','+54'],
            'email_admin_subject' => 'New booking #{{id}} — {{package}} ({{session}})',
            'email_customer_subject' => 'Your booking request received — {{package}}',
            'enable_message'      => 1,
            'show_images'         => 1,
            'bg_light'            => '#ffffff',
            'bg_dark'             => '#0b0b0c',
            'card_light'          => '#ffffff',
            'card_dark'           => '#17171a',
        ];
    }

    private static function seed_extras() {
        $extras = [
            ['title'=>'Flying Dress','price'=>3000,'desc'=>'Flowing flying dress — Istanbul breeze.'],
            ['title'=>'Special Dress','price'=>7000,'desc'=>'Signature rose dress.'],
            ['title'=>'Traditional Dress','price'=>2500,'desc'=>'Ottoman heritage dress.'],
            ['title'=>'Men Suit','price'=>5000,'desc'=>'Elegant suit for gentleman.'],
            ['title'=>'Hair Styling','price'=>3000,'desc'=>'Professional hair styling.'],
            ['title'=>'Makeup & Hair','price'=>5000,'desc'=>'Full makeup + hair.'],
            ['title'=>'Classic Car','price'=>20000,'desc'=>'Vintage classic car add-on.'],
        ];
        foreach ($extras as $e) {
            $id = wp_insert_post([
                'post_type'=>NSBC_CPT_EXTRA,
                'post_title'=>$e['title'],
                'post_content'=>$e['desc'],
                'post_status'=>'publish',
            ]);
            if ($id && !is_wp_error($id)) {
                update_post_meta($id,'_extra_price_cents',(int)$e['price']);
                update_post_meta($id,'_extra_active',1);
            }
        }
    }

    private static function seed_packages() {
        $extras = get_posts(['post_type'=>NSBC_CPT_EXTRA,'posts_per_page'=>-1,'post_status'=>'publish','orderby'=>'title','order'=>'ASC']);
        $extraIds = array_map(fn($p)=>$p->ID, $extras);
        // Map titles to ids for convenience
        $byTitle = [];
        foreach ($extras as $p) $byTitle[$p->post_title] = $p->ID;

        $packages = [
            ['title'=>'1 Location','solo'=>14000,'couple'=>16000,'extras'=>['Flying Dress']],
            ['title'=>'2 Locations','solo'=>19000,'couple'=>21000,'extras'=>['Flying Dress','Hair Styling']],
            ['title'=>'All Locations','solo'=>30000,'couple'=>33000,'extras'=>['Flying Dress','Special Dress','Makeup & Hair']],
            ['title'=>'Ortaköy','solo'=>14000,'couple'=>16000,'extras'=>['Flying Dress','Classic Car']],
        ];
        foreach ($packages as $pkg) {
            $ids = [];
            foreach ($pkg['extras'] as $t) if (isset($byTitle[$t])) $ids[]=$byTitle[$t];
            if (empty($ids)) $ids = array_slice($extraIds,0,2);
            $id = wp_insert_post([
                'post_type'=>NSBC_CPT_PACKAGE,
                'post_title'=>$pkg['title'],
                'post_content'=>'Beautiful session — see details.',
                'post_status'=>'publish',
            ]);
            if ($id && !is_wp_error($id)) {
                update_post_meta($id,'_package_price_solo',(int)$pkg['solo']);
                update_post_meta($id,'_package_price_couple',(int)$pkg['couple']);
                update_post_meta($id,'_package_extra_ids',$ids);
                update_post_meta($id,'_package_active',1);
            }
        }
    }
}
