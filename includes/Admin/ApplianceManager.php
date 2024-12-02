<?php
namespace ApplianceRepairManager\Admin;

class ApplianceManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_appliances_menu']);
        add_action('admin_post_arm_add_appliance', [$this, 'handle_add_appliance']);
        add_action('admin_post_arm_delete_appliance_image', [$this, 'handle_delete_appliance_image']);
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

        check_admin_referer('arm_add_appliance');
        
        try {
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            $image_handler = \ApplianceRepairManager\Core\ImageHandler::getInstance();
        
            // Basic appliance data
            $appliance_data = [
                'client_id' => intval($_POST['client_id']),
                'type' => sanitize_text_field($_POST['appliance_type']),
                'brand' => sanitize_text_field($_POST['appliance_brand']),
                'model' => sanitize_text_field($_POST['appliance_model']),
                'serial_number' => sanitize_text_field($_POST['serial_number']),
                'status' => 'pending'
            ];

            // Insert appliance first
            $result = $wpdb->insert(
                $wpdb->prefix . 'arm_appliances',
                $appliance_data,
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );

            if ($result === false) {
                throw new \Exception($wpdb->last_error);
            }

            $appliance_id = $wpdb->insert_id;

            // Handle image upload if present
            if (!empty($_FILES['appliance_image']['name'])) {
                $image_id = $image_handler->handleUpload($_FILES['appliance_image'], $appliance_id);

                // Update appliance with image ID
                $result = $wpdb->update(
                    $wpdb->prefix . 'arm_appliances',
                    ['image_id' => $image_id],
                    ['id' => $appliance_id],
                    ['%d'],
                    ['%d']
                );

                if ($result === false) {
                    throw new \Exception($wpdb->last_error);
                }
            }

            $wpdb->query('COMMIT');

            wp_redirect(add_query_arg([
                'page' => 'arm-appliances',
                'message' => 'appliance_added'
            ], admin_url('admin.php')));
            exit;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_die(sprintf(
                __('Error saving appliance: %s', 'appliance-repair-manager'),
                $e->getMessage()
            ));
        }
    }

    public function handle_delete_appliance_image() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_delete_appliance_image');

        try {
            global $wpdb;
            $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
            
            $appliance = $wpdb->get_row($wpdb->prepare(
                "SELECT image_id FROM {$wpdb->prefix}arm_appliances WHERE id = %d",
                $appliance_id
            ));

            if ($appliance && $appliance->image_id) {
                $image_handler = \ApplianceRepairManager\Core\ImageHandler::getInstance();
                $image_handler->deleteImage($appliance->image_id);
                
                $wpdb->update(
                    $wpdb->prefix . 'arm_appliances',
                    ['image_id' => null],
                    ['id' => $appliance_id]
                );
            }
        } catch (\Exception $e) {
            wp_die($e->getMessage());
        }
    }
}