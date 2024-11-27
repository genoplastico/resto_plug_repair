<?php
namespace ApplianceRepairManager\Admin;

class SettingsManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_settings_menu']);
    }

    public function add_settings_menu() {
        add_submenu_page(
            'appliance-repair-manager',
            __('Settings', 'appliance-repair-manager'),
            __('Settings', 'appliance-repair-manager'),
            'manage_options',
            'arm-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}