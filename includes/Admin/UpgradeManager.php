<?php
namespace ApplianceRepairManager\Admin;

class UpgradeManager {
    private $upgrade;
    private $logger;
    private $menu_added = false;

    public function __construct() {
        $this->upgrade = new \ApplianceRepairManager\Core\Modal\Upgrade();
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
        
        // Log initialization with backtrace
        $this->logger->log('UpgradeManager initialization started', [
            'class' => __CLASS__,
            'time' => current_time('mysql'),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        
        // Add menu with normal and late priority to ensure it's added
        add_action('admin_menu', [$this, 'addUpgradeMenu']);
        add_action('admin_menu', [$this, 'addUpgradeMenu'], 99);
        add_action('admin_notices', [$this, 'debugNotice']);
        add_action('admin_post_arm_upgrade_modals', [$this, 'handleUpgrade']);

        $this->logger->log('UpgradeManager initialization completed');
    }

    public function debugNotice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $menu, $submenu;
        
        // Log menu structure
        $this->logger->log('Admin menu structure', [
            'menu' => $menu,
            'submenu' => $submenu,
            'menu_added' => $this->menu_added
        ]);

        // Only show debug notice if WP_DEBUG is true
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $parent_slug = 'appliance-repair-manager';
        $parent_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === $parent_slug) {
                $parent_exists = true;
                break;
            }
        }

        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>ARM Debug Info:</strong></p>';
        echo '<ul>';
        echo '<li>Parent menu exists: ' . ($parent_exists ? 'Yes' : 'No') . '</li>';
        echo '<li>Menu added: ' . ($this->menu_added ? 'Yes' : 'No') . '</li>';
        echo '<li>Current user can manage_options: ' . (current_user_can('manage_options') ? 'Yes' : 'No') . '</li>';
        echo '</ul>';
        echo '</div>';
    }

    public function addUpgradeMenu() {
        // Log attempt to add menu
        $this->logger->log('Attempting to add upgrade menu', [
            'current_filter' => current_filter(),
            'priority' => has_filter(current_filter(), [$this, 'addUpgradeMenu']),
            'user_id' => get_current_user_id(),
            'user_caps' => wp_get_current_user()->allcaps
        ]);

        if ($this->menu_added) {
            $this->logger->log('Upgrade menu already added, skipping');
            return;
        }

        global $menu;
        $parent_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'appliance-repair-manager') {
                $parent_exists = true;
                break;
            }
        }

        $this->logger->log('Parent menu check', [
            'exists' => $parent_exists,
            'menu' => $menu
        ]);

        if (!$parent_exists) {
            $this->logger->log('Parent menu does not exist, cannot add upgrade submenu');
            return;
        }

        // Add submenu page
        $page = add_submenu_page(
            'appliance-repair-manager',
            __('System Upgrade', 'appliance-repair-manager'),
            __('System Upgrade', 'appliance-repair-manager'),
            'manage_options',
            'arm-upgrade',
            [$this, 'renderUpgradePage']
        );

        if ($page) {
            $this->menu_added = true;
            $this->logger->log('Upgrade menu added successfully', [
                'page' => $page,
                'hook_suffix' => $page
            ]);
        } else {
            $this->logger->log('Failed to add upgrade menu', [
                'last_error' => error_get_last()
            ]);
        }
    }

    public function renderUpgradePage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $this->logger->log('Rendering upgrade page', [
            'user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI']
        ]);

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
                            'php_version' => PHP_VERSION,
                            'menu_added' => $this->menu_added,
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
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        check_admin_referer('arm_upgrade_modals');

        try {
            $this->logger->log('Starting modal upgrade process', [
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ]);

            $result = $this->upgrade->execute();

            $this->logger->log('Upgrade process completed', [
                'success' => $result,
                'timestamp' => current_time('mysql')
            ]);

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
                'trace' => $e->getTraceAsString(),
                'timestamp' => current_time('mysql')
            ]);

            wp_redirect(add_query_arg([
                'page' => 'arm-upgrade',
                'message' => 'error'
            ], admin_url('admin.php')));
        }

        exit;
    }
}