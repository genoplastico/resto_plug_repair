<?php
namespace ApplianceRepairManager\Core\Modal;

class State {
    private static $instance = null;
    private $activeModals = [];
    private $logger;

    private function __construct() {
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addModal($modalId) {
        if (!in_array($modalId, $this->activeModals)) {
            $this->activeModals[] = $modalId;
            $this->logger->log('Modal added to state', [
                'modal_id' => $modalId,
                'active_count' => count($this->activeModals)
            ]);
        }
    }

    public function removeModal($modalId) {
        $key = array_search($modalId, $this->activeModals);
        if ($key !== false) {
            unset($this->activeModals[$key]);
            $this->activeModals = array_values($this->activeModals);
            $this->logger->log('Modal removed from state', [
                'modal_id' => $modalId,
                'active_count' => count($this->activeModals)
            ]);
        }
    }

    public function hasModal($modalId) {
        return in_array($modalId, $this->activeModals);
    }

    public function canOpenModal($modalId) {
        // Add any validation logic here
        return true;
    }

    public function getActiveModals() {
        return $this->activeModals;
    }

    public function getLastActiveModal() {
        return end($this->activeModals);
    }

    public function clearModals() {
        $this->activeModals = [];
        $this->logger->log('All modals cleared from state');
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}