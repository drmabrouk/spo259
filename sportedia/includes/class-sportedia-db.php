<?php
if (!defined('ABSPATH')) exit;

class Sportedia_DB {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Branches Table
        $table_branches = $wpdb->prefix . 'sportedia_branches';
        $sql_branches = "CREATE TABLE $table_branches (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_name varchar(191) NOT NULL,
            code varchar(50) DEFAULT '' NOT NULL,
            address text DEFAULT '',
            phone varchar(50) DEFAULT '',
            email varchar(100) DEFAULT '',
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 2. User Branches Mapping Table (Multi-branch)
        $table_user_branches = $wpdb->prefix . 'sportedia_user_branches';
        $sql_user_branches = "CREATE TABLE $table_user_branches (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            branch_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 3. Subscriptions Table
        $table_subscriptions = $wpdb->prefix . 'sportedia_subscriptions';
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_number varchar(100) DEFAULT '' NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            branch_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            program_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            plan_name varchar(191) NOT NULL,
            subscription_type varchar(50) DEFAULT 'monthly' NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            price decimal(10,2) DEFAULT '0.00' NOT NULL,
            notes text DEFAULT '',
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 4. Programs Table
        $table_programs = $wpdb->prefix . 'sportedia_programs';
        $sql_programs = "CREATE TABLE $table_programs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            coach_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            program_name varchar(191) NOT NULL,
            category varchar(100) DEFAULT '' NOT NULL,
            schedule varchar(255) DEFAULT '' NOT NULL,
            capacity int(11) DEFAULT 20 NOT NULL,
            sessions_count int(11) DEFAULT 12 NOT NULL,
            duration_days int(11) DEFAULT 30 NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id),
            KEY coach_id (coach_id)
        ) $charset_collate;";

        // 5. Attendance Table
        $table_attendance = $wpdb->prefix . 'sportedia_attendance';
        $sql_attendance = "CREATE TABLE $table_attendance (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            program_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            user_type varchar(50) DEFAULT 'customer' NOT NULL,
            attendance_date date NOT NULL,
            status varchar(20) DEFAULT 'present' NOT NULL,
            checked_in_by bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id),
            KEY user_id (user_id),
            KEY attendance_date (attendance_date)
        ) $charset_collate;";

        // 6. Settings Table
        $table_settings = $wpdb->prefix . 'sportedia_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            setting_key varchar(191) NOT NULL,
            setting_value longtext DEFAULT '',
            PRIMARY KEY  (setting_key)
        ) $charset_collate;";

        dbDelta($sql_branches);
        dbDelta($sql_user_branches);
        dbDelta($sql_subscriptions);
        dbDelta($sql_programs);
        dbDelta($sql_attendance);
        dbDelta($sql_settings);
    }
}
