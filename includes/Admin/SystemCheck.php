<?php
namespace ApplianceRepairManager\Admin;

use ApplianceRepairManager\Core\FileSystem;
use ApplianceRepairManager\Core\Debug;

class SystemCheck {
    private $filesystem;
    private $debug;

    public function __construct() {
        $this->filesystem = FileSystem::getInstance();
        $this->debug = Debug::getInstance();
        
        add_action('admin_notices', [$this, 'showSystemWarnings']);
        add_action('wp_ajax_arm_check_system', [$this, 'ajaxCheckSystem']);
    }

    public function showSystemWarnings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $permissions = $this->filesystem->checkPermissions();
        
        if ($permissions === false || $this->hasPermissionIssues($permissions)) {
            $this->renderWarningNotice();
        }
    }

    private function hasPermissionIssues($permissions) {
        // Check plugin directory
        if (!$permissions['plugin_dir']['readable'] || !$permissions['plugin_dir']['writable']) {
            return true;
        }

        // Check assets directory
        if (!$permissions['assets_dir']['readable']) {
            return true;
        }

        // Check critical files
        foreach ($permissions['files'] as $file) {
            if (!$file['exists'] || !$file['readable']) {
                return true;
            }
        }

        // Check URL access
        foreach ($permissions['urls'] as $url) {
            if (!$url['accessible']) {
                return true;
            }
        }

        return false;
    }

    private function renderWarningNotice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Appliance Repair Manager - System Check Warning', 'appliance-repair-manager'); ?></strong>
            </p>
            <p>
                <?php _e('There are permission issues that may affect the plugin functionality.', 'appliance-repair-manager'); ?>
                <a href="#" class="arm-check-system">
                    <?php _e('Click here to run a system check', 'appliance-repair-manager'); ?>
                </a>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.arm-check-system').click(function(e) {
                e.preventDefault();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'arm_check_system',
                        nonce: '<?php echo wp_create_nonce('arm_check_system'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            if (response.data.details) {
                                console.log('System Check Details:', response.data.details);
                            }
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function ajaxCheckSystem() {
        check_ajax_referer('arm_check_system', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'appliance-repair-manager')]);
        }

        $permissions = $this->filesystem->checkPermissions();
        
        if ($permissions === false) {
            wp_send_json_error([
                'message' => __('Error checking system permissions.', 'appliance-repair-manager')
            ]);
        }

        if ($this->hasPermissionIssues($permissions)) {
            // Intentar corregir los permisos
            $fixed = $this->filesystem->fixPermissions();
            
            if ($fixed) {
                wp_send_json_success([
                    'message' => __('Permissions have been automatically fixed.', 'appliance-repair-manager'),
                    'details' => $permissions
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Permission issues found and could not be automatically fixed. Please check the console for details.', 'appliance-repair-manager'),
                    'details' => $permissions
                ]);
            }
        } else {
            wp_send_json_success([
                'message' => __('All system checks passed successfully.', 'appliance-repair-manager'),
                'details' => $permissions
            ]);
        }
    }
}