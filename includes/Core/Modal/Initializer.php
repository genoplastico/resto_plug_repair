<?php
namespace ApplianceRepairManager\Core\Modal;

class Initializer {
    private $manager;
    private $logger;

    public function __construct() {
        $this->logger = Utils\Logger::getInstance();
        $this->manager = Manager::getInstance();
    }

    public function initialize() {
        try {
            // Register core components
            $this->registerCoreComponents();

            // Initialize templates
            $this->initializeTemplates();

            // Register default modals
            $this->registerDefaultModals();

            // Set up event listeners
            $this->setupEventListeners();

            $this->logger->log('Modal system initialized successfully');
            return true;

        } catch (\Exception $e) {
            $this->logger->log('Error initializing modal system', [
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    private function registerCoreComponents() {
        // Register core modal templates
        Templates::getInstance()->registerTemplate('base', ARM_PLUGIN_DIR . 'templates/modal/base.php');
        Templates::getInstance()->registerTemplate('loading', ARM_PLUGIN_DIR . 'templates/modal/loading.php');
        Templates::getInstance()->registerTemplate('error', ARM_PLUGIN_DIR . 'templates/modal/error.php');
    }

    private function initializeTemplates() {
        // Register partial templates
        Templates::getInstance()->registerTemplate('header', ARM_PLUGIN_DIR . 'templates/modal/header.php');
        Templates::getInstance()->registerTemplate('content', ARM_PLUGIN_DIR . 'templates/modal/content.php');
        Templates::getInstance()->registerTemplate('footer', ARM_PLUGIN_DIR . 'templates/modal/footer.php');
    }

    private function registerDefaultModals() {
        // Register common modals
        $this->manager->registerModal('confirm', [
            'title' => __('Confirm Action', 'appliance-repair-manager'),
            'template' => 'base',
            'class' => 'arm-modal-confirm'
        ]);

        $this->manager->registerModal('alert', [
            'title' => __('Notice', 'appliance-repair-manager'),
            'template' => 'base',
            'class' => 'arm-modal-alert'
        ]);
    }

    private function setupEventListeners() {
        Events::getInstance()->on('modalOpen', function($modalId) {
            $this->logger->log('Modal opened', ['modal_id' => $modalId]);
        });

        Events::getInstance()->on('modalClose', function($modalId) {
            $this->logger->log('Modal closed', ['modal_id' => $modalId]);
        });
    }
}