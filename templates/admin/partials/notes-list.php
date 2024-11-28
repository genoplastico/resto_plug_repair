<?php if (!defined('ABSPATH')) exit; ?>

<?php if ($notes): ?>
    <?php foreach ($notes as $note): ?>
        <div class="arm-note <?php echo $note->is_public() ? 'arm-note-public' : 'arm-note-private'; ?>">
            <div class="arm-note-header">
                <span class="arm-note-author"><?php echo esc_html(get_userdata($note->get_user_id())->display_name); ?></span>
                <span class="arm-note-date">
                    <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $note->get_created_at())); ?>
                </span>
                <?php if ($note->is_public()): ?>
                    <span class="arm-note-visibility"><?php _e('Public', 'appliance-repair-manager'); ?></span>
                <?php endif; ?>
            </div>
            <div class="arm-note-content">
                <?php echo nl2br(esc_html($note->get_note())); ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="arm-no-notes"><?php _e('No notes available.', 'appliance-repair-manager'); ?></p>
<?php endif; ?>