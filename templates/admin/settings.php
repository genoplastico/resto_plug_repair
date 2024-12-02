<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('Appliance Repair Manager Settings', 'appliance-repair-manager'); ?></h1>

    <?php settings_errors('arm_settings'); ?>

    <form method="post" action="">
        <?php wp_nonce_field('arm_settings_nonce'); ?>
        
        <div class="arm-settings-section">
            <h2><?php _e('Debug Settings', 'appliance-repair-manager'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Translation Debug', 'appliance-repair-manager'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="arm_translation_debug" 
                                   value="1" 
                                   <?php checked(get_option('arm_translation_debug_enabled', 0), 1); ?>>
                            <?php _e('Enable translation debug logging', 'appliance-repair-manager'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, untranslated strings will be logged and displayed in the admin footer (requires WP_DEBUG).', 'appliance-repair-manager'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" 
                       name="arm_save_cloudinary" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Save Cloudinary Settings', 'appliance-repair-manager'); ?>">
            </p>
        </form>
        </div>

        <p class="submit">
            <input type="submit" 
                   name="arm_save_settings" 
                   class="button button-primary" 
                   value="<?php esc_attr_e('Save Settings', 'appliance-repair-manager'); ?>">
        </p>
    </form>

    <div class="arm-settings-section">
        <h2><?php _e('Cloudinary Settings', 'appliance-repair-manager'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('arm_cloudinary_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php _e('Cloud Name', 'appliance-repair-manager'); ?>
                </th>
                <td>
                    <input type="text" 
                           name="arm_cloudinary_cloud_name" 
                           value="<?php echo esc_attr(get_option('arm_cloudinary_cloud_name')); ?>"
                           class="regular-text"
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e('API Key', 'appliance-repair-manager'); ?>
                </th>
                <td>
                    <input type="text" 
                           name="arm_cloudinary_api_key" 
                           value="<?php echo esc_attr(get_option('arm_cloudinary_api_key')); ?>"
                           class="regular-text"
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e('API Secret', 'appliance-repair-manager'); ?>
                </th>
                <td>
                    <input type="password" 
                           name="arm_cloudinary_api_secret" 
                           value="<?php echo esc_attr(get_option('arm_cloudinary_api_secret')); ?>"
                           class="regular-text"
                           required>
                </td>
            </tr>
        </table>
    </div>

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