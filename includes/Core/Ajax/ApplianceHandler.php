<?php
namespace ApplianceRepairManager\Core\Ajax;

use ApplianceRepairManager\Core\Debug\ErrorLogger;

class ApplianceHandler {
    private $logger;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = ErrorLogger::getInstance();
        
        // Register AJAX handlers
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'getClientAppliances']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'getRepairDetails']);
        add_action('wp_ajax_nopriv_arm_get_repair_details', [$this, 'getRepairDetails']);
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
                'wpdb_error' => $this->wpdb->last_error,
                'user_id' => get_current_user_id()
            ]);
            
            wp_send_json_error([
                'message' => __('Error loading appliances', 'appliance-repair-manager'),
                'error' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }

    public function getRepairDetails() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            if (!$repair_id) {
                throw new \Exception('Invalid repair ID');
            }

            // Get repair details with joins
            $repair = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT r.*, 
                        a.type as appliance_type, 
                        a.brand, 
                        a.model,
                        c.name as client_name,
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

            // Add notes to repair object
            $repair->notes = $notes;

            // Start output buffering
            ob_start();
            
            // Include the template
            $template_path = ARM_PLUGIN_DIR . 'templates/admin/modals/repair-details.php';
            if (!file_exists($template_path)) {
                throw new \Exception('Template file not found: ' . $template_path);
            }
            
            include $template_path;
            
            // Get the buffered content
            $html = ob_get_clean();

            if (empty($html)) {
                throw new \Exception('Failed to generate repair details HTML');
            }

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_get_repair_details', $e->getMessage(), [
                'repair_id' => $repair_id ?? null,
                'wpdb_error' => $this->wpdb->last_error ?? null,
                'template_path' => $template_path ?? null,
                'user_id' => get_current_user_id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            wp_send_json_error([
                'message' => __('Error loading repair details', 'appliance-repair-manager'),
                'error' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }
}