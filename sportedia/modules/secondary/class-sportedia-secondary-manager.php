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
        add_action('wp_ajax_sportedia_sec_get_coach_details', array($this, 'ajax_get_coach_details'));

        add_action('wp_ajax_sportedia_sec_save_branch', array($this, 'ajax_save_branch'));
        add_action('wp_ajax_sportedia_sec_delete_branch', array($this, 'ajax_delete_branch'));

        add_action('wp_ajax_sportedia_sec_search_entities', array($this, 'ajax_search_entities'));
        add_action('wp_ajax_sportedia_sec_record_attendance', array($this, 'ajax_record_attendance'));

        add_action('wp_ajax_sportedia_sec_save_daily_report', array($this, 'ajax_save_daily_report'));
        add_action('wp_ajax_sportedia_sec_add_eod_item', array($this, 'ajax_add_eod_item'));
        add_action('wp_ajax_sportedia_sec_delete_eod_item', array($this, 'ajax_delete_eod_item'));

        add_action('wp_ajax_sportedia_sec_export_eod', array($this, 'handle_export_eod'));
        add_action('wp_ajax_sportedia_sec_export_attendance', array($this, 'handle_export_attendance'));

        add_action('wp_ajax_sportedia_sec_export_coach_report', array($this, 'handle_export_coach_report'));
        add_action('wp_ajax_sportedia_sec_export_all_coaches_report', array($this, 'handle_export_all_coaches_report'));
    }

    // Helper: Get Secondary Branches
    public static function get_sec_branches($search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_branches';

        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
        $branches = array();

        foreach ($results as $row) {
            if (!empty($search)) {
                $s = strtolower(trim($search));
                if (stripos(strtolower($row['branch_name']), $s) === false && stripos(strtolower($row['code']), $s) === false) {
                    continue;
                }
            }
            $branches[] = $row;
        }

        return $branches;
    }

    // Helper: Get Players sorted newest to oldest
    public static function get_players($search = '', $coach = '', $sport = '', $status = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_players';

        $where = array('1=1');
        $params = array();

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

    // Helper: Get Coaches with Analytics
    public static function get_coaches($search = '', $start_date = '', $end_date = '') {
        global $wpdb;
        $coaches_table = $wpdb->prefix . 'sportedia_sec_coaches';
        $players_table = $wpdb->prefix . 'sportedia_sec_players';
        $att_table     = $wpdb->prefix . 'sportedia_sec_attendance';

        if (empty($start_date)) $start_date = date('Y-m-d');
        if (empty($end_date))   $end_date   = date('Y-m-d');

        $results = $wpdb->get_results("SELECT * FROM $coaches_table ORDER BY id DESC", ARRAY_A);
        $coaches = array();

        foreach ($results as $c) {
            if (!empty($search)) {
                $s = strtolower(trim($search));
                $match = (stripos(strtolower($c['coach_name']), $s) !== false) ||
                         (stripos(strtolower($c['sport']), $s) !== false) ||
                         (!empty($c['mobile_number']) && stripos(strtolower($c['mobile_number']), $s) !== false) ||
                         (!empty($c['branch']) && stripos(strtolower($c['branch']), $s) !== false);
                if (!$match) continue;
            }

            $coach_name = $c['coach_name'];

            // Assigned Players
            $assigned_players = $wpdb->get_results($wpdb->prepare("SELECT id, player_code, player_name, remaining_classes, total_classes, status FROM $players_table WHERE assigned_coach = %s", $coach_name), ARRAY_A);
            $total_players = count($assigned_players);

            // Present Players in Date Range
            $present_player_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT player_id FROM $att_table WHERE coach_name = %s AND attendance_date >= %s AND attendance_date <= %s",
                $coach_name, $start_date, $end_date
            ));
            $present_count = count($present_player_ids);
            $absent_count  = max(0, $total_players - $present_count);

            // Sessions Total
            $total_sessions_conducted = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(classes_used) FROM $att_table WHERE coach_name = %s AND attendance_date >= %s AND attendance_date <= %s",
                $coach_name, $start_date, $end_date
            ));
            $total_sessions_conducted = intval($total_sessions_conducted);

            // Attendance Rate
            $att_rate = $total_players > 0 ? round(($present_count / $total_players) * 100, 1) : 0;

            $c['total_players']     = $total_players;
            $c['present_players']   = $present_count;
            $c['absent_players']    = $absent_count;
            $c['completed_sessions']= $total_sessions_conducted;
            $c['attendance_rate']   = $att_rate;
            $c['assigned_players']  = $assigned_players;

            $coaches[] = $c;
        }

        return $coaches;
    }

    // AJAX Get Coach Details & Player Tracking
    public function ajax_get_coach_details() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $coach_id   = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d');
        $end_date   = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');

        global $wpdb;
        $coaches_table = $wpdb->prefix . 'sportedia_sec_coaches';
        $players_table = $wpdb->prefix . 'sportedia_sec_players';
        $att_table     = $wpdb->prefix . 'sportedia_sec_attendance';

        $c = $wpdb->get_row($wpdb->prepare("SELECT * FROM $coaches_table WHERE id = %d", $coach_id), ARRAY_A);
        if (!$c) {
            wp_send_json_error('Coach record not found.');
        }

        $coach_name = $c['coach_name'];
        $players    = $wpdb->get_results($wpdb->prepare("SELECT * FROM $players_table WHERE assigned_coach = %s ORDER BY id DESC", $coach_name), ARRAY_A);

        $present_player_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT player_id FROM $att_table WHERE coach_name = %s AND attendance_date >= %s AND attendance_date <= %s",
            $coach_name, $start_date, $end_date
        ));

        $player_list = array();
        foreach ($players as $p) {
            $p_id = intval($p['id']);
            $is_present = in_array($p_id, $present_player_ids, true);
            $p['today_attendance'] = $is_present ? 'Present' : 'Absent';
            $player_list[] = $p;
        }

        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $att_table WHERE coach_name = %s AND attendance_date >= %s AND attendance_date <= %s ORDER BY id DESC LIMIT 50",
            $coach_name, $start_date, $end_date
        ), ARRAY_A);

        wp_send_json_success(array(
            'coach'       => $c,
            'players'     => $player_list,
            'history'     => $history,
            'start_date'  => $start_date,
            'end_date'    => $end_date
        ));
    }

    // AJAX Save Secondary Branch
    public function ajax_save_branch() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_branches';

        $branch_id   = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $branch_name = sanitize_text_field($_POST['branch_name']);
        $code        = sanitize_text_field($_POST['code']);
        $status      = sanitize_text_field($_POST['status']);

        if (empty($branch_name)) {
            wp_send_json_error('Branch Name is required.');
        }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE branch_name = %s AND id != %d", $branch_name, $branch_id));
        if ($existing) {
            wp_send_json_error('Secondary Branch Name already exists.');
        }

        $data = array(
            'branch_name' => $branch_name,
            'code'        => !empty($code) ? $code : ('BR-' . rand(10, 99)),
            'status'      => !empty($status) ? $status : 'active'
        );

        if ($branch_id > 0) {
            $wpdb->update($table, $data, array('id' => $branch_id), array('%s', '%s', '%s'), array('%d'));
            Sportedia_DB::log_activity('sec_branch_update', 'Updated secondary branch ' . $branch_name);
            wp_send_json_success('Secondary branch updated successfully.');
        } else {
            $wpdb->insert($table, $data, array('%s', '%s', '%s'));
            Sportedia_DB::log_activity('sec_branch_create', 'Created secondary branch ' . $branch_name);
            wp_send_json_success('Secondary branch created successfully.');
        }
    }

    // AJAX Delete Secondary Branch
    public function ajax_delete_branch() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_branches') && !Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_branches';
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;

        if ($branch_id > 0) {
            $b = $wpdb->get_row($wpdb->prepare("SELECT branch_name FROM $table WHERE id = %d", $branch_id));
            if ($b) {
                $wpdb->delete($table, array('id' => $branch_id), array('%d'));
                Sportedia_DB::log_activity('sec_branch_delete', 'Deleted secondary branch ' . $b->branch_name);
                wp_send_json_success('Secondary branch deleted successfully.');
            }
        }

        wp_send_json_error('Invalid branch ID.');
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

        $coach_id      = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
        $coach_name    = sanitize_text_field($_POST['coach_name']);
        $mobile_number = sanitize_text_field($_POST['mobile_number']);
        $sport         = isset($_POST['sport']) ? sanitize_text_field($_POST['sport']) : 'General';
        $branch        = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : 'Main';

        if (empty($coach_name)) {
            wp_send_json_error('Coach Name is required.');
        }

        if (!empty($mobile_number) && !preg_match('/^[0-9+\s-]{7,20}$/', $mobile_number)) {
            wp_send_json_error('Invalid mobile number format.');
        }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE coach_name = %s AND id != %d", $coach_name, $coach_id));
        if ($existing) {
            wp_send_json_error('Coach Name already exists.');
        }

        $data = array(
            'coach_name'    => $coach_name,
            'mobile_number' => $mobile_number,
            'sport'         => $sport,
            'branch'        => $branch
        );

        if ($coach_id > 0) {
            $wpdb->update($table, $data, array('id' => $coach_id), array('%s', '%s', '%s', '%s'), array('%d'));
            Sportedia_DB::log_activity('sec_coach_update', 'Updated secondary coach ' . $coach_name);
            wp_send_json_success('Coach updated successfully.');
        } else {
            $wpdb->insert($table, $data, array('%s', '%s', '%s', '%s'));
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
            "SELECT id, coach_name, mobile_number, sport, branch FROM $coaches_table WHERE coach_name LIKE %s OR sport LIKE %s OR mobile_number LIKE %s LIMIT 10",
            $s, $s, $s
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

    // AJAX Add / Edit EOD Excel Record
    public function ajax_add_eod_item() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_eod_records';

        $item_id_pk  = isset($_POST['item_id_pk']) ? intval($_POST['item_id_pk']) : 0;
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

        $data = array(
            'report_date'         => $report_date,
            'serial_no'           => $serial_no > 0 ? $serial_no : 1,
            'item_date'           => !empty($item_date) ? $item_date : $report_date,
            'item_id'             => $item_id,
            'item_name'           => $item_name,
            'branch'              => !empty($branch) ? $branch : 'Main',
            'sport'               => !empty($sport) ? $sport : 'SWIMMING ACADEMY',
            'program'             => !empty($program) ? $program : 'SWIMMING',
            'registration_status' => !empty($reg_status) ? $reg_status : 'NEW REGISTRATION',
            'payment_amount'      => $amount,
            'payment_method'      => !empty($method) ? $method : 'CARD',
            'notes'               => $notes
        );

        if ($item_id_pk > 0) {
            $wpdb->update($table, $data, array('id' => $item_id_pk));
            wp_send_json_success('Transaction entry updated successfully.');
        } else {
            $wpdb->insert($table, $data);
            wp_send_json_success('Record entry added to daily report.');
        }
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

    // Export Individual Coach Management Report
    public function handle_export_coach_report() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        @set_time_limit(300);
        $coach_id   = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : 0;
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d');
        $end_date   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

        global $wpdb;
        $coaches_table = $wpdb->prefix . 'sportedia_sec_coaches';
        $players_table = $wpdb->prefix . 'sportedia_sec_players';
        $att_table     = $wpdb->prefix . 'sportedia_sec_attendance';

        $c = $wpdb->get_row($wpdb->prepare("SELECT * FROM $coaches_table WHERE id = %d", $coach_id), ARRAY_A);
        if (!$c) {
            wp_die('Coach not found.');
        }

        $coach_name = $c['coach_name'];
        $players    = $wpdb->get_results($wpdb->prepare("SELECT * FROM $players_table WHERE assigned_coach = %s", $coach_name), ARRAY_A);
        $total_players = count($players);

        $present_player_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT player_id FROM $att_table WHERE coach_name = %s AND attendance_date >= %s AND attendance_date <= %s",
            $coach_name, $start_date, $end_date
        ));
        $present_count = count($present_player_ids);
        $absent_count  = max(0, $total_players - $present_count);

        $att_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $att_table WHERE coach_name = %s AND attendance_date >= %s AND attendance_date <= %s ORDER BY attendance_date ASC, entry_time ASC",
            $coach_name, $start_date, $end_date
        ), ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Coach_Report_' . str_replace(' ', '_', $coach_name) . '_' . $start_date . '_to_' . $end_date . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        // Coach Header Section
        fputcsv($output, array('INDIVIDUAL COACH PERFORMANCE REPORT'));
        fputcsv($output, array('Coach Name', $c['coach_name']));
        fputcsv($output, array('Mobile Number', $c['mobile_number'] ? $c['mobile_number'] : 'N/A'));
        fputcsv($output, array('Branch', $c['branch']));
        fputcsv($output, array('Report Period', $start_date . ' to ' . $end_date));
        fputcsv($output, array('Total Assigned Players', $total_players));
        fputcsv($output, array('Players Present', $present_count));
        fputcsv($output, array('Players Absent', $absent_count));
        fputcsv($output, array('Attendance Rate', ($total_players > 0 ? round(($present_count / $total_players) * 100, 1) : 0) . '%'));
        fputcsv($output, array('')); // Blank row

        // Player Attendance Breakdown Header
        fputcsv($output, array('Serial No.', 'Date', 'Time', 'Period', 'Player Code', 'Player Name', 'Sport', 'Classes Used', 'Attendance Status', 'Session Status', 'Remaining Classes', 'Notes'));

        $sr = 1;
        foreach ($att_records as $a) {
            fputcsv($output, array(
                $sr++,
                $a['attendance_date'],
                $a['entry_time'],
                $a['period'],
                $a['player_code'],
                $a['player_name'],
                $a['sport'],
                $a['classes_used'],
                'Present',
                $a['status'],
                $a['remaining_classes'],
                'Verified Session'
            ));
        }

        // Include Absent Players List
        foreach ($players as $p) {
            if (!in_array(intval($p['id']), $present_player_ids, true)) {
                fputcsv($output, array(
                    $sr++,
                    $start_date,
                    'N/A',
                    'N/A',
                    $p['player_code'],
                    $p['player_name'],
                    $p['sport'],
                    0,
                    'Absent',
                    $p['status'],
                    $p['remaining_classes'],
                    'No session recorded in date range'
                ));
            }
        }

        fclose($output);
        exit;
    }

    // Export Complete All Coaches Report
    public function handle_export_all_coaches_report() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        @set_time_limit(300);
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d');
        $end_date   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

        global $wpdb;
        $coaches = self::get_coaches('', $start_date, $end_date);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Complete_Coaches_Management_Report_' . $start_date . '_to_' . $end_date . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        // Executive Summary
        fputcsv($output, array('ALL COACHES EXECUTIVE SUMMARY REPORT'));
        fputcsv($output, array('Reporting Period', $start_date . ' to ' . $end_date));
        fputcsv($output, array('Total Coaches', count($coaches)));
        fputcsv($output, array('Generated Date', date('Y-m-d H:i:s')));
        fputcsv($output, array('')); // Blank row

        // Summary Table Header
        fputcsv($output, array('Coach Name', 'Branch', 'Mobile Number', 'Total Players', 'Present', 'Absent', 'Sessions Conducted', 'Attendance Rate (%)'));

        foreach ($coaches as $c) {
            fputcsv($output, array(
                $c['coach_name'],
                $c['branch'],
                $c['mobile_number'] ? $c['mobile_number'] : 'N/A',
                $c['total_players'],
                $c['present_players'],
                $c['absent_players'],
                $c['completed_sessions'],
                $c['attendance_rate'] . '%'
            ));
        }

        fclose($output);
        exit;
    }

    // Export EOD Excel (Professional 11-column A-K uppercase report) or CSV
    public function handle_export_eod() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        @set_time_limit(300);
        $date   = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : date('Y-m-d');
        $format = isset($_GET['export_format']) ? sanitize_text_field($_GET['export_format']) : 'xls';

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_sec_eod_records';
        $rep_table = $wpdb->prefix . 'sportedia_sec_daily_reports';

        $records   = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE report_date = %s ORDER BY serial_no ASC, id ASC", $date), ARRAY_A);
        $daily_rep = $wpdb->get_row($wpdb->prepare("SELECT * FROM $rep_table WHERE report_date = %s", $date), ARRAY_A);

        $branch_name = $daily_rep && !empty($daily_rep['branch']) ? $daily_rep['branch'] : 'ISCS MUW';

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=Secondary_EOD_Report_' . $date . '.csv');

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

            // 11 Exact Columns A to K Header
            fputcsv($output, array('SERIAL', 'DATE', 'ID', 'NAME', 'BRANCH', 'ACADEMY', 'PROGRAM', 'REGISTRATION TYPE', 'PAYMENT AMOUNT', 'PAYMENT METHOD', 'NOTES'));

            $sr = 1;
            foreach ($records as $r) {
                fputcsv($output, array(
                    $r['serial_no'] ? $r['serial_no'] : $sr++,
                    $r['item_date'],
                    $r['item_id'],
                    mb_strtoupper($r['item_name'], 'UTF-8'),
                    mb_strtoupper($r['branch'], 'UTF-8'),
                    mb_strtoupper($r['sport'], 'UTF-8'),
                    mb_strtoupper($r['program'], 'UTF-8'),
                    mb_strtoupper($r['registration_status'], 'UTF-8'),
                    number_format((float)$r['payment_amount'], 2, '.', ''),
                    mb_strtoupper($r['payment_method'], 'UTF-8'),
                    mb_strtoupper($r['notes'], 'UTF-8')
                ));
            }

            fclose($output);
            exit;
        }

        // Default: Professional Printable Excel Workbook (.xls)
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=Secondary_EOD_Report_' . $date . '.xls');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>End of Day Report</x:Name>';
        echo '<x:WorksheetOptions><x:DisplayGridlines/><x:Print><x:ValidPrinterInfo/><x:PaperSizeIndex>9</x:PaperSizeIndex><x:Scale>100</x:Scale><x:FitWidth>1</x:FitWidth><x:FitHeight>0</x:FitHeight></x:Print><x:Selected/><x:FreezePanes/><x:FrozenNoSplit/><x:SplitHorizontal>4</x:SplitHorizontal><x:TopRowBottomPane>4</x:TopRowBottomPane><x:ActivePane>2</x:ActivePane></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>';
        echo 'body { font-family: "Calibri", "Segoe UI", Arial, sans-serif; font-size: 11pt; }';
        echo 'table { border-collapse: collapse; width: 100%; table-layout: fixed; }';
        echo 'th, td { border: 0.5pt solid #CBD5E1; padding: 8px; font-size: 10pt; vertical-align: middle; }';
        echo '.title-row { background-color: #0F172A; color: #FFFFFF; font-size: 16pt; font-weight: bold; text-align: center; height: 40px; }';
        echo '.subtitle-row { background-color: #1E293B; color: #F8FAFC; font-size: 11pt; font-weight: bold; text-align: center; height: 28px; }';
        echo '.header-cell { background-color: #0284C7; color: #FFFFFF; font-weight: bold; text-align: center; height: 32px; font-size: 10pt; }';
        echo '.text-cell { text-align: left; mso-number-format:"\@"; }';
        echo '.num-cell { text-align: right; mso-number-format:"\#\,\#\#0\.00"; }';
        echo '.center-cell { text-align: center; mso-number-format:"\@"; }';
        echo '.notes-cell { text-align: left; word-wrap: break-word; mso-number-format:"\@"; }';
        echo '.total-row { font-weight: bold; background-color: #F1F5F9; height: 30px; font-size: 11pt; }';
        echo '</style></head><body>';

        echo '<table>';
        // Column Width Specifications for A-K
        echo '<col style="width: 50px;">';  // A: SERIAL
        echo '<col style="width: 100px;">'; // B: DATE
        echo '<col style="width: 90px;">';  // C: ID
        echo '<col style="width: 180px;">'; // D: NAME
        echo '<col style="width: 120px;">'; // E: BRANCH
        echo '<col style="width: 140px;">'; // F: ACADEMY
        echo '<col style="width: 130px;">'; // G: PROGRAM
        echo '<col style="width: 150px;">'; // H: REGISTRATION TYPE
        echo '<col style="width: 120px;">'; // I: PAYMENT AMOUNT
        echo '<col style="width: 120px;">'; // J: PAYMENT METHOD
        echo '<col style="width: 200px;">'; // K: NOTES

        // Title Header
        echo '<tr><td colspan="11" class="title-row">SPORTEDIA ACADEMY — END OF DAY REPORT</td></tr>';
        echo '<tr><td colspan="11" class="subtitle-row">REPORT DATE: ' . esc_html($date) . ' | BRANCH: ' . esc_html(mb_strtoupper($branch_name, 'UTF-8')) . '</td></tr>';
        echo '<tr><td colspan="11" style="height: 10px; border: none;"></td></tr>';

        // Table Header Row (A to K)
        echo '<tr>';
        echo '<th class="header-cell">S</th>';
        echo '<th class="header-cell">DATE</th>';
        echo '<th class="header-cell">ID</th>';
        echo '<th class="header-cell">NAME</th>';
        echo '<th class="header-cell">BRANCH</th>';
        echo '<th class="header-cell">ACADEMY</th>';
        echo '<th class="header-cell">PROGRAM</th>';
        echo '<th class="header-cell">REGISTRATION TYPE</th>';
        echo '<th class="header-cell">PAYMENT AMOUNT</th>';
        echo '<th class="header-cell">PAYMENT METHOD</th>';
        echo '<th class="header-cell">NOTES</th>';
        echo '</tr>';

        $total_amount = 0;
        $sr = 1;

        if (!empty($records)) {
            foreach ($records as $r) {
                $amount = floatval($r['payment_amount']);
                $total_amount += $amount;

                echo '<tr>';
                echo '<td class="center-cell">' . esc_html($r['serial_no'] ? $r['serial_no'] : $sr) . '</td>';
                echo '<td class="center-cell">' . esc_html($r['item_date']) . '</td>';
                echo '<td class="center-cell">' . esc_html($r['item_id']) . '</td>';
                echo '<td class="text-cell">' . esc_html(mb_strtoupper($r['item_name'], 'UTF-8')) . '</td>';
                echo '<td class="text-cell">' . esc_html(mb_strtoupper($r['branch'], 'UTF-8')) . '</td>';
                echo '<td class="text-cell">' . esc_html(mb_strtoupper($r['sport'], 'UTF-8')) . '</td>';
                echo '<td class="text-cell">' . esc_html(mb_strtoupper($r['program'], 'UTF-8')) . '</td>';
                echo '<td class="center-cell">' . esc_html(mb_strtoupper($r['registration_status'], 'UTF-8')) . '</td>';
                echo '<td class="num-cell">' . number_format($amount, 2, '.', ',') . '</td>';
                echo '<td class="center-cell">' . esc_html(mb_strtoupper($r['payment_method'], 'UTF-8')) . '</td>';
                echo '<td class="notes-cell">' . esc_html(mb_strtoupper($r['notes'], 'UTF-8')) . '</td>';
                echo '</tr>';
                $sr++;
            }
        } else {
            echo '<tr><td colspan="11" class="center-cell" style="padding: 20px;">NO RECORDED TRANSACTIONS FOR THIS DATE.</td></tr>';
        }

        // Summary / Total Row
        echo '<tr class="total-row">';
        echo '<td colspan="8" style="text-align: right; font-weight: bold; border-top: 1.5pt solid #0F172A;">TOTAL TRANSACTION INCOME (AED):</td>';
        echo '<td class="num-cell" style="font-weight: bold; color: #0F172A; border-top: 1.5pt solid #0F172A;">' . number_format($total_amount, 2, '.', ',') . '</td>';
        echo '<td colspan="2" style="border-top: 1.5pt solid #0F172A;"></td>';
        echo '</tr>';

        echo '</table></body></html>';
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
