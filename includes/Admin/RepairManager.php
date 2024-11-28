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
}