<?php
namespace ApplianceRepairManager\Core\Ajax;

use ApplianceRepairManager\Core\Debug\ErrorLogger;

class NotesHandler {
    private $logger;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = ErrorLogger::getInstance();
        
        add_action('wp_ajax_arm_add_note', [$this, 'addNote']);
        add_action('wp_ajax_arm_delete_note', [$this, 'deleteNote']);
    }

    public function addNote() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
            $note_text = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
            $is_public = isset($_POST['is_public']) ? (bool)$_POST['is_public'] : false;

            if (!$repair_id || empty($note_text)) {
                throw new \Exception('Invalid parameters');
            }

            // Insert note
            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'arm_repair_notes',
                [
                    'repair_id' => $repair_id,
                    'user_id' => get_current_user_id(),
                    'note' => $note_text,
                    'is_public' => $is_public,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%d', '%s']
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            // Get updated notes list
            $notes = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT n.*, u.display_name as author_name
                FROM {$this->wpdb->prefix}arm_repair_notes n
                LEFT JOIN {$this->wpdb->users} u ON n.user_id = u.ID
                WHERE n.repair_id = %d
                ORDER BY n.created_at DESC",
                $repair_id
            ));

            ob_start();
            foreach ($notes as $note) {
                include ARM_PLUGIN_DIR . 'templates/admin/partials/note-item.php';
            }
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_add_note', $e->getMessage(), [
                'repair_id' => $repair_id ?? null,
                'wpdb_error' => $this->wpdb->last_error ?? null
            ]);
            
            wp_send_json_error([
                'message' => __('Error adding note', 'appliance-repair-manager'),
                'error' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }

    public function deleteNote() {
        try {
            check_ajax_referer('arm_ajax_nonce', 'nonce');

            if (!current_user_can('edit_arm_repairs')) {
                throw new \Exception('Insufficient permissions');
            }

            $note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;
            if (!$note_id) {
                throw new \Exception('Invalid note ID');
            }

            // Get note info before deletion for verification
            $note = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}arm_repair_notes WHERE id = %d",
                $note_id
            ));

            if (!$note) {
                throw new \Exception('Note not found');
            }

            // Only allow users to delete their own notes unless they're admins
            if (!current_user_can('manage_options') && $note->user_id !== get_current_user_id()) {
                throw new \Exception(__('You can only delete your own notes.', 'appliance-repair-manager'));
            }

            // Delete the note
            $result = $this->wpdb->delete(
                $this->wpdb->prefix . 'arm_repair_notes',
                ['id' => $note_id],
                ['%d']
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            // Get updated notes list
            $notes = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT n.*, u.display_name as author_name
                FROM {$this->wpdb->prefix}arm_repair_notes n
                LEFT JOIN {$this->wpdb->users} u ON n.user_id = u.ID
                WHERE n.repair_id = %d
                ORDER BY n.created_at DESC",
                $note->repair_id
            ));

            ob_start();
            foreach ($notes as $note) {
                include ARM_PLUGIN_DIR . 'templates/admin/partials/note-item.php';
            }
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->logger->logAjaxError('arm_delete_note', $e->getMessage(), [
                'note_id' => $note_id ?? null,
                'wpdb_error' => $this->wpdb->last_error ?? null
            ]);
            
            wp_send_json_error([
                'message' => __('Error deleting note', 'appliance-repair-manager'),
                'message' => $e->getMessage()
            ]);
        }
    }
}