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
 * Format a repair status for display
 *
 * @param string $status The status key
 * @return string The formatted status label
 */
function arm_get_status_label($status) {
    $statuses = get_option('arm_repair_statuses', []);
    return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
}

/**
 * Get CSS class for a repair status
 *
 * @param string $status The status key
 * @return string The CSS class
 */
function arm_get_status_class($status) {
    return 'arm-status-' . sanitize_html_class($status);
}