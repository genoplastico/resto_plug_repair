<?php
namespace ApplianceRepairManager\Core\Modal\Utils;

class Sanitizer {
    public static function sanitizeModalId($id) {
        return sanitize_key($id);
    }

    public static function sanitizeModalData($data) {
        $sanitized = [];

        if (isset($data['id'])) {
            $sanitized['id'] = self::sanitizeModalId($data['id']);
        }

        if (isset($data['title'])) {
            $sanitized['title'] = sanitize_text_field($data['title']);
        }

        if (isset($data['content'])) {
            $sanitized['content'] = wp_kses_post($data['content']);
        }

        if (isset($data['class'])) {
            $sanitized['class'] = sanitize_html_class($data['class']);
        }

        if (isset($data['buttons']) && is_array($data['buttons'])) {
            $sanitized['buttons'] = array_map([self::class, 'sanitizeButton'], $data['buttons']);
        }

        return $sanitized;
    }

    public static function sanitizeButton($button) {
        return [
            'text' => sanitize_text_field($button['text'] ?? ''),
            'class' => sanitize_html_class($button['class'] ?? 'button'),
            'type' => in_array($button['type'] ?? '', ['button', 'submit']) ? $button['type'] : 'button',
            'action' => sanitize_key($button['action'] ?? '')
        ];
    }

    public static function sanitizeAttributes($attributes) {
        $sanitized = [];
        foreach ($attributes as $key => $value) {
            $sanitized[sanitize_key($key)] = sanitize_text_field($value);
        }
        return $sanitized;
    }
}