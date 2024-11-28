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

    private function __construct() {
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
    }

    public function init() {
        load_plugin_textdomain('appliance-repair-manager', false, dirname(plugin_basename(ARM_PLUGIN_FILE)) . '/languages');
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

        // Add submenu pages in specific order
        add_submenu_page(
            'appliance-repair-manager',
            __('Dashboard', 'appliance-repair-manager'),
            __('Dashboard', 'appliance-repair-manager'),
            'manage_options',
            'appliance-repair-manager',
            [$this, 'render_dashboard']
        );

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
            $action = sanitize_text_field($_GET['arm_action']);
            
            switch ($action) {
                case 'view_client_appliances':
                case 'view_appliance':
                    // Let template_include handle the template loading
                    status_header(200);
                    return;
                default:
                    return;
            }
        }
    }

    public function load_plugin_template($template) {
        if (isset($_GET['arm_action'])) {
            $action = sanitize_text_field($_GET['arm_action']);
            
            switch ($action) {
                case 'view_client_appliances':
                    $new_template = ARM_PLUGIN_DIR . 'templates/public/client-appliances.php';
                    break;
                case 'view_appliance':
                    $new_template = ARM_PLUGIN_DIR . 'templates/public/appliance-view.php';
                    break;
                default:
                    return $template;
            }

            if (file_exists($new_template)) {
                return $new_template;
            }
        }

        return $template;
    }

    public static function get_repair_statuses() {
        return [
            'pending' => __('Pendiente de Revisión', 'appliance-repair-manager'),
            'in_progress' => __('En Reparación', 'appliance-repair-manager'),
            'completed' => __('Reparado', 'appliance-repair-manager'),
            'delivered' => __('Entregado', 'appliance-repair-manager'),
        ];
    }
}