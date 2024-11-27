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
    private $debug_manager;
    private $assets;
    private $debug;

    private function __construct() {
        $this->debug = Debug::get_instance();
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
        // Initialize managers
        if (is_admin()) {
            $this->client_manager = new \ApplianceRepairManager\Admin\ClientManager();
            $this->appliance_manager = new \ApplianceRepairManager\Admin\ApplianceManager();
            $this->repair_manager = new \ApplianceRepairManager\Admin\RepairManager();
            $this->user_manager = new \ApplianceRepairManager\Admin\UserManager();
            $this->settings_manager = new \ApplianceRepairManager\Admin\SettingsManager();
            $this->debug_manager = new \ApplianceRepairManager\Admin\DebugManager();
            $this->email_manager = new EmailManager();
            $this->assets = new Assets();
        }
    }

    private function init_hooks() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_filter('map_meta_cap', [$this, 'map_arm_capabilities'], 10, 4);
        add_action('template_redirect', [$this, 'handle_public_views']);
        add_action('wp_ajax_arm_debug_log', [$this, 'handle_debug_log']);
        add_action('admin_post_arm_clear_debug_log', [$this, 'handle_clear_debug_log']);
    }

    public function init() {
        load_plugin_textdomain('appliance-repair-manager', false, dirname(plugin_basename(ARM_PLUGIN_FILE)) . '/languages');
    }

    public function handle_debug_log() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }

        $message = sanitize_text_field($_POST['message']);
        $context = isset($_POST['context']) ? $_POST['context'] : [];

        $this->debug->log($message, $context);
        wp_send_json_success();
    }

    public function handle_clear_debug_log() {
        check_admin_referer('arm_clear_debug_log');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        $this->debug->clear_logs();

        wp_redirect(add_query_arg([
            'page' => 'arm-debug',
            'message' => 'log_cleared'
        ], admin_url('admin.php')));
        exit;
    }

    public function add_admin_menu() {
        // Add main menu
        add_menu_page(
            __('Repair Manager', 'appliance-repair-manager'),
            __('Repair Manager', 'appliance-repair-manager'),
            'manage_options',
            'appliance-repair-manager',
            [$this, 'render_dashboard'],
            'dashicons-admin-tools',
            30
        );

        // Add submenu pages
        add_submenu_page(
            'appliance-repair-manager',
            __('Dashboard', 'appliance-repair-manager'),
            __('Dashboard', 'appliance-repair-manager'),
            'manage_options',
            'appliance-repair-manager',
            [$this, 'render_dashboard']
        );

        // Los submenús serán manejados por sus respectivos managers
        do_action('arm_admin_menu');
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options') && !current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function map_arm_capabilities($caps, $cap, $user_id, $args) {
        if ('edit_arm_repairs' === $cap) {
            $caps = ['edit_arm_repairs'];
        }
        
        if ('view_arm_repairs' === $cap) {
            $caps = ['view_arm_repairs'];
        }
        
        return $caps;
    }

    public function handle_public_views() {
        if (isset($_GET['arm_action'])) {
            switch ($_GET['arm_action']) {
                case 'view_client_appliances':
                    include ARM_PLUGIN_DIR . 'templates/public/client-appliances.php';
                    exit;
                    break;
            }
        }
    }

    public static function get_repair_statuses() {
        return get_option('arm_repair_statuses', [
            'pending' => __('Pending Review', 'appliance-repair-manager'),
            'in_progress' => __('In Repair', 'appliance-repair-manager'),
            'completed' => __('Repaired', 'appliance-repair-manager'),
            'delivered' => __('Delivered', 'appliance-repair-manager'),
        ]);
    }
}