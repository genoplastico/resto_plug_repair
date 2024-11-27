<?php
namespace ApplianceRepairManager\Core;

class Assets {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_footer', [$this, 'debug_asset_urls']);
    }

    public function debug_asset_urls() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $plugin_url = plugins_url('', ARM_PLUGIN_FILE);
            echo "<!-- ARM Asset URLs Debug:\n";
            echo "Plugin URL Base: " . $plugin_url . "\n";
            echo "Admin CSS: " . $plugin_url . "/assets/css/admin.css\n";
            echo "Admin JS: " . $plugin_url . "/assets/js/admin.js\n";
            echo "-->\n";
        }
    }

    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'arm-') === false && strpos($hook, 'appliance-repair-manager') === false) {
            return;
        }

        // Get plugin base URL
        $plugin_url = plugins_url('', ARM_PLUGIN_FILE);

        // Enqueue Select2
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            [],
            '4.1.0-rc.0'
        );

        // Enqueue jQuery first
        wp_enqueue_script('jquery');

        // Enqueue Select2
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            '4.1.0-rc.0',
            true
        );

        // Enqueue plugin styles
        wp_enqueue_style(
            'arm-admin-styles',
            $plugin_url . '/assets/css/admin.css',
            [],
            ARM_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'arm-admin-scripts',
            $plugin_url . '/assets/js/admin.js',
            ['jquery', 'select2'],
            ARM_VERSION,
            true
        );

        // Localize script
        wp_localize_script('arm-admin-scripts', 'armL10n', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'pluginUrl' => $plugin_url,
            'debug' => [
                'pluginUrl' => $plugin_url,
                'adminUrl' => admin_url(),
                'ajaxUrl' => admin_url('admin-ajax.php')
            ],
            'i18n' => [
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
            ]
        ]);
    }

    public function enqueue_public_assets() {
        // Only load on plugin public pages
        if (!isset($_GET['arm_action'])) {
            return;
        }

        $plugin_url = plugins_url('', ARM_PLUGIN_FILE);

        // Enqueue jQuery first
        wp_enqueue_script('jquery');

        // Enqueue plugin styles
        wp_enqueue_style(
            'arm-public-styles',
            $plugin_url . '/assets/css/admin.css',
            [],
            ARM_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'arm-admin-scripts',
            $plugin_url . '/assets/js/admin.js',
            ['jquery'],
            ARM_VERSION,
            true
        );

        // Add ajaxurl for front-end
        wp_localize_script('arm-admin-scripts', 'armL10n', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'pluginUrl' => $plugin_url
        ]);
    }
}