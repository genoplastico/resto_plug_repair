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

        // Register hooks for repair management
        add_action('admin_post_arm_add_repair', [$this, 'handleAddRepair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handleUpdateRepairStatus']);
        add_action('admin_post_arm_assign_technician', [$this, 'handleAssignTechnician']);
    }

    public function render_repairs_page() {
        if (!current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/repairs.php';
    }

    public function handleAddRepair() {
        try {
            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            check_admin_referer('arm_add_repair');

            // Validate required fields
            $required_fields = ['client_id', 'appliance_id', 'technician_id', 'diagnosis', 'cost'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            $data = [
                'appliance_id' => intval($_POST['appliance_id']),
                'technician_id' => intval($_POST['technician_id']),
                'diagnosis' => sanitize_textarea_field($_POST['diagnosis']),
                'parts_used' => sanitize_textarea_field($_POST['parts_used'] ?? ''),
                'cost' => floatval($_POST['cost']),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];

            $this->logger->logError('Adding new repair', [
                'data' => $data,
                'user_id' => get_current_user_id()
            ], 'INFO');

            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'arm_repairs',
                $data,
                ['%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s']
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            $repair_id = $this->wpdb->insert_id;

            // Log success
            $this->logger->logError('Repair added successfully', [
                'repair_id' => $repair_id,
                'data' => $data
            ], 'INFO');

            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'repair_added'
            ], admin_url('admin.php')));
            exit;

        } catch (\Exception $e) {
            $this->logger->logError('Error adding repair', [
                'error' => $e->getMessage(),
                'post_data' => $_POST,
                'trace' => $e->getTraceAsString()
            ], 'ERROR');

            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'error',
                'error' => urlencode($e->getMessage())
            ], admin_url('admin.php')));
            exit;
        }
    }

    public function handleUpdateRepairStatus() {
        try {
            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            check_admin_referer('arm_update_repair_status');

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

            if (!$repair_id || !$status) {
                throw new \Exception('Invalid parameters');
            }

            $result = $this->wpdb->update(
                $this->wpdb->prefix . 'arm_repairs',
                [
                    'status' => $status,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $repair_id],
                ['%s', '%s'],
                ['%d']
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'status_updated'
            ], admin_url('admin.php')));
            exit;

        } catch (\Exception $e) {
            $this->logger->logError('Error updating repair status', [
                'error' => $e->getMessage(),
                'repair_id' => $repair_id ?? null,
                'status' => $status ?? null
            ]);

            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'error',
                'error' => urlencode($e->getMessage())
            ], admin_url('admin.php')));
            exit;
        }
    }

    public function handleAssignTechnician() {
        try {
            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            check_admin_referer('arm_assign_technician');

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            $technician_id = isset($_POST['technician_id']) ? intval($_POST['technician_id']) : 0;

            if (!$repair_id || !$technician_id) {
                throw new \Exception('Invalid parameters');
            }

            $result = $this->wpdb->update(
                $this->wpdb->prefix . 'arm_repairs',
                [
                    'technician_id' => $technician_id,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $repair_id],
                ['%d', '%s'],
                ['%d']
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'technician_assigned'
            ], admin_url('admin.php')));
            exit;

        } catch (\Exception $e) {
            $this->logger->logError('Error assigning technician', [
                'error' => $e->getMessage(),
                'repair_id' => $repair_id ?? null,
                'technician_id' => $technician_id ?? null
            ]);

            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'error',
                'error' => urlencode($e->getMessage())
            ], admin_url('admin.php')));
            exit;
        }
    }
}