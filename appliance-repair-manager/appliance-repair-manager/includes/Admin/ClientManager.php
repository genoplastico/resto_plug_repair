<?php
namespace ApplianceRepairManager\Admin;

class ClientManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_clients_menu']);
        add_action('admin_post_arm_add_client', [$this, 'handle_add_client']);
    }

    public function add_clients_menu() {
        add_submenu_page(
            'appliance-repair-manager', // Parent slug
            __('Clients', 'appliance-repair-manager'), // Page title
            __('Clients', 'appliance-repair-manager'), // Menu title
            'manage_options', // Capability
            'arm-clients', // Menu slug
            [$this, 'render_clients_page'] // Function
        );
    }

    public function render_clients_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/clients.php';
    }

    public function handle_add_client() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_add_client');

        $client_data = [
            'name' => sanitize_text_field($_POST['client_name']),
            'email' => sanitize_email($_POST['client_email']),
            'phone' => sanitize_text_field($_POST['client_phone']),
            'address' => sanitize_textarea_field($_POST['client_address'])
        ];

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'arm_clients',
            $client_data,
            ['%s', '%s', '%s', '%s']
        );

        wp_redirect(add_query_arg([
            'page' => 'arm-clients',
            'message' => 'client_added'
        ], admin_url('admin.php')));
        exit;
    }
}