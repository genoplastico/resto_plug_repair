<?php
namespace ApplianceRepairManager\Admin;

use ApplianceRepairManager\Core\Notes\NotesManager;
use ApplianceRepairManager\Core\Status\RepairStatusManager;
use ApplianceRepairManager\Core\Debug\Logger;

class RepairManager {
    private $notes_manager;
    private $status_manager;
    private $logger;

    public function __construct() {
        $this->notes_manager = NotesManager::getInstance();
        $this->status_manager = RepairStatusManager::getInstance();
        $this->logger = Logger::getInstance();
        
        add_action('admin_menu', [$this, 'add_repairs_menu']);
        add_action('wp_ajax_arm_get_client_appliances', [$this, 'get_client_appliances']);
        add_action('admin_post_arm_add_repair', [$this, 'handle_add_repair']);
        add_action('admin_post_arm_update_repair_status', [$this, 'handle_update_repair_status']);
        add_action('admin_post_arm_assign_technician', [$this, 'handle_assign_technician']);
        add_action('wp_ajax_arm_add_note', [$this, 'handle_add_note']);
        add_action('wp_ajax_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_nopriv_arm_get_repair_details', [$this, 'get_repair_details']);
        add_action('wp_ajax_arm_get_appliance_history', [$this, 'get_appliance_history']);
    }

    // ... otros métodos permanecen igual ...

    public function handle_add_note() {
        try {
            $this->logger->log('Iniciando handle_add_note', $_POST);

            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            if (empty($_POST['repair_id']) || empty($_POST['note'])) {
                throw new \Exception('Missing required fields');
            }

            $repair_id = intval($_POST['repair_id']);
            $note = sanitize_textarea_field($_POST['note']);
            $is_public = isset($_POST['is_public']) ? true : false;

            $this->logger->log('Agregando nota', [
                'repair_id' => $repair_id,
                'note_length' => strlen($note),
                'is_public' => $is_public
            ]);

            if (!$this->notes_manager->add_note($repair_id, $note, $is_public)) {
                throw new \Exception('Failed to add note');
            }

            $notes = $this->notes_manager->get_notes($repair_id);
            
            $this->logger->log('Nota agregada exitosamente', [
                'repair_id' => $repair_id,
                'notes_count' => count($notes)
            ]);

            ob_start();
            include ARM_PLUGIN_DIR . 'templates/admin/partials/notes-list.php';
            $html = ob_get_clean();
            
            wp_send_json_success([
                'message' => __('Note added successfully.', 'appliance-repair-manager'),
                'html' => $html
            ]);

        } catch (\Exception $e) {
            $this->logger->log('Error en handle_add_note', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            wp_send_json_error([
                'message' => __('Error adding note.', 'appliance-repair-manager'),
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }
}