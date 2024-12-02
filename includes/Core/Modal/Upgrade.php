<?php
namespace ApplianceRepairManager\Core\Modal;

class Upgrade {
    private $migration;
    private $initializer;
    private $referenceUpdater;
    private $logger;

    public function __construct() {
        $this->migration = new Migration();
        $this->initializer = new Initializer();
        $this->referenceUpdater = new ReferenceUpdater();
        $this->logger = Utils\Logger::getInstance();
    }

    public function execute() {
        try {
            // Step 1: Create backup
            $backupDir = $this->migration->backup();
            $this->logger->log('Backup created', ['directory' => $backupDir]);

            // Step 2: Initialize new system
            if (!$this->initializer->initialize()) {
                throw new \Exception('Failed to initialize new modal system');
            }

            // Step 3: Update references
            if (!$this->referenceUpdater->updateReferences()) {
                throw new \Exception('Failed to update modal references');
            }

            // Step 4: Remove old files
            $this->migration->removeOldFiles();

            // Step 5: Update dependencies
            $this->migration->updateDependencies();

            $this->logger->log('Modal system upgrade completed successfully');
            return true;

        } catch (\Exception $e) {
            $this->logger->log('Error during modal system upgrade', [
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    public function rollback($backupDir) {
        try {
            // Restore files from backup
            $files = glob($backupDir . '/*');
            foreach ($files as $file) {
                $destPath = ARM_PLUGIN_DIR . basename($file);
                copy($file, $destPath);
            }

            // Restore old dependencies
            $assetsManager = new \ApplianceRepairManager\Core\Assets();
            $assetsManager->registerOldModalAssets();

            $this->logger->log('Modal system rollback completed');
            return true;

        } catch (\Exception $e) {
            $this->logger->log('Error during rollback', [
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }
}