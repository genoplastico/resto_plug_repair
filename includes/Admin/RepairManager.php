<?php
namespace ApplianceRepairManager\Admin;

use ApplianceRepairManager\Core\Notes\NotesManager;

class RepairManager {
    private $notes_manager;

    public function __construct() {
        $this->notes_manager = NotesManager::getInstance();
        
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'get_client_appliances']);
        add_action('admin_post_arm_add_repair', [$this, 'handle_add_repair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handle_update_repair_status']);
        add_action('admin_post_arm_assign_technician', [$this, 'handle_assign_technician']);
        add_action('wp_ajax_arm_add_note', [$this, 'handle_add_note']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_nopriv_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_arm_get_appliance_history', [$this, 'get_appliance_history']);
    }

    public function handle_add_note() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');

        if (!current_user_can('edit_arm_repairs')) {
            wp_send_json_error(['message' => __('You do not have sufficient permissions to perform this action.', 'appliance-repair-manager')]);
            return;
        }

        $repair_id = intval($_POST['repair_id']);
        $note = sanitize_textarea_field($_POST['note']);
        $is_public = isset($_POST['is_public']) ? true : false;

        if ($this->notes_manager->add_note($repair_id, $note, $is_public)) {
            $notes = $this->notes_manager->get_notes($repair_id);
            ob_start();
            include ARM_PLUGIN_DIR . 'templates/admin/partials/notes-list.php';
            $html = ob_get_clean();
            
            wp_send_json_success([
                'message' => __('Note added successfully.', 'appliance-repair-manager'),
                'html' => $html
            ]);
        } else {
            wp_send_json_error(['message' => __('Error adding note.', 'appliance-repair-manager')]);
        }
    }

    // ... resto de los métodos permanecen igual ...
}