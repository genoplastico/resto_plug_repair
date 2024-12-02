<?php
namespace ApplianceRepairManager\Core;

class HookManager {
    private static $instance = null;
    private $logger;
    private $registered_hooks = [];

    private function __construct() {
        $this->logger = Debug\Logger::getInstance();
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addAction($hook, $component, $method, $priority = 10, $accepted_args = 1) {
        try {
            if (!method_exists($component, $method)) {
                throw new \Exception(sprintf(
                    'Method %s does not exist in class %s',
                    $method,
                    get_class($component)
                ));
            }

            $hook_key = $this->generateHookKey($hook, $component, $method);
            
            // Evitar duplicados
            if (isset($this->registered_hooks[$hook_key])) {
                return true;
            }

            add_action($hook, [$component, $method], $priority, $accepted_args);
            $this->registered_hooks[$hook_key] = true;

            $this->logger->log('Hook registered successfully', [
                'hook' => $hook,
                'component' => get_class($component),
                'method' => $method,
                'priority' => $priority
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->log('Error registering hook', [
                'error' => $e->getMessage(),
                'hook' => $hook,
                'component' => get_class($component),
                'method' => $method
            ], 'error');

            return false;
        }
    }

    public function addFilter($hook, $component, $method, $priority = 10, $accepted_args = 1) {
        try {
            if (!method_exists($component, $method)) {
                throw new \Exception(sprintf(
                    'Method %s does not exist in class %s',
                    $method,
                    get_class($component)
                ));
            }

            $hook_key = $this->generateHookKey($hook, $component, $method);
            
            if (isset($this->registered_hooks[$hook_key])) {
                return true;
            }

            add_filter($hook, [$component, $method], $priority, $accepted_args);
            $this->registered_hooks[$hook_key] = true;

            $this->logger->log('Filter registered successfully', [
                'hook' => $hook,
                'component' => get_class($component),
                'method' => $method,
                'priority' => $priority
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->log('Error registering filter', [
                'error' => $e->getMessage(),
                'hook' => $hook,
                'component' => get_class($component),
                'method' => $method
            ], 'error');

            return false;
        }
    }

    private function generateHookKey($hook, $component, $method) {
        return sprintf(
            '%s_%s_%s',
            $hook,
            get_class($component),
            $method
        );
    }

    public function removeHook($hook, $component, $method, $priority = 10) {
        $hook_key = $this->generateHookKey($hook, $component, $method);
        
        if (isset($this->registered_hooks[$hook_key])) {
            remove_action($hook, [$component, $method], $priority);
            remove_filter($hook, [$component, $method], $priority);
            unset($this->registered_hooks[$hook_key]);
            
            $this->logger->log('Hook removed successfully', [
                'hook' => $hook,
                'component' => get_class($component),
                'method' => $method
            ]);
        }
    }

    public function clearHooks() {
        foreach ($this->registered_hooks as $hook_key => $_) {
            list($hook, $class, $method) = explode('_', $hook_key);
            $this->removeHook($hook, $class, $method);
        }
    }
}