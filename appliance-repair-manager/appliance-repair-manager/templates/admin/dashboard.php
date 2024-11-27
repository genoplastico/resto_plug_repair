<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="arm-dashboard-wrapper">
        <div class="arm-dashboard-header">
            <div class="arm-stats-container">
                <div class="arm-stat-box">
                    <h3><?php _e('Pending Repairs', 'appliance-repair-manager'); ?></h3>
                    <p class="arm-stat-number">
                        <?php echo esc_html(arm_get_repairs_count('pending')); ?>
                    </p>
                </div>
                <div class="arm-stat-box">
                    <h3><?php _e('In Progress', 'appliance-repair-manager'); ?></h3>
                    <p class="arm-stat-number">
                        <?php echo esc_html(arm_get_repairs_count('in_progress')); ?>
                    </p>
                </div>
                <div class="arm-stat-box">
                    <h3><?php _e('Completed', 'appliance-repair-manager'); ?></h3>
                    <p class="arm-stat-number">
                        <?php echo esc_html(arm_get_repairs_count('completed')); ?>
                    </p>
                </div>
                <div class="arm-stat-box">
                    <h3><?php _e('Delivered', 'appliance-repair-manager'); ?></h3>
                    <p class="arm-stat-number">
                        <?php echo esc_html(arm_get_repairs_count('delivered')); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="arm-dashboard-content">
            <div class="arm-recent-repairs">
                <h2><?php _e('Recent Repairs', 'appliance-repair-manager'); ?></h2>
                <?php
                $recent_repairs = arm_get_recent_repairs(5);
                if ($recent_repairs): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Client', 'appliance-repair-manager'); ?></th>
                                <th><?php _e('Appliance', 'appliance-repair-manager'); ?></th>
                                <th><?php _e('Status', 'appliance-repair-manager'); ?></th>
                                <th><?php _e('Technician', 'appliance-repair-manager'); ?></th>
                                <th><?php _e('Date', 'appliance-repair-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_repairs as $repair): ?>
                                <tr>
                                    <td><?php echo esc_html($repair->client_name); ?></td>
                                    <td><?php echo esc_html($repair->appliance_type); ?></td>
                                    <td>
                                        <span class="arm-status <?php echo esc_attr(arm_get_status_class($repair->status)); ?>">
                                            <?php echo esc_html(arm_get_status_label($repair->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($repair->technician_name); ?></td>
                                    <td><?php echo esc_html(
                                        mysql2date(
                                            get_option('date_format') . ' ' . get_option('time_format'),
                                            $repair->created_at
                                        )
                                    ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No recent repairs found.', 'appliance-repair-manager'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>