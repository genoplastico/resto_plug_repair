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
    private $hook_manager;

    private function __construct() {
        $this->debug = Debug\Logger::getInstance();
        $this->hook_manager = HookManager::getInstance();
        $this->init_managers();
        $this->register_hooks();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_managers() {
        // Initialize all managers
        $this->client_manager = new \ApplianceRepairManager\Admin\ClientManager();
        $this->appliance_manager = new \ApplianceRepairManager\Admin\ApplianceManager();
        $this->repair_manager = new \ApplianceRepairManager\Admin\RepairManager();
        $this->user_manager = new \ApplianceRepairManager\Admin\UserManager();
        $this->settings_manager = new \ApplianceRepairManager\Admin\SettingsManager();
        $this->email_manager = new EmailManager();
        $this->assets = new Assets();
        $this->system_check = new \ApplianceRepairManager\Admin\SystemCheck();
    }

    private function register_hooks() {
        $hooks = [
            ['init', 'init'],
            ['admin_menu', 'add_admin_menu'],
            ['map_meta_cap', 'map_meta_cap', 10, 4],
            ['template_redirect', 'handle_public_views'],
            ['template_include', 'load_plugin_template', 999],
            ['init', 'add_rewrite_rules'],
            ['query_vars', 'add_query_vars']
        ];

        foreach ($hooks as $hook) {
            $priority = isset($hook[2]) ? $hook[2] : 10;
            $args = isset($hook[3]) ? $hook[3] : 1;
            $this->hook_manager->addFilter($hook[0], $this, $hook[1], $priority, $args);
        }
    }

    public function init() {
        // Initialize plugin functionality
        do_action('arm_init');
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Dashboard', 'appliance-repair-manager'),
            __('Dashboard', 'appliance-repair-manager'),
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
            'appliance-repair-manager'
        );

        add_submenu_page(
            'appliance-repair-manager',
            __('Repairs', 'appliance-repair-manager'),
            __('Repairs', 'appliance-repair-manager'),
            'edit_arm_repairs',
            'arm-repairs',
            [$this->repair_manager, 'render_repairs_page']
        );

        do_action('arm_admin_menu');
    }

    public function render_dashboard() {
        include ARM_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function map_meta_cap($caps, $cap, $user_id, $args) {
        if ('edit_arm_repairs' === $cap) {
            $caps = ['edit_arm_repairs'];
        }
        return $caps;
    }

    public function handle_public_views() {
        if (!isset($_GET['arm_action'])) {
            return;
        }

        $action = sanitize_text_field($_GET['arm_action']);
        switch ($action) {
            case 'view_appliance':
                include ARM_PLUGIN_DIR . 'templates/public/appliance-view.php';
                exit;
            case 'view_client_appliances':
                include ARM_PLUGIN_DIR . 'templates/public/client-appliances.php';
                exit;
        }
    }

    public function load_plugin_template($template) {
        if (isset($_GET['arm_action'])) {
            return ARM_PLUGIN_DIR . 'templates/public/blank.php';
        }
        return $template;
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            'repair/([^/]+)/?$',
            'index.php?arm_action=view_repair&repair_id=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'arm_action';
        $vars[] = 'repair_id';
        return $vars;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {}
}