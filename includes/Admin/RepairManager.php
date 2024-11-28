<?php
namespace ApplianceRepairManager\Admin;

use ApplianceRepairManager\Core\Notes\NotesManager;
use ApplianceRepairManager\Core\Status\RepairStatusManager;

class RepairManager {
    private $notes_manager;
    private $status_manager;

    public function __construct() {
        $this->notes_manager = NotesManager::getInstance();
        $this->status_manager = RepairStatusManager::getInstance();
        
        // Agregar menú de reparaciones
        add_action('admin_menu', [$this, 'add_repairs_menu']);
        
        // Manejadores AJAX y acciones
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'get_client_appliances']);
        add_action('admin_post_arm_add_repair', [$this, 'handle_add_repair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handle_update_repair_status']);
        add_action('admin_post_arm_assign_technician', [$this, 'handle_assign_technician']);
        add_action('wp_ajax_arm_add_note', [$this, 'handle_add_note']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_nopriv_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_arm_get_appliance_history', [$this, 'get_appliance_history']);
    }

    public function add_repairs_menu() {
        add_submenu_page(
            'appliance-repair-manager',
            __('Repairs', 'appliance-repair-manager'),
            __('Repairs', 'appliance-repair-manager'),
            'manage_options',
            'arm-repairs',
            [$this, 'render_repairs_page']
        );
    }

    public function render_repairs_page() {
        if (!current_user_can('manage_options') && !current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/repairs.php';
    }

    public function get_client_appliances() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        $client_id = intval($_POST['client_id']);
        
        global $wpdb;
        $appliances = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}arm_appliances 
            WHERE client_id = %d 
            ORDER BY type, brand, model",
            $client_id
        ));

        if ($appliances) {
            wp_send_json_success($appliances);
        } else {
            wp_send_json_error(['message' => __('No appliances found for this client.', 'appliance-repair-manager')]);
        }
    }

    public function handle_add_repair() {
        if (!current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager'));
        }

        check_admin_referer('arm_add_repair');

        $repair_data = [
            'appliance_id' => intval($_POST['appliance_id']),
            'technician_id' => intval($_POST['technician_id']),
            'diagnosis' => sanitize_textarea_field($_POST['diagnosis']),
            'parts_used' => sanitize_textarea_field($_POST['parts_used']),
            'cost' => floatval($_POST['cost']),
            'status' => 'pending'
        ];

        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'arm_repairs',
            $repair_data,
            ['%d', '%d', '%s', '%s', '%f', '%s']
        );

        if ($result) {
            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'repair_added'
            ], admin_url('admin.php')));
        } else {
            wp_die(__('Error adding repair record.', 'appliance-repair-manager'));
        }
        exit;
    }

    public function handle_update_repair_status() {
        if (!current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager'));
        }

        check_admin_referer('arm_update_repair_status');

        $repair_id = intval($_POST['repair_id']);
        $status = sanitize_text_field($_POST['status']);

        if (!$this->status_manager->isValidStatus($status)) {
            wp_die(__('Invalid status.', 'appliance-repair-manager'));
        }

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'arm_repairs',
            ['status' => $status],
            ['id' => $repair_id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            // Actualizar también el estado del electrodoméstico si es necesario
            if ($status === 'completed' || $status === 'delivered') {
                $wpdb->update(
                    $wpdb->prefix . 'arm_appliances',
                    ['status' => $status],
                    ['id' => intval($_POST['appliance_id'])],
                    ['%s'],
                    ['%d']
                );
            }

            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'status_updated'
            ], admin_url('admin.php')));
        } else {
            wp_die(__('Error updating repair status.', 'appliance-repair-manager'));
        }
        exit;
    }

    public function handle_assign_technician() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager'));
        }

        check_admin_referer('arm_assign_technician');

        $repair_id = intval($_POST['repair_id']);
        $technician_id = intval($_POST['technician_id']);

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'arm_repairs',
            ['technician_id' => $technician_id],
            ['id' => $repair_id],
            ['%d'],
            ['%d']
        );

        if ($result !== false) {
            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'technician_assigned'
            ], admin_url('admin.php')));
        } else {
            wp_die(__('Error assigning technician.', 'appliance-repair-manager'));
        }
        exit;
    }

    public function handle_add_note() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        if (!current_user_can('edit_arm_repairs')) {
            wp_send_json_error(['message' => __('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager')]);
            return;
        }

        $repair_id = intval($_POST['repair_id']);
        $note = sanitize_textarea_field($_POST['note']);
        $is_public = isset($_POST['is_public']) ? true : false;

        if ($this->notes_manager->add_note($repair_id, $note, $is_public)) {
            $notes = $this->notes_manager->get_notes($repair_id);
            ob_start();
            include ARM_PLUGIN_DIR . 'templates/admin/partials/notes-list.php';
            $html = ob_get_clean();
            
            wp_send_json_success([
                'message' => __('Note added successfully.', 'appliance-repair-manager'),
                'html' => $html
            ]);
        } else {
            wp_send_json_error(['message' => __('Error adding note.', 'appliance-repair-manager')]);
        }
    }

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

    public function get_appliance_history() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        $appliance_id = intval($_POST['appliance_id']);

        global $wpdb;
        $appliance = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, c.name as client_name
            FROM {$wpdb->prefix}arm_appliances a
            LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
            WHERE a.id = %d
        ", $appliance_id));

        if (!$appliance) {
            wp_send_json_error(['message' => __('Appliance not found.', 'appliance-repair-manager')]);
            return;
        }

        $repairs = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, u.display_name as technician_name
            FROM {$wpdb->prefix}arm_repairs r
            LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
            WHERE r.appliance_id = %d
            ORDER BY r.created_at DESC
        ", $appliance_id));

        ob_start();
        include ARM_PLUGIN_DIR . 'templates/admin/modals/appliance-history.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
}