<?php
function customer_care_html( $main_class, $close_icon ){?>
    <div class="<?php echo esc_attr($main_class); ?>">
    <div class="two-contact-care-popup">
        <?php echo $close_icon ? '<span class="two-contact-care-close"></span>' : ''?>
        <div class="two-contact-care-content-section">
            <div class="two-contact-care-wp-section">
                <div class="two-contact-care-title">
                    <?php echo __('Get free and fast support<br>on WordPress.org', 'tenweb-speed-optimizer' );?>
                </div>
                <div class="two-contact-care-description">
                    <p class="two-contact-care-content-text">
                        <?php echo __('If you’re having issues or need help with your website,<br>
                            the fastest way to get assistance is by creating a topic<br> on ',
                            'tenweb-speed-optimizer' );?>
                        <a href="<?php echo esc_url('https://wordpress.org/support/plugin/tenweb-speed-optimizer/');?>">
                            <?php echo __( 'WordPress.org.', 'tenweb-speed-optimizer' );?>
                        </a>
                    </p>
                    <p class="two-contact-care-content-text">
                        <?php echo __('Our support team constantly monitors and resolves topics<br> within 24 hours 
to provide users with a smooth<br> optimization process.',
                            'tenweb-speed-optimizer' );?>
                    </p>
                </div>
                <a target="_blank" class="two-contact-care-green-button"
                   href="<?php echo esc_url('https://wordpress.org/support/plugin/tenweb-speed-optimizer/');?>">
                    <?php echo __('CREATE A TOPIC', 'tenweb-speed-optimizer' );?>
                </a>
            </div>
            <div class="two-contact-care-pro-section">
                <div class="two-contact-care-booster-pro">
                    <p class="two-contact-care-content-text two-option-diamond">
                        <?php echo __('Priority support is available to ', 'tenweb-speed-optimizer' );?>
                        <a href="<?php echo esc_url(TENWEB_DASHBOARD . '/upgrade-plan');?>">
                            <?php echo __( 'Booster Pro', 'tenweb-speed-optimizer' );?></a>
                        <?php echo __( ' users:', 'tenweb-speed-optimizer' );?>
                    </p>
                    <p class="two-contact-care-content-text two-option-point">
                        <?php echo __('Manual website optimization',
                            'tenweb-speed-optimizer' );?>
                    </p>
                    <p class="two-contact-care-content-text two-option-point">
                        <?php echo __(' 24/7 live chat support',
                            'tenweb-speed-optimizer' );?>
                    </p>
                </div>
            </div>
        </div>
        <div class="two-contact-care-video-section">
            <video width="470" height="590" controls>
                <source src="<?php echo TENWEB_SO_URL . '/assets/images/wp_care_popup.mp4';?> " type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>
</div>
<?php } ?>