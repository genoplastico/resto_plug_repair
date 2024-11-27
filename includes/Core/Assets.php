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

        wp_enqueue_script('jquery');

        // Select2
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

        // Modal Manager CSS
        $modal_css_url = $this->debug->getAssetUrl('assets/css/modal-manager.css');
        wp_enqueue_style(
            'arm-modal-styles',
            $modal_css_url,
            [],
            ARM_VERSION
        );

        // Admin CSS
        $admin_css_url = $this->debug->getAssetUrl('assets/css/admin.css');
        wp_enqueue_style(
            'arm-admin-styles',
            $admin_css_url,
            ['arm-modal-styles'],
            ARM_VERSION
        );

        // Modal Manager JS
        $modal_js_url = $this->debug->getAssetUrl('assets/js/modal-manager.js');
        wp_enqueue_script(
            'arm-modal-manager',
            $modal_js_url,
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Admin JS
        $admin_js_url = $this->debug->getAssetUrl('assets/js/admin.js');
        wp_enqueue_script(
            'arm-admin-scripts',
            $admin_js_url,
            ['jquery', 'select2', 'arm-modal-manager'],
            ARM_VERSION,
            true
        );

        // Localize scripts
        wp_localize_script('arm-modal-manager', 'armAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'debug' => $this->debug->getDebugInfo()
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
            'errorLoadingRepairDetails' => __('Error loading repair details.', 'appliance-repair-manager'),
            'assetLoadError' => __('Error loading required assets.', 'appliance-repair-manager')
        ]);

        $this->debug->checkModalStructure();
    }

    public function enqueue_public_assets() {
        if (!isset($_GET['arm_action'])) {
            return;
        }

        $this->debug->log('Enqueuing public assets');

        wp_enqueue_script('jquery');

        // Modal Manager CSS
        wp_enqueue_style(
            'arm-modal-styles',
            $this->debug->getAssetUrl('assets/css/modal-manager.css'),
            [],
            ARM_VERSION
        );

        // Public styles
        wp_enqueue_style(
            'arm-public-styles',
            $this->debug->getAssetUrl('assets/css/admin.css'),
            ['arm-modal-styles'],
            ARM_VERSION
        );

        // Modal Manager JS
        wp_enqueue_script(
            'arm-modal-manager',
            $this->debug->getAssetUrl('assets/js/modal-manager.js'),
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Public scripts
        wp_enqueue_script(
            'arm-public-scripts',
            $this->debug->getAssetUrl('assets/js/admin.js'),
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

    public function print_debug_info() {
        if (WP_DEBUG && current_user_can('manage_options')) {
            echo '<script>console.log("ARM Debug Info:", ' . json_encode($this->debug->getDebugInfo()) . ');</script>';
        }
    }
}