<?php
namespace ApplianceRepairManager\Core\Notes;

class RepairNote {
    private $id;
    private $repair_id;
    private $user_id;
    private $note;
    private $is_public;
    private $created_at;

    public function __construct($data) {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->repair_id = (int)$data['repair_id'];
        $this->user_id = (int)$data['user_id'];
        $this->note = $data['note'];
        $this->is_public = (bool)$data['is_public'];
        $this->created_at = isset($data['created_at']) ? $data['created_at'] : current_time('mysql');
    }

    public function save() {
        global $wpdb;
        
        $data = [
            'repair_id' => $this->repair_id,
            'user_id' => $this->user_id,
            'note' => $this->note,
            'is_public' => $this->is_public ? 1 : 0,
            'created_at' => $this->created_at
        ];

        $format = [
            '%d', // repair_id
            '%d', // user_id
            '%s', // note
            '%d', // is_public
            '%s'  // created_at
        ];

        if ($this->id > 0) {
            $result = $wpdb->update(
                $wpdb->prefix . 'arm_repair_notes',
                $data,
                ['id' => $this->id],
                $format,
                ['%d']
            );
        } else {
            $result = $wpdb->insert(
                $wpdb->prefix . 'arm_repair_notes',
                $data,
                $format
            );
            if ($result) {
                $this->id = $wpdb->insert_id;
            }
        }

        return $result !== false;
    }

    public static function get_notes_for_repair($repair_id) {
        global $wpdb;
        
        $notes = $wpdb->get_results($wpdb->prepare("
            SELECT n.*, u.display_name as author_name
            FROM {$wpdb->prefix}arm_repair_notes n
            LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID
            WHERE n.repair_id = %d
            ORDER BY n.created_at DESC
        ", $repair_id), ARRAY_A);

        return array_map(function($note) {
            return new self($note);
        }, $notes);
    }

    // Getters
    public function get_id() { return $this->id; }
    public function get_repair_id() { return $this->repair_id; }
    public function get_user_id() { return $this->user_id; }
    public function get_note() { return $this->note; }
    public function is_public() { return $this->is_public; }
    public function get_created_at() { return $this->created_at; }
}