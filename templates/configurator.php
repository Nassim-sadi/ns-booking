<?php
if (!defined('ABSPATH')) exit;
/** @var string $minDate */
/** @var string $currency */
/** @var string $symbol */
?>
<div class="nsbc-configurator" data-nsbc>
    <div class="nsbc-layout">
        <div class="nsbc-main">
            <!-- Step 1: Configuration -->
            <section class="nsbc-card nsbc-step" data-step="1">
                <h3 class="nsbc-card-title"><?php esc_html_e('Choose Your Experience','ns-booking'); ?></h3>

                <div class="nsbc-field">
                    <label class="nsbc-label"><?php esc_html_e('Package','ns-booking'); ?> <span class="nsbc-req">*</span></label>
                    <div class="nsbc-packages" data-role="packages">
                        <!-- injected by JS -->
                    </div>
                </div>

                <div class="nsbc-field">
                    <label class="nsbc-label"><?php esc_html_e('Photo Type','ns-booking'); ?></label>
                    <div class="nsbc-pills" data-role="session-pills">
                        <label class="nsbc-pill is-active"><input type="radio" name="nsbc_session" value="solo" checked> <?php esc_html_e('Solo','ns-booking'); ?></label>
                        <label class="nsbc-pill"><input type="radio" name="nsbc_session" value="couple"> <?php esc_html_e('Couple','ns-booking'); ?></label>
                    </div>
                </div>

                <div class="nsbc-field" data-role="extras-wrap" style="display:none">
                    <label class="nsbc-label"><?php esc_html_e('Extra Services','ns-booking'); ?></label>
                    <div class="nsbc-extras" data-role="extras"></div>
                </div>

                <div class="nsbc-field">
                    <label class="nsbc-label" for="nsbc-date"><?php esc_html_e('Preferred Date','ns-booking'); ?> <span class="nsbc-req">*</span></label>
                    <input type="date" id="nsbc-date" data-role="date" min="<?php echo esc_attr($minDate); ?>" class="nsbc-input">
                    <small class="nsbc-hint"><?php esc_html_e('Select a future date.','ns-booking'); ?></small>
                </div>
            </section>

            <!-- Step 3: Customer -->
            <section class="nsbc-card nsbc-step" data-step="3">
                <h3 class="nsbc-card-title"><?php esc_html_e('Your Details','ns-booking'); ?></h3>
                <div class="nsbc-grid2">
                    <div class="nsbc-field">
                        <label class="nsbc-label" for="nsbc-name"><?php esc_html_e('Full Name','ns-booking'); ?> <span class="nsbc-req">*</span></label>
                        <input type="text" id="nsbc-name" data-role="name" class="nsbc-input" placeholder="<?php esc_attr_e('John Doe','ns-booking'); ?>" autocomplete="name" required>
                    </div>
                    <div class="nsbc-field">
                        <label class="nsbc-label" for="nsbc-email"><?php esc_html_e('Email','ns-booking'); ?> <span class="nsbc-req">*</span></label>
                        <input type="email" id="nsbc-email" data-role="email" class="nsbc-input" placeholder="you@example.com" autocomplete="email" required>
                    </div>
                </div>

                <div class="nsbc-field">
                    <label class="nsbc-label"><?php esc_html_e('Phone / WhatsApp','ns-booking'); ?> <span class="nsbc-req">*</span></label>
                    <div class="nsbc-phone-row">
                        <select data-role="phone-country" class="nsbc-input nsbc-phone-country" aria-label="Country code"></select>
                        <input type="tel" data-role="phone" class="nsbc-input nsbc-phone-number" placeholder="5XX XXX XX XX" autocomplete="tel" required>
                    </div>
                </div>

                <div class="nsbc-field">
                    <label class="nsbc-label" for="nsbc-message"><?php esc_html_e('Message','ns-booking'); ?> <span class="nsbc-optional">(<?php esc_html_e('optional','ns-booking'); ?>)</span></label>
                    <textarea id="nsbc-message" data-role="message" class="nsbc-input nsbc-textarea" rows="3" placeholder="<?php esc_attr_e('Tell us anything we should know...','ns-booking'); ?>"></textarea>
                </div>

                <!-- honeypot -->
                <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true">
                    <label>Website <input type="text" name="website" data-role="honeypot" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="nsbc-actions">
                    <button type="button" class="nsbc-btn nsbc-btn-primary" data-role="submit"><?php esc_html_e('Send Booking Request','ns-booking'); ?></button>
                    <span class="nsbc-form-msg" data-role="form-msg" role="status"></span>
                </div>
            </section>

            <div class="nsbc-success" data-role="success" style="display:none">
                <div class="nsbc-success-icon">✓</div>
                <h3><?php esc_html_e('Reservation Received!','ns-booking'); ?></h3>
                <p><?php esc_html_e('Thank you! We have received your booking request and will confirm your session within 24 hours. Check your email for details.','ns-booking'); ?></p>
            </div>
        </div>

        <!-- Step 2: Live Summary (sticky) -->
        <aside class="nsbc-sidebar">
            <div class="nsbc-summary" data-role="summary">
                <h4 class="nsbc-summary-title"><?php esc_html_e('Summary','ns-booking'); ?></h4>
                <div class="nsbc-summary-body" data-role="summary-body">
                    <p class="nsbc-summary-empty"><?php esc_html_e('Select a package to begin','ns-booking'); ?></p>
                </div>
                <div class="nsbc-total" data-role="total-row">
                    <span><?php esc_html_e('Total','ns-booking'); ?></span>
                    <strong data-role="total">0<?php echo esc_html($symbol); ?></strong>
                </div>
                <small class="nsbc-summary-hint"><?php esc_html_e('Final price confirmed on submission.','ns-booking'); ?></small>
            </div>
        </aside>
    </div>
</div>
