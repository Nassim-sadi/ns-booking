<?php
if (!defined('ABSPATH')) exit;

class NSBC_CPT {
    public function register() {
        // Booking statuses
        $statuses = [
            'nsbc-pending'   => ['label'=> _x('Pending','booking status','ns-booking'), 'public'=>false, 'show_in_admin_all_list'=>true],
            'nsbc-confirmed' => ['label'=> _x('Confirmed','booking status','ns-booking'), 'public'=>false, 'show_in_admin_all_list'=>true],
            'nsbc-cancelled' => ['label'=> _x('Cancelled','booking status','ns-booking'), 'public'=>false, 'show_in_admin_all_list'=>true],
            'nsbc-completed' => ['label'=> _x('Completed','booking status','ns-booking'), 'public'=>false, 'show_in_admin_all_list'=>true],
        ];
        foreach ($statuses as $name=>$args) {
            register_post_status($name, array_merge([
                'label_count'=> _n_noop($args['label'].' <span class="count">(%s)</span>', $args['label'].' <span class="count">(%s)</span>'),
            ], $args));
        }

        register_post_type(NSBC_CPT_EXTRA, [
            'labels'=>['name'=>__('Booking Extras','ns-booking'),'singular_name'=>__('Extra','ns-booking'),'add_new_item'=>__('Add New Extra','ns-booking')],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>'edit.php?post_type='.NSBC_CPT_BOOKING,
            'show_in_rest'=>false,'supports'=>['title','editor'],'menu_icon'=>'dashicons-star-filled',
            'capability_type'=>'post','map_meta_cap'=>true,
        ]);

        register_post_type(NSBC_CPT_PACKAGE, [
            'labels'=>['name'=>__('Packages','ns-booking'),'singular_name'=>__('Package','ns-booking'),'add_new_item'=>__('Add New Package','ns-booking')],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>'edit.php?post_type='.NSBC_CPT_BOOKING,
            'show_in_rest'=>false,'supports'=>['title','editor','thumbnail'],'menu_icon'=>'dashicons-portfolio',
            'capability_type'=>'post','map_meta_cap'=>true,
        ]);

        register_post_type(NSBC_CPT_BOOKING, [
            'labels'=>['name'=>__('Bookings','ns-booking'),'singular_name'=>__('Booking','ns-booking')],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'menu_icon'=>'dashicons-calendar-alt','menu_position'=>56,
            'show_in_rest'=>false,'supports'=>['title'],'capability_type'=>'post','map_meta_cap'=>true,
            'has_archive'=>false,'rewrite'=>false,
        ]);

        // Post meta registrations for queryability (sanitized elsewhere)
        $metas = [
            [NSBC_CPT_PACKAGE,'_package_price_solo','integer'],
            [NSBC_CPT_PACKAGE,'_package_price_couple','integer'],
            [NSBC_CPT_EXTRA,'_extra_price_cents','integer'],
            [NSBC_CPT_EXTRA,'_extra_icon_id','integer'],
        ];
        foreach ($metas as $m) {
            register_post_meta($m[0], $m[1], ['type'=>$m[2],'single'=>true,'show_in_rest'=>false,'auth_callback'=>function(){return current_user_can('edit_posts');}]);
        }
    }
}
