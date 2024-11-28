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

// Cargar estilos y scripts antes del header
wp_enqueue_style('arm-admin-styles', ARM_PLUGIN_URL . 'assets/css/admin.css', [], ARM_VERSION);
wp_enqueue_style('arm-modal-styles', ARM_PLUGIN_URL . 'assets/css/modal-manager.css', [], ARM_VERSION);

wp_enqueue_script('jquery');
wp_enqueue_script('arm-modal-manager', ARM_PLUGIN_URL . 'assets/js/modal-manager.js', ['jquery'], ARM_VERSION, true);
wp_enqueue_script('arm-admin-scripts', ARM_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'arm-modal-manager'], ARM_VERSION, true);

wp_localize_script('arm-admin-scripts', 'armL10n', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('arm_ajax_nonce'),
    'loading' => __('Loading...', 'appliance-repair-manager'),
    'errorLoadingRepairDetails' => __('Error loading repair details.', 'appliance-repair-manager')
]);

get_header();
?>

<div class="arm-public-view">
    <div class="wrap">
        <h1><?php echo esc_html(sprintf(
            __('Repair History for %s', 'appliance-repair-manager'),
            $client->name
        )); ?></h1>

        <?php
        $repairs = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, 
                   a.type as appliance_type,
                   a.brand,
                   a.model,
                   u.display_name as technician_name
            FROM {$wpdb->prefix}arm_repairs r
            LEFT JOIN {$wpdb->prefix}arm_appliances a ON r.appliance_id = a.id
            LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
            WHERE a.client_id = %d
            ORDER BY r.created_at DESC
        ", $client_id));
        
        if ($repairs): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Appliance', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Diagnosis', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Technician', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Status', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Date', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repairs as $repair): ?>
                        <tr>
                            <td>
                                <?php echo esc_html(sprintf(
                                    '%s %s - %s',
                                    $repair->brand,
                                    $repair->appliance_type,
                                    $repair->model
                                )); ?>
                            </td>
                            <td><?php echo esc_html($repair->diagnosis); ?></td>
                            <td><?php echo esc_html($repair->technician_name); ?></td>
                            <td>
                                <span class="arm-status <?php echo esc_attr(arm_get_status_class($repair->status)); ?>">
                                    <?php echo esc_html(arm_get_status_label($repair->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(mysql2date(
                                get_option('date_format') . ' ' . get_option('time_format'),
                                $repair->created_at
                            )); ?></td>
                            <td>
                                <button type="button" class="button button-small view-repair-details" 
                                        data-repair-id="<?php echo esc_attr($repair->id); ?>">
                                    <?php _e('View Details', 'appliance-repair-manager'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No repair records found.', 'appliance-repair-manager'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para detalles de reparación -->
<div id="repair-details-modal" class="arm-modal">
    <div id="repair-details-content" class="arm-modal-content"></div>
</div>

<?php wp_nonce_field('arm_ajax_nonce', 'arm_ajax_nonce'); ?>

<?php get_footer(); ?>