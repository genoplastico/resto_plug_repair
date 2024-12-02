<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the count of repairs by status
 *
 * @param string $status The status to count
 * @return int The number of repairs with the given status
 */
function arm_get_repairs_count($status) {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}arm_repairs WHERE status = %s",
        $status
    ));
    return intval($count);
}

/**
 * Get recent repairs with related data
 *
 * @param int $limit Maximum number of repairs to return
 * @return array Array of repair objects with related data
 */
function arm_get_recent_repairs($limit = 5) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("
        SELECT 
            r.*,
            a.type as appliance_type,
            c.name as client_name,
            u.display_name as technician_name
        FROM {$wpdb->prefix}arm_repairs r
        LEFT JOIN {$wpdb->prefix}arm_appliances a ON r.appliance_id = a.id
        LEFT JOIN {$wpdb->prefix}arm_clients c ON a.client_id = c.id
        LEFT JOIN {$wpdb->users} u ON r.technician_id = u.ID
        ORDER BY r.created_at DESC
        LIMIT %d",
        $limit
    ));
}

/**
 * Get status label
 *
 * @param string $status The status key
 * @return string The formatted status label
 */
function arm_get_status_label($status) {
    return \ApplianceRepairManager\Core\Status\RepairStatus::getInstance()->getLabel($status);
}

/**
 * Get status CSS class
 *
 * @param string $status The status key
 * @return string The CSS class
 */
function arm_get_status_class($status) {
    return \ApplianceRepairManager\Core\Status\RepairStatus::getInstance()->getClass($status);
}

/**
 * Render a status badge
 *
 * @param string $status The status to render
 * @return string HTML for the status badge
 */
function arm_render_status_badge($status) {
    $renderer = new \ApplianceRepairManager\Core\Status\StatusRenderer();
    return $renderer->renderStatusBadge($status);
}

/**
 * Render a status select dropdown
 *
 * @param string $current_status Current status value
 * @param string $name Form field name
 * @param string $id Form field ID
 * @param string $class CSS classes
 * @return string HTML for the select dropdown
 */
function arm_render_status_select($current_status, $name = 'status', $id = '', $class = '') {
    $renderer = new \ApplianceRepairManager\Core\Status\StatusRenderer();
    return $renderer->renderStatusSelect($current_status, $name, $id, $class);
}