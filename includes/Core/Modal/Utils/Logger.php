<?php
namespace ApplianceRepairManager\Core\Modal\Utils;

class Logger {
    private static $instance = null;
    private $enabled;
    private $logs = [];

    private function __construct() {
        $this->enabled = defined('WP_DEBUG') && WP_DEBUG;
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($message, $data = [], $type = 'info') {
        if (!$this->enabled) {
            return;
        }

        $log = [
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];

        $this->logs[] = $log;

        error_log(sprintf(
            '[ARM Modal] [%s] %s - %s',
            strtoupper($type),
            $message,
            json_encode($data)
        ));
    }

    public function getLogs() {
        return $this->logs;
    }

    public function clearLogs() {
        $this->logs = [];
    }
}