<?php
if (!defined('ABSPATH')) exit;

class NSBC_Metabox_Extra {
    public function register() {
        add_meta_box('nsbc_extra', __('Extra Details','ns-booking'), [$this,'render'], NSBC_CPT_EXTRA, 'normal', 'high');
    }
    public function render($post) {
        wp_nonce_field('nsbc_extra_save','nsbc_extra_nonce');
        $price = (int)get_post_meta($post->ID,'_extra_price_cents',true);
        $icon_id = (int)get_post_meta($post->ID,'_extra_icon_id',true);
        $icon_class = get_post_meta($post->ID,'_extra_icon_class',true);
        $active = get_post_meta($post->ID,'_extra_active',true);
        if ($active === '') $active = 1;
        $settings = get_option('nsbc_settings', []);
        $currency = $settings['currency'] ?? 'EUR';
        $icon_url = $icon_id ? wp_get_attachment_image_url($icon_id,'thumbnail') : '';
        ?>
        <p>
            <label><strong><?php esc_html_e('Price','ns-booking'); ?> (<?php echo esc_html($currency); ?>)</strong></label><br>
            <input type="number" name="nsbc_extra_price" value="<?php echo esc_attr($price/100); ?>" step="0.01" min="0" style="width:160px">
        </p>
        <p>
            <label><input type="checkbox" name="nsbc_extra_active" value="1" <?php checked($active,1); ?>> <?php esc_html_e('Active','ns-booking'); ?></label>
        </p>
        <hr>
        <p><strong><?php esc_html_e('Icon','ns-booking'); ?></strong> <span class="description"><?php esc_html_e('Upload an image (SVG/PNG) or enter a Dashicon class.','ns-booking'); ?></span></p>
        <div style="display:flex;gap:12px;align-items:center">
            <div id="nsbc-icon-preview" style="width:64px;height:64px;border:1px solid #ccd0d4;display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden">
                <?php if ($icon_url): ?><img src="<?php echo esc_url($icon_url); ?>" style="max-width:100%;max-height:100%"><?php elseif($icon_class): ?><span class="dashicons <?php echo esc_attr($icon_class); ?>" style="font-size:32px;width:32px;height:32px"></span><?php else: ?><span style="color:#999">—</span><?php endif; ?>
            </div>
            <div>
                <input type="hidden" name="nsbc_icon_id" id="nsbc_icon_id" value="<?php echo esc_attr($icon_id); ?>">
                <button type="button" class="button" id="nsbc_icon_select"><?php esc_html_e('Select Image','ns-booking'); ?></button>
                <button type="button" class="button" id="nsbc_icon_remove"><?php esc_html_e('Remove','ns-booking'); ?></button>
                <br><small><?php esc_html_e('Recommended 64×64px, transparent background.','ns-booking'); ?></small>
            </div>
        </div>
        <p>
            <label><?php esc_html_e('Or Dashicon class','ns-booking'); ?> <input type="text" name="nsbc_icon_class" value="<?php echo esc_attr($icon_class); ?>" placeholder="dashicons-star-filled" style="width:220px"></label>
            <span class="description">e.g. dashicons-heart, dashicons-camera</span>
        </p>
        <script>
        jQuery(function($){
            var frame;
            $('#nsbc_icon_select').on('click', function(e){
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({title:'Select Icon', button:{text:'Use this icon'}, multiple:false, library:{type:'image'}});
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    $('#nsbc_icon_id').val(att.id);
                    $('#nsbc-icon-preview').html('<img src="'+att.url+'" style="max-width:100%;max-height:100%">');
                });
                frame.open();
            });
            $('#nsbc_icon_remove').on('click', function(){
                $('#nsbc_icon_id').val('');
                $('#nsbc-icon-preview').html('<span style="color:#999">—</span>');
            });
        });
        </script>
        <?php
    }
    public function save($post_id, $post) {
        if (!isset($_POST['nsbc_extra_nonce']) || !wp_verify_nonce($_POST['nsbc_extra_nonce'],'nsbc_extra_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post',$post_id)) return;
        $price = isset($_POST['nsbc_extra_price']) ? (float)$_POST['nsbc_extra_price'] : 0;
        update_post_meta($post_id,'_extra_price_cents', (int)round($price*100));
        update_post_meta($post_id,'_extra_active', isset($_POST['nsbc_extra_active']) ? 1 : 0);
        $icon_id = isset($_POST['nsbc_icon_id']) ? (int)$_POST['nsbc_icon_id'] : 0;
        update_post_meta($post_id,'_extra_icon_id', $icon_id);
        $cls = isset($_POST['nsbc_icon_class']) ? sanitize_text_field($_POST['nsbc_icon_class']) : '';
        update_post_meta($post_id,'_extra_icon_class', $cls);
    }
}
