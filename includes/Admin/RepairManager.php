<?php
namespace ApplianceRepairManager\Admin;

class RepairManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_repairs_menu']);
        add_action('admin_post_arm_add_repair', [$this, 'handle_add_repair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handle_update_repair_status']);
        add_action('admin_post_arm_assign_technician', [$this, 'handle_assign_technician']);
        add_action('wp_ajax_arm_add_note_ajax', [$this, 'handle_add_note_ajax']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_nopriv_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_arm_get_appliance_history', [$this, 'get_appliance_history']);
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'get_client_appliances']);
    }

    // ... (rest of the existing methods remain the same)

    public function get_repair_details() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        $repair_id = intval($_POST['repair_id']);
        $is_public = isset($_POST['is_public']) && $_POST['is_public'] === 'true';

        if (!$is_public && !current_user_can('edit_arm_repairs')) {
            wp_send_json_error(['message' => __('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager')]);
            return;
        }

        global $wpdb;
        $repair = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, 
                   a.type as appliance_type,
                   a.brand,
                   a.model,
                   c.name as client_name,
                   u.display_name as technician_name
            FROM {$wpdb->prefix}arm_repairs r
            LEFT JOIN {$wpdb->prefix}arm_appliances a ON r.appliance_id = a.id
            LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
            LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
            WHERE r.id = %d
        ", $repair_id));

        if (!$repair) {
            wp_send_json_error(['message' => __('Repair not found.', 'appliance-repair-manager')]);
            return;
        }

        ob_start();
        include ARM_PLUGIN_DIR . 'templates/admin/modals/repair-details.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    // ... (rest of the existing methods remain the same)
}