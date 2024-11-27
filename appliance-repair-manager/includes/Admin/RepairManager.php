<?php
namespace ApplianceRepairManager\Admin;

class RepairManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_repairs_menu']);
        add_action('admin_post_arm_add_repair', [$this, 'handle_add_repair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handle_update_repair_status']);
        add_action('admin_post_arm_assign_technician', [$this, 'handle_assign_technician']);
        add_action('admin_post_arm_add_repair_note', [$this, 'handle_add_repair_note']);
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'get_client_appliances']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_arm_get_appliance_history', [$this, 'get_appliance_history']);
        add_action('wp_ajax_arm_add_note_ajax', [$this, 'handle_add_note_ajax']);
        add_action('wp_ajax_nopriv_arm_get_appliance_history', [$this, 'get_appliance_history']);
    }

    public function add_repairs_menu() {
        add_submenu_page(
            'appliance-repair-manager',
            __('Repairs', 'appliance-repair-manager'),
            __('Repairs', 'appliance-repair-manager'),
            'edit_arm_repairs',
            'arm-repairs',
            [$this, 'render_repairs_page']
        );
    }

    // ... (resto del código existente permanece igual)

    public function get_appliance_history() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');
        
        $appliance_id = intval($_POST['appliance_id']);
        
        global $wpdb;
        $appliance = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, c.name as client_name
            FROM {$wpdb->prefix}arm_appliances a
            LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
            WHERE a.id = %d",
            $appliance_id
        ));

        if (!$appliance) {
            wp_send_json_error(['message' => __('Appliance not found.', 'appliance-repair-manager')]);
            return;
        }

        $repairs = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, u.display_name as technician_name
            FROM {$wpdb->prefix}arm_repairs r
            LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
            WHERE r.appliance_id = %d
            ORDER BY r.created_at DESC",
            $appliance_id
        ));

        ob_start();
        include ARM_PLUGIN_DIR . 'templates/admin/modals/appliance-history.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
}