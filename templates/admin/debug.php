<?php
if (!defined('ABSPATH')) {
    exit;
}

$debug = \ApplianceRepairManager\Core\Debug::get_instance();
$logs = $debug->get_logs();
$log_file = WP_CONTENT_DIR . '/arm-logs/debug.log';
$log_contents = file_exists($log_file) ? file_get_contents($log_file) : '';
?>

<div class="wrap">
    <h1><?php _e('Debug Information', 'appliance-repair-manager'); ?></h1>

    <div class="arm-debug-section">
        <h2><?php _e('System Status', 'appliance-repair-manager'); ?></h2>
        <table class="widefat" style="margin-bottom: 20px;">
            <tbody>
                <tr>
                    <td><strong><?php _e('WP_DEBUG:', 'appliance-repair-manager'); ?></strong></td>
                    <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '<span style="color: green;">Enabled</span>' : '<span style="color: red;">Disabled</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('PHP Version:', 'appliance-repair-manager'); ?></strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('WordPress Version:', 'appliance-repair-manager'); ?></strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Plugin Version:', 'appliance-repair-manager'); ?></strong></td>
                    <td><?php echo ARM_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Debug Log File:', 'appliance-repair-manager'); ?></strong></td>
                    <td><?php echo $log_file; ?></td>
                </tr>
            </tbody>
        </table>

        <h2><?php _e('Debug Log', 'appliance-repair-manager'); ?></h2>
        <div class="arm-debug-actions" style="margin-bottom: 10px;">
            <button type="button" class="button" onclick="document.getElementById('debug-log').select()">
                <?php _e('Select All', 'appliance-repair-manager'); ?>
            </button>
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('arm_clear_debug_log'); ?>
                <input type="hidden" name="action" value="arm_clear_debug_log">
                <button type="submit" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear the debug log?', 'appliance-repair-manager'); ?>')">
                    <?php _e('Clear Log', 'appliance-repair-manager'); ?>
                </button>
            </form>
        </div>
        <textarea id="debug-log" style="width: 100%; height: 500px; font-family: monospace;" readonly><?php 
            echo esc_textarea($log_contents);
        ?></textarea>
    </div>

    <div class="arm-debug-section" style="margin-top: 20px;">
        <h2><?php _e('JavaScript Console', 'appliance-repair-manager'); ?></h2>
        <p><?php _e('Open your browser\'s developer tools (F12) to view JavaScript debug messages in the console.', 'appliance-repair-manager'); ?></p>
        <div class="arm-debug-test">
            <button type="button" class="button" onclick="armDebug('Test debug message', {test: 'data'})">
                <?php _e('Send Test Debug Message', 'appliance-repair-manager'); ?>
            </button>
        </div>
    </div>
</div>

<script>
function armDebug(message, data = {}) {
    if (typeof console !== 'undefined' && armL10n.debug) {
        console.log('[ARM Debug]', message, data);
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_debug_log',
                message: message,
                context: data,
                nonce: '<?php echo wp_create_nonce('arm_ajax_nonce'); ?>'
            }
        });
    }
}
</script>