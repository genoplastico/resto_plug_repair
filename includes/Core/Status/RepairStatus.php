<?php
namespace ApplianceRepairManager\Core\Status;

class RepairStatus {
    private static $instance = null;
    private $statuses = [];

    private function __construct() {
        // Initialize statuses immediately since we're after init hook
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
            'pending' => __('Pendiente de Revisión', 'appliance-repair-manager'),
            'in_progress' => __('En Reparación', 'appliance-repair-manager'),
            'completed' => __('Reparado', 'appliance-repair-manager'),
            'delivered' => __('Entregado', 'appliance-repair-manager'),
        ];
    }

    public function getStatuses() {
        return $this->statuses;
    }

    public function getLabel($status) {
        return isset($this->statuses[$status]) ? $this->statuses[$status] : ucfirst($status);
    }

    public function getClass($status) {
        return 'arm-status-' . sanitize_html_class($status);
    }

    public function isValidStatus($status) {
        return array_key_exists($status, $this->statuses);
    }
}