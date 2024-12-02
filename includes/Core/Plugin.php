<?php
namespace ApplianceRepairManager\Core;

class Plugin {
    private static $instance = null;
    private $client_manager;
    private $appliance_manager;
    private $repair_manager;
    private $repair_handler;
    private $user_manager;
    private $settings_manager;
    private $email_manager;
    private $assets;
    private $system_check;
    private $debug;
    private $hook_manager;
    private $notes_handler;
    private $ajax_handler;
    private $translation_debugger;
    private $appliance_images;
    private $appliance_images;

    private function __construct() {
        $this->debug = Debug\ErrorLogger::getInstance();
        $this->hook_manager = HookManager::getInstance();
        $this->translation_debugger = Debug\TranslationDebugger::getInstance();
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
        $this->repair_handler = new \ApplianceRepairManager\Core\Handlers\RepairHandler();
        $this->user_manager = new \ApplianceRepairManager\Admin\UserManager();
        $this->settings_manager = new \ApplianceRepairManager\Admin\SettingsManager();
        $this->email_manager = new EmailManager();
        $this->assets = new Assets();
        $this->system_check = new \ApplianceRepairManager\Admin\SystemCheck();
        $this->notes_handler = new Ajax\NotesHandler();
        $this->ajax_handler = new Ajax\ApplianceHandler();
        $this->appliance_images = ApplianceImages::getInstance();
        $this->appliance_images = ApplianceImages::getInstance();
    }

    private function register_hooks() {
        // Core WordPress hooks
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_filter('map_meta_cap', [$this, 'map_meta_cap'], 10, 4);
        
        // Template and rewrite hooks
        add_action('template_redirect', [$this, 'handle_public_views']);
        add_filter('template_include', [$this, 'load_plugin_template'], 999);
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);

        // AJAX handlers
        add_action('wp_ajax_arm_get_client_appliances', [$this->repair_manager, 'getClientAppliances']);
        add_action('wp_ajax_arm_get_repair_details', [$this->repair_manager, 'getRepairDetails']);
        add_action('wp_ajax_nopriv_arm_get_repair_details', [$this->ajax_handler, 'getRepairDetails']);
    }

    public function init() {
        do_action('arm_init');
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_footer', [$this->translation_debugger, 'print_debug_info']);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Panel de Control', 'appliance-repair-manager'),
            __('Panel de Control', 'appliance-repair-manager'),
            'manage_options',
            'appliance-repair-manager',
            [$this, 'render_dashboard'],
            'dashicons-admin-tools',
            30
        );

        add_submenu_page(
            'appliance-repair-manager',
            __('Panel de Control', 'appliance-repair-manager'),
            __('Panel de Control', 'appliance-repair-manager'),
            'manage_options',
            'appliance-repair-manager'
        );

        add_submenu_page(
            'appliance-repair-manager',
            __('Reparaciones', 'appliance-repair-manager'),
            __('Reparaciones', 'appliance-repair-manager'),
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

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}