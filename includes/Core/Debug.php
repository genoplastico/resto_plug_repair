<?php
namespace ApplianceRepairManager\Core;

class Debug {
    private static $instance = null;
    private $logs = [];
    private $is_enabled = false;

    private function __construct() {
        $this->is_enabled = defined('WP_DEBUG') && WP_DEBUG;
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($message, $context = []) {
        if (!$this->is_enabled) {
            return;
        }

        $log_entry = [
            'timestamp' => microtime(true),
            'message' => $message,
            'context' => $context,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]
        ];

        $this->logs[] = $log_entry;
        $this->write_to_file($log_entry);
    }

    private function write_to_file($log_entry) {
        $log_dir = WP_CONTENT_DIR . '/arm-logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $log_file = $log_dir . '/debug.log';
        $formatted_log = sprintf(
            "[%s] %s in %s:%s\nContext: %s\n\n",
            date('Y-m-d H:i:s', (int)$log_entry['timestamp']),
            $log_entry['message'],
            $log_entry['backtrace']['file'] ?? 'unknown',
            $log_entry['backtrace']['line'] ?? 'unknown',
            json_encode($log_entry['context'], JSON_PRETTY_PRINT)
        );

        file_put_contents($log_file, $formatted_log, FILE_APPEND);
    }

    public function get_logs() {
        return $this->logs;
    }

    public function clear_logs() {
        $this->logs = [];
        $log_file = WP_CONTENT_DIR . '/arm-logs/debug.log';
        if (file_exists($log_file)) {
            unlink($log_file);
        }
    }
}