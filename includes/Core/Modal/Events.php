<?php
namespace ApplianceRepairManager\Core\Modal;

class Events {
    private static $instance = null;
    private $listeners = [];
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

    public function registerHandlers() {
        add_action('wp_ajax_arm_modal_action', [$this, 'handleAjaxAction']);
        add_action('wp_ajax_nopriv_arm_modal_action', [$this, 'handleAjaxAction']);
    }

    public function on($event, $callback) {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $callback;
    }

    public function trigger($event, $data = null) {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $callback) {
            try {
                call_user_func($callback, $data);
            } catch (\Exception $e) {
                $this->logger->logError('Error triggering modal event', [
                    'event' => $event,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function handleAjaxAction() {
        check_ajax_referer('arm_modal_nonce', 'nonce');

        $action = isset($_POST['modal_action']) ? sanitize_text_field($_POST['modal_action']) : '';
        $modalId = isset($_POST['modal_id']) ? sanitize_text_field($_POST['modal_id']) : '';

        try {
            switch ($action) {
                case 'open':
                    $result = Manager::getInstance()->openModal($modalId, $_POST);
                    break;
                case 'close':
                    $result = Manager::getInstance()->closeModal($modalId);
                    break;
                default:
                    throw new \Exception("Invalid modal action: {$action}");
            }

            wp_send_json_success(['result' => $result]);

        } catch (\Exception $e) {
            $this->logger->logError('Modal AJAX error', [
                'action' => $action,
                'modal_id' => $modalId,
                'error' => $e->getMessage()
            ]);

            wp_send_json_error([
                'message' => __('Error processing modal action', 'appliance-repair-manager')
            ]);
        }
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}