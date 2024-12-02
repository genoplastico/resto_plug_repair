<?php
namespace ApplianceRepairManager\Core;

class ApplianceImages {
    private static $instance = null;
    private $debug;

    private function __construct() {
        $this->debug = Debug\ErrorLogger::getInstance();
        add_action('wp_ajax_arm_upload_appliance_image', [$this, 'handleImageUpload']);
        add_action('wp_ajax_arm_delete_appliance_image', [$this, 'handleImageDelete']);
        add_action('wp_ajax_arm_get_appliance_images', [$this, 'handleGetImages']);
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function handleGetImages() {
        try {
            global $wpdb;
            
            // Verify table existence first
            $table_name = $wpdb->prefix . 'arm_appliance_images';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            $this->debug->logError('Starting handleGetImages', [
                'POST' => $_POST,
                'user_can_upload' => current_user_can('upload_files'),
                'wpdb_prefix' => $wpdb->prefix,
                'table_exists' => $table_exists,
                'table_name' => $table_name
            ]);
            
            if (!$table_exists) {
                throw new \Exception(sprintf(
                    'Table %s does not exist. Please deactivate and reactivate the plugin.',
                    $table_name
                ));
            }

            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('upload_files')) {
                throw new \Exception(__('Permission denied', 'appliance-repair-manager'));
            }

            $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
            
            $this->debug->logError('Checking table existence', [
                'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}arm_appliance_images'"),
                'appliance_id' => $appliance_id,
                'table_name' => $wpdb->prefix . 'arm_appliance_images',
                'last_error' => $wpdb->last_error
            ]);

            if (!$appliance_id) {
                throw new \Exception(__('Invalid appliance ID', 'appliance-repair-manager'));
            }

            $this->debug->logError('Getting images', [
                'appliance_id' => $appliance_id,
                'query' => $wpdb->prepare(
                    "SELECT attachment_id FROM {$wpdb->prefix}arm_appliance_images 
                    WHERE appliance_id = %d ORDER BY created_at DESC",
                    $appliance_id
                )
            ]);

            $images = $this->getApplianceImages($appliance_id);
            
            $this->debug->logError('Images retrieved', [
                'count' => count($images),
                'images' => $images
            ]);

            ob_start();
            include ARM_PLUGIN_DIR . 'templates/admin/partials/appliance-images.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->debug->logError('Error getting appliance images', [
                'error' => $e->getMessage(),
                'appliance_id' => $appliance_id ?? null
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handleImageUpload() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('upload_files')) {
                throw new \Exception(__('Permission denied', 'appliance-repair-manager'));
            }

            $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
            if (!$appliance_id) {
                throw new \Exception(__('Invalid appliance ID', 'appliance-repair-manager'));
            }

            if (!isset($_FILES['image'])) {
                throw new \Exception(__('No image provided', 'appliance-repair-manager'));
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('image', 0);

            if (is_wp_error($attachment_id)) {
                throw new \Exception($attachment_id->get_error_message());
            }

            $this->addImageToAppliance($appliance_id, $attachment_id);

            wp_send_json_success([
                'id' => $attachment_id,
                'url' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                'full_url' => wp_get_attachment_image_url($attachment_id, 'full')
            ]);

        } catch (\Exception $e) {
            $this->debug->logError('Error uploading image', [
                'error' => $e->getMessage(),
                'appliance_id' => $appliance_id ?? null
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handleImageDelete() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('upload_files')) {
                throw new \Exception(__('Permission denied', 'appliance-repair-manager'));
            }

            $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
            $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

            if (!$appliance_id || !$attachment_id) {
                throw new \Exception(__('Invalid parameters', 'appliance-repair-manager'));
            }

            $this->removeImageFromAppliance($appliance_id, $attachment_id);
            wp_delete_attachment($attachment_id, true);

            wp_send_json_success();

        } catch (\Exception $e) {
            $this->debug->logError('Error deleting image', [
                'error' => $e->getMessage(),
                'appliance_id' => $appliance_id ?? null,
                'attachment_id' => $attachment_id ?? null
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function addImageToAppliance($appliance_id, $attachment_id) {
        global $wpdb;
        
        $this->debug->logError('Adding image to appliance', [
            'appliance_id' => $appliance_id,
            'attachment_id' => $attachment_id,
            'table' => $wpdb->prefix . 'arm_appliance_images'
        ]);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'arm_appliance_images',
            [
                'appliance_id' => $appliance_id,
                'attachment_id' => $attachment_id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );
        
        $this->debug->logError('Insert result', [
            'result' => $result,
            'last_error' => $wpdb->last_error,
            'last_query' => $wpdb->last_query
        ]);

        if ($result === false) {
            throw new \Exception($wpdb->last_error);
        }
    }

    private function removeImageFromAppliance($appliance_id, $attachment_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'arm_appliance_images',
            [
                'appliance_id' => $appliance_id,
                'attachment_id' => $attachment_id
            ],
            ['%d', '%d']
        );

        if ($result === false) {
            throw new \Exception($wpdb->last_error);
        }
    }

    public function getApplianceImages($appliance_id) {
        global $wpdb;
        
        $this->debug->logError('getApplianceImages called', [
            'appliance_id' => $appliance_id,
            'table_name' => $wpdb->prefix . 'arm_appliance_images'
        ]);

        // Check if table exists
        $table_name = $wpdb->prefix . 'arm_appliance_images';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        $this->debug->logError('Table existence check', [
            'table_exists' => $table_exists,
            'table_name' => $table_name,
            'all_tables' => $wpdb->get_col('SHOW TABLES')
        ]);

        if ($table_exists != $table_name) {
            $this->debug->logError('Table does not exist', [
                'expected' => $table_name,
                'found' => $table_exists
            ]);
            return [];
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT attachment_id FROM {$wpdb->prefix}arm_appliance_images 
            WHERE appliance_id = %d ORDER BY created_at DESC",
            $appliance_id
        ));

        if ($results === false) {
            throw new \Exception($wpdb->last_error);
        }

        $images = [];
        foreach ($results as $result) {
            $images[] = [
                'id' => $result->attachment_id,
                'url' => wp_get_attachment_image_url($result->attachment_id, 'thumbnail'),
                'full_url' => wp_get_attachment_image_url($result->attachment_id, 'full')
            ];
        }

        return $images;
    }
}