<?php
if (!defined('ABSPATH')) {
    exit;
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
?>
<div class="wrap">
    <h1><?php _e('Clients Management', 'appliance-repair-manager'); ?></h1>

    <?php if ($message === 'client_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Client added successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php endif; ?>

    <div class="arm-client-form-container">
        <h2><?php _e('Add New Client', 'appliance-repair-manager'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('arm_add_client'); ?>
            <input type="hidden" name="action" value="arm_add_client">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="client_name"><?php _e('Name', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="client_name" id="client_name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="client_email"><?php _e('Email', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="client_email" id="client_email" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="client_phone"><?php _e('Phone', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="tel" name="client_phone" id="client_phone" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="client_address"><?php _e('Address', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <textarea name="client_address" id="client_address" class="large-text" rows="3"></textarea>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Add Client', 'appliance-repair-manager')); ?>
        </form>
    </div>

    <div class="arm-clients-list">
        <h2><?php _e('Existing Clients', 'appliance-repair-manager'); ?></h2>
        <?php
        global $wpdb;
        $clients = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}arm_clients ORDER BY created_at DESC");
        
        if ($clients): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Email', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Phone', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Appliances', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Created', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): 
                        // Generate unique access token for this client
                        $access_token = wp_hash($client->id . $client->email . wp_salt());
                        $public_url = add_query_arg([
                            'arm_action' => 'view_client_appliances',
                            'client_id' => $client->id,
                            'token' => $access_token
                        ], home_url());
                    ?>
                        <tr>
                            <td><?php echo esc_html($client->name); ?></td>
                            <td><?php echo esc_html($client->email); ?></td>
                            <td><?php echo esc_html($client->phone); ?></td>
                            <td><?php
                                $appliance_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}arm_appliances WHERE client_id = %d",
                                    $client->id
                                ));
                                echo esc_html($appliance_count);
                            ?></td>
                            <td><?php echo esc_html($client->created_at); ?></td>
                            <td>
                                <button type="button" class="button button-small copy-public-url" 
                                        data-url="<?php echo esc_url($public_url); ?>">
                                    <?php _e('Copy Public URL', 'appliance-repair-manager'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No clients found.', 'appliance-repair-manager'); ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.copy-public-url').click(function() {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            alert(armL10n.publicUrlCopied);
        }).catch(function() {
            // Fallback para navegadores que no soportan clipboard API
            var temp = $("<input>");
            $("body").append(temp);
            temp.val(url).select();
            document.execCommand("copy");
            temp.remove();
            alert(armL10n.publicUrlCopied);
        });
    });
});
</script>