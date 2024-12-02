<?php
namespace ApplianceRepairManager\Core\Modal;

class Utils {
    public static function generateModalId($prefix = 'arm-modal') {
        return uniqid($prefix . '-');
    }

    public static function sanitizeModalId($modalId) {
        return sanitize_key($modalId);
    }

    public static function validateModalData($data) {
        $required = ['title', 'content'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            throw new \Exception('Missing required modal data: ' . implode(', ', $missing));
        }

        return array_map('sanitize_text_field', $data);
    }

    public static function getModalClasses($config, $additional = []) {
        $classes = [
            $config['classes']['modal']
        ];

        if (!empty($additional)) {
            $classes = array_merge($classes, array_map('sanitize_html_class', $additional));
        }

        return implode(' ', array_unique($classes));
    }

    public static function isAjaxRequest() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    public static function getTemplatePartial($template, $name = null) {
        $path = ARM_PLUGIN_DIR . 'templates/modal/';
        $file = $path . $template . ($name ? '-' . $name : '') . '.php';
        
        if (file_exists($file)) {
            return $file;
        }
        
        return false;
    }
}