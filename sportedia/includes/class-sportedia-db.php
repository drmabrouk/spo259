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
            coach_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            plan_name varchar(191) NOT NULL,
            subscription_type varchar(50) DEFAULT 'monthly' NOT NULL,
            sessions_count int(11) DEFAULT 12 NOT NULL,
            sessions_used int(11) DEFAULT 0 NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            price decimal(10,2) DEFAULT '0.00' NOT NULL,
            notes text DEFAULT '',
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY branch_id (branch_id),
            KEY coach_id (coach_id)
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
            user_type varchar(50) DEFAULT 'employee' NOT NULL,
            attendance_date date NOT NULL,
            check_in_time datetime DEFAULT NULL,
            check_out_time datetime DEFAULT NULL,
            scheduled_start time DEFAULT NULL,
            scheduled_end time DEFAULT NULL,
            lateness_minutes int(11) DEFAULT 0 NOT NULL,
            working_duration_minutes int(11) DEFAULT 0 NOT NULL,
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

        // 7. Activity Log Table (Max 200 Retention)
        $table_activity_log = $wpdb->prefix . 'sportedia_activity_log';
        $sql_activity_log = "CREATE TABLE $table_activity_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
            action varchar(191) NOT NULL,
            details text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // =========================================================================
        // SECONDARY DASHBOARD DATA-ISOLATED TABLES
        // =========================================================================

        // 8. Secondary Players Table
        $table_sec_players = $wpdb->prefix . 'sportedia_sec_players';
        $sql_sec_players = "CREATE TABLE $table_sec_players (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            player_code varchar(100) NOT NULL,
            player_name varchar(191) NOT NULL,
            sport varchar(100) DEFAULT 'Swimming' NOT NULL,
            expiry_date date NOT NULL,
            total_classes int(11) DEFAULT 12 NOT NULL,
            remaining_classes int(11) DEFAULT 12 NOT NULL,
            assigned_coach varchar(191) DEFAULT '' NOT NULL,
            branch varchar(100) DEFAULT 'Main' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY player_code (player_code),
            KEY sport (sport),
            KEY assigned_coach (assigned_coach)
        ) $charset_collate;";

        // 9. Secondary Coaches Table
        $table_sec_coaches = $wpdb->prefix . 'sportedia_sec_coaches';
        $sql_sec_coaches = "CREATE TABLE $table_sec_coaches (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            coach_name varchar(191) NOT NULL,
            mobile_number varchar(50) DEFAULT '' NOT NULL,
            sport varchar(100) DEFAULT 'General' NOT NULL,
            branch varchar(100) DEFAULT 'Main' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY coach_name (coach_name)
        ) $charset_collate;";

        // 10. Secondary Branches Table (Completely Independent from Main Dashboard)
        $table_sec_branches = $wpdb->prefix . 'sportedia_sec_branches';
        $sql_sec_branches = "CREATE TABLE $table_sec_branches (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_name varchar(191) NOT NULL,
            code varchar(50) DEFAULT '' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY branch_name (branch_name)
        ) $charset_collate;";

        // 11. Secondary Attendance & Session Tracking Table
        $table_sec_attendance = $wpdb->prefix . 'sportedia_sec_attendance';
        $sql_sec_attendance = "CREATE TABLE $table_sec_attendance (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            player_id bigint(20) UNSIGNED NOT NULL,
            player_code varchar(100) NOT NULL,
            player_name varchar(191) NOT NULL,
            coach_name varchar(191) NOT NULL,
            sport varchar(100) DEFAULT 'Swimming' NOT NULL,
            attendance_date date NOT NULL,
            entry_time time NOT NULL,
            period varchar(50) DEFAULT '09:00' NOT NULL,
            classes_used int(11) DEFAULT 1 NOT NULL,
            remaining_classes int(11) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'completed' NOT NULL,
            branch varchar(100) DEFAULT 'Main' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY player_code (player_code),
            KEY coach_name (coach_name),
            KEY attendance_date (attendance_date),
            KEY period (period)
        ) $charset_collate;";

        // 12. Secondary End-of-Day Daily Reports Table
        $table_sec_daily_reports = $wpdb->prefix . 'sportedia_sec_daily_reports';
        $sql_sec_daily_reports = "CREATE TABLE $table_sec_daily_reports (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_date date NOT NULL,
            branch varchar(100) DEFAULT 'ISCS MUW' NOT NULL,
            new_registrations int(11) DEFAULT 0 NOT NULL,
            card_payments decimal(10,2) DEFAULT '0.00' NOT NULL,
            renewals int(11) DEFAULT 0 NOT NULL,
            caps_count int(11) DEFAULT 0 NOT NULL,
            cash_payments decimal(10,2) DEFAULT '0.00' NOT NULL,
            total_caps_amount decimal(10,2) DEFAULT '0.00' NOT NULL,
            staff_status varchar(255) DEFAULT 'All staff and coaches are present.' NOT NULL,
            player_absences_count int(11) DEFAULT 0 NOT NULL,
            recommendations text DEFAULT '',
            total_income decimal(10,2) DEFAULT '0.00' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY report_date_branch (report_date, branch)
        ) $charset_collate;";

        // 13. Secondary End-of-Day Excel Records Table
        $table_sec_eod_records = $wpdb->prefix . 'sportedia_sec_eod_records';
        $sql_sec_eod_records = "CREATE TABLE $table_sec_eod_records (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_date date NOT NULL,
            serial_no int(11) NOT NULL,
            item_date date NOT NULL,
            item_id varchar(100) NOT NULL,
            item_name varchar(191) NOT NULL,
            branch varchar(100) DEFAULT 'Main' NOT NULL,
            sport varchar(100) DEFAULT 'Swimming' NOT NULL,
            program varchar(191) DEFAULT 'Academy' NOT NULL,
            registration_status varchar(50) DEFAULT 'New Registration' NOT NULL,
            payment_amount decimal(10,2) DEFAULT '0.00' NOT NULL,
            payment_method varchar(50) DEFAULT 'Card' NOT NULL,
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY report_date (report_date),
            KEY item_id (item_id)
        ) $charset_collate;";

        dbDelta($sql_branches);
        dbDelta($sql_user_branches);
        dbDelta($sql_subscriptions);
        dbDelta($sql_programs);
        dbDelta($sql_attendance);
        dbDelta($sql_settings);
        dbDelta($sql_activity_log);

        dbDelta($sql_sec_players);
        dbDelta($sql_sec_coaches);
        dbDelta($sql_sec_branches);
        dbDelta($sql_sec_attendance);
        dbDelta($sql_sec_daily_reports);
        dbDelta($sql_sec_eod_records);
    }

    public static function log_activity($action, $details = '', $user_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_activity_log';

        if ($user_id <= 0) {
            $user_id = get_current_user_id();
        }

        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'action'  => sanitize_text_field($action),
            'details' => sanitize_textarea_field($details)
        ), array('%d', '%s', '%s'));

        // Enforce maximum 200 records retention rule
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 200) {
            $cutoff = $wpdb->get_var("SELECT id FROM $table ORDER BY id DESC LIMIT 1 OFFSET 199");
            if ($cutoff) {
                $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE id < %d", $cutoff));
            }
        }
    }
}
