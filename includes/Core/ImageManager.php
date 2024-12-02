<?php
namespace ApplianceRepairManager\Core;

class ImageManager {
    private static $instance = null;
    private $cloudinary;
    
    private function __construct() {
        $this->init_cloudinary();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_cloudinary() {
        \Cloudinary::config([
            "cloud_name" => get_option('arm_cloudinary_cloud_name'),
            "api_key" => get_option('arm_cloudinary_api_key'),
            "api_secret" => get_option('arm_cloudinary_api_secret'),
            "secure" => true
        ]);
    }

    public function upload_image($file, $type, $id) {
        try {
            $upload = \Cloudinary\Uploader::upload($file['tmp_name'], [
                'folder' => "appliance-repair/{$type}",
                'public_id' => "{$type}_{$id}_" . time(),
                'transformation' => [
                    ['width' => 800, 'height' => 800, 'crop' => 'limit'],
                    ['quality' => 'auto:good']
                ]
            ]);

            return [
                'url' => $upload['secure_url'],
                'public_id' => $upload['public_id'],
                'thumbnail_url' => cloudinary_url($upload['public_id'], [
                    'width' => 150,
                    'height' => 150,
                    'crop' => 'fill'
                ])
            ];
        } catch (\Exception $e) {
            error_log('Cloudinary upload error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_image($public_id) {
        try {
            \Cloudinary\Uploader::destroy($public_id);
            return true;
        } catch (\Exception $e) {
            error_log('Cloudinary delete error: ' . $e->getMessage());
            return false;
        }
    }
}