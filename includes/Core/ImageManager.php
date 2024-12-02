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
            if (!$this->cloud_name || !$this->api_key || !$this->api_secret) {
                throw new \Exception('Cloudinary credentials not configured');
            }

            $timestamp = time();
            $folder = "appliance-repair/{$type}";
            $public_id = "{$type}_{$id}_" . $timestamp;

            // Build signature
            $params = [
                'folder' => $folder,
                'public_id' => $public_id,
                'timestamp' => $timestamp,
            ];
            ksort($params);
            
            $signature_string = '';
            foreach ($params as $key => $value) {
                $signature_string .= $key . '=' . $value;
            }
            $signature_string .= $this->api_secret;
            $signature = hash('sha256', $signature_string);

            // Prepare form data
            $body = [
                'file' => new \CURLFile($file['tmp_name'], $file['type']),
                'api_key' => $this->api_key,
                'timestamp' => $timestamp,
                'folder' => $folder,
                'public_id' => $public_id,
                'signature' => $signature
            ];

            // Upload to Cloudinary
            $response = wp_remote_post("https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload", [
                'method' => 'POST',
                'body' => $body,
                'headers' => [
                    'Content-Type' => 'multipart/form-data'
                ]
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $result = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!isset($result['secure_url'])) {
                throw new \Exception('Invalid response from Cloudinary');
            }

            return [
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'thumbnail_url' => str_replace('/upload/', '/upload/w_150,h_150,c_fill/', $result['secure_url'])
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

            $timestamp = time();
            
            // Build signature
            $params = [
                'public_id' => $public_id,
                'timestamp' => $timestamp,
            ];
            ksort($params);
            
            $signature_string = '';
            foreach ($params as $key => $value) {
                $signature_string .= $key . '=' . $value;
            }
            $signature_string .= $this->api_secret;
            $signature = hash('sha256', $signature_string);

            // Delete from Cloudinary
            $response = wp_remote_post("https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/destroy", [
                'body' => [
                    'public_id' => $public_id,
                    'api_key' => $this->api_key,
                    'timestamp' => $timestamp,
                    'signature' => $signature
                ]
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $result = json_decode(wp_remote_retrieve_body($response), true);
            return isset($result['result']) && $result['result'] === 'ok';

        } catch (\Exception $e) {
            error_log('Cloudinary delete error: ' . $e->getMessage());
            return false;
        }
    }
}