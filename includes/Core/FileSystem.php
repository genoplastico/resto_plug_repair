<?php
namespace ApplianceRepairManager\Core;

class FileSystem {
    private static $instance = null;
    private $debug;

    private function __construct() {
        $this->debug = Debug::getInstance();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function checkPermissions($path = '') {
        $base_path = empty($path) ? ARM_PLUGIN_DIR : $path;
        $results = [];

        try {
            // Verificar directorio base del plugin
            $results['plugin_dir'] = [
                'path' => $base_path,
                'exists' => is_dir($base_path),
                'readable' => is_readable($base_path),
                'writable' => is_writable($base_path),
                'permissions' => $this->getPermissions($base_path),
                'owner' => $this->getOwner($base_path)
            ];

            // Verificar directorio de assets
            $assets_path = ARM_PLUGIN_DIR . 'assets';
            $results['assets_dir'] = [
                'path' => $assets_path,
                'exists' => is_dir($assets_path),
                'readable' => is_readable($assets_path),
                'writable' => is_writable($assets_path),
                'permissions' => $this->getPermissions($assets_path),
                'owner' => $this->getOwner($assets_path)
            ];

            // Verificar archivos críticos
            $critical_files = [
                'assets/js/admin.js',
                'assets/js/modal-manager.js',
                'assets/css/admin.css',
                'assets/css/modal-manager.css'
            ];

            foreach ($critical_files as $file) {
                $file_path = ARM_PLUGIN_DIR . $file;
                $results['files'][$file] = [
                    'path' => $file_path,
                    'exists' => file_exists($file_path),
                    'readable' => is_readable($file_path),
                    'writable' => is_writable($file_path),
                    'permissions' => $this->getPermissions($file_path),
                    'owner' => $this->getOwner($file_path),
                    'size' => file_exists($file_path) ? filesize($file_path) : 0
                ];
            }

            // Verificar URL de assets
            foreach ($critical_files as $file) {
                $file_url = plugins_url($file, ARM_PLUGIN_FILE);
                $results['urls'][$file] = [
                    'url' => $file_url,
                    'accessible' => $this->checkUrlAccess($file_url)
                ];
            }

            $this->debug->log('File permissions check completed', $results);
            return $results;

        } catch (\Exception $e) {
            $this->debug->log('Error checking permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function getPermissions($path) {
        if (!file_exists($path)) {
            return false;
        }
        return substr(sprintf('%o', fileperms($path)), -4);
    }

    private function getOwner($path) {
        if (!file_exists($path)) {
            return false;
        }
        
        $owner = [
            'user' => fileowner($path),
            'group' => filegroup($path)
        ];

        if (function_exists('posix_getpwuid')) {
            $user_info = posix_getpwuid($owner['user']);
            $group_info = posix_getgrgid($owner['group']);
            $owner['user_name'] = $user_info['name'];
            $owner['group_name'] = $group_info['name'];
        }

        return $owner;
    }

    private function checkUrlAccess($url) {
        $response = wp_remote_head($url);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    public function fixPermissions() {
        $base_path = ARM_PLUGIN_DIR;
        $success = true;

        try {
            // Establecer permisos para directorios (755)
            $directories = [
                $base_path,
                $base_path . 'assets',
                $base_path . 'assets/js',
                $base_path . 'assets/css'
            ];

            foreach ($directories as $dir) {
                if (is_dir($dir) && !chmod($dir, 0755)) {
                    $success = false;
                    $this->debug->log('Failed to set directory permissions', ['path' => $dir]);
                }
            }

            // Establecer permisos para archivos (644)
            $files = [
                $base_path . 'assets/js/admin.js',
                $base_path . 'assets/js/modal-manager.js',
                $base_path . 'assets/css/admin.css',
                $base_path . 'assets/css/modal-manager.css'
            ];

            foreach ($files as $file) {
                if (file_exists($file) && !chmod($file, 0644)) {
                    $success = false;
                    $this->debug->log('Failed to set file permissions', ['path' => $file]);
                }
            }

            return $success;

        } catch (\Exception $e) {
            $this->debug->log('Error fixing permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}