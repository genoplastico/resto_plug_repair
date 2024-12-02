<?php
namespace ApplianceRepairManager\Core\Modal\Utils;

class Validator {
    public static function validateModalData($data) {
        $required = ['id', 'title'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            throw new \Exception('Missing required modal data: ' . implode(', ', $missing));
        }

        return true;
    }

    public static function validateModalId($id) {
        if (empty($id) || !is_string($id)) {
            throw new \Exception('Invalid modal ID');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            throw new \Exception('Modal ID contains invalid characters');
        }

        return true;
    }

    public static function validateTemplate($template) {
        if (!file_exists($template)) {
            throw new \Exception("Template file not found: {$template}");
        }

        return true;
    }

    public static function validateButtons($buttons) {
        if (!is_array($buttons)) {
            throw new \Exception('Buttons must be an array');
        }

        foreach ($buttons as $button) {
            if (!isset($button['text'])) {
                throw new \Exception('Button text is required');
            }
        }

        return true;
    }
}