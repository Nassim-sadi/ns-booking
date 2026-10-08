<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;

function nsbc_uninstall_cleanup() {
    global $wpdb;

    $cpts = ['ns_booking', 'ns_booking_package', 'ns_booking_extra'];
    $deleted = 0;
    foreach ($cpts as $cpt) {
        do {
            $ids = get_posts([
                'post_type' => $cpt,
                'post_status' => 'any',
                'posts_per_page' => 100,
                'fields' => 'ids',
                'orderby' => 'ID',
                'suppress_filters' => true,
            ]);
            foreach ($ids as $id) {
                wp_delete_post((int)$id, true);
                $deleted++;
            }
        } while ($ids);
    }

    delete_option('nsbc_settings');
    delete_option('nsbc_migrated_1_1');

    // Self-expiring rate-limit transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nsbc_%' OR option_name LIKE '_transient_timeout_nsbc_%'");
}

if (is_multisite()) {
    foreach (get_sites(['number' => 0, 'fields' => 'ids']) as $site_id) {
        switch_to_blog($site_id);
        nsbc_uninstall_cleanup();
        restore_current_blog();
    }
} else {
    nsbc_uninstall_cleanup();
}