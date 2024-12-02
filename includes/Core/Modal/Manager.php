<?php
namespace ApplianceRepairManager\Core\Modal;

class Manager {
    private static $instance = null;
    private $config;
    private $events;
    private $state;
    private $templates;
    private $logger;

    private function __construct() {
        $this->config = Config::getInstance();
        $this->events = Events::getInstance();
        $this->state = State::getInstance();
        $this->templates = Templates::getInstance();
        $this->logger = \ApplianceRepairManager\Core\Debug\ErrorLogger::getInstance();
        
        $this->init();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init() {
        $this->events->registerHandlers();
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets() {
        wp_enqueue_style(
            'arm-modal-core',
            ARM_PLUGIN_URL . 'assets/css/modal/core.css',
            [],
            ARM_VERSION
        );

        wp_enqueue_style(
            'arm-modal-animations',
            ARM_PLUGIN_URL . 'assets/css/modal/animations.css',
            ['arm-modal-core'],
            ARM_VERSION
        );

        wp_enqueue_script(
            'arm-modal-core',
            ARM_PLUGIN_URL . 'assets/js/modal/core.js',
            ['jquery'],
            ARM_VERSION,
            true
        );

        wp_enqueue_script(
            'arm-modal-events',
            ARM_PLUGIN_URL . 'assets/js/modal/events.js',
            ['arm-modal-core'],
            ARM_VERSION,
            true
        );

        wp_localize_script('arm-modal-core', 'armModalConfig', $this->config->getClientConfig());
    }

    public function openModal($modalId, $data = []) {
        try {
            if (!$this->state->canOpenModal($modalId)) {
                throw new \Exception("Cannot open modal: {$modalId}");
            }

            $template = $this->templates->getTemplate($modalId);
            if (!$template) {
                throw new \Exception("Template not found for modal: {$modalId}");
            }

            $this->state->addModal($modalId);
            return $this->templates->render($template, $data);

        } catch (\Exception $e) {
            $this->logger->logError('Error opening modal', [
                'modal_id' => $modalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function closeModal($modalId) {
        try {
            if (!$this->state->hasModal($modalId)) {
                throw new \Exception("Modal not found: {$modalId}");
            }

            $this->state->removeModal($modalId);
            $this->events->trigger('modalClosed', $modalId);
            return true;

        } catch (\Exception $e) {
            $this->logger->logError('Error closing modal', [
                'modal_id' => $modalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getActiveModals() {
        return $this->state->getActiveModals();
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}