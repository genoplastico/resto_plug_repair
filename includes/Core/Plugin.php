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

    private function __construct() {
        $this->debug = Debug\ErrorLogger::getInstance();
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
        $this->repair_handler = new \ApplianceRepairManager\Core\Handlers\RepairHandler();
        $this->user_manager = new \ApplianceRepairManager\Admin\UserManager();
        $this->settings_manager = new \ApplianceRepairManager\Admin\SettingsManager();
        $this->email_manager = new EmailManager();
        $this->assets = new Assets();
        $this->system_check = new \ApplianceRepairManager\Admin\SystemCheck();
        $this->notes_handler = new Ajax\NotesHandler();
        $this->ajax_handler = new Ajax\ApplianceHandler();
    }

    // ... rest of the Plugin class implementation remains the same ...
}