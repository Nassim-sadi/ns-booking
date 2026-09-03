<?php
if (!defined('ABSPATH')) exit;

class NSBC_Metabox_Package {
    public function register() {
        add_meta_box('nsbc_package', __('Package Details','ns-booking'), [$this,'render'], NSBC_CPT_PACKAGE, 'normal', 'high');
    }
    public function render($post) {
        wp_nonce_field('nsbc_package_save','nsbc_package_nonce');
        $priceSolo = (int)get_post_meta($post->ID,'_package_price_solo',true);
        $priceCouple = (int)get_post_meta($post->ID,'_package_price_couple',true);
        $extraIds = (array)get_post_meta($post->ID,'_package_extra_ids',true);
        $active = get_post_meta($post->ID,'_package_active',true);
        if ($active === '') $active = 1;
        $settings = get_option('nsbc_settings', []);
        $currency = $settings['currency'] ?? 'EUR';
        $extras = get_posts(['post_type'=>NSBC_CPT_EXTRA,'posts_per_page'=>-1,'post_status'=>'any','orderby'=>'title','order'=>'ASC']);
        ?>
        <p>
            <label><strong><?php esc_html_e('Price — Solo','ns-booking'); ?> (<?php echo esc_html($currency); ?>)</strong></label><br>
            <input type="number" name="nsbc_price_solo" value="<?php echo esc_attr($priceSolo/100); ?>" step="0.01" min="0" style="width:160px"> 
            <span class="description"><?php esc_html_e('Stored as cents. e.g. 140.00','ns-booking'); ?></span>
        </p>
        <p>
            <label><strong><?php esc_html_e('Price — Couple','ns-booking'); ?> (<?php echo esc_html($currency); ?>)</strong></label><br>
            <input type="number" name="nsbc_price_couple" value="<?php echo esc_attr($priceCouple/100); ?>" step="0.01" min="0" style="width:160px">
        </p>
        <p>
            <label><input type="checkbox" name="nsbc_package_active" value="1" <?php checked($active,1); ?>> <?php esc_html_e('Active (visible in configurator)','ns-booking'); ?></label>
        </p>
        <hr>
        <p><strong><?php esc_html_e('Available Extras for this package','ns-booking'); ?></strong><br><span class="description"><?php esc_html_e('Only checked extras will appear when this package is selected.','ns-booking'); ?></span></p>
        <?php if (!$extras): ?>
            <p><em><?php esc_html_e('No extras yet. Create them under Bookings → Booking Extras.','ns-booking'); ?></em></p>
        <?php else: foreach ($extras as $ex):
            $price = (int)get_post_meta($ex->ID,'_extra_price_cents',true);
            $checked = in_array($ex->ID, $extraIds, true);
        ?>
            <label style="display:inline-block;min-width:260px;margin:4px 12px 4px 0;padding:6px 8px;border:1px solid #ccd0d4;border-radius:4px;<?php echo $checked?'background:#f0f6fc':''; ?>">
                <input type="checkbox" name="nsbc_extra_ids[]" value="<?php echo esc_attr($ex->ID); ?>" <?php checked($checked); ?>>
                <?php echo esc_html($ex->post_title); ?> — <?php echo esc_html(NSBC_Pricing::format($price, $currency)); ?>
                <small style="color:#666">(<?php echo esc_html(get_post_status($ex->ID)); ?>)</small>
            </label>
        <?php endforeach; endif; ?>
        <?php
    }
    public function save($post_id, $post) {
        if (!isset($_POST['nsbc_package_nonce']) || !wp_verify_nonce($_POST['nsbc_package_nonce'],'nsbc_package_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post',$post_id)) return;
        $solo = isset($_POST['nsbc_price_solo']) ? (float)$_POST['nsbc_price_solo'] : 0;
        $couple = isset($_POST['nsbc_price_couple']) ? (float)$_POST['nsbc_price_couple'] : 0;
        update_post_meta($post_id,'_package_price_solo', (int)round($solo*100));
        update_post_meta($post_id,'_package_price_couple', (int)round($couple*100));
        update_post_meta($post_id,'_package_active', isset($_POST['nsbc_package_active']) ? 1 : 0);
        $ids = isset($_POST['nsbc_extra_ids']) && is_array($_POST['nsbc_extra_ids']) ? array_map('intval', $_POST['nsbc_extra_ids']) : [];
        // validate ids are extras
        $ids = array_values(array_filter($ids, fn($id)=> get_post_type($id)===NSBC_CPT_EXTRA));
        update_post_meta($post_id,'_package_extra_ids', $ids);
    }
}
