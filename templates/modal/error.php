<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-error">
    <div class="arm-modal-error-icon">
        <span class="dashicons dashicons-warning"></span>
    </div>
    <div class="arm-modal-error-content">
        <h3 class="arm-modal-error-title">
            <?php echo esc_html($title ?? __('Error', 'appliance-repair-manager')); ?>
        </h3>
        <p class="arm-modal-error-message">
            <?php echo esc_html($message ?? __('An error occurred', 'appliance-repair-manager')); ?>
        </p>
        <?php if (!empty($details)): ?>
            <div class="arm-modal-error-details">
                <pre><?php echo esc_html($details); ?></pre>
            </div>
        <?php endif; ?>
    </div>
    <div class="arm-modal-error-actions">
        <button type="button" class="button arm-modal-close">
            <?php _e('Close', 'appliance-repair-manager'); ?>
        </button>
        <?php if (!empty($retry_action)): ?>
            <button type="button" class="button button-primary" data-action="retry">
                <?php _e('Retry', 'appliance-repair-manager'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>