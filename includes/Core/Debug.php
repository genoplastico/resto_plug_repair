<?php
namespace ApplianceRepairManager\Core;

class Debug {
    private static $instance = null;
    private $logs = [];
    private $template_debug = [];

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($message, $context = [], $type = 'info') {
        if (WP_DEBUG) {
            $log = [
                'timestamp' => current_time('mysql'),
                'type' => $type,
                'message' => $message,
                'context' => $context,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ];
            
            $this->logs[] = $log;
            
            error_log(sprintf(
                '[ARM Debug] [%s] %s - %s - %s',
                strtoupper($type),
                $log['timestamp'],
                $log['message'],
                json_encode($log['context'])
            ));
        }
    }

    public function logTemplate($template, $context = []) {
        $this->template_debug[] = [
            'timestamp' => current_time('mysql'),
            'template' => $template,
            'context' => $context,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];
        
        $this->log('Template loading attempt', [
            'template' => $template,
            'context' => $context
        ], 'template');
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
            'exists' => $status === 200,
            'response' => wp_remote_retrieve_response_message($response)
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
            'template_debug' => $this->template_debug,
            'assets_check' => [
                'modal_css' => $this->verifyAsset($this->getAssetUrl('assets/css/modal-manager.css')),
                'modal_js' => $this->verifyAsset($this->getAssetUrl('assets/js/modal-manager.js')),
                'admin_css' => $this->verifyAsset($this->getAssetUrl('assets/css/admin.css')),
                'admin_js' => $this->verifyAsset($this->getAssetUrl('assets/js/admin.js'))
            ],
            'current_user' => [
                'can_manage_options' => current_user_can('manage_options'),
                'can_edit_repairs' => current_user_can('edit_arm_repairs')
            ],
            'request' => [
                'query_vars' => $GLOBALS['wp_query']->query_vars,
                'request_uri' => $_SERVER['REQUEST_URI'],
                'arm_action' => isset($_GET['arm_action']) ? $_GET['arm_action'] : null
            ]
        ];
    }

    public function printDebugInfo() {
        if (WP_DEBUG && current_user_can('manage_options')) {
            echo '<div id="arm-debug-info" style="display:none;">';
            echo '<pre>' . esc_html(json_encode($this->getDebugInfo(), JSON_PRETTY_PRINT)) . '</pre>';
            echo '</div>';
            
            echo '<script>
                console.group("ARM Debug Information");
                console.log("Debug Info:", ' . json_encode($this->getDebugInfo()) . ');
                console.log("Template Debug:", ' . json_encode($this->template_debug) . ');
                console.groupEnd();
            </script>';
        }
    }
}