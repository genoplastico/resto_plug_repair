<?php
namespace ApplianceRepairManager\Core\Handlers;

use ApplianceRepairManager\Core\Debug\ErrorLogger;

class RepairHandler {
    private $logger;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = ErrorLogger::getInstance();

        // Register handlers
        add_action('admin_post_arm_add_repair', [$this, 'handleAddRepair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handleUpdateRepairStatus']);
        add_action('admin_post_arm_assign_technician', [$this, 'handleAssignTechnician']);
    }

    public function handleAddRepair() {
        try {
            if (!current_user_can('edit_arm_repairs')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
            }

            check_admin_referer('arm_add_repair');

            // Validate and sanitize input
            $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
            $technician_id = isset($_POST['technician_id']) ? intval($_POST['technician_id']) : 0;
            $diagnosis = isset($_POST['diagnosis']) ? sanitize_textarea_field($_POST['diagnosis']) : '';
            $parts_used = isset($_POST['parts_used']) ? sanitize_textarea_field($_POST['parts_used']) : '';
            $cost = isset($_POST['cost']) ? floatval($_POST['cost']) : 0.0;

            // Validate required fields
            if (!$appliance_id || !$technician_id || empty($diagnosis)) {
                throw new \Exception(__('Please fill in all required fields.', 'appliance-repair-manager'));
            }

            // Insert repair record
            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'arm_repairs',
                [
                    'appliance_id' => $appliance_id,
                    'technician_id' => $technician_id,
                    'diagnosis' => $diagnosis,
                    'parts_used' => $parts_used,
                    'cost' => $cost,
                    'status' => 'pending',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s']
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            // Update appliance status
            $this->wpdb->update(
                $this->wpdb->prefix . 'arm_appliances',
                ['status' => 'in_repair'],
                ['id' => $appliance_id],
                ['%s'],
                ['%d']
            );

            // Redirect with success message
            wp_redirect(add_query_arg([
                'page' => 'arm-repairs',
                'message' => 'repair_added'
            ], admin_url('admin.php')));
            exit;

        } catch (\Exception $e) {
            $this->logger->logError('Error adding repair', [
                'error' => $e->getMessage(),
                'appliance_id' => $appliance_id ?? null,
                'technician_id' => $technician_id ?? null,
                'wpdb_error' => $this->wpdb->last_error ?? null
            ]);

            wp_die($e->getMessage());
        }
    }

    public function handleUpdateRepairStatus() {
        try {
            if (!current_user_can('edit_arm_repairs')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
            }

            check_admin_referer('arm_update_repair_status');

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

            if (!$repair_id || !$appliance_id || empty($status)) {
                throw new \Exception(__('Invalid request parameters.', 'appliance-repair-manager'));
            }

            // Update repair status
            $result = $this->wpdb->update(
                $this->wpdb->prefix . 'arm_repairs',
                [
                    'status' => $status,
                    'updated_at' => current_time('mysql'),
                    'completed_at' => $status === 'completed' ? current_time('mysql') : null
                ],
                ['id' => $repair_id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            // Update appliance status if repair is completed
            if ($status === 'completed' || $status === 'delivered') {
                $this->wpdb->update(
                    $this->wpdb->prefix . 'arm_appliances',
                    ['status' => 'ready'],
                    ['id' => $appliance_id],
                    ['%s'],
                    ['%d']
                );
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
                'status' => $status ?? null,
                'wpdb_error' => $this->wpdb->last_error ?? null
            ]);

            wp_die($e->getMessage());
        }
    }

    public function handleAssignTechnician() {
        try {
            if (!current_user_can('edit_arm_repairs')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
            }

            check_admin_referer('arm_assign_technician');

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            $technician_id = isset($_POST['technician_id']) ? intval($_POST['technician_id']) : 0;

            if (!$repair_id || !$technician_id) {
                throw new \Exception(__('Invalid request parameters.', 'appliance-repair-manager'));
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
                'technician_id' => $technician_id ?? null,
                'wpdb_error' => $this->wpdb->last_error ?? null
            ]);

            wp_die($e->getMessage());
        }
    }
}