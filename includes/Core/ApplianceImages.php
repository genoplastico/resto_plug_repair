<?php
namespace ApplianceRepairManager\Core;

class ApplianceImages {
    private static $instance = null;
    private $debug;

    private function __construct() {
        $this->debug = Debug::getInstance();
        add_action('wp_ajax_arm_upload_appliance_image', [$this, 'handleImageUpload']);
        add_action('wp_ajax_arm_delete_appliance_image', [$this, 'handleImageDelete']);
        add_action('wp_ajax_arm_get_appliance_images', [$this, 'handleGetImages']);
    }

    public function handleGetImages() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Permission denied', 'appliance-repair-manager')]);
        }

        $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
        if (!$appliance_id) {
            wp_send_json_error(['message' => __('Invalid appliance ID', 'appliance-repair-manager')]);
        }

        $images = $this->getApplianceImages($appliance_id);
        
        ob_start();
        include ARM_PLUGIN_DIR . 'templates/admin/partials/appliance-images.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addImageMetaBox() {
        add_meta_box(
            'arm_appliance_images',
            __('Appliance Photos', 'appliance-repair-manager'),
            [$this, 'renderImageMetaBox'],
            'arm-appliance'
        );
    }

    public function renderImageMetaBox($post) {
        $images = $this->getApplianceImages($post->ID);
        include ARM_PLUGIN_DIR . 'templates/admin/partials/appliance-images.php';
    }

    public function handleImageUpload() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Permission denied', 'appliance-repair-manager')]);
        }

        $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
        if (!$appliance_id) {
            wp_send_json_error(['message' => __('Invalid appliance ID', 'appliance-repair-manager')]);
        }

        if (!isset($_FILES['image'])) {
            wp_send_json_error(['message' => __('No image provided', 'appliance-repair-manager')]);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('image', 0);

        if (is_wp_error($attachment_id)) {
            $this->debug->log('Error uploading image', [
                'error' => $attachment_id->get_error_message(),
                'appliance_id' => $appliance_id
            ]);
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        // Store the attachment ID in appliance meta
        $this->addImageToAppliance($appliance_id, $attachment_id);

        wp_send_json_success([
            'id' => $attachment_id,
            'url' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
            'full_url' => wp_get_attachment_image_url($attachment_id, 'full')
        ]);
    }

    public function handleImageDelete() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Permission denied', 'appliance-repair-manager')]);
        }

        $appliance_id = isset($_POST['appliance_id']) ? intval($_POST['appliance_id']) : 0;
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

        if (!$appliance_id || !$attachment_id) {
            wp_send_json_error(['message' => __('Invalid parameters', 'appliance-repair-manager')]);
        }

        $this->removeImageFromAppliance($appliance_id, $attachment_id);
        wp_delete_attachment($attachment_id, true);

        wp_send_json_success();
    }

    private function addImageToAppliance($appliance_id, $attachment_id) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'arm_appliance_images',
            [
                'appliance_id' => $appliance_id,
                'attachment_id' => $attachment_id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );
    }

    private function removeImageFromAppliance($appliance_id, $attachment_id) {
        global $wpdb;
        
        $wpdb->delete(
            $wpdb->prefix . 'arm_appliance_images',
            [
                'appliance_id' => $appliance_id,
                'attachment_id' => $attachment_id
            ],
            ['%d', '%d']
        );
    }

    public function getApplianceImages($appliance_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT attachment_id FROM {$wpdb->prefix}arm_appliance_images 
            WHERE appliance_id = %d ORDER BY created_at DESC",
            $appliance_id
        ));

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