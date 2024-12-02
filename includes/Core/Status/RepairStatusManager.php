<?php
namespace ApplianceRepairManager\Core\Status;

class RepairStatusManager {
    private static $instance = null;
    private $statuses;

    private function __construct() {
        $this->initializeStatuses();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeStatuses() {
        $this->statuses = [
            'pending' => _x('Pending Review', 'repair status', 'appliance-repair-manager'),
            'in_progress' => _x('In Repair', 'repair status', 'appliance-repair-manager'),
            'completed' => _x('Repaired', 'repair status', 'appliance-repair-manager'),
            'delivered' => _x('Delivered', 'repair status', 'appliance-repair-manager'),
        ];
    }

    public function getStatuses() {
        return $this->statuses;
    }

    public function getLabel($status) {
        return isset($this->statuses[$status]) ? $this->statuses[$status] : $status;
    }

    public function isValidStatus($status) {
        return array_key_exists($status, $this->statuses);
    }
}