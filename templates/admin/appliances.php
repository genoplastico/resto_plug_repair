<?php
if (!defined('ABSPATH')) {
    exit;
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
global $wpdb;

// Get selected client filter
$filter_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Add nonce field for AJAX requests
wp_nonce_field('arm_ajax_nonce', 'arm_ajax_nonce');
?>
<div class="wrap">
    <h1><?php _e('Appliances Management', 'appliance-repair-manager'); ?></h1>

    <?php if ($message === 'appliance_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Appliance added successfully.', 'appliance-repair-manager'); ?></p>
        </div>
    <?php endif; ?>

    <div class="arm-appliance-form-container">
        <h2><?php _e('Add New Appliance', 'appliance-repair-manager'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('arm_add_appliance'); ?>
            <input type="hidden" name="action" value="arm_add_appliance">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="client_id"><?php _e('Client', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <select name="client_id" id="client_id" class="regular-text arm-select2" required>
                            <option value=""><?php _e('Select Client', 'appliance-repair-manager'); ?></option>
                            <?php
                            $clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}arm_clients ORDER BY name ASC");
                            foreach ($clients as $client) {
                                echo '<option value="' . esc_attr($client->id) . '">' . esc_html($client->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="appliance_type"><?php _e('Type', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="appliance_type" id="appliance_type" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="appliance_brand"><?php _e('Brand', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="appliance_brand" id="appliance_brand" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="appliance_model"><?php _e('Model', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="appliance_model" id="appliance_model" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="serial_number"><?php _e('Serial Number', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="serial_number" id="serial_number" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="appliance_image"><?php _e('Image', 'appliance-repair-manager'); ?></label>
                    </th>
                    <td>
                        <input type="file" 
                               name="appliance_image" 
                               id="appliance_image" 
                               class="arm-image-upload"
                               accept="image/*">
                        <p class="description">
                            <?php _e('Allowed formats: JPG, PNG, GIF. Maximum size: 5MB', 'appliance-repair-manager'); ?>
                            <br>
                            <?php _e('Maximum dimensions: 4096x4096 pixels', 'appliance-repair-manager'); ?>
                        </p>
                        <div id="image-preview" class="arm-image-preview" style="display:none;">
                            <img src="" alt="Preview">
                            <button type="button" class="button button-small arm-remove-preview">
                                <?php _e('Remove', 'appliance-repair-manager'); ?>
                            </button>
                        </div>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Add Appliance', 'appliance-repair-manager')); ?>
        </form>
    </div>

    <div class="arm-appliances-list">
        <h2><?php _e('Existing Appliances', 'appliance-repair-manager'); ?></h2>
        
        <!-- Filter by client -->
        <div class="arm-filter-section">
            <form method="get" action="">
                <input type="hidden" name="page" value="arm-appliances">
                <select name="client_id" class="arm-select2" onchange="this.form.submit()">
                    <option value=""><?php _e('All Clients', 'appliance-repair-manager'); ?></option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo esc_attr($client->id); ?>" <?php selected($filter_client_id, $client->id); ?>>
                            <?php echo esc_html($client->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php
        $query = "
            SELECT a.*, c.name as client_name, c.email as client_email 
            FROM {$wpdb->prefix}arm_appliances a 
            LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id 
            WHERE 1=1
        ";

        if ($filter_client_id) {
            $query .= $wpdb->prepare(" AND a.client_id = %d", $filter_client_id);
        }

        $query .= " ORDER BY a.created_at DESC";
        
        $appliances = $wpdb->get_results($query);
        
        if ($appliances): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Client', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Type', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Brand', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Model', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Serial Number', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Status', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appliances as $appliance): 
                        // Generate unique access token for this appliance
                        $access_token = wp_hash($appliance->id . $appliance->client_email . wp_salt());
                        $public_url = add_query_arg([
                            'arm_action' => 'view_appliance',
                            'id' => $appliance->id,
                            'token' => $access_token
                        ], home_url());
                    ?>
                        <tr>
                            <td><?php echo esc_html($appliance->client_name); ?></td>
                            <td>
                                <?php 
                                if (!empty($appliance->image_id) && $image = wp_get_attachment_image_src($appliance->image_id, 'thumbnail')): ?>
                                    <a href="<?php echo esc_url($image[0]); ?>" class="arm-image-preview" target="_blank">
                                        <img src="<?php echo esc_url($image[0]); ?>" 
                                             alt="<?php echo esc_attr($appliance->type); ?>"
                                             class="arm-appliance-thumbnail"
                                             data-full-size="<?php echo esc_url(wp_get_attachment_url($appliance->image_id)); ?>">
                                    </a>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" 
                                          class="arm-delete-image-form" style="display:inline;">
                                        <?php wp_nonce_field('arm_delete_appliance_image'); ?>
                                        <input type="hidden" name="action" value="arm_delete_appliance_image">
                                        <input type="hidden" name="appliance_id" value="<?php echo esc_attr($appliance->id); ?>">
                                        <button type="submit" class="button button-small arm-delete-image" 
                                                onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this image?', 'appliance-repair-manager'); ?>')">
                                            <?php _e('Delete Image', 'appliance-repair-manager'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php echo esc_html($appliance->type); ?>
                            </td>
                            <td><?php echo esc_html($appliance->brand); ?></td>
                            <td><?php echo esc_html($appliance->model); ?></td>
                            <td><?php echo esc_html($appliance->serial_number); ?></td>
                            <td>
                                <span class="arm-status <?php echo esc_attr(arm_get_status_class($appliance->status)); ?>">
                                    <?php echo esc_html(arm_get_status_label($appliance->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small view-appliance-history" 
                                        data-appliance-id="<?php echo esc_attr($appliance->id); ?>">
                                    <?php _e('View History', 'appliance-repair-manager'); ?>
                                </button>
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
            <p><?php _e('No appliances found.', 'appliance-repair-manager'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para historial de aparato -->
<div id="appliance-history-modal" class="arm-modal">
    <div id="appliance-history-content" class="arm-modal-dialog"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Copiar URL pública al portapapeles
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
<style>
.arm-image-preview {
    margin-top: 10px;
    max-width: 200px;
    position: relative;
}

.arm-image-preview img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.arm-remove-preview {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255,255,255,0.9) !important;
}

.arm-appliance-thumbnail {
    max-width: 50px;
    height: auto;
    margin-right: 8px;
    vertical-align: middle;
    border-radius: 4px;
    border: 1px solid #ddd;
    padding: 2px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    transition: all 0.2s ease-in-out;
}

.arm-image-preview:hover .arm-appliance-thumbnail {
    transform: scale(1.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}