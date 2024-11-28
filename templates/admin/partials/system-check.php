<?php if (!defined('ABSPATH')) exit; ?>

<div class="arm-system-check">
    <h2><?php _e('System Check', 'appliance-repair-manager'); ?></h2>
    
    <div class="arm-system-status">
        <button type="button" class="button button-secondary arm-check-permissions">
            <?php _e('Check File Permissions', 'appliance-repair-manager'); ?>
        </button>
        <span class="arm-check-status"></span>
    </div>

    <div class="arm-permissions-results" style="display:none;">
        <h3><?php _e('Permission Check Results', 'appliance-repair-manager'); ?></h3>
        <div class="arm-results-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.arm-check-permissions').click(function() {
        var $button = $(this);
        var $status = $('.arm-check-status');
        var $results = $('.arm-permissions-results');
        var $content = $('.arm-results-content');

        $button.prop('disabled', true);
        $status.html('<?php _e('Checking permissions...', 'appliance-repair-manager'); ?>');
        $results.hide();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arm_check_system',
                nonce: '<?php echo wp_create_nonce('arm_check_system'); ?>'
            },
            success: function(response) {
                $button.prop('disabled', false);
                
                if (response.success) {
                    $status.html('<?php _e('Check completed', 'appliance-repair-manager'); ?>');
                    
                    var html = '<table class="widefat">';
                    html += '<thead><tr>';
                    html += '<th><?php _e('Item', 'appliance-repair-manager'); ?></th>';
                    html += '<th><?php _e('Status', 'appliance-repair-manager'); ?></th>';
                    html += '<th><?php _e('Details', 'appliance-repair-manager'); ?></th>';
                    html += '</tr></thead><tbody>';

                    // Plugin Directory
                    html += formatPermissionRow(
                        '<?php _e('Plugin Directory', 'appliance-repair-manager'); ?>',
                        response.data.plugin_dir
                    );

                    // Assets Directory
                    html += formatPermissionRow(
                        '<?php _e('Assets Directory', 'appliance-repair-manager'); ?>',
                        response.data.assets_dir
                    );

                    // Critical Files
                    if (response.data.files) {
                        Object.entries(response.data.files).forEach(function([file, status]) {
                            html += formatPermissionRow(file, status);
                        });
                    }

                    html += '</tbody></table>';
                    
                    $content.html(html);
                    $results.show();
                } else {
                    $status.html('<?php _e('Error checking permissions', 'appliance-repair-manager'); ?>');
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $status.html('<?php _e('Error checking permissions', 'appliance-repair-manager'); ?>');
            }
        });
    });

    function formatPermissionRow(item, status) {
        var statusClass = status.readable && status.writable ? 'success' : 'error';
        var statusText = status.readable && status.writable ? 
            '<?php _e('OK', 'appliance-repair-manager'); ?>' : 
            '<?php _e('Issue Found', 'appliance-repair-manager'); ?>';
        
        var details = [];
        if (!status.readable) details.push('<?php _e('Not readable', 'appliance-repair-manager'); ?>');
        if (!status.writable) details.push('<?php _e('Not writable', 'appliance-repair-manager'); ?>');
        if (status.permissions) details.push('<?php _e('Permissions:', 'appliance-repair-manager'); ?> ' + status.permissions);

        return '<tr>' +
            '<td>' + item + '</td>' +
            '<td><span class="arm-status-' + statusClass + '">' + statusText + '</span></td>' +
            '<td>' + details.join(', ') + '</td>' +
            '</tr>';
    }
});
</script>

<style>
.arm-system-check {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.arm-system-status {
    margin: 15px 0;
}

.arm-check-status {
    margin-left: 10px;
    line-height: 28px;
}

.arm-permissions-results table {
    margin-top: 15px;
}

.arm-status-success {
    color: #46b450;
}

.arm-status-error {
    color: #dc3232;
}
</style>