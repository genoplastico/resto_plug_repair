<?php
namespace ApplianceRepairManager\Core;

class EmailManager {
    public function __construct() {
        // Solo personalizamos el correo de nuevo usuario
        add_filter('wp_new_user_notification_email', [$this, 'customize_new_user_email'], 10, 3);
        
        // Mostramos aviso para instalar WP Mail SMTP si no está activo
        add_action('admin_notices', [$this, 'smtp_plugin_notice']);
    }

    public function smtp_plugin_notice() {
        if (!is_plugin_active('wp-mail-smtp/wp_mail_smtp.php')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php 
                    echo sprintf(
                        __('Appliance Repair Manager requires %sWP Mail SMTP%s plugin for reliable email delivery. Please install and configure it.', 'appliance-repair-manager'),
                        '<a href="' . admin_url('plugin-install.php?s=wp+mail+smtp&tab=search&type=term') . '">',
                        '</a>'
                    ); 
                    ?>
                </p>
            </div>
            <?php
        }
    }

    public function customize_new_user_email($wp_new_user_notification_email, $user, $blogname) {
        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            return $wp_new_user_notification_email;
        }

        $message = sprintf(__('Welcome to %s!', 'appliance-repair-manager'), $blogname) . "\r\n\r\n";
        $message .= __('Your account has been created with the following details:', 'appliance-repair-manager') . "\r\n\r\n";
        $message .= sprintf(__('Username: %s', 'appliance-repair-manager'), $user->user_login) . "\r\n";
        $message .= __('To set your password, visit the following address:', 'appliance-repair-manager') . "\r\n\r\n";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "\r\n\r\n";
        $message .= __('After setting your password, you can log in using your username and password.', 'appliance-repair-manager') . "\r\n\r\n";
        $message .= __('If you have any questions, please contact the site administrator.', 'appliance-repair-manager');

        $wp_new_user_notification_email['subject'] = sprintf(__('[%s] Your technician account details', 'appliance-repair-manager'), $blogname);
        $wp_new_user_notification_email['message'] = $message;

        return $wp_new_user_notification_email;
    }
}