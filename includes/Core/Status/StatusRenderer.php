<?php
namespace ApplianceRepairManager\Core\Status;

class StatusRenderer {
    private $status_manager;

    public function __construct() {
        $this->status_manager = RepairStatus::getInstance();
    }

    public function renderStatusBadge($status) {
        $class = $this->status_manager->getClass($status);
        $label = $this->status_manager->getLabel($status);
        
        return sprintf(
            '<span class="arm-status %s">%s</span>',
            esc_attr($class),
            esc_html($label)
        );
    }

    public function renderStatusSelect($current_status, $name = 'status', $id = '', $class = '') {
        $statuses = $this->status_manager->getStatuses();
        $id = $id ?: $name;
        $class = $class ?: 'arm-select2';

        $output = sprintf('<select name="%s" id="%s" class="%s">', 
            esc_attr($name),
            esc_attr($id),
            esc_attr($class)
        );

        foreach ($statuses as $status_key => $status_label) {
            $output .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($status_key),
                selected($current_status, $status_key, false),
                esc_html($status_label)
            );
        }

        $output .= '</select>';
        return $output;
    }
}