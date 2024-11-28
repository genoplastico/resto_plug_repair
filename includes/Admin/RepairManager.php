<?php
namespace ApplianceRepairManager\Admin;

class RepairManager {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_repairs_menu']);
        add_action('admin_post_arm_add_repair', [$this, 'handle_add_repair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handle_update_repair_status']);
        add_action('admin_post_arm_assign_technician', [$this, 'handle_assign_technician']);
        add_action('wp_ajax_arm_add_note_ajax', [$this, 'handle_add_note_ajax']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_nopriv_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_arm_get_appliance_history', [$this, 'get_appliance_history']);
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'get_client_appliances']);
    }

    public function add_repairs_menu() {
        add_submenu_page(
            'appliance-repair-manager',
            __('Repairs', 'appliance-repair-manager'),
            __('Repairs', 'appliance-repair-manager'),
            'manage_options',
            'arm-repairs',
            [$this, 'render_repairs_page']
        );
    }

    public function render_repairs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include ARM_PLUGIN_DIR . 'templates/admin/repairs.php';
    }

    public function handle_add_repair() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_add_repair');

        $repair_data = [
            'appliance_id' => intval($_POST['appliance_id']),
            'technician_id' => intval($_POST['technician_id']),
            'diagnosis' => sanitize_textarea_field($_POST['diagnosis']),
            'parts_used' => sanitize_textarea_field($_POST['parts_used']),
            'cost' => floatval($_POST['cost']),
            'status' => 'pending'
        ];

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'arm_repairs',
            $repair_data,
            ['%d', '%d', '%s', '%s', '%f', '%s']
        );

        wp_redirect(add_query_arg([
            'page' => 'arm-repairs',
            'message' => 'repair_added'
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_update_repair_status() {
        if (!current_user_can('edit_arm_repairs')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_update_repair_status');

        $repair_id = intval($_POST['repair_id']);
        $status = sanitize_text_field($_POST['status']);
        $appliance_id = intval($_POST['appliance_id']);

        global $wpdb;
        
        // Update repair status
        $wpdb->update(
            $wpdb->prefix . 'arm_repairs',
            ['status' => $status],
            ['id' => $repair_id],
            ['%s'],
            ['%d']
        );

        // Update appliance status if repair is completed or delivered
        if (in_array($status, ['completed', 'delivered'])) {
            $wpdb->update(
                $wpdb->prefix . 'arm_appliances',
                ['status' => $status],
                ['id' => $appliance_id],
                ['%s'],
                ['%d']
            );
        }

        wp_redirect(add_query_arg([
            'page' => 'arm-repairs',
            'message' => 'status_updated'
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_assign_technician() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('arm_assign_technician');

        $repair_id = intval($_POST['repair_id']);
        $technician_id = intval($_POST['technician_id']);

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'arm_repairs',
            ['technician_id' => $technician_id],
            ['id' => $repair_id],
            ['%d'],
            ['%d']
        );

        wp_redirect(add_query_arg([
            'page' => 'arm-repairs',
            'message' => 'technician_assigned'
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_add_note_ajax() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        if (!current_user_can('edit_arm_repairs')) {
            wp_send_json_error(['message' => __('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager')]);
            return;
        }

        $repair_id = intval($_POST['repair_id']);
        $note = sanitize_textarea_field($_POST['note']);
        $is_public = isset($_POST['is_public']) && $_POST['is_public'] === '1' ? 1 : 0;

        if (empty($note)) {
            wp_send_json_error(['message' => __('Note cannot be empty.', 'appliance-repair-manager')]);
            return;
        }

        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'arm_repair_notes',
            [
                'repair_id' => $repair_id,
                'user_id' => get_current_user_id(),
                'note' => $note,
                'is_public' => $is_public,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%d', '%s']
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Error adding note.', 'appliance-repair-manager')]);
            return;
        }

        $note_id = $wpdb->insert_id;
        $user = wp_get_current_user();
        
        $note_html = sprintf(
            '<div class="arm-note %s">
                <div class="arm-note-header">
                    <span class="arm-note-author">%s</span>
                    <span class="arm-note-date">%s</span>
                    %s
                </div>
                <div class="arm-note-content">%s</div>
            </div>',
            $is_public ? 'arm-note-public' : 'arm-note-private',
            esc_html($user->display_name),
            esc_html(current_time(get_option('date_format') . ' ' . get_option('time_format'))),
            $is_public ? '<span class="arm-note-visibility">' . __('Public', 'appliance-repair-manager') . '</span>' : '',
            nl2br(esc_html($note))
        );

        wp_send_json_success([
            'note_html' => $note_html,
            'note_id' => $note_id
        ]);
    }

    public function get_repair_details() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        $repair_id = intval($_POST['repair_id']);
        $is_public = isset($_POST['is_public']) && $_POST['is_public'] === 'true';

        if (!$is_public && !current_user_can('edit_arm_repairs')) {
            wp_send_json_error(['message' => __('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager')]);
            return;
        }

        global $wpdb;
        $repair = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, 
                   a.type as appliance_type,
                   a.brand,
                   a.model,
                   c.name as client_name,
                   u.display_name as technician_name
            FROM {$wpdb->prefix}arm_repairs r
            LEFT JOIN {$wpdb->prefix}arm_appliances a ON r.appliance_id = a.id
            LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
            LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
            WHERE r.id = %d
        ", $repair_id));

        if (!$repair) {
            wp_send_json_error(['message' => __('Repair not found.', 'appliance-repair-manager')]);
            return;
        }

        ob_start();
        include ARM_PLUGIN_DIR . 'templates/admin/modals/repair-details.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function get_appliance_history() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        $appliance_id = intval($_POST['appliance_id']);

        global $wpdb;
        $appliance = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, c.name as client_name
            FROM {$wpdb->prefix}arm_appliances a
            LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
            WHERE a.id = %d
        ", $appliance_id));

        if (!$appliance) {
            wp_send_json_error(['message' => __('Appliance not found.', 'appliance-repair-manager')]);
            return;
        }

        $repairs = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, u.display_name as technician_name
            FROM {$wpdb->prefix}arm_repairs r
            LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
            WHERE r.appliance_id = %d
            ORDER BY r.created_at DESC
        ", $appliance_id));

        ob_start();
        include ARM_PLUGIN_DIR . 'templates/admin/modals/appliance-history.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function get_client_appliances() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        $client_id = intval($_POST['client_id']);

        global $wpdb;
        $appliances = $wpdb->get_results($wpdb->prepare("
            SELECT id, type, brand, model 
            FROM {$wpdb->prefix}arm_appliances 
            WHERE client_id = %d 
            ORDER BY type ASC
        ", $client_id));

        wp_send_json_success($appliances);
    }

    public function get_repair_notes($repair_id, $include_private = true) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT n.*, u.display_name as author_name
            FROM {$wpdb->prefix}arm_repair_notes n
            LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID
            WHERE n.repair_id = %d
        ", $repair_id);

        if (!$include_private) {
            $query .= " AND n.is_public = 1";
        }

        $query .= " ORDER BY n.created_at DESC";

        return $wpdb->get_results($query);
    }
}