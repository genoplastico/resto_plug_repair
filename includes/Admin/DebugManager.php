<?php
namespace ApplianceRepairManager\Admin;

class DebugManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_debug_menu']);
    }

    public function add_debug_menu() {
        add_submenu_page(
            'appliance-repair-manager',
            __('Debug', 'appliance-repair-manager'),
            __('Debug', 'appliance-repair-manager'),
            'manage_options',
            'arm-debug',
            [$this, 'render_debug_page']
        );
    }

    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/debug.php';
    }
}