<?php
namespace ApplianceRepairManager\Core\Modal\Utils;

class ErrorHandler {
    private static $instance = null;
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

    public function handleError($error, $context = []) {
        $this->logger->logError('Modal Error', [
            'error' => $error instanceof \Exception ? $error->getMessage() : $error,
            'context' => $context
        ]);

        if (wp_doing_ajax()) {
            wp_send_json_error([
                'message' => __('Error processing modal request', 'appliance-repair-manager'),
                'details' => WP_DEBUG ? ($error instanceof \Exception ? $error->getMessage() : $error) : null
            ]);
        }

        return false;
    }

    public function handleValidationError($message, $data = []) {
        $this->logger->logError('Modal Validation Error', [
            'message' => $message,
            'data' => $data
        ]);

        if (wp_doing_ajax()) {
            wp_send_json_error([
                'message' => $message,
                'data' => $data
            ]);
        }

        return false;
    }

    public function handleAjaxError($action, $error, $context = []) {
        $this->logger->logAjaxError($action, $error instanceof \Exception ? $error->getMessage() : $error, $context);

        wp_send_json_error([
            'message' => __('Error processing modal action', 'appliance-repair-manager'),
            'details' => WP_DEBUG ? ($error instanceof \Exception ? $error->getMessage() : $error) : null
        ]);
    }
}