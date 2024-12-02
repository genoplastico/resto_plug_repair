<?php
namespace ApplianceRepairManager\Core;

class ImageManager {
    private static $instance = null;
    private $cloud_name;
    private $api_key;
    private $api_secret;
    
    private function __construct() {
        $this->cloud_name = get_option('arm_cloudinary_cloud_name');
        $this->api_key = get_option('arm_cloudinary_api_key');
        $this->api_secret = get_option('arm_cloudinary_api_secret');
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function upload_image($file, $type, $id) {
        try {
            $timestamp = time();
            $upload_dir = wp_upload_dir();
            $folder = "appliance-repair/{$type}/{$id}";
            $filename = sanitize_file_name($file['name']);
            $unique_filename = wp_unique_filename($upload_dir['path'], $filename);
            $filepath = $upload_dir['path'] . '/' . $unique_filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new \Exception('Failed to move uploaded file');
            }

            // Generate thumbnail
            $editor = wp_get_image_editor($filepath);
            if (is_wp_error($editor)) {
                throw new \Exception('Failed to create image editor');
            }

            $editor->resize(150, 150, true);
            $thumb_filename = 'thumb_' . $unique_filename;
            $thumb_filepath = $upload_dir['path'] . '/' . $thumb_filename;
            $editor->save($thumb_filepath);

            return [
                'url' => $upload_dir['url'] . '/' . $unique_filename,
                'public_id' => $folder . '/' . $unique_filename,
                'thumbnail_url' => $upload_dir['url'] . '/' . $thumb_filename
            ];

        } catch (\Exception $e) {
            error_log('Image upload error: ' . $e->getMessage());
            return false;
        }
    }
}