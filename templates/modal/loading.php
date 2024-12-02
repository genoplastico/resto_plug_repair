<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-loading">
    <div class="arm-modal-loading-spinner"></div>
    <div class="arm-modal-loading-text">
        <?php echo esc_html($message ?? __('Loading...', 'appliance-repair-manager')); ?>
    </div>
</div>