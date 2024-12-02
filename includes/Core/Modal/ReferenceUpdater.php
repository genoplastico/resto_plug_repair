<?php
namespace ApplianceRepairManager\Core\Modal;

class ReferenceUpdater {
    private $logger;
    private $templates = [
        'templates/admin/modals/repair-details.php',
        'templates/admin/modals/appliance-history.php',
        'templates/public/modals/repair-details.php'
    ];

    public function __construct() {
        $this->logger = Utils\Logger::getInstance();
    }

    public function updateReferences() {
        try {
            // Update template references
            $this->updateTemplateReferences();

            // Update JavaScript references
            $this->updateJavaScriptReferences();

            // Update CSS references
            $this->updateCSSReferences();

            $this->logger->log('Modal references updated successfully');
            return true;

        } catch (\Exception $e) {
            $this->logger->log('Error updating modal references', [
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    private function updateTemplateReferences() {
        foreach ($this->templates as $template) {
            $path = ARM_PLUGIN_DIR . $template;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                
                // Update class names
                $content = str_replace('modal-content', 'arm-modal-content', $content);
                $content = str_replace('modal-header', 'arm-modal-header', $content);
                $content = str_replace('modal-body', 'arm-modal-body', $content);
                $content = str_replace('modal-footer', 'arm-modal-footer', $content);
                
                // Update data attributes
                $content = str_replace('data-modal', 'data-arm-modal', $content);
                
                file_put_contents($path, $content);
            }
        }
    }

    private function updateJavaScriptReferences() {
        $jsFiles = [
            'assets/js/admin.js',
            'assets/js/public.js'
        ];

        foreach ($jsFiles as $file) {
            $path = ARM_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                
                // Update modal initialization
                $content = str_replace(
                    'window.modalManager',
                    'window.armModalManager',
                    $content
                );
                
                // Update event handlers
                $content = str_replace(
                    '.modal(',
                    '.armModal(',
                    $content
                );
                
                file_put_contents($path, $content);
            }
        }
    }

    private function updateCSSReferences() {
        $cssFiles = [
            'assets/css/admin.css',
            'assets/css/public.css'
        ];

        foreach ($cssFiles as $file) {
            $path = ARM_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                
                // Update modal classes
                $content = str_replace('.modal', '.arm-modal', $content);
                $content = str_replace('.modal-', '.arm-modal-', $content);
                
                file_put_contents($path, $content);
            }
        }
    }
}