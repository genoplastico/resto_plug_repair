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

        check_admin_referer('arm_add_appliance');
        
        try {
            global $wpdb;
            $wpdb->query('START TRANSACTION');
        
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
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                }

                // Set post ID for media attachment
                $_POST['post_id'] = $appliance_id;

                // Upload and get attachment ID
                $image_id = media_handle_upload('appliance_image', 0);

                if (is_wp_error($image_id)) {
                    throw new \Exception($image_id->get_error_message());
                }

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
}