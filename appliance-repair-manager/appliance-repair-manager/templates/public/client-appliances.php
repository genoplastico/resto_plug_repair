<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Verificar token de acceso
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

$client = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}arm_clients WHERE id = %d
", $client_id));

if (!$client || wp_hash($client->id . $client->email . wp_salt()) !== $token) {
    wp_die(__('Invalid or expired access token.', 'appliance-repair-manager'));
}

get_header();

// Cargar estilos del admin
wp_enqueue_style('arm-admin-styles', ARM_PLUGIN_URL . 'assets/css/admin.css', [], ARM_VERSION);
?>

<div class="arm-public-view">
    <div class="wrap">
        <h1><?php echo esc_html(sprintf(
            __('Appliances for %s', 'appliance-repair-manager'),
            $client->name
        )); ?></h1>

        <?php
        $appliances = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}arm_appliances WHERE client_id = %d ORDER BY created_at DESC
        ", $client_id));
        
        if ($appliances): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Type', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Brand', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Model', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Serial Number', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Status', 'appliance-repair-manager'); ?></th>
                        <th><?php _e('Actions', 'appliance-repair-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appliances as $appliance): ?>
                        <tr>
                            <td><?php echo esc_html($appliance->type); ?></td>
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
    <div id="appliance-history-content" class="arm-modal-content"></div>
</div>

<!-- Agregar nonce field para las peticiones AJAX -->
<?php wp_nonce_field('arm_ajax_nonce', 'arm_ajax_nonce'); ?>

<script>
jQuery(document).ready(function($) {
    // Manejar clic en el bot¨®n de ver historial
    $('.view-appliance-history').click(function(e) {
        e.preventDefault();
        var applianceId = $(this).data('appliance-id');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'arm_get_appliance_history',
                appliance_id: applianceId,
                nonce: $('#arm_ajax_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#appliance-history-content').html(response.data.html);
                    $('#appliance-history-modal').fadeIn(300);
                } else {
                    alert(armL10n.errorLoadingHistory);
                }
            },
            error: function() {
                alert(armL10n.errorLoadingHistory);
            }
        });
    });

    // Cerrar modal al hacer clic en el bot¨®n de cerrar
    $(document).on('click', '.arm-modal-close', function() {
        $(this).closest('.arm-modal').fadeOut(300);
    });

    // Cerrar modal al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('arm-modal')) {
            $('.arm-modal').fadeOut(300);
        }
    });

    // Prevenir que el clic dentro del modal lo cierre
    $(document).on('click', '.arm-modal-content', function(e) {
        e.stopPropagation();
    });

    // Tecla ESC para cerrar modales
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            $('.arm-modal').fadeOut(300);
        }
    });
});
</script>

<?php
// Cargar scripts necesarios
wp_enqueue_script('jquery');
wp_enqueue_script('arm-admin-scripts', ARM_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], ARM_VERSION, true);
wp_localize_script('arm-admin-scripts', 'armL10n', [
    'errorLoadingHistory' => __('Error loading appliance history.', 'appliance-repair-manager')
]);

get_footer();
?>