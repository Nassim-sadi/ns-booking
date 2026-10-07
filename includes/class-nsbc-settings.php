<?php
if (!defined('ABSPATH')) exit;

class NSBC_Settings {
    public function menu() {
        add_submenu_page(
            'edit.php?post_type='.NSBC_CPT_BOOKING,
            __('Booking Settings','ns-booking'),
            __('Settings','ns-booking'),
            'manage_options',
            'nsbc-settings',
            [$this,'render']
        );
    }
    public function register() {
        register_setting('nsbc_settings_group','nsbc_settings',['sanitize_callback'=>['NSBC_Validation','sanitize_settings']]);
    }
    public function render() {
        if (!current_user_can('manage_options')) return;
        $opt = get_option('nsbc_settings', function_exists('nsbc_default_settings') ? nsbc_default_settings() : []);
        // allow phone_countries to be array or string
        if (is_array($opt['phone_countries'] ?? null)) $opt['phone_countries'] = implode(',', $opt['phone_countries']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Booking Settings','ns-booking'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('nsbc_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr><th><?php esc_html_e('Currency','ns-booking'); ?></th><td>
                        <select name="nsbc_settings[currency]">
                            <?php foreach(['EUR'=>'EUR (€)','USD'=>'USD ($)','GBP'=>'GBP (£)','MAD'=>'MAD','TRY'=>'TRY (₺)','AED'=>'AED','SAR'=>'SAR'] as $k=>$l): ?>
                                <option value="<?php echo esc_attr($k); ?>" <?php selected($opt['currency']??'EUR',$k); ?>><?php echo esc_html($l); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th><?php esc_html_e('Admin notification emails','ns-booking'); ?></th><td>
                        <input type="text" name="nsbc_settings[admin_emails]" value="<?php echo esc_attr($opt['admin_emails']??''); ?>" style="width:420px" placeholder="admin@example.com, other@example.com">
                        <p class="description"><?php esc_html_e('Comma separated. Requires SMTP plugin for reliable delivery.','ns-booking'); ?></p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Min lead days','ns-booking'); ?></th><td>
                        <input type="number" name="nsbc_settings[min_lead_days]" value="<?php echo esc_attr($opt['min_lead_days']??1); ?>" min="0" style="width:80px">
                    </td></tr>
                    <tr><th><?php esc_html_e('Blackout dates','ns-booking'); ?></th><td>
                        <input type="text" name="nsbc_settings[blackout_dates]" value="<?php echo esc_attr($opt['blackout_dates']??''); ?>" style="width:420px" placeholder="2026-12-25, 2026-01-01">
                        <p class="description">YYYY-MM-DD, comma separated</p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Phone default country','ns-booking'); ?></th><td>
                        <input type="text" name="nsbc_settings[phone_default_country]" value="<?php echo esc_attr($opt['phone_default_country']??'+33'); ?>" style="width:80px">
                    </td></tr>
                    <tr><th><?php esc_html_e('Phone countries (comma list)','ns-booking'); ?></th><td>
                        <input type="text" name="nsbc_settings[phone_countries]" value="<?php echo esc_attr($opt['phone_countries']??''); ?>" style="width:100%;max-width:700px">
                        <p class="description"><?php esc_html_e('Country codes shown in form. e.g. +90,+1,+44,+33','ns-booking'); ?></p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Message field','ns-booking'); ?></th><td>
                        <label><input type="checkbox" name="nsbc_settings[enable_message]" value="1" <?php checked($opt['enable_message']??1,1); ?>> <?php esc_html_e('Show message field in form','ns-booking'); ?></label>
                        <p class="description"><?php esc_html_e('When disabled, the field is hidden and not required.','ns-booking'); ?></p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Package & extra images','ns-booking'); ?></th><td>
                        <label><input type="checkbox" name="nsbc_settings[show_images]" value="1" <?php checked($opt['show_images']??1,1); ?>> <?php esc_html_e('Show images (package featured image & extra icons)','ns-booking'); ?></label>
                        <p class="description"><?php esc_html_e('When disabled, cards show title + excerpt + price only, no images.','ns-booking'); ?></p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Theme mode','ns-booking'); ?></th><td>
                        <select name="nsbc_settings[theme_mode]">
                            <option value="auto" <?php selected($opt['theme_mode']??'auto','auto'); ?>><?php esc_html_e('Auto — follow system (prefers-color-scheme)','ns-booking'); ?></option>
                            <option value="light" <?php selected($opt['theme_mode']??'auto','light'); ?>><?php esc_html_e('Light — always light','ns-booking'); ?></option>
                            <option value="dark" <?php selected($opt['theme_mode']??'auto','dark'); ?>><?php esc_html_e('Dark — always dark','ns-booking'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Controls which background set is used. Auto follows OS preference; Light/Dark forces site style regardless of OS.','ns-booking'); ?></p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Background colors','ns-booking'); ?></th><td>
                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px">
                            Light <input type="color" name="nsbc_settings[bg_light]" value="<?php echo esc_attr($opt['bg_light']??'#ffffff'); ?>"> 
                            <input type="text" name="nsbc_settings[bg_light_text]" value="<?php echo esc_attr($opt['bg_light']??'#ffffff'); ?>" style="width:90px" placeholder="#ffffff" oninput="this.previousElementSibling.value=this.value">
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px">
                            Dark <input type="color" name="nsbc_settings[bg_dark]" value="<?php echo esc_attr($opt['bg_dark']??'#0b0b0c'); ?>">
                            <input type="text" name="nsbc_settings[bg_dark_text]" value="<?php echo esc_attr($opt['bg_dark']??'#0b0b0c'); ?>" style="width:90px" placeholder="#0b0b0c" oninput="this.previousElementSibling.value=this.value">
                        </label>
                        <br><small class="description"><?php esc_html_e('Page/section background surrounding the configurator. Cards use card colors below. Leave as #ffffff / #0b0b0c to inherit site background.','ns-booking'); ?></small>
                    </td></tr>
                    <tr><th><?php esc_html_e('Card background','ns-booking'); ?></th><td>
                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px">
                            Light <input type="color" name="nsbc_settings[card_light]" value="<?php echo esc_attr($opt['card_light']??'#ffffff'); ?>">
                            <input type="text" name="nsbc_settings[card_light_text]" value="<?php echo esc_attr($opt['card_light']??'#ffffff'); ?>" style="width:90px" placeholder="#ffffff" oninput="this.previousElementSibling.value=this.value">
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px">
                            Dark <input type="color" name="nsbc_settings[card_dark]" value="<?php echo esc_attr($opt['card_dark']??'#17171a'); ?>">
                            <input type="text" name="nsbc_settings[card_dark_text]" value="<?php echo esc_attr($opt['card_dark']??'#17171a'); ?>" style="width:90px" placeholder="#17171a" oninput="this.previousElementSibling.value=this.value">
                        </label>
                        <br><small class="description"><?php esc_html_e('Background for package cards, summary and form. Set to match or contrast your site.','ns-booking'); ?></small>
                    </td></tr>
                    <tr><th><?php esc_html_e('Card shadow','ns-booking'); ?></th><td>
                        <select name="nsbc_settings[card_shadow]">
                            <option value="auto" <?php selected($opt['card_shadow']??'auto','auto'); ?>><?php esc_html_e('Auto — theme default','ns-booking'); ?></option>
                            <option value="none" <?php selected($opt['card_shadow']??'auto','none'); ?>><?php esc_html_e('None','ns-booking'); ?></option>
                            <option value="subtle" <?php selected($opt['card_shadow']??'auto','subtle'); ?>><?php esc_html_e('Subtle','ns-booking'); ?></option>
                            <option value="medium" <?php selected($opt['card_shadow']??'auto','medium'); ?>><?php esc_html_e('Medium','ns-booking'); ?></option>
                            <option value="strong" <?php selected($opt['card_shadow']??'auto','strong'); ?>><?php esc_html_e('Strong','ns-booking'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Shadow on package cards, step cards and summary. Auto keeps the built-in light/dark shadows.','ns-booking'); ?></p>
                    </td></tr>
                    <?php
                    $nsbcColorRows = [
                        ['accent_light','Main color','#111827','accent_dark','#fafafa','#fafafa',false,
                         'Primary button background, active pill/card borders, focus rings and price highlight.'],
                        ['btn_hover_light','Primary button hover','','btn_hover_dark','','',true,
                         'Button background on hover. Empty = auto-derive from the main color.'],
                        ['text_light','Text color','#111827','text_dark','#f4f4f5','#f4f4f5',false,
                         'Main text: titles, labels, inputs, summary body.'],
                        ['muted_light','Muted color','#6b7280','muted_dark','#a1a1aa','#a1a1aa',false,
                         'Secondary text: hints, excerpts, prices, placeholders.'],
                    ];
                    foreach ($nsbcColorRows as $r) {
                        $lk=$r[0];$ll=$r[1];$ld=$r[2];$dk=$r[3];$dl=$r[4];$dd=$r[5];$auto=$r[6];$desc=$r[7];
                        $lv = (string)($opt[$lk] ?? ''); $dv = (string)($opt[$dk] ?? '');
                        $lHas = $lv !== '' || !$auto; $dHas = $dv !== '' || !$auto;
                        $lTxt = $auto ? $lv : ($lv ?: $ld); $dTxt = $auto ? $dv : ($dv ?: $dl);
                    ?>
                    <tr><th><?php echo esc_html($ll); ?></th><td>
                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px">
                            Light <input type="color" name="nsbc_settings[<?php echo esc_attr($lk); ?>]" value="<?php echo esc_attr($lHas ? ($lv ?: $ld) : '#888888'); ?>" <?php disabled(!$lHas); ?>>
                            <input type="text" name="nsbc_settings[<?php echo esc_attr($lk); ?>_text]" value="<?php echo esc_attr($lTxt); ?>" style="width:90px" placeholder="<?php echo esc_attr($ld !== '' ? $ld : 'auto'); ?>" data-nsbc-color-sync>
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px">
                            Dark <input type="color" name="nsbc_settings[<?php echo esc_attr($dk); ?>]" value="<?php echo esc_attr($dHas ? ($dv ?: $dl) : '#888888'); ?>" <?php disabled(!$dHas); ?>>
                            <input type="text" name="nsbc_settings[<?php echo esc_attr($dk); ?>_text]" value="<?php echo esc_attr($dTxt); ?>" style="width:90px" placeholder="<?php echo esc_attr($dl !== '' ? $dl : 'auto'); ?>" data-nsbc-color-sync>
                        </label>
                        <br><small class="description"><?php echo esc_html($desc); ?></small>
                    </td></tr>
                    <?php } ?>
                    <tr><th><?php esc_html_e('Admin email subject','ns-booking'); ?></th><td>
                        <input type="text" name="nsbc_settings[email_admin_subject]" value="<?php echo esc_attr($opt['email_admin_subject']??''); ?>" style="width:100%;max-width:700px">
                        <p class="description">Tags: {{id}} {{package}} {{session}} {{date}} {{total}} {{customer_name}}</p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Customer email subject','ns-booking'); ?></th><td>
                        <input type="text" name="nsbc_settings[email_customer_subject]" value="<?php echo esc_attr($opt['email_customer_subject']??''); ?>" style="width:100%;max-width:700px">
                    </td></tr>
                </table>
                <script>
                (function(){
                  const pairs=[['bg_light','bg_light_text'],['bg_dark','bg_dark_text'],['card_light','card_light_text'],['card_dark','card_dark_text'],
                               ['accent_light','accent_light_text'],['accent_dark','accent_dark_text'],
                               ['text_light','text_light_text'],['text_dark','text_dark_text'],
                               ['muted_light','muted_light_text'],['muted_dark','muted_dark_text'],
                               ['btn_hover_light','btn_hover_light_text'],['btn_hover_dark','btn_hover_dark_text']];
                  pairs.forEach(p=>{
                    const c=document.querySelector('input[name="nsbc_settings['+p[0]+']"]');
                    const t=document.querySelector('input[name="nsbc_settings['+p[1]+']"]');
                    if(!c||!t) return;
                    const optional=p[0].indexOf('btn_hover_')===0;
                    const valid=v=>/^#[0-9a-fA-F]{6}$/.test(v)||/^#[0-9a-fA-F]{3}$/.test(v);
                    c.addEventListener('input',()=>{ t.value=c.value; if(optional) c.disabled=false; });
                    t.addEventListener('input',()=>{
                      if(valid(t.value)){ c.value=t.value; if(optional) c.disabled=false; }
                      else if(optional && t.value==='') c.disabled=true;
                    });
                    if(optional && t.value==='') c.disabled=true;
                  });
                })();
                </script>
                <?php submit_button(); ?>
            </form>
            <hr>
            <p><strong>Shortcode:</strong> <code>[booking_configurator]</code> or <code>[ns_booking]</code></p>
            <p class="description">Place on any page. No TheGem dependency. Styling inherits theme but works standalone.</p>
        </div>
        <?php
    }
}
