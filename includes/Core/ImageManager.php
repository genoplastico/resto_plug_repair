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
        
        $this->init_cloudinary();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_cloudinary() {
        if ($this->cloud_name && $this->api_key && $this->api_secret) {
            \Cloudinary::config([
                "cloud_name" => $this->cloud_name,
                "api_key" => $this->api_key,
                "api_secret" => $this->api_secret,
                "secure" => true
            ]);
            return true;
        }
        return false;
    }

    public function upload_image($file, $type, $id) {
        try {
            if (!$this->cloud_name || !$this->api_key || !$this->api_secret) {
                throw new \Exception('Cloudinary credentials not configured');
            }

            if (!$this->init_cloudinary()) {
                throw new \Exception('Failed to initialize Cloudinary');
            }

            $folder = "appliance-repair/{$type}/{$id}";
            $public_id = "{$folder}/" . pathinfo($file['name'], PATHINFO_FILENAME) . '_' . time();

            $result = \Cloudinary\Uploader::upload($file['tmp_name'], [
            $result = \Cloudinary_Uploader::upload($file['tmp_name'], [
                'folder' => $folder,
                'public_id' => $public_id,
                'overwrite' => true,
                'resource_type' => 'image'
            ]);

            if (!isset($result['secure_url'])) {
                throw new \Exception('Invalid response from Cloudinary');
            }

            $thumbnail_url = cloudinary_url($result['public_id'], array(
                "width" => 150,
                "height" => 150,
                "crop" => "fill"
            ));

            return [
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'thumbnail_url' => $thumbnail_url
            ];

        } catch (\Exception $e) {
            error_log('Cloudinary upload error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_image($public_id) {
        try {
            if (!$this->cloud_name || !$this->api_key || !$this->api_secret) {
                throw new \Exception('Cloudinary credentials not configured');
            }

            $result = \Cloudinary\Uploader::destroy($public_id);
            $result = \Cloudinary_Uploader::destroy($public_id);
            return isset($result['result']) && $result['result'] === 'ok';

        } catch (\Exception $e) {
            error_log('Cloudinary delete error: ' . $e->getMessage());
            return false;
        }
    }
}