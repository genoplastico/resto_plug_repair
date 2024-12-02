<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-modal-footer">
    <?php if (!empty($buttons)): ?>
        <div class="arm-modal-buttons">
            <?php foreach ($buttons as $button): ?>
                <button type="<?php echo esc_attr($button['type'] ?? 'button'); ?>"
                        class="<?php echo esc_attr($button['class'] ?? 'button'); ?>"
                        <?php if (!empty($button['action'])): ?>
                            data-action="<?php echo esc_attr($button['action']); ?>"
                        <?php endif; ?>>
                    <?php echo esc_html($button['text']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>