<?php
namespace ApplianceRepairManager\Core;

class Assets {
    private $debug;

    public function __construct() {
        $this->debug = Debug::getInstance();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_footer', [$this, 'print_debug_info']);
        
        // Remove version query string from assets
        add_filter('style_loader_src', [$this, 'remove_version_query'], 999);
        add_filter('script_loader_src', [$this, 'remove_version_query'], 999);
    }

    public function remove_version_query($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
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
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
        );
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            null,
            true
        );

        // Admin CSS
        wp_enqueue_style(
            'arm-admin-styles',
            ARM_PLUGIN_URL . 'assets/css/admin.css'
        );

        // Admin JS
        wp_enqueue_script(
            'arm-admin-scripts',
            ARM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'select2'],
            null,
            true
        );

        // Localize scripts
        wp_localize_script('arm-admin-scripts', 'armL10n', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
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

        // Public styles
        wp_enqueue_style(
            'arm-public-styles',
            ARM_PLUGIN_URL . 'assets/css/admin.css'
        );

        // Public scripts
        wp_enqueue_script(
            'arm-public-scripts',
            ARM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            null,
            true
        );

        wp_localize_script('arm-public-scripts', 'armL10n', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce')
        ]);
    }

    public function print_debug_info() {
        if (WP_DEBUG && current_user_can('manage_options')) {
            echo '<script>console.log("ARM Debug Info:", ' . json_encode($this->debug->getDebugInfo()) . ');</script>';
        }
    }
}