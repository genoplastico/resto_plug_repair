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
        
        // Basic appliance data
        $appliance_data = [
            'client_id' => intval($_POST['client_id']),
            'type' => sanitize_text_field($_POST['appliance_type']),
            'brand' => sanitize_text_field($_POST['appliance_brand']),
            'model' => sanitize_text_field($_POST['appliance_model']),
            'serial_number' => sanitize_text_field($_POST['serial_number']),
            'status' => 'pending'
        ];

        global $wpdb;

        // Handle image upload
        if (!empty($_FILES['appliance_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            // Use WordPress media handling
            $image_id = media_handle_upload('appliance_image', 0);
            
            if (is_wp_error($image_id)) {
                wp_die(sprintf(
                    __('Error uploading image: %s', 'appliance-repair-manager'),
                    $image_id->get_error_message()
                ));
            }
            
            $appliance_data['image_id'] = $image_id;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'arm_appliances',
            $appliance_data, 
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d']
        );

        if ($result === false) {
            // Clean up uploaded image if insert failed
            if (!empty($image_id)) {
                wp_delete_attachment($image_id, true);
            }
            wp_die(__('Error saving appliance data.', 'appliance-repair-manager'));
        }

        wp_redirect(add_query_arg([
            'page' => 'arm-appliances',
            'message' => 'appliance_added'
        ], admin_url('admin.php')));
        exit;
    }
}