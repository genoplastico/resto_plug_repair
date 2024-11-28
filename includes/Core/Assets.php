<?php
namespace ApplianceRepairManager\Core;

class Assets {
    private $debug;

    public function __construct() {
        $this->debug = Debug::getInstance();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_footer', [$this, 'print_debug_info']);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'arm-') === false && strpos($hook, 'appliance-repair-manager') === false) {
            return;
        }

        $this->debug->log('Enqueuing admin assets', ['hook' => $hook]);

        $this->enqueue_common_assets();

        // Select2
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
        );
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            null,
            true
        );

        wp_localize_script('arm-admin-scripts', 'armL10n', array_merge(
            $this->get_common_translations(),
            [
                'selectClient' => __('Select Client', 'appliance-repair-manager'),
                'selectAppliance' => __('Select Appliance', 'appliance-repair-manager'),
                'confirmStatusChange' => __('Are you sure you want to change the status?', 'appliance-repair-manager'),
                'fillRequiredFields' => __('Please fill in all required fields.', 'appliance-repair-manager')
            ]
        ));
    }

    public function enqueue_public_assets() {
        if (!isset($_GET['arm_action'])) {
            return;
        }

        $this->enqueue_common_assets();

        wp_localize_script('arm-admin-scripts', 'armL10n', $this->get_common_translations());
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

        // Admin/Public CSS
        wp_enqueue_style(
            'arm-admin-styles',
            ARM_PLUGIN_URL . 'assets/css/admin.css',
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

        // Admin/Public JS
        wp_enqueue_script(
            'arm-admin-scripts',
            ARM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'arm-modal-manager'],
            ARM_VERSION,
            true
        );

        wp_localize_script('arm-modal-manager', 'armAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'debug' => $this->debug->getDebugInfo()
        ]);
    }

    private function get_common_translations() {
        return [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'loading' => __('Loading...', 'appliance-repair-manager'),
            'errorLoadingRepairDetails' => __('Error loading repair details.', 'appliance-repair-manager'),
            'errorLoadingAppliances' => __('Error loading appliances.', 'appliance-repair-manager'),
            'publicUrlCopied' => __('Public URL has been copied to clipboard.', 'appliance-repair-manager'),
            'errorCopyingUrl' => __('Error copying URL to clipboard.', 'appliance-repair-manager')
        ];
    }

    public function print_debug_info() {
        if (WP_DEBUG && current_user_can('manage_options')) {
            echo '<script>console.log("ARM Debug Info:", ' . json_encode($this->debug->getDebugInfo()) . ');</script>';
        }
    }
}