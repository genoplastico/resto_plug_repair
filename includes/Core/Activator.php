<?php
namespace ApplianceRepairManager\Core;

class Activator {
    public static function activate() {
        self::create_tables();
        self::setup_roles();
        self::setup_repair_statuses();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Images table
        $sql_images = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}arm_images (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            reference_id bigint(20) NOT NULL,
            url varchar(255) NOT NULL,
            thumbnail_url varchar(255) NOT NULL,
            public_id varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY reference_id (reference_id)
        ) $charset_collate;";

        // Clients table
        $sql_clients = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}arm_clients (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            address text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset_collate;";

        // Appliances table
        $sql_appliances = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}arm_appliances (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            client_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            brand varchar(50) NOT NULL,
            model varchar(50) NOT NULL,
            serial_number varchar(50),
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY client_id (client_id),
            KEY status (status)
        ) $charset_collate;";

        // Repairs table
        $sql_repairs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}arm_repairs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            appliance_id bigint(20) NOT NULL,
            technician_id bigint(20) NOT NULL,
            diagnosis text NOT NULL,
            parts_used text,
            cost decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'pending',
            started_at datetime,
            completed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY appliance_id (appliance_id),
            KEY technician_id (technician_id),
            KEY status (status)
        ) $charset_collate;";

        // Repair Notes table
        $sql_repair_notes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}arm_repair_notes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            repair_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            note text NOT NULL,
            is_public tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY repair_id (repair_id),
            KEY user_id (user_id),
            KEY is_public (is_public)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_images);
        dbDelta($sql_clients);
        dbDelta($sql_appliances);
        dbDelta($sql_repairs);
        dbDelta($sql_repair_notes);
    }

    private static function setup_roles() {
        // Add Technician role
        add_role('arm_technician', __('Technician', 'appliance-repair-manager'), [
            'read' => true,
            'edit_arm_repairs' => true,
            'view_arm_repairs' => true,
        ]);

        // Add capabilities to administrator
        $admin = get_role('administrator');
        $admin->add_cap('edit_arm_repairs');
        $admin->add_cap('view_arm_repairs');
        $admin->add_cap('manage_arm_settings');
    }

    private static function setup_repair_statuses() {
        if (!get_option('arm_repair_statuses')) {
            $statuses = [
                'pending' => __('Pendiente de Revisión', 'appliance-repair-manager'),
                'in_progress' => __('En Reparación', 'appliance-repair-manager'),
                'completed' => __('Reparado', 'appliance-repair-manager'),
                'delivered' => __('Entregado', 'appliance-repair-manager'),
            ];
            update_option('arm_repair_statuses', $statuses);
        }
    }
}