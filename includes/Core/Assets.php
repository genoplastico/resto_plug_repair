<?php
namespace ApplianceRepairManager\Core;

class Assets {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }

    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'arm-') === false && strpos($hook, 'appliance-repair-manager') === false) {
            return;
        }

        // Enqueue Select2
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            [],
            '4.1.0-rc.0'
        );
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            '4.1.0-rc.0',
            true
        );

        // Enqueue modal styles
        wp_enqueue_style(
            'arm-modal-styles',
            ARM_PLUGIN_URL . 'assets/css/modals.css',
            [],
            ARM_VERSION
        );

        // Enqueue plugin styles
        wp_enqueue_style(
            'arm-admin-styles',
            ARM_PLUGIN_URL . 'assets/css/admin.css',
            ['arm-modal-styles'],
            ARM_VERSION
        );

        // Enqueue modal scripts
        wp_enqueue_script(
            'arm-modal-scripts',
            ARM_PLUGIN_URL . 'assets/js/modals.js',
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Enqueue plugin scripts
        wp_enqueue_script(
            'arm-admin-scripts',
            ARM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'select2', 'arm-modal-scripts'],
            ARM_VERSION,
            true
        );

        // Localize script
        wp_localize_script('arm-admin-scripts', 'armL10n', [
            'confirmStatusChange' => __('Are you sure you want to change the status?', 'appliance-repair-manager'),
            'fillRequiredFields' => __('Please fill in all required fields.', 'appliance-repair-manager'),
            'errorAddingNote' => __('Error adding note.', 'appliance-repair-manager'),
            'publicUrlCopied' => __('Public URL has been copied to clipboard.', 'appliance-repair-manager'),
            'selectClient' => __('Select Client', 'appliance-repair-manager'),
            'selectAppliance' => __('Select Appliance', 'appliance-repair-manager'),
            'errorLoadingHistory' => __('Error loading appliance history.', 'appliance-repair-manager')
        ]);

        // Add ajaxurl if not in admin
        if (!is_admin()) {
            wp_localize_script('arm-modal-scripts', 'ajaxurl', [admin_url('admin-ajax.php')]);
        }
    }

    public function enqueue_public_assets() {
        // Only load on plugin public pages
        if (!isset($_GET['arm_action'])) {
            return;
        }

        // Enqueue modal styles
        wp_enqueue_style(
            'arm-modal-styles',
            ARM_PLUGIN_URL . 'assets/css/modals.css',
            [],
            ARM_VERSION
        );

        // Enqueue plugin styles
        wp_enqueue_style(
            'arm-public-styles',
            ARM_PLUGIN_URL . 'assets/css/admin.css',
            ['arm-modal-styles'],
            ARM_VERSION
        );

        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue modal scripts
        wp_enqueue_script(
            'arm-modal-scripts',
            ARM_PLUGIN_URL . 'assets/js/modals.js',
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Add ajaxurl for front-end
        wp_localize_script('arm-modal-scripts', 'ajaxurl', admin_url('admin-ajax.php'));
    }
}