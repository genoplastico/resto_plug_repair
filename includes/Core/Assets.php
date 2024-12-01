<?php
namespace ApplianceRepairManager\Core;

class Assets {
    private $debug;

    public function __construct() {
        $this->debug = Debug\ErrorLogger::getInstance();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'arm-') === false && strpos($hook, 'appliance-repair-manager') === false) {
            return;
        }

        $this->enqueue_common_assets();
        $this->enqueue_admin_specific_assets();
    }

    public function enqueue_public_assets() {
        if (!isset($_GET['arm_action'])) {
            return;
        }

        $this->enqueue_common_assets();
        $this->enqueue_public_specific_assets();
    }

    private function enqueue_common_assets() {
        wp_enqueue_script('jquery');

        // Modal Manager CSS
        wp_enqueue_style(
            'arm-modal-styles',
            ARM_PLUGIN_URL . 'assets/css/modal-manager.css',
            [],
            ARM_VERSION
        );

        // Modal Manager JS
        wp_enqueue_script(
            'arm-modal-manager',
            ARM_PLUGIN_URL . 'assets/js/modal-manager.js',
            ['jquery'],
            ARM_VERSION,
            true
        );
    }

    private function enqueue_admin_specific_assets() {
        // Admin CSS
        wp_enqueue_style(
            'arm-admin-styles',
            ARM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ARM_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'arm-admin-scripts',
            ARM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'arm-modal-manager'],
            ARM_VERSION,
            true
        );

        wp_localize_script('arm-admin-scripts', 'armL10n', array_merge(
            $this->get_common_translations(),
            [
                'selectClient' => __('Select Client', 'appliance-repair-manager'),
                'selectAppliance' => __('Select Appliance', 'appliance-repair-manager'),
                'confirmDeleteNote' => __('Are you sure you want to delete this note?', 'appliance-repair-manager')
            ]
        ));
    }

    private function enqueue_public_specific_assets() {
        // Public CSS
        wp_enqueue_style(
            'arm-public-styles',
            ARM_PLUGIN_URL . 'assets/css/public.css',
            [],
            ARM_VERSION
        );

        // Public JS
        wp_enqueue_script(
            'arm-public-scripts',
            ARM_PLUGIN_URL . 'assets/js/public.js',
            ['jquery', 'arm-modal-manager'],
            ARM_VERSION,
            true
        );

        wp_localize_script('arm-public-scripts', 'armPublicL10n', array_merge(
            $this->get_common_translations(),
            [
                'viewDetails' => __('View Details', 'appliance-repair-manager')
            ]
        ));
    }

    private function get_common_translations() {
        return [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'loading' => __('Loading...', 'appliance-repair-manager'),
            'errorLoadingRepairDetails' => __('Error loading repair details.', 'appliance-repair-manager'),
            'errorLoadingAppliances' => __('Error loading appliances.', 'appliance-repair-manager'),
            'errorLoadingHistory' => __('Error loading appliance history.', 'appliance-repair-manager')
        ];
    }
}