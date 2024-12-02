<?php
namespace ApplianceRepairManager\Admin;

class UpgradeManager {
    private $upgrade;
    private $logger;

    public function __construct() {
        $this->upgrade = new \ApplianceRepairManager\Core\Modal\Upgrade();
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
        
        // Add debug log for initialization
        $this->logger->log('UpgradeManager initialized');
        
        add_action('admin_menu', [$this, 'addUpgradeMenu'], 99); // Higher priority to ensure main menu exists
        add_action('admin_post_arm_upgrade_modals', [$this, 'handleUpgrade']);
    }

    public function addUpgradeMenu() {
        // Debug log before adding menu
        $this->logger->log('Adding upgrade menu');
        
        // Check if parent menu exists
        global $menu;
        $parent_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'appliance-repair-manager') {
                $parent_exists = true;
                break;
            }
        }
        
        // Log parent menu status
        $this->logger->log('Parent menu status', ['exists' => $parent_exists]);

        // Add submenu page
        $page = add_submenu_page(
            'appliance-repair-manager', // Parent slug
            __('System Upgrade', 'appliance-repair-manager'), // Page title
            __('System Upgrade', 'appliance-repair-manager'), // Menu title
            'manage_options', // Capability
            'arm-upgrade', // Menu slug
            [$this, 'renderUpgradePage'] // Callback
        );

        // Log result
        $this->logger->log('Upgrade menu added', ['page' => $page]);
    }

    public function renderUpgradePage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Log page render
        $this->logger->log('Rendering upgrade page');

        // Get message parameter
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
        ?>
        <div class="wrap">
            <h1><?php _e('System Upgrade', 'appliance-repair-manager'); ?></h1>

            <?php if ($message === 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Modal system upgraded successfully!', 'appliance-repair-manager'); ?></p>
                </div>
            <?php elseif ($message === 'error'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e('Error upgrading modal system. Please check the error logs.', 'appliance-repair-manager'); ?></p>
                </div>
            <?php endif; ?>

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

            <?php if (WP_DEBUG): ?>
                <div class="card">
                    <h3><?php _e('Debug Information', 'appliance-repair-manager'); ?></h3>
                    <pre><?php 
                        $debug_info = [
                            'parent_menu' => 'appliance-repair-manager',
                            'capability' => 'manage_options',
                            'user_can_access' => current_user_can('manage_options'),
                            'wp_debug' => WP_DEBUG,
                            'php_version' => PHP_VERSION
                        ];
                        echo esc_html(print_r($debug_info, true)); 
                    ?></pre>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handleUpgrade() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        check_admin_referer('arm_upgrade_modals');

        try {
            // Log upgrade start
            $this->logger->log('Starting modal upgrade');

            $result = $this->upgrade->execute();

            // Log upgrade result
            $this->logger->log('Upgrade completed', ['success' => $result]);

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            wp_redirect(add_query_arg([
                'page' => 'arm-upgrade',
                'message' => 'error'
            ], admin_url('admin.php')));
        }

        exit;
    }
}