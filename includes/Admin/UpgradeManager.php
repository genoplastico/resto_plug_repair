<?php
namespace ApplianceRepairManager\Admin;

class UpgradeManager {
    private $upgrade;
    private $logger;

    public function __construct() {
        $this->upgrade = new \ApplianceRepairManager\Core\Modal\Upgrade();
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
        
        add_action('admin_menu', [$this, 'addUpgradeMenu']);
        add_action('admin_post_arm_upgrade_modals', [$this, 'handleUpgrade']);
    }

    public function addUpgradeMenu() {
        add_submenu_page(
            'appliance-repair-manager',
            __('System Upgrade', 'appliance-repair-manager'),
            __('System Upgrade', 'appliance-repair-manager'),
            'manage_options',
            'arm-upgrade',
            [$this, 'renderUpgradePage']
        );
    }

    public function renderUpgradePage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('System Upgrade', 'appliance-repair-manager'); ?></h1>

            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('Important:', 'appliance-repair-manager'); ?></strong>
                    <?php _e('This will upgrade the modal system to the new version. Please backup your database before proceeding.', 'appliance-repair-manager'); ?>
                </p>
            </div>

            <div class="card">
                <h2><?php _e('Modal System Upgrade', 'appliance-repair-manager'); ?></h2>
                <p><?php _e('Click the button below to upgrade the modal system to the latest version.', 'appliance-repair-manager'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('arm_upgrade_modals'); ?>
                    <input type="hidden" name="action" value="arm_upgrade_modals">
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Start Upgrade', 'appliance-repair-manager'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function handleUpgrade() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        check_admin_referer('arm_upgrade_modals');

        try {
            $result = $this->upgrade->execute();

            if ($result) {
                wp_redirect(add_query_arg([
                    'page' => 'arm-upgrade',
                    'message' => 'success'
                ], admin_url('admin.php')));
            } else {
                wp_redirect(add_query_arg([
                    'page' => 'arm-upgrade',
                    'message' => 'error'
                ], admin_url('admin.php')));
            }
        } catch (\Exception $e) {
            $this->logger->logError('Error during modal upgrade', [
                'error' => $e->getMessage()
            ]);

            wp_redirect(add_query_arg([
                'page' => 'arm-upgrade',
                'message' => 'error'
            ], admin_url('admin.php')));
        }

        exit;
    }
}