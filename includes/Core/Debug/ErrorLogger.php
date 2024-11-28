<?php
namespace ApplianceRepairManager\Core\Debug;

class ErrorLogger {
    private static $instance = null;
    private $log_file;
    private $debug_mode;

    private function __construct() {
        $this->debug_mode = WP_DEBUG;
        $this->log_file = ARM_PLUGIN_DIR . 'logs/error.log';
        $this->init_log_directory();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_log_directory() {
        $log_dir = ARM_PLUGIN_DIR . 'logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            file_put_contents($log_dir . '/.htaccess', 'Deny from all');
        }
    }

    public function logError($message, $context = [], $severity = 'ERROR') {
        if (!$this->debug_mode) {
            return;
        }

        $timestamp = current_time('mysql');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        $log_entry = [
            'timestamp' => $timestamp,
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
            'file' => $backtrace[0]['file'] ?? 'unknown',
            'line' => $backtrace[0]['line'] ?? 'unknown',
            'function' => $backtrace[1]['function'] ?? 'unknown',
            'class' => $backtrace[1]['class'] ?? 'unknown'
        ];

        $log_message = sprintf(
            "[%s] [%s] %s - %s:%s - %s::%s\nContext: %s\n\n",
            $log_entry['timestamp'],
            $log_entry['severity'],
            $log_entry['message'],
            $log_entry['file'],
            $log_entry['line'],
            $log_entry['class'],
            $log_entry['function'],
            json_encode($context, JSON_PRETTY_PRINT)
        );

        error_log($log_message, 3, $this->log_file);
    }

    public function logAjaxError($action, $error, $context = []) {
        $this->logError(
            sprintf('AJAX Error in action: %s', $action),
            array_merge(['error' => $error], $context),
            'AJAX_ERROR'
        );
    }

    public function getLogContents($lines = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $logs = file($this->log_file);
        return array_slice($logs, -$lines);
    }

    public function clearLogs() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }
}