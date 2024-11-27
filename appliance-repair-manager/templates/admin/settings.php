<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('Appliance Repair Manager Settings', 'appliance-repair-manager'); ?></h1>

    <div class="arm-settings-section">
        <h2><?php _e('Email Configuration', 'appliance-repair-manager'); ?></h2>
        
        <?php if (!is_plugin_active('wp-mail-smtp/wp_mail_smtp.php')): ?>
            <div class="notice notice-warning">
                <p>
                    <?php 
                    echo sprintf(
                        __('Please install and configure the %sWP Mail SMTP%s plugin to ensure reliable email delivery.', 'appliance-repair-manager'),
                        '<a href="' . admin_url('plugin-install.php?s=wp+mail+smtp&tab=search&type=term') . '">',
                        '</a>'
                    ); 
                    ?>
                </p>
                <p>
                    <?php _e('After installation:', 'appliance-repair-manager'); ?>
                    <ol>
                        <li><?php _e('Go to WP Mail SMTP settings', 'appliance-repair-manager'); ?></li>
                        <li><?php _e('Choose Gmail mailer', 'appliance-repair-manager'); ?></li>
                        <li><?php _e('Follow the setup wizard to configure Gmail authentication', 'appliance-repair-manager'); ?></li>
                    </ol>
                </p>
            </div>
        <?php else: ?>
            <div class="notice notice-info">
                <p>
                    <?php 
                    echo sprintf(
                        __('Email settings are managed through the %sWP Mail SMTP Settings page%s.', 'appliance-repair-manager'),
                        '<a href="' . admin_url('admin.php?page=wp-mail-smtp') . '">',
                        '</a>'
                    ); 
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>