<?php
/**
 * Plugin Name: Appliance Repair Manager
 * Plugin URI: https://your-domain.com/plugins/appliance-repair-manager
 * Description: Sistema de gestión de reparaciones de electrodomésticos
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: appliance-repair-manager
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ARM_VERSION', '1.0.0');
define('ARM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ARM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ARM_PLUGIN_FILE', __FILE__);
define('ARM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Debug mode
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Load helper functions
require_once ARM_PLUGIN_DIR . 'includes/Core/functions.php';

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'ApplianceRepairManager\\';
    $base_dir = ARM_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Activation Hook
register_activation_hook(__FILE__, function() {
    require_once ARM_PLUGIN_DIR . 'includes/Core/Activator.php';
    \ApplianceRepairManager\Core\Activator::activate();
});

// Load translations early
function arm_load_textdomain() {
    load_plugin_textdomain('appliance-repair-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'arm_load_textdomain');

// Initialize plugin
function arm_init() {
    // Initialize main plugin class
    \ApplianceRepairManager\Core\Plugin::getInstance();
}
add_action('init', 'arm_init');