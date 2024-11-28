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
        add_filter('map_meta_cap', [$this, 'map_meta_cap'], 10, 4);
        add_action('template_redirect', [$this, 'handle_public_views']);
        add_filter('template_include', [$this, 'load_plugin_template'], 999);
        add_action('wp_enqueue_scripts', [$this->assets, 'enqueue_public_assets']);
        add_action('wp_footer', [$this->debug, 'printDebugInfo']);
        add_action('admin_footer', [$this->debug, 'printDebugInfo']);
        
        // Add rewrite rules for public views
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
    }

    public function init() {
        load_plugin_textdomain('appliance-repair-manager', false, dirname(plugin_basename(ARM_PLUGIN_FILE)) . '/languages');
    }

    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            __('Repair Manager', 'appliance-repair-manager'),
            __('Repair Manager', 'appliance-repair-manager'),
            'manage_options',
            'appliance-repair-manager',
            [$this, 'render_dashboard'],
            'dashicons-admin-tools',
            30
        );

        // Submenús
        add_submenu_page(
            'appliance-repair-manager',
            __('Dashboard', 'appliance-repair-manager'),
            __('Dashboard', 'appliance-repair-manager'),
            'manage_options',
            'appliance-repair-manager',
            [$this, 'render_dashboard']
        );

        // Remover el menú duplicado de reparaciones
        remove_menu_page('arm-repairs');

        // Agregar los submenús correctamente
        if ($this->client_manager) {
            add_submenu_page(
                'appliance-repair-manager',
                __('Clients', 'appliance-repair-manager'),
                __('Clients', 'appliance-repair-manager'),
                'manage_options',
                'arm-clients',
                [$this->client_manager, 'render_clients_page']
            );
        }

        if ($this->appliance_manager) {
            add_submenu_page(
                'appliance-repair-manager',
                __('Appliances', 'appliance-repair-manager'),
                __('Appliances', 'appliance-repair-manager'),
                'manage_options',
                'arm-appliances',
                [$this->appliance_manager, 'render_appliances_page']
            );
        }

        if ($this->repair_manager) {
            add_submenu_page(
                'appliance-repair-manager',
                __('Repairs', 'appliance-repair-manager'),
                __('Repairs', 'appliance-repair-manager'),
                'manage_options',
                'arm-repairs',
                [$this->repair_manager, 'render_repairs_page']
            );
        }

        if ($this->user_manager) {
            add_submenu_page(
                'appliance-repair-manager',
                __('Technicians', 'appliance-repair-manager'),
                __('Technicians', 'appliance-repair-manager'),
                'manage_options',
                'arm-technicians',
                [$this->user_manager, 'render_technicians_page']
            );
        }

        if ($this->settings_manager) {
            add_submenu_page(
                'appliance-repair-manager',
                __('Settings', 'appliance-repair-manager'),
                __('Settings', 'appliance-repair-manager'),
                'manage_options',
                'arm-settings',
                [$this->settings_manager, 'render_settings_page']
            );
        }
    }

    // ... resto del código permanece igual ...
}