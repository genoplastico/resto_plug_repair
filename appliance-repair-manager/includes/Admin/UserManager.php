<?php
namespace ApplianceRepairManager\Admin;

class UserManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_users_menu']);
        add_action('admin_post_arm_add_technician', [$this, 'handle_add_technician']);
        add_action('admin_post_arm_update_technician', [$this, 'handle_update_technician']);
        add_action('admin_post_arm_delete_technician', [$this, 'handle_delete_technician']);
    }

    public function add_users_menu() {
        add_submenu_page(
            'appliance-repair-manager',
            __('Technicians', 'appliance-repair-manager'),
            __('Technicians', 'appliance-repair-manager'),
            'manage_options',
            'arm-technicians',
            [$this, 'render_technicians_page']
        );
    }

    public function render_technicians_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/technicians.php';
    }

    public function handle_add_technician() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_add_technician');

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = wp_generate_password(12, true, true);

        // Create the user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_die($user_id->get_error_message());
        }

        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'arm_technician'
        ]);

        // Send email to new technician
        wp_new_user_notification($user_id, null, 'both');

        wp_redirect(add_query_arg([
            'page' => 'arm-technicians',
            'message' => 'technician_added'
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_update_technician() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_update_technician');

        $user_id = intval($_POST['user_id']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email
        ]);

        wp_redirect(add_query_arg([
            'page' => 'arm-technicians',
            'message' => 'technician_updated'
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_delete_technician() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_delete_technician');

        $user_id = intval($_POST['user_id']);
        
        // Check if user has any assigned repairs
        global $wpdb;
        $has_repairs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}arm_repairs WHERE technician_id = %d",
            $user_id
        ));

        if ($has_repairs > 0) {
            wp_die(__('Cannot delete technician with assigned repairs.', 'appliance-repair-manager'));
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);

        wp_redirect(add_query_arg([
            'page' => 'arm-technicians',
            'message' => 'technician_deleted'
        ], admin_url('admin.php')));
        exit;
    }
}