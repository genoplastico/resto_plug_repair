<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Verificar token de acceso
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

$client = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}arm_clients WHERE id = %d
", $client_id));

if (!$client || wp_hash($client->id . $client->email . wp_salt()) !== $token) {
    wp_die(__('Invalid or expired access token.', 'appliance-repair-manager'));
}

get_header();

// Cargar estilos del admin
wp_enqueue_style('arm-admin-styles', ARM_PLUGIN_URL . 'assets/css/admin.css', [], ARM_VERSION);
?>

<div class="arm-public-view">
    <div class="wrap">
        <h1><?php echo esc_html(sprintf(
            __('Appliances for %s', 'appliance-repair-manager'),
            $client->name
        )); ?></h1>

        <?php
        $appliances = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}arm_appliances WHERE client_id = %d ORDER BY created_at DESC
        ", $client_id));
        
        if ($appliances): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Type', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Brand', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Model', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Serial Number', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Status', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appliances as $appliance): ?>
                        <tr>
                            <td><?php echo esc_html($appliance->type); ?></td>
                            <td><?php echo esc_html($appliance->brand); ?></td>
                            <td><?php echo esc_html($appliance->model); ?></td>
                            <td><?php echo esc_html($appliance->serial_number); ?></td>
                            <td>
                                <span class="arm-status <?php echo esc_attr(arm_get_status_class($appliance->status)); ?>">
                                    <?php echo esc_html(arm_get_status_label($appliance->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small view-appliance-history" 
                                        data-appliance-id="<?php echo esc_attr($appliance->id); ?>">
                                    <?php _e('View History', 'appliance-repair-manager'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No appliances found.', 'appliance-repair-manager'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para historial de aparato -->
<div id="appliance-history-modal" class="arm-modal">
    <div id="appliance-history-content" class="arm-modal-content"></div>
</div>

<?php
// Cargar scripts necesarios
wp_enqueue_script('jquery');
wp_enqueue_script('arm-admin-scripts', ARM_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], ARM_VERSION, true);
wp_localize_script('arm-admin-scripts', 'armL10n', [
    'errorLoadingHistory' => __('Error loading appliance history.', 'appliance-repair-manager')
]);
wp_nonce_field('arm_ajax_nonce', 'arm_ajax_nonce');

get_footer();
?>