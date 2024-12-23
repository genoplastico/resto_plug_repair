<?php
namespace ApplianceRepairManager\Admin;

use ApplianceRepairManager\Core\Debug\ErrorLogger;

class RepairManager {
    private $logger;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = ErrorLogger::getInstance();
    }

    public function render_repairs_page() {
        if (!current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/repairs.php';
    }

    public function getClientAppliances() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
            if (!$client_id) {
                throw new \Exception('Invalid client ID');
            }

            $appliances = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT id, type, brand, model 
                FROM {$this->wpdb->prefix}arm_appliances 
                WHERE client_id = %d 
                ORDER BY brand, type",
                $client_id
            ));

            if ($appliances === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            wp_send_json_success(['appliances' => $appliances]);

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_get_client_appliances', $e->getMessage(), [
                'client_id' => $client_id ?? null,
                'wpdb_error' => $this->wpdb->last_error
            ]);
            
            wp_send_json_error([
                'message' => __('Error loading appliances', 'appliance-repair-manager'),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getRepairDetails() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            if (!$repair_id) {
                throw new \Exception('Invalid repair ID');
            }

            $repair = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT r.*, 
                        a.type as appliance_type, 
                        a.brand, 
                        a.model,
                        c.name as client_name,
                        c.email as client_email,
                        u.display_name as technician_name
                FROM {$this->wpdb->prefix}arm_repairs r
                LEFT JOIN {$this->wpdb->prefix}arm_appliances a ON r.appliance_id = a.id
                LEFT JOIN {$this->wpdb->prefix}arm_clients c ON a.client_id = c.id
                LEFT JOIN {$this->wpdb->users} u ON r.technician_id = u.ID
                WHERE r.id = %d",
                $repair_id
            ));

            if (!$repair) {
                throw new \Exception('Repair not found');
            }

            // Get repair notes
            $notes = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT n.*, u.display_name as author_name
                FROM {$this->wpdb->prefix}arm_repair_notes n
                LEFT JOIN {$this->wpdb->users} u ON n.user_id = u.ID
                WHERE n.repair_id = %d
                ORDER BY n.created_at DESC",
                $repair_id
            ));

            $repair->notes = $notes;

            ob_start();
            include ARM_PLUGIN_DIR . 'templates/admin/modals/repair-details.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_get_repair_details', $e->getMessage(), [
                'repair_id' => $repair_id ?? null,
                'wpdb_error' => $this->wpdb->last_error
            ]);
            
            wp_send_json_error([
                'message' => __('Error loading repair details', 'appliance-repair-manager'),
                'error' => $e->getMessage()
            ]);
        }
    }
}