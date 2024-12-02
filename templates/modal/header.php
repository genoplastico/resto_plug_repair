<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-header">
    <h2 id="<?php echo esc_attr($id); ?>-title"><?php echo esc_html($title); ?></h2>
    <button type="button" 
            class="arm-modal-close" 
            aria-label="<?php esc_attr_e('Close', 'appliance-repair-manager'); ?>"
            data-dismiss="modal">
        &times;
    </button>
</div>