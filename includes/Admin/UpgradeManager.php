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
            'arm-settings',
            __('System Upgrade', 'appliance-repair-manager'),
            __('System Upgrade', 'appliance-repair-manager'),
            'manage_options',
            'arm-upgrade',
            [$this, 'renderUpgradePage']
        );
    }

    public function renderUpgradePage() {
        ?>
        <div class="wrap">
            <h1><?php _e('Modal System Upgrade', 'appliance-repair-manager'); ?></h1>
            
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('Important:', 'appliance-repair-manager'); ?></strong>
                    <?php _e('This will upgrade the modal system to the latest version. Please backup your database before proceeding.', 'appliance-repair-manager'); ?>
                </p>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="arm-upgrade-form">
                <?php wp_nonce_field('arm_upgrade_modals'); ?>
                <input type="hidden" name="action" value="arm_upgrade_modals">
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="arm-upgrade-button">
                        <?php _e('Start Upgrade', 'appliance-repair-manager'); ?>
                    </button>
                </p>
            </form>

            <div id="arm-upgrade-progress" style="display:none;">
                <div class="arm-progress-bar">
                    <div class="arm-progress-bar-fill"></div>
                </div>
                <p class="arm-progress-status"></p>
            </div>
        </div>

        <style>
            .arm-progress-bar {
                width: 100%;
                height: 20px;
                background-color: #f0f0f1;
                border-radius: 3px;
                margin: 20px 0;
                overflow: hidden;
            }

            .arm-progress-bar-fill {
                width: 0;
                height: 100%;
                background-color: #2271b1;
                transition: width 0.3s ease;
            }

            .arm-progress-status {
                margin: 10px 0;
                font-weight: 500;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.arm-upgrade-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $button = $('#arm-upgrade-button');
                const $progress = $('#arm-upgrade-progress');
                const $status = $('.arm-progress-status');
                const $fill = $('.arm-progress-bar-fill');
                
                $button.prop('disabled', true);
                $progress.show();
                
                const steps = [
                    {
                        message: '<?php _e('Creating backup...', 'appliance-repair-manager'); ?>',
                        progress: 20
                    },
                    {
                        message: '<?php _e('Initializing new system...', 'appliance-repair-manager'); ?>',
                        progress: 40
                    },
                    {
                        message: '<?php _e('Updating references...', 'appliance-repair-manager'); ?>',
                        progress: 60
                    },
                    {
                        message: '<?php _e('Cleaning up...', 'appliance-repair-manager'); ?>',
                        progress: 80
                    },
                    {
                        message: '<?php _e('Completing upgrade...', 'appliance-repair-manager'); ?>',
                        progress: 100
                    }
                ];

                let currentStep = 0;
                const interval = setInterval(() => {
                    if (currentStep < steps.length) {
                        $status.text(steps[currentStep].message);
                        $fill.css('width', steps[currentStep].progress + '%');
                        currentStep++;
                    } else {
                        clearInterval(interval);
                    }
                }, 1000);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        clearInterval(interval);
                        
                        if (response.success) {
                            $status.html('<?php _e('Upgrade completed successfully!', 'appliance-repair-manager'); ?>');
                            $fill.css('width', '100%');
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            $status.html('<?php _e('Error: ', 'appliance-repair-manager'); ?>' + response.data.message);
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        clearInterval(interval);
                        $status.html('<?php _e('Error during upgrade. Please try again.', 'appliance-repair-manager'); ?>');
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function handleUpgrade() {
        check_admin_referer('arm_upgrade_modals');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        try {
            $result = $this->upgrade->execute();

            if ($result) {
                $this->logger->log('Modal system upgrade completed via admin');
                wp_send_json_success([
                    'message' => __('Modal system upgraded successfully!', 'appliance-repair-manager')
                ]);
            } else {
                throw new \Exception(__('Failed to upgrade modal system', 'appliance-repair-manager'));
            }

        } catch (\Exception $e) {
            $this->logger->logError('Error during modal system upgrade', [
                'error' => $e->getMessage()
            ]);

            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}