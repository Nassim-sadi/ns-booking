<?php
if (!defined('ABSPATH')) exit;

class NSBC_Admin {
    public function enqueue($hook) {
        $screen = get_current_screen();
        if (!$screen) return;
        if (in_array($screen->post_type, [NSBC_CPT_BOOKING, NSBC_CPT_PACKAGE, NSBC_CPT_EXTRA], true)) {
            wp_enqueue_media();
            wp_enqueue_script('nsbc-admin', NSBC_PLUGIN_URL.'assets/js/admin.js', ['jquery'], NSBC_VERSION, true);
            wp_enqueue_style('nsbc-admin', NSBC_PLUGIN_URL.'assets/css/admin.css', [], NSBC_VERSION);
        }
    }
    public function reorder_menu() {
        // Keep bookings at top, settings already submenu.
    }
    public function columns($cols) {
        $new = [];
        $new['cb']= $cols['cb'] ?? '';
        $new['title']= __('Booking','ns-booking');
        $new['nsbc_package']= __('Package','ns-booking');
        $new['nsbc_session']= __('Session','ns-booking');
        $new['nsbc_date']= __('Date','ns-booking');
        $new['nsbc_total']= __('Total','ns-booking');
        $new['nsbc_customer']= __('Customer','ns-booking');
        $new['nsbc_status']= __('Status','ns-booking');
        $new['date']= $cols['date'] ?? __('Created','ns-booking');
        return $new;
    }
    public function sortable_columns($cols) {
        $cols['nsbc_date']='nsbc_date';
        $cols['nsbc_total']='nsbc_total';
        return $cols;
    }
    public function render_column($col, $post_id) {
        switch($col){
            case 'nsbc_package':
                $pid=(int)get_post_meta($post_id,'_booking_package_id',true);
                echo $pid ? esc_html(get_the_title($pid)) : '—';
                break;
            case 'nsbc_session':
                echo esc_html(get_post_meta($post_id,'_booking_session_type',true) ?: '—');
                break;
            case 'nsbc_date':
                echo esc_html(get_post_meta($post_id,'_booking_date',true) ?: '—');
                break;
            case 'nsbc_total':
                echo esc_html(get_post_meta($post_id,'_booking_total_formatted',true) ?: '—');
                break;
            case 'nsbc_customer':
                $n=esc_html(get_post_meta($post_id,'_booking_customer_name',true));
                $e=esc_html(get_post_meta($post_id,'_booking_customer_email',true));
                $p=esc_html(get_post_meta($post_id,'_booking_phone_full',true));
                echo $n ? "$n<br><small>$e<br>$p</small>" : '—';
                break;
            case 'nsbc_status':
                $s=get_post_meta($post_id,'_booking_status',true) ?: 'pending';
                $colors=['pending'=>'#d63638','confirmed'=>'#00a32a','cancelled'=>'#646970','completed'=>'#2271b1'];
                $c=$colors[$s]??'#646970';
                echo '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:'.esc_attr($c).';color:#fff;font-size:11px;text-transform:uppercase">'.esc_html($s).'</span>';
                break;
        }
    }
    public function filters($post_type) {
        if ($post_type !== NSBC_CPT_BOOKING) return;
        $packages=get_posts(['post_type'=>NSBC_CPT_PACKAGE,'posts_per_page'=>-1,'post_status'=>'any']);
        $curPkg = isset($_GET['nsbc_pkg']) ? (int)$_GET['nsbc_pkg'] : 0;
        $curStatus = isset($_GET['nsbc_status']) ? sanitize_key($_GET['nsbc_status']) : '';
        $curSession = isset($_GET['nsbc_session']) ? sanitize_key($_GET['nsbc_session']) : '';
        echo '<select name="nsbc_pkg"><option value="">All Packages</option>';
        foreach($packages as $p) echo '<option value="'.esc_attr($p->ID).'" '.selected($curPkg,$p->ID,false).'>'.esc_html($p->post_title).'</option>';
        echo '</select> ';
        echo '<select name="nsbc_session"><option value="">Solo/Couple</option><option value="solo" '.selected($curSession,'solo',false).'>Solo</option><option value="couple" '.selected($curSession,'couple',false).'>Couple</option></select> ';
        echo '<select name="nsbc_status"><option value="">All Status</option>';
        foreach(['pending','confirmed','cancelled','completed'] as $s) echo '<option value="'.esc_attr($s).'" '.selected($curStatus,$s,false).'>'.esc_html(ucfirst($s)).'</option>';
        echo '</select>';
    }
    public function filter_query($q) {
        if (!is_admin() || !$q->is_main_query()) return;
        if ($q->get('post_type') !== NSBC_CPT_BOOKING) return;
        $meta=[];
        if (!empty($_GET['nsbc_pkg'])) $meta[]=['key'=>'_booking_package_id','value'=>(int)$_GET['nsbc_pkg']];
        if (!empty($_GET['nsbc_status'])) $meta[]=['key'=>'_booking_status','value'=>sanitize_key($_GET['nsbc_status'])];
        if (!empty($_GET['nsbc_session'])) $meta[]=['key'=>'_booking_session_type','value'=>sanitize_key($_GET['nsbc_session'])];
        if ($meta) $q->set('meta_query',$meta);
        $orderby=$q->get('orderby');
        if ($orderby==='nsbc_date') { $q->set('meta_key','_booking_date'); $q->set('orderby','meta_value'); }
        if ($orderby==='nsbc_total') { $q->set('meta_key','_booking_total_cents'); $q->set('orderby','meta_value_num'); }
    }
}
