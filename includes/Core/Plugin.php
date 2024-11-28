<?php
namespace ApplianceRepairManager\Core;

class Plugin {
    private static $instance = null;
    private $client_manager;
    private $appliance_manager;
    private $repair_manager;
    private $user_manager;
    private $settings_manager;
    private $email_manager;
    private $assets;
    private $system_check;
    private $debug;

    private function __construct() {
        $this->debug = Debug::getInstance();
        $this->init_managers();
        $this->init_hooks();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_managers() {
        // Initialize managers needed for both admin and public
        $this->repair_manager = new \ApplianceRepairManager\Admin\RepairManager();
        $this->assets = new Assets();
        
        // Initialize admin-only managers
        if (is_admin()) {
            $this->client_manager = new \ApplianceRepairManager\Admin\ClientManager();
            $this->appliance_manager = new \ApplianceRepairManager\Admin\ApplianceManager();
            $this->user_manager = new \ApplianceRepairManager\Admin\UserManager();
            $this->settings_manager = new \ApplianceRepairManager\Admin\SettingsManager();
            $this->email_manager = new EmailManager();
            $this->system_check = new \ApplianceRepairManager\Admin\SystemCheck();
        }
    }

    private function init_hooks() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_filter('map_meta_cap', [$this, 'map_arm_capabilities'], 10, 4);
        add_action('template_redirect', [$this, 'handle_public_views']);
        add_filter('template_include', [$this, 'load_plugin_template']);
        add_action('wp_enqueue_scripts', [$this->assets, 'enqueue_public_assets']);
        add_action('wp_footer', [$this->debug, 'printDebugInfo']);
        add_action('admin_footer', [$this->debug, 'printDebugInfo']);
    }

    public function init() {
        load_plugin_textdomain('appliance-repair-manager', false, dirname(plugin_basename(ARM_PLUGIN_FILE)) . '/languages');
    }

    public function handle_public_views() {
        if (isset($_GET['arm_action'])) {
            $action = sanitize_text_field($_GET['arm_action']);
            
            $this->debug->log('Public view requested', [
                'action' => $action,
                'request_uri' => $_SERVER['REQUEST_URI'],
                'query_string' => $_SERVER['QUERY_STRING']
            ]);
            
            switch ($action) {
                case 'view_client_appliances':
                case 'view_appliance':
                    // Let template_include handle the template loading
                    status_header(200);
                    return;
                default:
                    $this->debug->log('Invalid public view action', ['action' => $action], 'error');
                    return;
            }
        }
    }

    public function load_plugin_template($template) {
        if (isset($_GET['arm_action'])) {
            $action = sanitize_text_field($_GET['arm_action']);
            
            $this->debug->log('Template loading', [
                'action' => $action,
                'current_template' => $template
            ]);
            
            switch ($action) {
                case 'view_client_appliances':
                    $new_template = ARM_PLUGIN_DIR . 'templates/public/client-appliances.php';
                    break;
                case 'view_appliance':
                    $new_template = ARM_PLUGIN_DIR . 'templates/public/appliance-view.php';
                    break;
                default:
                    $this->debug->log('No matching template for action', ['action' => $action], 'warning');
                    return $template;
            }

            if (file_exists($new_template)) {
                $this->debug->log('Loading plugin template', [
                    'template' => $new_template,
                    'exists' => true
                ]);
                return $new_template;
            } else {
                $this->debug->log('Template file not found', [
                    'template' => $new_template
                ], 'error');
            }
        }

        return $template;
    }

    // ... rest of the class methods remain the same ...
}