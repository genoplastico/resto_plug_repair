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
            notes longtext,
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_clients);
        dbDelta($sql_appliances);
        dbDelta($sql_repairs);

        // Check if notes column exists and add it if it doesn't
        $repair_table = $wpdb->prefix . 'arm_repairs';
        $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE table_name = '$repair_table' AND column_name = 'notes'");
        
        if(empty($row)) {
            $wpdb->query("ALTER TABLE $repair_table ADD COLUMN notes longtext AFTER parts_used");
        }
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
                'pending' => __('Pending Review', 'appliance-repair-manager'),
                'in_progress' => __('In Repair', 'appliance-repair-manager'),
                'completed' => __('Repaired', 'appliance-repair-manager'),
                'delivered' => __('Delivered', 'appliance-repair-manager'),
            ];
            update_option('arm_repair_statuses', $statuses);
        }
    }
}