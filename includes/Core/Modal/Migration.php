<?php
namespace ApplianceRepairManager\Core\Modal;

class Migration {
    private $oldFiles = [
        'assets/css/modals.css',
        'assets/css/modal-manager.css',
        'assets/js/modals.js',
        'assets/js/modal-manager.js'
    ];

    public function backup() {
        $backupDir = ARM_PLUGIN_DIR . 'backups/modal-system-' . date('Y-m-d-His');
        if (!file_exists($backupDir)) {
            wp_mkdir_p($backupDir);
        }

        foreach ($this->oldFiles as $file) {
            $sourcePath = ARM_PLUGIN_DIR . $file;
            if (file_exists($sourcePath)) {
                $backupPath = $backupDir . '/' . basename($file);
                copy($sourcePath, $backupPath);
            }
        }

        return $backupDir;
    }

    public function removeOldFiles() {
        foreach ($this->oldFiles as $file) {
            $filePath = ARM_PLUGIN_DIR . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function updateDependencies() {
        $assetsManager = new \ApplianceRepairManager\Core\Assets();
        $assetsManager->deregisterOldModalAssets();
        $assetsManager->registerNewModalAssets();
    }
}