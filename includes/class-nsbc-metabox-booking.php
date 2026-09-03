<?php
if (!defined('ABSPATH')) exit;

class NSBC_Metabox_Booking {
    public function register() {
        add_meta_box('nsbc_booking', __('Booking Details','ns-booking'), [$this,'render'], NSBC_CPT_BOOKING, 'normal', 'high');
        add_meta_box('nsbc_booking_status', __('Status','ns-booking'), [$this,'render_status'], NSBC_CPT_BOOKING, 'side', 'high');
    }
    public function render($post) {
        wp_nonce_field('nsbc_booking_save','nsbc_booking_nonce');
        $pkgId = (int)get_post_meta($post->ID,'_booking_package_id',true);
        $session = get_post_meta($post->ID,'_booking_session_type',true);
        $extras = (array)get_post_meta($post->ID,'_booking_extras',true);
        $date = get_post_meta($post->ID,'_booking_date',true);
        $total = (int)get_post_meta($post->ID,'_booking_total_cents',true);
        $totalFmt = get_post_meta($post->ID,'_booking_total_formatted',true);
        $name = get_post_meta($post->ID,'_booking_customer_name',true);
        $email = get_post_meta($post->ID,'_booking_customer_email',true);
        $phone = get_post_meta($post->ID,'_booking_phone_full',true);
        $msg = get_post_meta($post->ID,'_booking_customer_message',true);
        $currency = get_post_meta($post->ID,'_booking_currency',true) ?: (get_option('nsbc_settings')['currency'] ?? 'EUR');
        $snapshot = get_post_meta($post->ID,'_booking_snapshot',true);
        $packages = get_posts(['post_type'=>NSBC_CPT_PACKAGE,'posts_per_page'=>-1,'post_status'=>'any','orderby'=>'title','order'=>'ASC']);
        $allExtras = get_posts(['post_type'=>NSBC_CPT_EXTRA,'posts_per_page'=>-1,'post_status'=>'any']);
        $byId = []; foreach($allExtras as $e) $byId[$e->ID]=$e;
        ?>
        <table class="form-table">
            <tr><th>Package</th><td>
                <select name="nsbc_pkg" style="min-width:220px">
                    <option value="0">—</option>
                    <?php foreach($packages as $p): ?><option value="<?php echo esc_attr($p->ID); ?>" <?php selected($pkgId,$p->ID); ?>><?php echo esc_html($p->post_title); ?></option><?php endforeach; ?>
                </select>
            </td></tr>
            <tr><th>Session</th><td>
                <label><input type="radio" name="nsbc_session" value="solo" <?php checked($session,'solo'); ?>> Solo</label>
                <label style="margin-left:12px"><input type="radio" name="nsbc_session" value="couple" <?php checked($session,'couple'); ?>> Couple</label>
            </td></tr>
            <tr><th>Date</th><td><input type="date" name="nsbc_date" value="<?php echo esc_attr($date); ?>"></td></tr>
            <tr><th>Extras</th><td>
                <?php foreach($allExtras as $ex): $price=(int)get_post_meta($ex->ID,'_extra_price_cents',true); ?>
                    <label style="display:inline-block;margin:4px 10px 4px 0"><input type="checkbox" name="nsbc_extras[]" value="<?php echo esc_attr($ex->ID); ?>" <?php checked(in_array($ex->ID,$extras,true)); ?>> <?php echo esc_html($ex->post_title); ?> (<?php echo esc_html(NSBC_Pricing::format($price,$currency)); ?>)</label>
                <?php endforeach; ?>
            </td></tr>
            <tr><th>Total</th><td>
                <strong><?php echo esc_html($totalFmt ?: NSBC_Pricing::format($total,$currency)); ?></strong>
                <button type="button" class="button" id="nsbc-recalc" style="margin-left:10px"><?php esc_html_e('Recalculate','ns-booking'); ?></button>
                <span id="nsbc-recalc-result" style="margin-left:8px;color:#2271b1"></span>
                <p class="description"><?php esc_html_e('Total is calculated automatically from package price (Solo/Couple) + selected extras.','ns-booking'); ?></p>
            </td></tr>
            <tr><th>Customer</th><td>
                <p><input type="text" name="nsbc_name" value="<?php echo esc_attr($name); ?>" placeholder="Name" style="width:260px"> <input type="email" name="nsbc_email" value="<?php echo esc_attr($email); ?>" placeholder="Email" style="width:260px"></p>
                <p><input type="text" name="nsbc_phone" value="<?php echo esc_attr($phone); ?>" placeholder="+337..." style="width:260px"></p>
                <p><textarea name="nsbc_message" rows="3" style="width:100%" placeholder="Message"><?php echo esc_textarea($msg); ?></textarea></p>
            </td></tr>
        </table>
        <?php if ($snapshot): ?>
            <details style="margin-top:12px"><summary>Snapshot JSON (audit)</summary><pre style="background:#f6f7f7;padding:8px;overflow:auto"><?php echo esc_html($snapshot); ?></pre></details>
        <?php endif; ?>
        <script>
        jQuery(function($){
            $('#nsbc-recalc').on('click', function(){
                var data = {
                    action:'nsbc_recalc',
                    _ajax_nonce:'<?php echo esc_js(wp_create_nonce('nsbc_recalc')); ?>',
                    post_id: <?php echo (int)$post->ID; ?>,
                    package_id: $('[name=nsbc_pkg]').val(),
                    session_type: $('[name=nsbc_session]:checked').val(),
                    extras: $('[name="nsbc_extras[]"]:checked').map(function(){return this.value;}).get()
                };
                $('#nsbc-recalc-result').text('…');
                $.post(ajaxurl, data, function(res){
                    if (res && res.success) $('#nsbc-recalc-result').text(res.data.formatted + ' ('+res.data.cents+' cents)');
                    else $('#nsbc-recalc-result').text(res.data || 'error');
                });
            });
        });
        </script>
        <?php
    }
    public function render_status($post) {
        $status = get_post_meta($post->ID,'_booking_status',true) ?: 'pending';
        $options = ['pending'=>'Pending','confirmed'=>'Confirmed','cancelled'=>'Cancelled','completed'=>'Completed'];
        echo '<select name="nsbc_status" style="width:100%">';
        foreach($options as $k=>$l) echo '<option value="'.esc_attr($k).'" '.selected($status,$k,false).'>'.esc_html($l).'</option>';
        echo '</select>';
        echo '<p><button type="button" class="button" onclick="if(confirm(\'Resend emails?\')){jQuery.post(ajaxurl,{action:\'nsbc_recalc\',_ajax_nonce:\''.esc_js(wp_create_nonce('nsbc_recalc')).'\',post_id:'.$post->ID.',resend:1},function(r){alert(r.data||r);});}">Resend emails</button></p>';
    }
    public function save($post_id, $post) {
        if (!isset($_POST['nsbc_booking_nonce']) || !wp_verify_nonce($_POST['nsbc_booking_nonce'],'nsbc_booking_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post',$post_id)) return;

        $pkg = isset($_POST['nsbc_pkg']) ? (int)$_POST['nsbc_pkg'] : 0;
        $session = isset($_POST['nsbc_session']) ? sanitize_key($_POST['nsbc_session']) : 'solo';
        if (!in_array($session,['solo','couple'],true)) $session='solo';
        $date = isset($_POST['nsbc_date']) ? sanitize_text_field($_POST['nsbc_date']) : '';
        $extras = isset($_POST['nsbc_extras']) && is_array($_POST['nsbc_extras']) ? array_map('intval', $_POST['nsbc_extras']) : [];
        $name = isset($_POST['nsbc_name']) ? sanitize_text_field($_POST['nsbc_name']) : '';
        $email = isset($_POST['nsbc_email']) ? sanitize_email($_POST['nsbc_email']) : '';
        $phone = isset($_POST['nsbc_phone']) ? sanitize_text_field($_POST['nsbc_phone']) : '';
        $msg = isset($_POST['nsbc_message']) ? sanitize_textarea_field($_POST['nsbc_message']) : '';
        $status = isset($_POST['nsbc_status']) ? sanitize_key($_POST['nsbc_status']) : 'pending';

        if ($pkg && get_post_type($pkg)===NSBC_CPT_PACKAGE) update_post_meta($post_id,'_booking_package_id',$pkg);
        update_post_meta($post_id,'_booking_session_type',$session);
        update_post_meta($post_id,'_booking_date',$date);
        update_post_meta($post_id,'_booking_extras',$extras);
        // denormalize labels
        $labels=[]; foreach($extras as $eid){ $t=get_the_title($eid); if($t) $labels[]=$t; }
        update_post_meta($post_id,'_booking_extras_labels',$labels);
        update_post_meta($post_id,'_booking_customer_name',$name);
        update_post_meta($post_id,'_booking_customer_email',$email);
        update_post_meta($post_id,'_booking_phone_full',$phone);
        update_post_meta($post_id,'_booking_customer_message',$msg);
        update_post_meta($post_id,'_booking_status',$status);
        // recalc total server-side
        if ($pkg) {
            $cents = NSBC_Pricing::calculate($pkg,$session,$extras);
            $settings=get_option('nsbc_settings',[]);
            $curr = get_post_meta($post_id,'_booking_currency',true) ?: ($settings['currency'] ?? 'EUR');
            update_post_meta($post_id,'_booking_total_cents',$cents);
            update_post_meta($post_id,'_booking_total_formatted',NSBC_Pricing::format($cents,$curr));
        }
        // sync post title
        $title = sprintf('Booking #%d — %s — %s', $post_id, $name ?: '—', $date ?: '—');
        if ($post->post_title !== $title) {
            wp_update_post(['ID'=>$post_id,'post_title'=>$title]);
        }
    }
}
