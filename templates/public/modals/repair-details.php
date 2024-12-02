<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-content">
  <div class="arm-modal-header">
    <h2 class="arm-modal-title"><?php _e('Repair Details', 'appliance-repair-manager'); ?></h2>
    <button type="button" class="arm-modal-close" aria-label="<?php esc_attr_e('Close', 'appliance-repair-manager'); ?>">&times;</button>
  </div>

  <div class="arm-modal-body">
    <?php if (isset($repair) && $repair): ?>
        <div class="arm-repair-details">
            <div class="arm-detail-section">
                <h3><?php _e('Appliance Information', 'appliance-repair-manager'); ?></h3>
                <p><strong><?php _e('Type:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->appliance_type); ?></p>
                <p><strong><?php _e('Brand:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->brand); ?></p>
                <p><strong><?php _e('Model:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->model); ?></p>
            </div>

            <div class="arm-detail-section">
                <h3><?php _e('Repair Information', 'appliance-repair-manager'); ?></h3>
                <p><strong><?php _e('Status:', 'appliance-repair-manager'); ?></strong>
                    <span class="arm-status <?php echo esc_attr(arm_get_status_class($repair->status)); ?>">
                        <?php echo esc_html(arm_get_status_label($repair->status)); ?>
                    </span>
                </p>
                <p><strong><?php _e('Diagnosis:', 'appliance-repair-manager'); ?></strong></p>
                <div class="arm-diagnosis-text"><?php echo nl2br(esc_html($repair->diagnosis)); ?></div>
                <?php if ($repair->parts_used): ?>
                    <p><strong><?php _e('Parts Used:', 'appliance-repair-manager'); ?></strong></p>
                    <div class="arm-parts-text"><?php echo nl2br(esc_html($repair->parts_used)); ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($repair->notes)): ?>
            <div class="arm-detail-section">
                <h3><?php _e('Public Notes', 'appliance-repair-manager'); ?></h3>
                <div class="arm-notes-list">
                    <?php 
                    $public_notes = array_filter($repair->notes, function($note) {
                        return $note->is_public;
                    });
                    
                    if ($public_notes): foreach ($public_notes as $note): ?>
                        <div class="arm-note arm-note-public">
                            <div class="arm-note-header">
                                <span class="arm-note-date">
                                    <?php echo esc_html(mysql2date(get_option('date_format'), $note->created_at)); ?>
                                </span>
                            </div>
                            <div class="arm-note-content">
                                <?php echo nl2br(esc_html($note->note)); ?>
                            </div>
                        </div>
                    <?php endforeach; 
                    else: ?>
                        <p class="arm-no-notes"><?php _e('No public notes available.', 'appliance-repair-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="arm-error">
            <?php _e('Repair details not found.', 'appliance-repair-manager'); ?>
        </div>
    <?php endif; ?>
  </div>