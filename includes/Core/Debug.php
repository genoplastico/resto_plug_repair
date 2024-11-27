<?php
namespace ApplianceRepairManager\Core;

class Debug {
    private static $instance = null;
    private $logs = [];

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($message, $context = []) {
        if (WP_DEBUG) {
            $log = [
                'timestamp' => current_time('mysql'),
                'message' => $message,
                'context' => $context
            ];
            
            $this->logs[] = $log;
            error_log(sprintf(
                '[ARM Debug] %s - %s - %s',
                $log['timestamp'],
                $log['message'],
                json_encode($log['context'])
            ));
        }
    }

    public function getAssetUrl($file) {
        $url = plugins_url($file, ARM_PLUGIN_FILE);
        $this->log('Asset URL generated', [
            'file' => $file,
            'url' => $url,
            'plugin_file' => ARM_PLUGIN_FILE,
            'plugin_url' => plugins_url('', ARM_PLUGIN_FILE)
        ]);
        return $url;
    }

    public function verifyAsset($url) {
        $response = wp_remote_head($url);
        $status = wp_remote_retrieve_response_code($response);
        
        $this->log('Asset verification', [
            'url' => $url,
            'status' => $status,
            'exists' => $status === 200
        ]);
        
        return $status === 200;
    }

    public function checkModalStructure() {
        global $wp_scripts, $wp_styles;
        
        $this->log('Modal dependencies check', [
            'jquery_loaded' => isset($wp_scripts->registered['jquery']),
            'modal_css_loaded' => isset($wp_styles->registered['arm-modal-styles']),
            'modal_js_loaded' => isset($wp_scripts->registered['arm-modal-manager'])
        ]);
    }

    public function getDebugInfo() {
        return [
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => ARM_VERSION,
            'debug_mode' => WP_DEBUG,
            'plugin_path' => ARM_PLUGIN_DIR,
            'plugin_url' => plugins_url('', ARM_PLUGIN_FILE),
            'assets_check' => [
                'modal_css' => $this->verifyAsset($this->getAssetUrl('assets/css/modal-manager.css')),
                'modal_js' => $this->verifyAsset($this->getAssetUrl('assets/js/modal-manager.js')),
                'admin_css' => $this->verifyAsset($this->getAssetUrl('assets/css/admin.css')),
                'admin_js' => $this->verifyAsset($this->getAssetUrl('assets/js/admin.js'))
            ]
        ];
    }
}