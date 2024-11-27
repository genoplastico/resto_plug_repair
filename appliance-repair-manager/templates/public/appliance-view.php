<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Verificar token de acceso
$appliance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

$appliance = $wpdb->get_row($wpdb->prepare("
    SELECT a.*, c.name as client_name, c.email as client_email
    FROM {$wpdb->prefix}arm_appliances a
    LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
    WHERE a.id = %d
", $appliance_id));

if (!$appliance || wp_hash($appliance->id . $appliance->client_email . wp_salt()) !== $token) {
    wp_die(__('Invalid or expired access token.', 'appliance-repair-manager'));
}

get_header();
?>

<div class="arm-public-view">
    <div class="arm-appliance-details">
        <h1><?php echo esc_html(sprintf(
            __('%s %s - %s', 'appliance-repair-manager'),
            $appliance->brand,
            $appliance->type,
            $appliance->model
        )); ?></h1>

        <div class="arm-detail-section">
            <h2><?php _e('Appliance Information', 'appliance-repair-manager'); ?></h2>
            <p><strong><?php _e('Serial Number:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($appliance->serial_number); ?></p>
            <p><strong><?php _e('Status:', 'appliance-repair-manager'); ?></strong> 
                <span class="arm-status <?php echo esc_attr(arm_get_status_class($appliance->status)); ?>">
                    <?php echo esc_html(arm_get_status_label($appliance->status)); ?>
                </span>
            </p>
        </div>

        <div class="arm-detail-section">
            <h2><?php _e('Repair History', 'appliance-repair-manager'); ?></h2>
            <?php
            $repairs = $wpdb->get_results($wpdb->prepare("
                SELECT r.*, u.display_name as technician_name
                FROM {$wpdb->prefix}arm_repairs r
                LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
                WHERE r.appliance_id = %d
                ORDER BY r.created_at DESC
            ", $appliance->id));

            if ($repairs): ?>
                <div class="arm-repair-timeline">
                    <?php foreach ($repairs as $repair): ?>
                        <div class="arm-timeline-item">
                            <div class="arm-timeline-date">
                                <?php echo esc_html(mysql2date(get_option('date_format'), $repair->created_at)); ?>
                            </div>
                            <div class="arm-timeline-content">
                                <span class="arm-status <?php echo esc_attr(arm_get_status_class($repair->status)); ?>">
                                    <?php echo esc_html(arm_get_status_label($repair->status)); ?>
                                </span>
                                <p><strong><?php _e('Technician:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->technician_name); ?></p>
                                <p><strong><?php _e('Diagnosis:', 'appliance-repair-manager'); ?></strong></p>
                                <div class="arm-diagnosis-text"><?php echo nl2br(esc_html($repair->diagnosis)); ?></div>
                                <?php if ($repair->parts_used): ?>
                                    <p><strong><?php _e('Parts Used:', 'appliance-repair-manager'); ?></strong></p>
                                    <div class="arm-parts-text"><?php echo nl2br(esc_html($repair->parts_used)); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('No repair history available.', 'appliance-repair-manager'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>