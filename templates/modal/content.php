<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-body">
    <?php if (!empty($content)): ?>
        <?php echo $content; ?>
    <?php else: ?>
        <div class="arm-modal-empty">
            <?php _e('No content available', 'appliance-repair-manager'); ?>
        </div>
    <?php endif; ?>
</div>