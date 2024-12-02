<?php
namespace ApplianceRepairManager\Core\Modal;

class Config {
    private static $instance = null;
    private $config;

    private function __construct() {
        $this->config = [
            'animation' => [
                'duration' => 200,
                'easing' => 'ease-out',
                'enabled' => true
            ],
            'overlay' => [
                'closeOnClick' => true,
                'opacity' => 0.6,
                'color' => '#000000'
            ],
            'accessibility' => [
                'trapFocus' => true,
                'closeOnEscape' => true,
                'role' => 'dialog'
            ],
            'classes' => [
                'modal' => 'arm-modal',
                'modalOpen' => 'arm-modal-open',
                'modalContent' => 'arm-modal-content',
                'modalHeader' => 'arm-modal-header',
                'modalBody' => 'arm-modal-body',
                'modalFooter' => 'arm-modal-footer',
                'closeButton' => 'arm-modal-close'
            ]
        ];
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key = null) {
        if ($key === null) {
            return $this->config;
        }
        
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }

    public function getClientConfig() {
        return [
            'animation' => $this->config['animation'],
            'overlay' => $this->config['overlay'],
            'classes' => $this->config['classes']
        ];
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}