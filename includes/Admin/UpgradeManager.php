<?php
namespace ApplianceRepairManager\Admin;

class UpgradeManager {
    private $logger;
    private $menu_slug = 'arm-upgrade';
    private $capability = 'manage_arm_system';  // Use plugin-specific capability
    private $parent_slug = 'appliance-repair-manager';

    public function __construct() {
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
        
        // Add menu with normal priority
        add_action('admin_menu', [$this, 'addUpgradeMenu'], 20);
        
        // Handle form submission
        add_action('admin_post_arm_upgrade_modals', [$this, 'handleUpgrade']);
        
        // Add settings link in plugins page
        add_filter('plugin_action_links_' . plugin_basename(ARM_PLUGIN_FILE), [$this, 'addPluginLinks']);
    }

    public function addUpgradeMenu() {
        add_submenu_page(
            $this->parent_slug,
            __('Modal System Upgrade', 'appliance-repair-manager'),
            __('Modal System Upgrade', 'appliance-repair-manager'),
            $this->capability,
            $this->menu_slug,
            [$this, 'renderUpgradePage']
        );
    }

    public function addPluginLinks($links) {
        if (current_user_can($this->capability)) {
            $upgrade_link = sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=' . $this->menu_slug),
                __('Upgrade System', 'appliance-repair-manager')
            );
            array_unshift($links, $upgrade_link);
        }
        return $links;
    }

    public function renderUpgradePage() {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'appliance-repair-manager'));
        }

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

            <div class="card">
                <h2><?php _e('Modal System Upgrade', 'appliance-repair-manager'); ?></h2>
                <p><?php _e('This will upgrade the modal system to the latest version. Please backup your database before proceeding.', 'appliance-repair-manager'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="arm-form">
                    <?php wp_nonce_field('arm_upgrade_modals'); ?>
                    <input type="hidden" name="action" value="arm_upgrade_modals">
                    
                    <div class="arm-form-footer">
                        <button type="submit" class="button button-primary">
                            <?php _e('Start Upgrade', 'appliance-repair-manager'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                <div class="card">
                    <h3><?php _e('Debug Information', 'appliance-repair-manager'); ?></h3>
                    <pre><?php 
                        $debug_info = [
                            'parent_menu' => $this->parent_slug,
                            'capability' => $this->capability,
                            'user_can_access' => current_user_can($this->capability),
                            'wp_debug' => WP_DEBUG,
                            'php_version' => PHP_VERSION,
                            'plugin_version' => ARM_VERSION
                        ];
                        echo esc_html(print_r($debug_info, true)); 
                    ?></pre>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handleUpgrade() {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager'));
        }

        check_admin_referer('arm_upgrade_modals');

        try {
            $this->logger->log('Starting modal upgrade process', [
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ]);

            $upgrade = new \ApplianceRepairManager\Core\Modal\Upgrade();
            $result = $upgrade->execute();

            $this->logger->log('Upgrade process completed', [
                'success' => $result,
                'timestamp' => current_time('mysql')
            ]);

            wp_redirect(add_query_arg([
                'page' => $this->menu_slug,
                'message' => $result ? 'success' : 'error'
            ], admin_url('admin.php')));
            exit;

        } catch (\Exception $e) {
            $this->logger->logError('Error during modal upgrade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            wp_redirect(add_query_arg([
                'page' => $this->menu_slug,
                'message' => 'error'
            ], admin_url('admin.php')));
            exit;
        }
    }
}