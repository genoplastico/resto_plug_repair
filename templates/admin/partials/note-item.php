<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-note <?php echo $note->is_public ? 'arm-note-public' : 'arm-note-private'; ?>" data-note-id="<?php echo esc_attr($note->id); ?>">
    <div class="arm-note-header">
        <span class="arm-note-author"><?php echo esc_html($note->author_name); ?></span>
        <span class="arm-note-date">
            <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $note->created_at)); ?>
        </span>
        <?php if ($note->is_public): ?>
            <span class="arm-note-visibility"><?php _e('Public', 'appliance-repair-manager'); ?></span>
        <?php endif; ?>
        <?php if (current_user_can('manage_options') || $note->user_id == get_current_user_id()): ?>
            <button type="button" class="arm-delete-note" title="<?php esc_attr_e('Delete Note', 'appliance-repair-manager'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        <?php endif; ?>
    </div>
    <div class="arm-note-content">
        <?php echo nl2br(esc_html($note->note)); ?>
    </div>
</div>