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

    // ... resto de los métodos permanecen igual ...
}