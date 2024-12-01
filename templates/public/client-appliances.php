<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Verify access token
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

$client = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}arm_clients WHERE id = %d
", $client_id));

if (!$client || wp_hash($client->id . $client->email . wp_salt()) !== $token) {
    wp_die(__('Invalid or expired access token.', 'appliance-repair-manager'));
}

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
                        <th><?php _e('Status', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Date', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repairs as $repair): ?>
                        <tr>
                            <td data-label="<?php _e('Appliance', 'appliance-repair-manager'); ?>">
                                <?php echo esc_html(sprintf(
                                    '%s %s - %s',
                                    $repair->brand,
                                    $repair->appliance_type,
                                    $repair->model
                                )); ?>
                            </td>
                            <td data-label="<?php _e('Diagnosis', 'appliance-repair-manager'); ?>">
                                <?php echo esc_html($repair->diagnosis); ?>
                            </td>
                            <td data-label="<?php _e('Status', 'appliance-repair-manager'); ?>">
                                <span class="arm-status <?php echo esc_attr(arm_get_status_class($repair->status)); ?>">
                                    <?php echo esc_html(arm_get_status_label($repair->status)); ?>
                                </span>
                            </td>
                            <td data-label="<?php _e('Date', 'appliance-repair-manager'); ?>">
                                <?php echo esc_html(mysql2date(
                                get_option('date_format') . ' ' . get_option('time_format'),
                                $repair->created_at
                                )); ?>
                            </td>
                            <td data-label="<?php _e('Actions', 'appliance-repair-manager'); ?>">
                                <button type="button" class="button button-small view-repair-details" 
                                        data-repair-id="<?php echo esc_attr($repair->id); ?>"
                                        data-client-id="<?php echo esc_attr($client_id); ?>"
                                        data-token="<?php echo esc_attr($token); ?>">
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

<!-- Modal for repair details -->
<div id="repair-details-modal" class="arm-modal">
    <div id="repair-details-content" class="arm-modal-content"></div>
</div>

<?php wp_nonce_field('arm_ajax_nonce', 'arm_ajax_nonce'); ?>

<script>
jQuery(document).ready(function($) {
    // View repair details handler
    $(document).on('click', '.view-repair-details', function(e) {
        e.preventDefault();
        var $button = $(this);
        var repairId = $button.data('repair-id');
        var token = $button.data('token');
        var $modal = $('#repair-details-modal');
        var $content = $('#repair-details-content');
        
        $content.html('<div class="arm-loading"><?php echo esc_js(__('Loading...', 'appliance-repair-manager')); ?></div>');
        $modal.show();

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'arm_get_repair_details',
                repair_id: repairId,
                token: token,
                is_public: true,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $content.html(response.data.html);
                } else {
                    var errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : '<?php echo esc_js(__('Error loading repair details.', 'appliance-repair-manager')); ?>';
                    
                    $content.html('<div class="arm-error">' + errorMessage + '</div>');
                }
            },
            error: function() {
                $content.html('<div class="arm-error"><?php echo esc_js(__('Error loading repair details.', 'appliance-repair-manager')); ?></div>');
            }
        });
    });

    // Close modal handlers
    $(document).on('click', '.arm-modal-close', function() {
        $(this).closest('.arm-modal').hide();
    });

    $(document).on('click', '.arm-modal', function(e) {
        if ($(e.target).hasClass('arm-modal')) {
            $(this).hide();
        }
    });

    $(document).keyup(function(e) {
        if (e.key === 'Escape') {
            $('.arm-modal').hide();
        }
    });
});
</script>

<?php get_footer(); ?>