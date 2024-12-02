<?php
namespace ApplianceRepairManager\Core\Notes;

class NotesManager {
    private static $instance = null;

    private function __construct() {}

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_note($repair_id, $note_text, $is_public = false) {
        if (!current_user_can('edit_arm_repairs')) {
            return false;
        }

        $note = new RepairNote([
            'repair_id' => $repair_id,
            'user_id' => get_current_user_id(),
            'note' => $note_text,
            'is_public' => $is_public
        ]);

        return $note->save();
    }

    public function get_notes($repair_id) {
        return RepairNote::get_notes_for_repair($repair_id);
    }
}