<?php
namespace ApplianceRepairManager\Core\Status;

class RepairStatus {
    private static $instance = null;
    private $statuses = [];

    private function __construct() {
        add_action('init', [$this, 'initializeStatuses']);
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function initializeStatuses() {
        $this->statuses = [
            'pending' => __('Pending Review', 'appliance-repair-manager'),
            'in_progress' => __('In Repair', 'appliance-repair-manager'),
            'completed' => __('Repaired', 'appliance-repair-manager'),
            'delivered' => __('Delivered', 'appliance-repair-manager'),
        ];
    }

    public function getStatuses() {
        if (empty($this->statuses)) {
            $this->initializeStatuses();
        }
        return $this->statuses;
    }

    public function getLabel($status) {
        $statuses = $this->getStatuses();
        return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
    }

    public function getClass($status) {
        return 'arm-status-' . sanitize_html_class($status);
    }

    public function isValidStatus($status) {
        return array_key_exists($status, $this->getStatuses());
    }
}