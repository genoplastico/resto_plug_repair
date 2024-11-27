<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-content">
    <div class="arm-modal-header">
        <h2><?php _e('Repair Details', 'appliance-repair-manager'); ?></h2>
        <button type="button" class="arm-modal-close" aria-label="<?php esc_attr_e('Close', 'appliance-repair-manager'); ?>">&times;</button>
    </div>
    
    <div class="arm-modal-body">
        <div class="arm-repair-details">
            <div class="arm-detail-section">
                <h3><?php _e('Client Information', 'appliance-repair-manager'); ?></h3>
                <p><strong><?php _e('Name:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->client_name); ?></p>
                <p><strong><?php _e('Address:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->client_address); ?></p>
                <p><strong><?php _e('Phone:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->client_phone); ?></p>
            </div>

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
                <p><strong><?php _e('Technician:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html($repair->technician_name); ?></p>
                <p><strong><?php _e('Cost:', 'appliance-repair-manager'); ?></strong> <?php echo esc_html(number_format($repair->cost, 2)); ?></p>
                <p><strong><?php _e('Diagnosis:', 'appliance-repair-manager'); ?></strong></p>
                <div class="arm-diagnosis-text"><?php echo nl2br(esc_html($repair->diagnosis)); ?></div>
                
                <?php if ($repair->parts_used): ?>
                    <p><strong><?php _e('Parts Used:', 'appliance-repair-manager'); ?></strong></p>
                    <div class="arm-parts-text"><?php echo nl2br(esc_html($repair->parts_used)); ?></div>
                <?php endif; ?>
            </div>

            <div class="arm-detail-section">
                <h3><?php _e('Repair Notes', 'appliance-repair-manager'); ?></h3>
                <div class="arm-notes-list">
                    <?php 
                    if ($repair->notes):
                        $notes_array = explode("\n", $repair->notes);
                        foreach ($notes_array as $note): ?>
                            <div class="arm-note">
                                <?php echo esc_html($note); ?>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <p><?php _e('No notes available.', 'appliance-repair-manager'); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!isset($is_technician) || $repair->technician_id == get_current_user_id()): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="arm-note-form">
                        <?php wp_nonce_field('arm_add_repair_note'); ?>
                        <input type="hidden" name="action" value="arm_add_repair_note">
                        <input type="hidden" name="repair_id" value="<?php echo esc_attr($repair->id); ?>">
                        <textarea name="note" class="arm-note-input" rows="2" placeholder="<?php esc_attr_e('Add a note...', 'appliance-repair-manager'); ?>"></textarea>
                        <button type="submit" class="button button-primary">
                            <?php _e('Add Note', 'appliance-repair-manager'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>