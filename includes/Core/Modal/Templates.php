<?php
namespace ApplianceRepairManager\Core\Modal;

class Templates {
    private static $instance = null;
    private $templates = [];
    private $logger;

    private function __construct() {
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
        $this->registerDefaultTemplates();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerDefaultTemplates() {
        $this->templates = [
            'default' => ARM_PLUGIN_DIR . 'templates/modal/base.php',
            'loading' => ARM_PLUGIN_DIR . 'templates/modal/loading.php',
            'error' => ARM_PLUGIN_DIR . 'templates/modal/error.php'
        ];
    }

    public function registerTemplate($id, $template) {
        if (!file_exists($template)) {
            $this->logger->logError('Template file not found', [
                'template_id' => $id,
                'template_path' => $template
            ]);
            return false;
        }

        $this->templates[$id] = $template;
        return true;
    }

    public function getTemplate($id) {
        return isset($this->templates[$id]) ? $this->templates[$id] : $this->templates['default'];
    }

    public function render($template, $data = []) {
        try {
            if (!file_exists($template)) {
                throw new \Exception("Template file not found: {$template}");
            }

            ob_start();
            extract($data);
            include $template;
            return ob_get_clean();

        } catch (\Exception $e) {
            $this->logger->logError('Error rendering modal template', [
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            
            return $this->renderError($e->getMessage());
        }
    }

    private function renderError($message) {
        return $this->render($this->templates['error'], [
            'message' => $message
        ]);
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}