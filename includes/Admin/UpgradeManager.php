<?php
namespace ApplianceRepairManager\Admin;

class UpgradeManager {
    private $upgrade;
    private $logger;

    public function __construct() {
        $this->upgrade = new \ApplianceRepairManager\Core\Modal\Upgrade();
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
        
        // Cambiar a arm_admin_menu para usar el hook correcto
        add_action('arm_admin_menu', [$this, 'addUpgradeMenu']);
        add_action('admin_post_arm_upgrade_modals', [$this, 'handleUpgrade']);
    }

    public function addUpgradeMenu() {
        add_submenu_page(
            'appliance-repair-manager', // Cambiar a la página principal del plugin
            __('System Upgrade', 'appliance-repair-manager'),
            __('System Upgrade', 'appliance-repair-manager'),
            'manage_options',
            'arm-upgrade',
            [$this, 'renderUpgradePage']
        );
    }

    // El resto del código permanece igual...
}