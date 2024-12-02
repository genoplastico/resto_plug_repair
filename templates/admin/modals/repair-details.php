<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="arm-modal-dialog">
    <div class="arm-modal-header">
        <h2 class="arm-modal-title"><?php _e('Repair Details', 'appliance-repair-manager'); ?></h2>
        <button type="button" class="arm-modal-close" aria-label="<?php esc_attr_e('Close', 'appliance-repair-manager'); ?>">&times;</button>
    </div>

    <div class="arm-modal-body">
    <?php if (isset($repair) && $repair): ?>
        <div class="arm-repair-details">
            <div class="arm-detail-section">
                <h3><?php _e('Client Information', 'appliance-repair-manager'); ?></h3>
                <p><strong><?php _e('Client:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->client_name); ?></p>
            </div>

            <div class="arm-detail-section">
                <h3><?php _e('Appliance Information', 'appliance-repair-manager'); ?></h3>
                <p><strong><?php _e('Type:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->appliance_type); ?></p>
                <p><strong><?php _e('Brand:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->brand); ?></p>
                <p><strong><?php _e('Model:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->model); ?></p>
            </div>

            <div class="arm-detail-section">
                <h3><?php _e('Repair Information', 'appliance-repair-manager'); ?></h3>
                <p><strong><?php _e('Technician:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->technician_name); ?></p>
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
                <?php endif;
                
                // Get repair images
                $repair_images = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}arm_images 
                     WHERE type = 'repair' AND reference_id = %d 
                     ORDER BY created_at DESC",
                    $repair->id
                ));
                
                if ($repair_images): ?>
                    <div class="arm-timeline-images">
                        <?php foreach ($repair_images as $image): ?>
                            <div class="arm-timeline-image">
                                <img src="<?php echo esc_url($image->thumbnail_url); ?>" 
                                     alt="<?php esc_attr_e('Repair photo', 'appliance-repair-manager'); ?>"
                                     data-full-url="<?php echo esc_url($image->url); ?>"
                                     class="arm-lightbox-image">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (current_user_can('edit_arm_repairs')): ?>
                    <div class="arm-image-upload" data-repair-id="<?php echo esc_attr($repair->id); ?>">
                        <span class="dashicons dashicons-camera"></span>
                        <?php _e('Add Photos', 'appliance-repair-manager'); ?>
                        <input type="file" class="arm-image-input" accept="image/*" multiple style="display: none;">
                    </div>
                <?php endif; ?>
                <p><strong><?php _e('Cost:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html(number_format($repair->cost, 2)); ?></p>
            </div>

            <div class="arm-detail-section">
                <h3><?php _e('Repair Notes', 'appliance-repair-manager'); ?></h3>
                <div class="arm-notes-list">
                    <?php if (!empty($repair->notes)): ?>
                        <?php foreach ($repair->notes as $note): ?>
                            <?php include ARM_PLUGIN_DIR . 'templates/admin/partials/note-item.php'; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('No notes available.', 'appliance-repair-manager'); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (current_user_can('edit_arm_repairs')): ?>
                    <form class="arm-note-form">
                        <?php wp_nonce_field('arm_ajax_nonce', 'note_nonce'); ?>
                        <input type="hidden" name="repair_id" value="<?php echo esc_attr($repair->id); ?>">
                        <textarea name="note" class="arm-note-input" rows="2" placeholder="<?php esc_attr_e('Add a note...', 'appliance-repair-manager'); ?>"></textarea>
                        <div class="arm-note-form-footer">
                            <label class="arm-note-visibility-toggle">
                                <input type="checkbox" name="is_public" value="1">
                                <?php _e('Make this note visible to client', 'appliance-repair-manager'); ?>
                            </label>
                            <button type="submit" class="button button-primary">
                                <?php _e('Add Note', 'appliance-repair-manager'); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="arm-error">
            <?php _e('Repair details not found.', 'appliance-repair-manager'); ?>
        </div>
    <?php endif; ?>
    </div>
</div>