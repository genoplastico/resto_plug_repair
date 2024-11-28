<?php
namespace ApplianceRepairManager\Admin;

class RepairManager {
    public function __construct() {
        add_action('arm_admin_menu', [$this, 'add_repairs_menu']);
        add_action('admin_post_arm_add_repair', [$this, 'handle_add_repair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handle_update_repair_status']);
        add_action('admin_post_arm_assign_technician', [$this, 'handle_assign_technician']);
        add_action('wp_ajax_arm_add_note_ajax', [$this, 'handle_add_note_ajax']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_arm_get_appliance_history', [$this, 'get_appliance_history']);
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'get_client_appliances']);
    }

    // ... (otros métodos permanecen igual)

    public function handle_add_note_ajax() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        if (!current_user_can('edit_arm_repairs')) {
            wp_send_json_error(['message' => __('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager')]);
            return;
        }

        $repair_id = intval($_POST['repair_id']);
        $note = sanitize_textarea_field($_POST['note']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;

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
                'is_public' => $is_public
            ],
            ['%d', '%d', '%s', '%d']
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