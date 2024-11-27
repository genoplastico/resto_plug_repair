<?php
if (!defined('ABSPATH')) {
    exit;
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
?>
<div class="wrap">
    <h1><?php _e('Technicians Management', 'appliance-repair-manager'); ?></h1>

    <?php if ($message === 'technician_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Technician added successfully. Login credentials have been sent by email.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php elseif ($message === 'technician_updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Technician updated successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php elseif ($message === 'technician_deleted'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Technician deleted successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php endif; ?>

    <div class="arm-technician-form-container">
        <h2><?php _e('Add New Technician', 'appliance-repair-manager'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="arm-form">
            <?php wp_nonce_field('arm_add_technician'); ?>
            <input type="hidden" name="action" value="arm_add_technician">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="username"><?php _e('Username', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="username" id="username" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email"><?php _e('Email', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="email" id="email" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="first_name"><?php _e('First Name', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="first_name" id="first_name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="last_name"><?php _e('Last Name', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="last_name" id="last_name" class="regular-text" required>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Add Technician', 'appliance-repair-manager')); ?>
        </form>
    </div>

    <div class="arm-technicians-list">
        <h2><?php _e('Existing Technicians', 'appliance-repair-manager'); ?></h2>
        <?php
        $technicians = get_users(['role' => 'arm_technician']);
        
        if ($technicians): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Username', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Email', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Active Repairs', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($technicians as $technician): 
                        global $wpdb;
                        $active_repairs = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}arm_repairs 
                            WHERE technician_id = %d AND status IN ('pending', 'in_progress')",
                            $technician->ID
                        ));
                    ?>
                        <tr>
                            <td><?php echo esc_html($technician->first_name . ' ' . $technician->last_name); ?></td>
                            <td><?php echo esc_html($technician->user_login); ?></td>
                            <td><?php echo esc_html($technician->user_email); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['page' => 'arm-repairs', 'technician_id' => $technician->ID], admin_url('admin.php'))); ?>">
                                    <?php echo esc_html($active_repairs); ?>
                                </a>
                            </td>
                            <td>
                                <button type="button" class="button edit-technician" 
                                        data-id="<?php echo esc_attr($technician->ID); ?>"
                                        data-first-name="<?php echo esc_attr($technician->first_name); ?>"
                                        data-last-name="<?php echo esc_attr($technician->last_name); ?>"
                                        data-email="<?php echo esc_attr($technician->user_email); ?>">
                                    <?php _e('Edit', 'appliance-repair-manager'); ?>
                                </button>
                                <?php if ($active_repairs == 0): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                        <?php wp_nonce_field('arm_delete_technician'); ?>
                                        <input type="hidden" name="action" value="arm_delete_technician">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($technician->ID); ?>">
                                        <button type="submit" class="button delete-technician" 
                                                onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this technician?', 'appliance-repair-manager'); ?>')">
                                            <?php _e('Delete', 'appliance-repair-manager'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No technicians found.', 'appliance-repair-manager'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Edit Technician Modal -->
    <div id="edit-technician-modal" class="arm-modal" style="display:none;">
        <div class="arm-modal-content">
            <h2><?php _e('Edit Technician', 'appliance-repair-manager'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="arm-form">
                <?php wp_nonce_field('arm_update_technician'); ?>
                <input type="hidden" name="action" value="arm_update_technician">
                <input type="hidden" name="user_id" id="edit-user-id">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit-first-name"><?php _e('First Name', 'appliance-repair-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="first_name" id="edit-first-name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit-last-name "><?php _e('Last Name', 'appliance-repair-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="last_name" id="edit-last-name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit-email"><?php _e('Email', 'appliance-repair-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="email" id="edit-email" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                
                <div class="arm-modal-footer">
                    <button type="button" class="button" onclick="closeEditModal()"><?php _e('Cancel', 'appliance-repair-manager'); ?></button>
                    <?php submit_button(__('Update Technician', 'appliance-repair-manager'), 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.arm-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.arm-modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    border-radius: 4px;
}

.arm-modal-footer {
    margin-top: 20px;
    text-align: right;
}

.arm-modal-footer .button {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.edit-technician').click(function() {
        var button = $(this);
        $('#edit-user-id').val(button.data('id'));
        $('#edit-first-name').val(button.data('first-name'));
        $('#edit-last-name').val(button.data('last-name'));
        $('#edit-email').val(button.data('email'));
        $('#edit-technician-modal').show();
    });
});

function closeEditModal() {
    document.getElementById('edit-technician-modal').style.display = 'none';
}
</script>