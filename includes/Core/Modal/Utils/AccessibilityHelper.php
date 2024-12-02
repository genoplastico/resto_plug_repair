<?php
namespace ApplianceRepairManager\Core\Modal\Utils;

class AccessibilityHelper {
    public static function getAriaAttributes($data) {
        $attributes = [
            'role' => 'dialog',
            'aria-modal' => 'true',
            'aria-labelledby' => $data['id'] . '-title'
        ];

        if (!empty($data['description'])) {
            $attributes['aria-describedby'] = $data['id'] . '-description';
        }

        return $attributes;
    }

    public static function getFocusableElements() {
        return [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ];
    }

    public static function renderLiveRegion($id) {
        return sprintf(
            '<div id="%s-live" class="arm-modal-live-region" aria-live="polite" aria-atomic="true"></div>',
            esc_attr($id)
        );
    }

    public static function getKeyboardNavigation() {
        return [
            'close' => [
                'key' => 'Escape',
                'description' => __('Close modal', 'appliance-repair-manager')
            ],
            'focusNext' => [
                'key' => 'Tab',
                'description' => __('Move to next focusable element', 'appliance-repair-manager')
            ],
            'focusPrev' => [
                'key' => 'Shift + Tab',
                'description' => __('Move to previous focusable element', 'appliance-repair-manager')
            ]
        ];
    }
}