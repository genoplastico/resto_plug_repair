<?php
namespace ApplianceRepairManager\Core\Debug;

class ErrorLogger {
    private static $instance = null;
    private $log_file;
    private $debug_mode;

    private function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
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
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Create or update .htaccess
        $htaccess = $log_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order Deny,Allow\nDeny from all");
        }
        
        // Create or update index.php
        $index = $log_dir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, '<?php // Silence is golden');
        }
        
        // Ensure log file exists and is writable
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
        }
        
        // Set proper permissions
        chmod($log_dir, 0755);
        chmod($this->log_file, 0644);
        chmod($htaccess, 0644);
        chmod($index, 0644);
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
            'class' => $backtrace[1]['class'] ?? 'unknown',
            'user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        $log_message = sprintf(
            "[%s] [%s] [User:%d] [%s] %s\nLocation: %s:%s\nFunction: %s::%s\nRequest: %s %s\nIP: %s\nContext: %s\n\n",
            $log_entry['timestamp'],
            $log_entry['severity'],
            $log_entry['user_id'],
            wp_generate_password(8, false),
            $log_entry['message'],
            $log_entry['file'],
            $log_entry['line'],
            $log_entry['class'],
            $log_entry['function'],
            $log_entry['request_method'],
            $log_entry['request_uri'],
            $log_entry['ip_address'],
            json_encode($context, JSON_PRETTY_PRINT)
        );

        if (!@error_log($log_message, 3, $this->log_file)) {
            error_log('Failed to write to ARM error log: ' . $this->log_file);
        }

        if ($severity === 'CRITICAL') {
            $this->notifyAdmin($log_entry);
        }
    }

    public function logAjaxError($action, $error, $context = []) {
        $context['ajax_action'] = $action;
        $context['request_data'] = $_POST;
        
        $this->logError(
            sprintf('AJAX Error: %s', $error),
            $context,
            'AJAX_ERROR'
        );
    }

    private function notifyAdmin($log_entry) {
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }

        $subject = sprintf(
            '[%s] Critical Error Detected',
            get_bloginfo('name')
        );

        $message = sprintf(
            "A critical error has occurred:\n\n" .
            "Time: %s\n" .
            "Message: %s\n" .
            "Location: %s:%s\n" .
            "User ID: %d\n" .
            "IP: %s\n\n" .
            "Please check the error logs for more details.",
            $log_entry['timestamp'],
            $log_entry['message'],
            $log_entry['file'],
            $log_entry['line'],
            $log_entry['user_id'],
            $log_entry['ip_address']
        );

        wp_mail($admin_email, $subject, $message);
    }

    public function getLogContents($lines = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $logs = @file($this->log_file);
        if (!$logs) {
            return [];
        }

        return array_slice($logs, -$lines);
    }

    public function clearLogs() {
        if (file_exists($this->log_file)) {
            @unlink($this->log_file);
        }
        $this->init_log_directory();
    }

    public function rotateLogs() {
        if (!file_exists($this->log_file)) {
            return;
        }

        $max_size = 5 * 1024 * 1024; // 5MB
        if (filesize($this->log_file) < $max_size) {
            return;
        }

        $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        @rename($this->log_file, $backup_file);
        
        // Keep only last 5 backup files
        $backup_files = glob($this->log_file . '.*.bak');
        if ($backup_files && count($backup_files) > 5) {
            array_map('unlink', array_slice($backup_files, 0, -5));
        }
    }
}