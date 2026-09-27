<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Secondary_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_sec_save_player', array($this, 'ajax_save_player'));
        add_action('wp_ajax_sportedia_sec_delete_player', array($this, 'ajax_delete_player'));

        add_action('wp_ajax_sportedia_sec_save_coach', array($this, 'ajax_save_coach'));
        add_action('wp_ajax_sportedia_sec_delete_coach', array($this, 'ajax_delete_coach'));

        add_action('wp_ajax_sportedia_sec_search_entities', array($this, 'ajax_search_entities'));
        add_action('wp_ajax_sportedia_sec_record_attendance', array($this, 'ajax_record_attendance'));

        add_action('wp_ajax_sportedia_sec_save_daily_report', array($this, 'ajax_save_daily_report'));
        add_action('wp_ajax_sportedia_sec_add_eod_item', array($this, 'ajax_add_eod_item'));
        add_action('wp_ajax_sportedia_sec_delete_eod_item', array($this, 'ajax_delete_eod_item'));

        add_action('wp_ajax_sportedia_sec_export_eod', array($this, 'handle_export_eod'));
        add_action('wp_ajax_sportedia_sec_export_attendance', array($this, 'handle_export_attendance'));
    }

    // Helper: Get Players sorted newest to oldest
    public static function get_players($search = '', $coach = '', $sport = '', $status = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_players';

        $where = array('1=1');
        $params = array();

        if (!empty($branch)) {
            $where[] = "branch = %s";
            $params[] = $branch;
        }

        if (!empty($coach)) {
            $where[] = "assigned_coach = %s";
            $params[] = $coach;
        }

        if (!empty($sport)) {
            $where[] = "sport = %s";
            $params[] = $sport;
        }

        if (!empty($status)) {
            $where[] = "status = %s";
            $params[] = $status;
        }

        $where_sql = implode(' AND ', $where);

        if (!empty($params)) {
            $sql = $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY id DESC", $params);
        } else {
            $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);
        $players = array();
        $today = date('Y-m-d');

        foreach ($results as $row) {
            if (!empty($search)) {
                $s = strtolower(trim($search));
                $match = (stripos(strtolower($row['player_name']), $s) !== false) ||
                         (stripos(strtolower($row['player_code']), $s) !== false) ||
                         (stripos(strtolower($row['sport']), $s) !== false) ||
                         (stripos(strtolower($row['assigned_coach']), $s) !== false);
                if (!$match) continue;
            }

            // Auto status calculation
            if (intval($row['remaining_classes']) <= 0) {
                $row['calculated_status'] = 'completed';
            } else if ($row['expiry_date'] < $today) {
                $row['calculated_status'] = 'expired';
            } else {
                $row['calculated_status'] = 'active';
            }

            $players[] = $row;
        }

        return $players;
    }

    // Helper: Get Coaches
    public static function get_coaches($search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_coaches';

        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
        $coaches = array();

        foreach ($results as $row) {
            if (!empty($search)) {
                $s = strtolower(trim($search));
                if (stripos(strtolower($row['coach_name']), $s) === false && stripos(strtolower($row['sport']), $s) === false) {
                    continue;
                }
            }
            $coaches[] = $row;
        }

        return $coaches;
    }

    // AJAX Save Player
    public function ajax_save_player() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_players';

        $player_id       = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
        $player_code     = sanitize_text_field($_POST['player_code']);
        $player_name     = sanitize_text_field($_POST['player_name']);
        $sport           = sanitize_text_field($_POST['sport']);
        $expiry_date     = sanitize_text_field($_POST['expiry_date']);
        $total_classes   = intval($_POST['total_classes']);
        $remaining       = isset($_POST['remaining_classes']) ? intval($_POST['remaining_classes']) : $total_classes;
        $assigned_coach  = sanitize_text_field($_POST['assigned_coach']);
        $branch          = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : 'Main';

        if (empty($player_name) || empty($player_code) || empty($expiry_date)) {
            wp_send_json_error('Player Name, Code, and Expiry Date are required.');
        }

        // Validate unique player_code
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE player_code = %s AND id != %d", $player_code, $player_id));
        if ($existing) {
            wp_send_json_error('Player Code already exists: ' . $player_code);
        }

        $today = date('Y-m-d');
        $status = 'active';
        if ($remaining <= 0) {
            $status = 'completed';
        } else if ($expiry_date < $today) {
            $status = 'expired';
        }

        $data = array(
            'player_code'       => $player_code,
            'player_name'       => $player_name,
            'sport'             => !empty($sport) ? $sport : 'Swimming',
            'expiry_date'       => $expiry_date,
            'total_classes'     => $total_classes > 0 ? $total_classes : 12,
            'remaining_classes' => $remaining,
            'assigned_coach'    => $assigned_coach,
            'branch'            => $branch,
            'status'            => $status,
        );

        $format = array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s');

        if ($player_id > 0) {
            $wpdb->update($table, $data, array('id' => $player_id), $format, array('%d'));
            Sportedia_DB::log_activity('sec_player_update', 'Updated secondary player ' . $player_name . ' (' . $player_code . ')');
            wp_send_json_success('Player profile updated successfully.');
        } else {
            $wpdb->insert($table, $data, $format);
            Sportedia_DB::log_activity('sec_player_create', 'Created secondary player ' . $player_name . ' (' . $player_code . ')');
            wp_send_json_success('Player created successfully.');
        }
    }

    // AJAX Delete Player
    public function ajax_delete_player() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_players';
        $player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;

        if ($player_id > 0) {
            $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $player_id));
            if ($player) {
                $wpdb->delete($table, array('id' => $player_id), array('%d'));
                Sportedia_DB::log_activity('sec_player_delete', 'Deleted secondary player ' . $player->player_name . ' (' . $player->player_code . ')');
                wp_send_json_success('Player record deleted successfully.');
            }
        }

        wp_send_json_error('Invalid player ID.');
    }

    // AJAX Save Coach
    public function ajax_save_coach() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_coaches';

        $coach_id   = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
        $coach_name = sanitize_text_field($_POST['coach_name']);
        $sport      = isset($_POST['sport']) ? sanitize_text_field($_POST['sport']) : 'General';
        $branch     = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : 'Main';

        if (empty($coach_name)) {
            wp_send_json_error('Coach Name is required.');
        }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE coach_name = %s AND id != %d", $coach_name, $coach_id));
        if ($existing) {
            wp_send_json_error('Coach Name already exists.');
        }

        $data = array(
            'coach_name' => $coach_name,
            'sport'      => $sport,
            'branch'     => $branch
        );

        if ($coach_id > 0) {
            $wpdb->update($table, $data, array('id' => $coach_id), array('%s', '%s', '%s'), array('%d'));
            Sportedia_DB::log_activity('sec_coach_update', 'Updated secondary coach ' . $coach_name);
            wp_send_json_success('Coach updated successfully.');
        } else {
            $wpdb->insert($table, $data, array('%s', '%s', '%s'));
            Sportedia_DB::log_activity('sec_coach_create', 'Created secondary coach ' . $coach_name);
            wp_send_json_success('Coach added successfully.');
        }
    }

    // AJAX Delete Coach
    public function ajax_delete_coach() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_coaches';
        $coach_id = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;

        if ($coach_id > 0) {
            $c = $wpdb->get_row($wpdb->prepare("SELECT coach_name FROM $table WHERE id = %d", $coach_id));
            if ($c) {
                $wpdb->delete($table, array('id' => $coach_id), array('%d'));
                Sportedia_DB::log_activity('sec_coach_delete', 'Deleted secondary coach ' . $c->coach_name);
                wp_send_json_success('Coach deleted successfully.');
            }
        }

        wp_send_json_error('Invalid coach ID.');
    }

    // AJAX Search Players & Coaches
    public function ajax_search_entities() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        if (empty($query)) {
            wp_send_json_success(array('players' => array(), 'coaches' => array()));
        }

        global $wpdb;
        $players_table = $wpdb->prefix . 'sportedia_sec_players';
        $coaches_table = $wpdb->prefix . 'sportedia_sec_coaches';

        $s = '%' . $wpdb->esc_like($query) . '%';

        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT id, player_code, player_name, sport, remaining_classes, assigned_coach, expiry_date, status FROM $players_table WHERE player_name LIKE %s OR player_code LIKE %s OR sport LIKE %s LIMIT 10",
            $s, $s, $s
        ), ARRAY_A);

        $coaches = $wpdb->get_results($wpdb->prepare(
            "SELECT id, coach_name, sport FROM $coaches_table WHERE coach_name LIKE %s OR sport LIKE %s LIMIT 10",
            $s, $s
        ), ARRAY_A);

        wp_send_json_success(array(
            'players' => $players,
            'coaches' => $coaches
        ));
    }

    // AJAX Record Attendance Session
    public function ajax_record_attendance() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $players_table = $wpdb->prefix . 'sportedia_sec_players';
        $att_table     = $wpdb->prefix . 'sportedia_sec_attendance';

        $player_id    = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
        $coach_name   = sanitize_text_field($_POST['coach_name']);
        $period       = sanitize_text_field($_POST['period']);
        $classes_used = isset($_POST['classes_used']) ? intval($_POST['classes_used']) : 1;
        $att_date     = isset($_POST['attendance_date']) ? sanitize_text_field($_POST['attendance_date']) : date('Y-m-d');
        $force_scan   = isset($_POST['force']) && $_POST['force'] === '1';

        if ($player_id <= 0) {
            wp_send_json_error('Please select a valid player.');
        }

        $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE id = %d", $player_id), ARRAY_A);
        if (!$player) {
            wp_send_json_error('Player record not found.');
        }

        if (intval($player['remaining_classes']) <= 0) {
            wp_send_json_error('No remaining classes available for player: ' . $player['player_name']);
        }

        if ($player['expiry_date'] < date('Y-m-d')) {
            wp_send_json_error('Player membership has expired on ' . $player['expiry_date']);
        }

        // Duplicate scan check for same player + same date + same period
        if (!$force_scan) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $att_table WHERE player_id = %d AND attendance_date = %s AND period = %s",
                $player_id, $att_date, $period
            ));

            if ($existing) {
                wp_send_json_error(array(
                    'type'    => 'duplicate_confirm',
                    'message' => 'Attendance for ' . $player['player_name'] . ' has already been recorded for period ' . $period . ' today. Record duplicate deduction?'
                ));
            }
        }

        $new_remaining = max(0, intval($player['remaining_classes']) - $classes_used);
        $new_status = $new_remaining <= 0 ? 'completed' : $player['status'];

        $wpdb->update($players_table, array(
            'remaining_classes' => $new_remaining,
            'status'            => $new_status
        ), array('id' => $player_id));

        $wpdb->insert($att_table, array(
            'player_id'         => $player_id,
            'player_code'       => $player['player_code'],
            'player_name'       => $player['player_name'],
            'coach_name'        => !empty($coach_name) ? $coach_name : $player['assigned_coach'],
            'sport'             => $player['sport'],
            'attendance_date'   => $att_date,
            'entry_time'        => date('H:i:s'),
            'period'            => !empty($period) ? $period : date('H:00'),
            'classes_used'      => $classes_used,
            'remaining_classes' => $new_remaining,
            'status'            => 'completed',
            'branch'            => $player['branch']
        ));

        Sportedia_DB::log_activity('sec_attendance_record', 'Recorded attendance for ' . $player['player_name'] . ' with coach ' . $coach_name . ' (' . $period . ')');

        wp_send_json_success(array(
            'message'           => 'Attendance recorded and 1 class deducted successfully.',
            'player_name'       => $player['player_name'],
            'player_code'       => $player['player_code'],
            'coach_name'        => $coach_name,
            'period'            => $period,
            'remaining_classes' => $new_remaining
        ));
    }

    // AJAX Save Daily Report (End-of-Day)
    public function ajax_save_daily_report() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_daily_reports';

        $report_date   = sanitize_text_field($_POST['report_date']);
        $branch        = sanitize_text_field($_POST['branch']);
        $new_regs      = intval($_POST['new_registrations']);
        $card_payments = floatval($_POST['card_payments']);
        $renewals      = intval($_POST['renewals']);
        $caps_count    = intval($_POST['caps_count']);
        $cash_payments = floatval($_POST['cash_payments']);
        $caps_total    = floatval($_POST['total_caps_amount']);
        $staff_status  = sanitize_text_field($_POST['staff_status']);
        $absences      = intval($_POST['player_absences_count']);
        $recs          = sanitize_textarea_field($_POST['recommendations']);

        $total_income  = $card_payments + $cash_payments + $caps_total;

        if (empty($report_date)) {
            wp_send_json_error('Report date is required.');
        }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE report_date = %s AND branch = %s", $report_date, $branch));

        $data = array(
            'report_date'           => $report_date,
            'branch'                => !empty($branch) ? $branch : 'ISCS MUW',
            'new_registrations'     => $new_regs,
            'card_payments'         => $card_payments,
            'renewals'              => $renewals,
            'caps_count'            => $caps_count,
            'cash_payments'         => $cash_payments,
            'total_caps_amount'     => $caps_total,
            'staff_status'          => !empty($staff_status) ? $staff_status : 'All staff and coaches are present.',
            'player_absences_count' => $absences,
            'recommendations'       => $recs,
            'total_income'          => $total_income
        );

        if ($existing) {
            $wpdb->update($table, $data, array('id' => $existing));
        } else {
            $wpdb->insert($table, $data);
        }

        wp_send_json_success(array(
            'message'      => 'Daily report saved successfully.',
            'total_income' => Sportedia_Finance::format_price($total_income)
        ));
    }

    // AJAX Add EOD Excel Record
    public function ajax_add_eod_item() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_eod_records';

        $report_date = sanitize_text_field($_POST['report_date']);
        $serial_no   = intval($_POST['serial_no']);
        $item_date   = sanitize_text_field($_POST['item_date']);
        $item_id     = sanitize_text_field($_POST['item_id']);
        $item_name   = sanitize_text_field($_POST['item_name']);
        $branch      = sanitize_text_field($_POST['branch']);
        $sport       = sanitize_text_field($_POST['sport']);
        $program     = sanitize_text_field($_POST['program']);
        $reg_status  = sanitize_text_field($_POST['registration_status']);
        $amount      = floatval($_POST['payment_amount']);
        $method      = sanitize_text_field($_POST['payment_method']);
        $notes       = sanitize_textarea_field($_POST['notes']);

        if (empty($item_name) || empty($item_id)) {
            wp_send_json_error('ID and Name are required.');
        }

        $wpdb->insert($table, array(
            'report_date'         => $report_date,
            'serial_no'           => $serial_no > 0 ? $serial_no : 1,
            'item_date'           => !empty($item_date) ? $item_date : $report_date,
            'item_id'             => $item_id,
            'item_name'           => $item_name,
            'branch'              => !empty($branch) ? $branch : 'Main',
            'sport'               => !empty($sport) ? $sport : 'Swimming',
            'program'             => !empty($program) ? $program : 'Academy',
            'registration_status' => !empty($reg_status) ? $reg_status : 'New Registration',
            'payment_amount'      => $amount,
            'payment_method'      => !empty($method) ? $method : 'Card',
            'notes'               => $notes
        ));

        wp_send_json_success('Record entry added to daily report.');
    }

    // AJAX Delete EOD Item
    public function ajax_delete_eod_item() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_eod_records';
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if ($item_id > 0) {
            $wpdb->delete($table, array('id' => $item_id), array('%d'));
            wp_send_json_success('Item deleted.');
        }

        wp_send_json_error('Invalid item ID.');
    }

    // Export EOD Excel/CSV
    public function handle_export_eod() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        @set_time_limit(300);
        $date = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : date('Y-m-d');

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_eod_records';
        $records = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE report_date = %s ORDER BY serial_no ASC, id ASC", $date), ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Secondary_EOD_Report_' . $date . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        fputcsv($output, array('Serial No.', 'Date', 'ID', 'Name', 'Branch', 'Sport', 'Program', 'Registration Status', 'Payment Amount', 'Payment Method', 'Notes'));

        $sr = 1;
        foreach ($records as $r) {
            fputcsv($output, array(
                $sr++,
                $r['item_date'],
                $r['item_id'],
                $r['item_name'],
                $r['branch'],
                $r['sport'],
                $r['program'],
                $r['registration_status'],
                $r['payment_amount'],
                $r['payment_method'],
                $r['notes']
            ));
        }

        fclose($output);
        exit;
    }

    // Export Attendance CSV
    public function handle_export_attendance() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        @set_time_limit(300);
        $date = isset($_GET['attendance_date']) ? sanitize_text_field($_GET['attendance_date']) : date('Y-m-d');

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_attendance';
        $records = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE attendance_date = %s ORDER BY id ASC", $date), ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Secondary_Attendance_Report_' . $date . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        fputcsv($output, array('Serial No.', 'Date', 'Entry Time', 'Period/Time Slot', 'Player Code', 'Player Name', 'Sport', 'Coach Name', 'Classes Used', 'Remaining Classes', 'Session Status', 'Branch', 'Created At'));

        $sr = 1;
        foreach ($records as $r) {
            fputcsv($output, array(
                $sr++,
                $r['attendance_date'],
                $r['entry_time'],
                $r['period'],
                $r['player_code'],
                $r['player_name'],
                $r['sport'],
                $r['coach_name'],
                $r['classes_used'],
                $r['remaining_classes'],
                $r['status'],
                $r['branch'],
                $r['created_at']
            ));
        }

        fclose($output);
        exit;
    }
}
