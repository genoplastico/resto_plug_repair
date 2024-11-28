<?php
namespace ApplianceRepairManager\Admin;

use ApplianceRepairManager\Core\Notes\NotesManager;
use ApplianceRepairManager\Core\Status\RepairStatusManager;
use ApplianceRepairManager\Core\Debug\Logger;
use ApplianceRepairManager\Core\HookManager;

class RepairManager {
    private $notes_manager;
    private $status_manager;
    private $logger;
    private $hook_manager;

    public function __construct() {
        $this->notes_manager = NotesManager::getInstance();
        $this->status_manager = RepairStatusManager::getInstance();
        $this->logger = Logger::getInstance();
        $this->hook_manager = HookManager::getInstance();
        
        $this->register_hooks();
    }

    private function register_hooks() {
        $hooks = [
            ['wp_ajax_arm_get_client_appliances', 'get_client_appliances'],
            ['admin_post_arm_add_repair', 'handle_add_repair'],
            ['admin_post_arm_update_repair_status', 'handle_update_repair_status'],
            ['admin_post_arm_assign_technician', 'handle_assign_technician'],
            ['wp_ajax_arm_add_note', 'handle_add_note'],
            ['wp_ajax_arm_get_repair_details', 'get_repair_details'],
            ['wp_ajax_nopriv_arm_get_repair_details', 'get_repair_details'],
            ['wp_ajax_arm_get_appliance_history', 'get_appliance_history']
        ];

        foreach ($hooks as $hook) {
            $this->hook_manager->addAction($hook[0], $this, $hook[1]);
        }
    }

    public function render_repairs_page() {
        if (!current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/repairs.php';
    }

    // ... resto de los métodos permanecen igual ...
}