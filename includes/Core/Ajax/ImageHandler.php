<?php
namespace ApplianceRepairManager\Core\Ajax;

use ApplianceRepairManager\Core\ImageManager;
use ApplianceRepairManager\Core\Debug\ErrorLogger;

class ImageHandler {
    private $logger;
    private $image_manager;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = ErrorLogger::getInstance();
        $this->image_manager = ImageManager::getInstance();

        add_action('wp_ajax_arm_upload_image', [$this, 'handle_upload']);
        add_action('wp_ajax_arm_delete_image', [$this, 'handle_delete']);
    }

    public function handle_upload() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            if (!isset($_FILES['image']) || !isset($_POST['type']) || !isset($_POST['id'])) {
                throw new \Exception('Missing required parameters');
            }

            $type = sanitize_text_field($_POST['type']);
            $id = intval($_POST['id']);

            $result = $this->image_manager->upload_image($_FILES['image'], $type, $id);
            
            if (!$result) {
                throw new \Exception('Upload failed');
            }

            // Store image reference in database
            $this->wpdb->insert(
                $this->wpdb->prefix . 'arm_images',
                [
                    'type' => $type,
                    'reference_id' => $id,
                    'url' => $result['url'],
                    'thumbnail_url' => $result['thumbnail_url'],
                    'public_id' => $result['public_id'],
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            );

            wp_send_json_success($result);

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_upload_image', $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_delete() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            
            $image = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}arm_images WHERE id = %d",
                $image_id
            ));

            if (!$image) {
                throw new \Exception('Image not found');
            }

            if ($this->image_manager->delete_image($image->public_id)) {
                $this->wpdb->delete(
                    $this->wpdb->prefix . 'arm_images',
                    ['id' => $image_id],
                    ['%d']
                );
                wp_send_json_success();
            } else {
                throw new \Exception('Delete failed');
            }

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_delete_image', $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}