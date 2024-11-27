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

        // Get plugin base URL correctly
        $plugin_url = plugins_url('', dirname(dirname(__FILE__)));

        // Enqueue jQuery first
        wp_enqueue_script('jquery');

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

        // Enqueue Modal Manager CSS
        wp_enqueue_style(
            'arm-modal-styles',
            $plugin_url . '/assets/css/modal-manager.css',
            [],
            ARM_VERSION
        );

        // Enqueue Admin CSS
        wp_enqueue_style(
            'arm-admin-styles',
            $plugin_url . '/assets/css/admin.css',
            ['arm-modal-styles'],
            ARM_VERSION
        );

        // Enqueue Modal Manager JS
        wp_enqueue_script(
            'arm-modal-manager',
            $plugin_url . '/assets/js/modal-manager.js',
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Enqueue Admin JS
        wp_enqueue_script(
            'arm-admin-scripts',
            $plugin_url . '/assets/js/admin.js',
            ['jquery', 'select2', 'arm-modal-manager'],
            ARM_VERSION,
            true
        );

        // Localize scripts
        wp_localize_script('arm-modal-manager', 'armAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'debug' => [
                'pluginUrl' => $plugin_url,
                'adminUrl' => admin_url(),
                'ajaxUrl' => admin_url('admin-ajax.php')
            ]
        ]);

        wp_localize_script('arm-admin-scripts', 'armL10n', [
            'confirmStatusChange' => __('Are you sure you want to change the status?', 'appliance-repair-manager'),
            'fillRequiredFields' => __('Please fill in all required fields.', 'appliance-repair-manager'),
            'errorAddingNote' => __('Error adding note.', 'appliance-repair-manager'),
            'publicUrlCopied' => __('Public URL has been copied to clipboard.', 'appliance-repair-manager'),
            'selectClient' => __('Select Client', 'appliance-repair-manager'),
            'selectAppliance' => __('Select Appliance', 'appliance-repair-manager'),
            'errorLoadingHistory' => __('Error loading appliance history.', 'appliance-repair-manager'),
            'modalError' => __('Error processing modal.', 'appliance-repair-manager'),
            'generalError' => __('An error has occurred.', 'appliance-repair-manager'),
            'errorLoadingAppliances' => __('Error loading appliances.', 'appliance-repair-manager'),
            'errorLoadingRepairDetails' => __('Error loading repair details.', 'appliance-repair-manager')
        ]);
    }

    public function enqueue_public_assets() {
        if (!isset($_GET['arm_action'])) {
            return;
        }

        $plugin_url = plugins_url('', dirname(dirname(__FILE__)));

        // Enqueue jQuery first
        wp_enqueue_script('jquery');

        // Enqueue Modal Manager CSS
        wp_enqueue_style(
            'arm-modal-styles',
            $plugin_url . '/assets/css/modal-manager.css',
            [],
            ARM_VERSION
        );

        // Enqueue public styles (reusing admin styles for now)
        wp_enqueue_style(
            'arm-public-styles',
            $plugin_url . '/assets/css/admin.css',
            ['arm-modal-styles'],
            ARM_VERSION
        );

        // Enqueue Modal Manager JS
        wp_enqueue_script(
            'arm-modal-manager',
            $plugin_url . '/assets/js/modal-manager.js',
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Enqueue public scripts (reusing admin scripts for now)
        wp_enqueue_script(
            'arm-public-scripts',
            $plugin_url . '/assets/js/admin.js',
            ['jquery', 'arm-modal-manager'],
            ARM_VERSION,
            true
        );

        // Localize scripts for public pages
        wp_localize_script('arm-modal-manager', 'armAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'pluginUrl' => $plugin_url
        ]);
    }
}