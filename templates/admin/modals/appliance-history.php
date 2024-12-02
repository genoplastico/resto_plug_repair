<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-dialog">
    <div class="arm-modal-header">
        <h2 class="arm-modal-title"><?php _e('Appliance Repair History', 'appliance-repair-manager'); ?></h2>
        <button type="button" class="arm-modal-close" aria-label="<?php esc_attr_e('Close', 'appliance-repair-manager'); ?>">&times;</button>
    </div>

    <div class="arm-modal-body">
    <div class="arm-appliance-details">
        <div class="arm-detail-section">
            <h3><?php _e('Appliance Information', 'appliance-repair-manager'); ?></h3>
            <p><strong><?php _e('Client:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($appliance->client_name); ?></p>
            <p><strong><?php _e('Type:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($appliance->type); ?></p>
            <p><strong><?php _e('Brand:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($appliance->brand); ?></p>
            <p><strong><?php _e('Model:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($appliance->model); ?></p>
            <p><strong><?php _e('Serial Number:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($appliance->serial_number); ?></p>
            <p><strong><?php _e('Status:', 'appliance-repair-manager'); ?></strong> 
                <span class="arm-status <?php echo esc_attr(arm_get_status_class($appliance->status)); ?>">
                    <?php echo esc_html(arm_get_status_label($appliance->status)); ?>
                </span>
            </p>
        </div>

        <div class="arm-detail-section">
            <h3><?php _e('Repair History', 'appliance-repair-manager'); ?></h3>
            <?php if ($repairs): ?>
                <div class="arm-repair-timeline">
                    <?php foreach ($repairs as $repair): ?>
                        <div class="arm-timeline-item">
                            <div class="arm-timeline-date">
                                <?php 
                                $date = new DateTime($repair->created_at);
                                echo esc_html($date->format('d/m/y')); 
                                ?>
                                <span class="arm-timeline-time">
                                    <?php echo esc_html($date->format('H:i')); ?>
                                </span>
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
</div>