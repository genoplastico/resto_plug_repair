<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-content">
    <div class="arm-modal-header">
        <h2><?php _e('Repair Details', 'appliance-repair-manager'); ?></h2>
        <button type="button" class="arm-modal-close" aria-label="<?php esc_attr_e('Close', 'appliance-repair-manager'); ?>">&times;</button>
    </div>
    
    <div class="arm-modal-body">
        <div class="arm-repair-details">
            <!-- ... (secciones anteriores permanecen igual) ... -->

            <div class="arm-detail-section">
                <h3><?php _e('Repair Notes', 'appliance-repair-manager'); ?></h3>
                <div class="arm-notes-list">
                    <?php 
                    $repair_manager = new \ApplianceRepairManager\Admin\RepairManager();
                    $notes = $repair_manager->get_repair_notes($repair->id);
                    
                    if ($notes):
                        foreach ($notes as $note): ?>
                            <div class="arm-note <?php echo $note->is_public ? 'arm-note-public' : 'arm-note-private'; ?>">
                                <div class="arm-note-header">
                                    <span class="arm-note-author"><?php echo esc_html($note->author_name); ?></span>
                                    <span class="arm-note-date"><?php echo esc_html(
                                        mysql2date(
                                            get_option('date_format') . ' ' . get_option('time_format'),
                                            $note->created_at
                                        )
                                    ); ?></span>
                                    <?php if ($note->is_public): ?>
                                        <span class="arm-note-visibility"><?php _e('Public', 'appliance-repair-manager'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="arm-note-content">
                                    <?php echo nl2br(esc_html($note->note)); ?>
                                </div>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <p><?php _e('No notes available.', 'appliance-repair-manager'); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!isset($is_technician) || $repair->technician_id == get_current_user_id()): ?>
                    <form method="post" class="arm-note-form">
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
    </div>
</div>