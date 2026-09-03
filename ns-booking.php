<?php
/**
 * Plugin Name:       NS Booking Configurator
 * Plugin URI:        https://nassimstudio.com
 * Description:       Standalone booking configurator — packages, session type Solo/Couple, extras, date + customer form = one booking record. No theme dependency. Server-side price recalculation.
 * Version:           1.0.0
 * Author:            Nassim Studio
 * Text Domain:       ns-booking
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if (!defined('ABSPATH')) exit;

define('NSBC_VERSION', '1.0.0');
define('NSBC_PLUGIN_FILE', __FILE__);
define('NSBC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NSBC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NSBC_TEXTDOMAIN', 'ns-booking');

// CPT slugs — namespaced but rewrite to clean URLs if available.
define('NSBC_CPT_BOOKING', 'ns_booking');
define('NSBC_CPT_PACKAGE', 'ns_booking_package');
define('NSBC_CPT_EXTRA', 'ns_booking_extra');

require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-loader.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-cpt.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-pricing.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-validation.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-emails.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-ajax.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-shortcode.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-settings.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-admin.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-metabox-package.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-metabox-extra.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-metabox-booking.php';
require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-activator.php';

function nsbc_default_settings() {
    if (class_exists('NSBC_Activator') && method_exists('NSBC_Activator','default_settings')) return NSBC_Activator::default_settings();
    return [
        'currency'=>'EUR','admin_emails'=>get_option('admin_email'),'min_lead_days'=>1,'blackout_dates'=>'',
        'phone_default_country'=>'+33','phone_countries'=>['+90','+1','+44','+49','+33','+39','+34','+971'],
        'email_admin_subject'=>'New booking #{{id}} — {{package}} ({{session}})',
        'email_customer_subject'=>'Your booking request received — {{package}}',
        'enable_message'=>1,
    ];
}

function nsbc_run() {
    $loader = new NSBC_Loader();

    $cpt = new NSBC_CPT();
    $loader->add_action('init', $cpt, 'register');

    $pkgBox = new NSBC_Metabox_Package();
    $loader->add_action('add_meta_boxes', $pkgBox, 'register');
    $loader->add_action('save_post_' . NSBC_CPT_PACKAGE, $pkgBox, 'save', 10, 2);

    $extraBox = new NSBC_Metabox_Extra();
    $loader->add_action('add_meta_boxes', $extraBox, 'register');
    $loader->add_action('save_post_' . NSBC_CPT_EXTRA, $extraBox, 'save', 10, 2);

    $bookingBox = new NSBC_Metabox_Booking();
    $loader->add_action('add_meta_boxes', $bookingBox, 'register');
    $loader->add_action('save_post_' . NSBC_CPT_BOOKING, $bookingBox, 'save', 10, 2);

    $admin = new NSBC_Admin();
    $loader->add_action('admin_enqueue_scripts', $admin, 'enqueue');
    $loader->add_action('manage_' . NSBC_CPT_BOOKING . '_posts_custom_column', $admin, 'render_column', 10, 2);
    $loader->add_filter('manage_' . NSBC_CPT_BOOKING . '_posts_columns', $admin, 'columns');
    $loader->add_filter('manage_edit-' . NSBC_CPT_BOOKING . '_sortable_columns', $admin, 'sortable_columns');
    $loader->add_action('restrict_manage_posts', $admin, 'filters');
    $loader->add_action('pre_get_posts', $admin, 'filter_query');
    $loader->add_action('admin_menu', $admin, 'reorder_menu');

    $settings = new NSBC_Settings();
    $loader->add_action('admin_init', $settings, 'register');
    $loader->add_action('admin_menu', $settings, 'menu');

    $shortcode = new NSBC_Shortcode();
    $loader->add_action('init', $shortcode, 'register_shortcode');
    $loader->add_action('wp_enqueue_scripts', $shortcode, 'register_assets');

    $ajax = new NSBC_Ajax();
    $loader->add_action('rest_api_init', $ajax, 'register_rest');
    $loader->add_action('wp_ajax_nsbc_submit', $ajax, 'handle_ajax');
    $loader->add_action('wp_ajax_nopriv_nsbc_submit', $ajax, 'handle_ajax');
    $loader->add_action('wp_ajax_nsbc_recalc', $ajax, 'handle_recalc');

    // i18n
    $loader->add_action('init', 'nsbc_load_textdomain');

    $loader->run();
}

function nsbc_load_textdomain() {
    load_plugin_textdomain(NSBC_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function nsbc_activate() {
    require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-activator.php';
    NSBC_Activator::activate();
}

function nsbc_deactivate() {
    require_once NSBC_PLUGIN_DIR . 'includes/class-nsbc-deactivator.php';
    NSBC_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'nsbc_activate');
register_deactivation_hook(__FILE__, 'nsbc_deactivate');

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links){
    $url = admin_url('edit.php?post_type=' . NSBC_CPT_BOOKING . '&page=nsbc-settings');
    $links[] = '<a href="' . esc_url($url) . '">' . esc_html__('Settings','ns-booking') . '</a>';
    return $links;
});

add_action('plugins_loaded', 'nsbc_run');
