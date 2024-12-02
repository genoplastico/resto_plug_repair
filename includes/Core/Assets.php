<?php
namespace ApplianceRepairManager\Core;

class Assets {
    public function deregisterOldModalAssets() {
        add_action('wp_enqueue_scripts', function() {
            wp_deregister_style('arm-modal-styles');
            wp_deregister_style('arm-modals');
            wp_deregister_script('arm-modal-manager');
            wp_deregister_script('arm-modals');
        }, 100);
    }

    public function registerNewModalAssets() {
        add_action('wp_enqueue_scripts', function() {
            // Core styles
            wp_enqueue_style(
                'arm-modal-core',
                ARM_PLUGIN_URL . 'assets/css/modal/core.css',
                [],
                ARM_VERSION
            );

            // Animation styles
            wp_enqueue_style(
                'arm-modal-animations',
                ARM_PLUGIN_URL . 'assets/css/modal/animations.css',
                ['arm-modal-core'],
                ARM_VERSION
            );

            // Theme styles
            wp_enqueue_style(
                'arm-modal-themes',
                ARM_PLUGIN_URL . 'assets/css/modal/themes.css',
                ['arm-modal-core'],
                ARM_VERSION
            );

            // Core JavaScript
            wp_enqueue_script(
                'arm-modal-core',
                ARM_PLUGIN_URL . 'assets/js/modal/core.js',
                ['jquery'],
                ARM_VERSION,
                true
            );

            // Events JavaScript
            wp_enqueue_script(
                'arm-modal-events',
                ARM_PLUGIN_URL . 'assets/js/modal/events.js',
                ['arm-modal-core'],
                ARM_VERSION,
                true
            );

            // Templates JavaScript
            wp_enqueue_script(
                'arm-modal-templates',
                ARM_PLUGIN_URL . 'assets/js/modal/templates.js',
                ['arm-modal-core'],
                ARM_VERSION,
                true
            );

            // Animations JavaScript
            wp_enqueue_script(
                'arm-modal-animations',
                ARM_PLUGIN_URL . 'assets/js/modal/animations.js',
                ['arm-modal-core'],
                ARM_VERSION,
                true
            );
        });
    }
}