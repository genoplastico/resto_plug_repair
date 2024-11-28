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

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            $is_public = isset($_POST['is_public']) ? (bool)$_POST['is_public'] : false;
            $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';

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

            // Verify access permissions
            if ($is_public) {
                // Get client info for token validation
                $client = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT c.* 
                    FROM {$this->wpdb->prefix}arm_clients c
                    JOIN {$this->wpdb->prefix}arm_appliances a ON c.id = a.client_id
                    JOIN {$this->wpdb->prefix}arm_repairs r ON a.id = r.appliance_id
                    WHERE r.id = %d",
                    $repair_id
                ));

                if (!$client) {
                    throw new \Exception('Client not found');
                }

                $expected_token = wp_hash($client->id . $client->email . wp_salt());
                
                if (!hash_equals($expected_token, $token)) {
                    $this->logger->logError('Invalid token provided', [
                        'repair_id' => $repair_id,
                        'provided_token' => $token,
                        'expected_token' => $expected_token,
                        'client_id' => $client->id
                    ]);
                    throw new \Exception('Invalid token');
                }
            } elseif (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            // Get repair notes (only public notes for public view)
            $notes_query = $this->wpdb->prepare(
                "SELECT n.*, u.display_name as author_name
                FROM {$this->wpdb->prefix}arm_repair_notes n
                LEFT JOIN {$this->wpdb->users} u ON n.user_id = u.ID
                WHERE n.repair_id = %d " .
                ($is_public ? "AND n.is_public = 1 " : "") .
                "ORDER BY n.created_at DESC",
                $repair_id
            );

            $repair->notes = $this->wpdb->get_results($notes_query);

            // Load appropriate template
            ob_start();
            include ARM_PLUGIN_DIR . 'templates/' . ($is_public ? 'public' : 'admin') . '/modals/repair-details.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_get_repair_details', $e->getMessage(), [
                'repair_id' => $repair_id ?? null,
                'is_public' => $is_public ?? false,
                'token' => $token ?? null,
                'wpdb_error' => $this->wpdb->last_error ?? null,
                'ajax_action' => 'arm_get_repair_details',
                'request_data' => $_POST
            ]);
            
            wp_send_json_error([
                'message' => __('Error loading repair details', 'appliance-repair-manager'),
                'error' => $e->getMessage()
            ]);
        }
    }
}