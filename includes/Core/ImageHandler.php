<?php
namespace ApplianceRepairManager\Core;

class ImageHandler {
    private static $instance = null;
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    private $max_size = 5242880; // 5MB in bytes
    private $max_dimension = 4096;

    private function __construct() {}

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function validateImage($file) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new \Exception(__('No image file uploaded', 'appliance-repair-manager'));
        }

        // Check file size
        if ($file['size'] > $this->max_size) {
            throw new \Exception(__('Image size exceeds 5MB limit', 'appliance-repair-manager'));
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $this->allowed_types)) {
            throw new \Exception(__('Invalid file type. Allowed types: JPG, PNG, GIF', 'appliance-repair-manager'));
        }

        // Verify it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            throw new \Exception(__('Invalid image file', 'appliance-repair-manager'));
        }

        // Check dimensions
        list($width, $height) = $image_info;
        if ($width > $this->max_dimension || $height > $this->max_dimension) {
            throw new \Exception(__('Image dimensions cannot exceed 4096x4096 pixels', 'appliance-repair-manager'));
        }

        return true;
    }

    public function handleUpload($file, $post_id = 0) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        $this->validateImage($file);

        // Setup upload overrides
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => [
                'jpg|jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            ]
        );

        // Handle the upload
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            throw new \Exception($uploaded_file['error']);
        }

        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert attachment into media library
        $attach_id = wp_insert_attachment($attachment, $uploaded_file['file'], $post_id);
        if (is_wp_error($attach_id)) {
            throw new \Exception($attach_id->get_error_message());
        }

        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    public function deleteImage($attachment_id) {
        if (!$attachment_id) return false;
        return wp_delete_attachment($attachment_id, true);
    }
}