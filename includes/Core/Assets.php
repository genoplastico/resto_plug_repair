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

        // Enqueue jQuery primero
        wp_enqueue_script('jquery');

        // Enqueue plugin styles
        wp_enqueue_style(
            'arm-admin-styles',
            plugins_url('assets/css/admin.css', ARM_PLUGIN_FILE),
            [],
            ARM_VERSION
        );

        // Enqueue modal styles
        wp_enqueue_style(
            'arm-modal-styles',
            plugins_url('assets/css/modal-manager.css', ARM_PLUGIN_FILE),
            [],
            ARM_VERSION
        );

        // Enqueue modal manager script
        wp_enqueue_script(
            'arm-modal-manager',
            plugins_url('assets/js/modal-manager.js', ARM_PLUGIN_FILE),
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'arm-admin-scripts',
            plugins_url('assets/js/admin.js', ARM_PLUGIN_FILE),
            ['jquery', 'select2', 'arm-modal-manager'],
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
            'errorLoadingHistory' => __('Error loading appliance history.', 'appliance-repair-manager'),
            'modalError' => __('Error al procesar el modal.', 'appliance-repair-manager'),
            'generalError' => __('Ha ocurrido un error.', 'appliance-repair-manager'),
            'errorLoadingAppliances' => __('Error al cargar los electrodomésticos.', 'appliance-repair-manager'),
            'errorLoadingRepairDetails' => __('Error al cargar los detalles de la reparación.', 'appliance-repair-manager')
        ]);

        // Add ajaxurl
        wp_localize_script('arm-modal-manager', 'armAjax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce')
        ]);
    }

    public function enqueue_public_assets() {
        // Only load on plugin public pages
        if (!isset($_GET['arm_action'])) {
            return;
        }

        // Enqueue jQuery primero
        wp_enqueue_script('jquery');

        // Enqueue plugin styles
        wp_enqueue_style(
            'arm-public-styles',
            plugins_url('assets/css/admin.css', ARM_PLUGIN_FILE),
            [],
            ARM_VERSION
        );

        // Enqueue modal styles
        wp_enqueue_style(
            'arm-modal-styles',
            plugins_url('assets/css/modal-manager.css', ARM_PLUGIN_FILE),
            [],
            ARM_VERSION
        );

        // Enqueue modal manager script
        wp_enqueue_script(
            'arm-modal-manager',
            plugins_url('assets/js/modal-manager.js', ARM_PLUGIN_FILE),
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Add ajaxurl for front-end
        wp_localize_script('arm-modal-manager', 'armAjax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce')
        ]);
    }
}