<?php if (!defined('ABSPATH')) exit; ?>

<div id="<?php echo esc_attr($id); ?>" 
     class="arm-modal <?php echo esc_attr($class ?? ''); ?>" 
     role="dialog" 
     aria-labelledby="<?php echo esc_attr($id); ?>-title" 
     aria-hidden="true"
     <?php if (!empty($data_attributes)): ?>
         <?php foreach ($data_attributes as $key => $value): ?>
             data-<?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>"
         <?php endforeach; ?>
     <?php endif; ?>>
    
    <div class="arm-modal-content">
        <?php 
        // Load header template
        include ARM_PLUGIN_DIR . 'templates/modal/header.php';
        
        // Load content template
        include ARM_PLUGIN_DIR . 'templates/modal/content.php';
        
        // Load footer template if buttons are provided
        if (!empty($buttons)) {
            include ARM_PLUGIN_DIR . 'templates/modal/footer.php';
        }
        ?>
    </div>
</div>