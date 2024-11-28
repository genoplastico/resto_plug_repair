<?php
if (!defined('ABSPATH')) {
    exit;
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
global $wpdb;

// Get repair statuses
$repair_statuses = \ApplianceRepairManager\Core\Status\RepairStatusManager::getInstance()->getStatuses();

// Get technicians for dropdown
$technicians = get_users(['role' => 'arm_technician']);

// Check if current user is technician
$is_technician = !current_user_can('manage_options') && current_user_can('edit_arm_repairs');
$current_user_id = get_current_user_id();

// Check if we're viewing a specific technician's repairs
$technician_id = isset($_GET['technician_id']) ? intval($_GET['technician_id']) : null;

// Get all clients for the dropdown
$clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}arm_clients ORDER BY name ASC");

// Add nonce field for AJAX requests
wp_nonce_field('arm_ajax_nonce', 'arm_ajax_nonce');
?>

<div class="wrap">
    <h1><?php _e('Repairs Management', 'appliance-repair-manager'); ?></h1>

    <?php if ($message === 'repair_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Repair record added successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php elseif ($message === 'status_updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Repair status updated successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php elseif ($message === 'technician_assigned'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Technician assigned successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php elseif ($message === 'note_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Note added successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$is_technician): // Only show form for administrators ?>
    <div class="arm-repair-form-container">
        <h2><?php _e('Add New Repair Record', 'appliance-repair-manager'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="arm-form">
            <?php wp_nonce_field('arm_add_repair'); ?>
            <input type="hidden" name="action" value="arm_add_repair">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="client_select"><?php _e('Client', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <select name="client_id" id="client_select" class="regular-text arm-select2" required>
                            <option value=""><?php _e('Select Client', 'appliance-repair-manager'); ?></option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo esc_attr($client->id); ?>">
                                    <?php echo esc_html($client->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="appliance_id"><?php _e('Appliance', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <select name="appliance_id" id="appliance_id" class="regular-text arm-select2" required>
                            <option value=""><?php _e('Select Client First', 'appliance-repair-manager'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="technician_id"><?php _e('Assign Technician', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <select name="technician_id" id="technician_id" class="regular-text arm-select2" required>
                            <option value=""><?php _e('Select Technician', 'appliance-repair-manager'); ?></option>
                            <?php foreach ($technicians as $technician): ?>
                                <option value="<?php echo esc_attr($technician->ID); ?>">
                                    <?php echo esc_html($technician->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="diagnosis"><?php _e('Diagnosis', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <textarea name="diagnosis" id="diagnosis" class="large-text" rows="3" required></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parts_used"><?php _e('Parts Used', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <textarea name="parts_used" id="parts_used" class="large-text" rows="3"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cost"><?php _e('Cost', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="cost" id="cost" class="regular-text" step="0.01" min="0" required>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Add Repair Record', 'appliance-repair-manager')); ?>
        </form>
    </div>
    <?php endif; ?>

    <div class="arm-repairs-list">
        <h2>
            <?php 
            if ($technician_id) {
                $technician = get_user_by('id', $technician_id);
                printf(
                    __('Repairs Assigned to %s', 'appliance-repair-manager'),
                    esc_html($technician->display_name)
                );
            } else {
                _e('Repair History', 'appliance-repair-manager');
            }
            ?>
        </h2>

        <?php
        // Build the query based on user role and filters
        $query = "
            SELECT r.*, 
                   a.type as appliance_type, 
                   a.model, 
                   a.brand,
                   c.name as client_name,
                   c.address as client_address,
                   c.phone as client_phone,
                   u.display_name as technician_name
            FROM {$wpdb->prefix}arm_repairs r
            LEFT JOIN {$wpdb->prefix}arm_appliances a ON r.appliance_id = a.id
            LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
            LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
            WHERE 1=1
        ";

        // Add WHERE clause for technicians to see only their repairs
        if ($is_technician) {
            $query .= $wpdb->prepare(" AND r.technician_id = %d", $current_user_id);
        } elseif ($technician_id) {
            $query .= $wpdb->prepare(" AND r.technician_id = %d", $technician_id);
        }

        $query .= " ORDER BY r.created_at DESC";
        
        $repairs = $wpdb->get_results($query);
        
        if ($repairs): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Client Details', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Appliance', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Diagnosis', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Notes', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Cost', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Technician', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Status', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repairs as $repair): ?>
                        <tr class="repair-row-<?php echo esc_attr($repair->id); ?>">
                            <td>
                                <strong><?php echo esc_html($repair->client_name); ?></strong><br>
                                <?php echo esc_html($repair->client_address); ?><br>
                                <?php echo esc_html($repair->client_phone); ?>
                            </td>
                            <td>
                                <?php 
                                echo esc_html(sprintf(
                                    '%s %s - %s',
                                    $repair->brand,
                                    $repair->appliance_type,
                                    $repair->model
                                )); 
                                ?>
                            </td>
                            <td><?php echo esc_html($repair->diagnosis); ?></td>
                            <td>
                                <div class="arm-notes-container">
                                    <?php if (!empty($repair->notes)): ?>
                                        <div class="arm-notes-list">
                                            <?php 
                                            $notes_array = explode("\n", $repair->notes);
                                            foreach ($notes_array as $note): ?>
                                                <div class="arm-note">
                                                    <?php echo esc_html($note); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$is_technician || $repair->technician_id == $current_user_id): ?>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="arm-note-form">
                                            <?php wp_nonce_field('arm_add_repair_note'); ?>
                                            <input type="hidden" name="action" value="arm_add_repair_note">
                                            <input type="hidden" name="repair_id" value="<?php echo esc_attr($repair->id); ?>">
                                            <textarea name="note" class="arm-note-input" rows="2" placeholder="<?php esc_attr_e('Add a note...', 'appliance-repair-manager'); ?>"></textarea>
                                            <button type="submit" class="button button-small">
                                                <?php _e('Add Note', 'appliance-repair-manager'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <a href="#" class="button button-small view-repair-details" data-repair-id="<?php echo esc_attr($repair->id); ?>">
                                    <?php _e('View Details', 'appliance-repair-manager'); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(number_format($repair->cost, 2)); ?></td>
                            <td><?php echo esc_html($repair->technician_name); ?></td>
                            <td>
                                <span class="arm-status <?php echo esc_attr(arm_get_status_class($repair->status)); ?>">
                                    <?php echo esc_html(arm_get_status_label($repair->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($repair->status !== 'delivered'): ?>
                                    <?php if (!$is_technician): // Show technician assignment for admins ?>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline; margin-right: 10px;">
                                            <?php wp_nonce_field('arm_assign_technician'); ?>
                                            <input type="hidden" name="action" value="arm_assign_technician">
                                            <input type="hidden" name="repair_id" value="<?php echo esc_attr($repair->id); ?>">
                                            <select name="technician_id" class="arm-select2" onchange="this.form.submit()">
                                                <option value=""><?php _e('Assign Technician', 'appliance-repair-manager'); ?></option>
                                                <?php foreach ($technicians as $technician): ?>
                                                    <option value="<?php echo esc_attr($technician->ID); ?>" 
                                                            <?php selected($repair->technician_id, $technician->ID); ?>>
                                                        <?php echo esc_html($technician->display_name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!$is_technician || $repair->technician_id == $current_user_id): ?>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                            <?php wp_nonce_field('arm_update_repair_status'); ?>
                                            <input type="hidden" name="action" value="arm_update_repair_status">
                                            <input type="hidden" name="repair_id" value="<?php echo esc_attr($repair->id); ?>">
                                            <input type="hidden" name="appliance_id" value="<?php echo esc_attr($repair->appliance_id); ?>">
                                            <select name="status" class="arm-select2" onchange="this.form.submit()">
                                                <?php foreach ($repair_statuses as $status_key => $status_label): ?>
                                                    <option value="<?php echo esc_attr($status_key); ?>" 
                                                            <?php selected($repair->status, $status_key); ?>>
                                                        <?php echo esc_html($status_label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No repair records found.', 'appliance-repair-manager'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para detalles de reparación -->
<div id="repair-details-modal" class="arm-modal">
    <div id="repair-details-content"></div>
</div>