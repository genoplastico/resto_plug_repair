<?php
namespace ApplianceRepairManager\Admin;

class ApplianceManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_appliances_menu']);
        add_action('admin_post_arm_add_appliance', [$this, 'handle_add_appliance']);
    }

    public function add_appliances_menu() {
        add_submenu_page(
            'appliance-repair-manager', // Parent slug
            __('Appliances', 'appliance-repair-manager'), // Page title
            __('Appliances', 'appliance-repair-manager'), // Menu title
            'manage_options', // Capability
            'arm-appliances', // Menu slug
            [$this, 'render_appliances_page'] // Function
        );
    }

    public function render_appliances_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/appliances.php';
    }

    public function handle_add_appliance() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_add_appliance', '_wpnonce');

        // Validate and sanitize basic input
        $appliance_data = [
            'client_id' => intval($_POST['client_id']),
            'type' => sanitize_text_field($_POST['appliance_type']),
            'brand' => sanitize_text_field($_POST['appliance_brand']),
            'model' => sanitize_text_field($_POST['appliance_model']),
            'serial_number' => sanitize_text_field($_POST['serial_number']),
            'status' => 'pending'
        ];

        // Handle image upload
        if (!empty($_FILES['appliance_image']['name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            $upload_overrides = ['test_form' => false];
            $uploaded_file = wp_handle_upload($_FILES['appliance_image'], $upload_overrides);

            if (!isset($uploaded_file['error'])) {
                $appliance_data['image_url'] = $uploaded_file['url'];
                $appliance_data['image_path'] = $uploaded_file['file'];
            } else {
                wp_die(sprintf(
                    __('Error uploading image: %s', 'appliance-repair-manager'),
                    $uploaded_file['error']
                ));
            }
        }

        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'arm_appliances',
            $appliance_data,
            array_fill(0, count($appliance_data), '%s')
        );

        if ($result === false) {
            wp_die(__('Error saving appliance data.', 'appliance-repair-manager'));
        }

        wp_redirect(add_query_arg([
            'page' => 'arm-appliances',
            'message' => 'appliance_added'
        ], admin_url('admin.php')));
        exit;
    }
}