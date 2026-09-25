<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Attendance_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_record_attendance', array($this, 'ajax_record_attendance'));
        add_action('wp_ajax_sportedia_delete_attendance', array($this, 'ajax_delete_attendance'));
        add_action('wp_ajax_sportedia_get_kiosk_qr', array($this, 'ajax_get_kiosk_qr'));
        add_action('wp_ajax_nopriv_sportedia_get_kiosk_qr', array($this, 'ajax_get_kiosk_qr'));
        add_action('wp_ajax_sportedia_process_employee_scan', array($this, 'ajax_process_employee_scan'));
    }

    public static function generate_attendance_qr_token($time = null) {
        if (is_null($time)) {
            $time = time();
        }
        $window = floor($time / 5) * 5;
        $formatted_time = date('dmYHis', $window);
        $secret = wp_salt('auth');
        $hash = substr(hash_hmac('sha256', $window . ':' . $formatted_time, $secret), 0, 10);
        return $formatted_time . '-' . $hash;
    }

    public static function validate_attendance_qr_token($token) {
        if (empty($token) || strpos($token, '-') === false) {
            return false;
        }

        $now = time();
        for ($i = -2; $i <= 1; $i++) {
            $test_time = $now + ($i * 5);
            $valid_token = self::generate_attendance_qr_token($test_time);
            if (hash_equals($valid_token, $token)) {
                return true;
            }
        }

        return false;
    }

    public function ajax_get_kiosk_qr() {
        $token = self::generate_attendance_qr_token();
        wp_send_json_success(array(
            'token'      => $token,
            'expires_in' => 5,
            'timestamp'  => date('Y-m-d H:i:s')
        ));
    }

    public static function get_monthly_payroll_attendance_summary($month_year = '', $user_id = 0, $branch_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_attendance';

        if (empty($month_year)) {
            $month_year = date('Y-m');
        }

        $where = array("DATE_FORMAT(attendance_date, '%%Y-%%m') = %s");
        $params = array($month_year);

        if ($user_id > 0) {
            $where[] = "user_id = %d";
            $params[] = $user_id;
        }

        if ($branch_id > 0) {
            $where[] = "branch_id = %d";
            $params[] = $branch_id;
        }

        $where_sql = implode(' AND ', $where);
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE $where_sql", $params);
        $records = $wpdb->get_results($sql, ARRAY_A);

        $present_count = 0;
        $absent_count = 0;
        $total_lateness = 0;
        $total_working_mins = 0;

        foreach ($records as $r) {
            if ($r['status'] === 'present' || $r['status'] === 'late') {
                $present_count++;
            } else if ($r['status'] === 'absent') {
                $absent_count++;
            }
            $total_lateness += intval($r['lateness_minutes']);
            $total_working_mins += intval($r['working_duration_minutes']);
        }

        $monthly_allowance = intval(Sportedia_Settings_Manager::get_setting('monthly_lateness_allowance', '30'));
        $excess_lateness = max(0, $total_lateness - $monthly_allowance);
        $remaining_allowance = max(0, $monthly_allowance - $total_lateness);

        return array(
            'month_year'          => $month_year,
            'present_days'        => $present_count,
            'absent_days'         => $absent_count,
            'total_lateness_mins' => $total_lateness,
            'allowed_lateness'    => $monthly_allowance,
            'excess_lateness'     => $excess_lateness,
            'remaining_allowance' => $remaining_allowance,
            'total_working_hours' => round($total_working_mins / 60, 2),
        );
    }

    public function ajax_process_employee_scan() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to scan attendance.');
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        $qr_token = isset($_POST['qr_token']) ? sanitize_text_field($_POST['qr_token']) : '';
        if (!self::validate_attendance_qr_token($qr_token)) {
            Sportedia_DB::log_activity('invalid_scan_attempt', 'Invalid or expired QR code scanned by ' . $user->display_name, $user_id);
            wp_send_json_error('Invalid or expired attendance QR code. Please scan the current live code.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_attendance';
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        // Get employee assigned primary branch
        $user_branches = Sportedia_User_Manager::get_user_branches($user_id);
        $branch_id = !empty($user_branches) ? intval(reset($user_branches)) : 0;

        // Check if employee already has an attendance record today
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND attendance_date = %s ORDER BY id DESC LIMIT 1",
            $user_id, $today
        ), ARRAY_A);

        $threshold_mins = intval(Sportedia_Settings_Manager::get_setting('checkout_threshold_minutes', '30'));
        if ($threshold_mins <= 0) $threshold_mins = 30;

        if ($existing) {
            $check_in_time = strtotime($existing['check_in_time'] ? $existing['check_in_time'] : $existing['created_at']);
            $diff_mins = floor((time() - $check_in_time) / 60);

            if ($diff_mins < $threshold_mins) {
                // Second scan within 30 minutes -> duplicate notice
                wp_send_json_success(array(
                    'type'    => 'duplicate',
                    'message' => 'Attendance has already been recorded today. Thank you.'
                ));
            } else {
                // Scan after 30 minutes -> Check-out
                $work_duration = floor((time() - $check_in_time) / 60);
                $wpdb->update($table, array(
                    'check_out_time'           => $now,
                    'working_duration_minutes' => $work_duration
                ), array('id' => $existing['id']));

                $hrs = floor($work_duration / 60);
                $mins = $work_duration % 60;
                $dur_str = ($hrs > 0 ? $hrs . ' hrs ' : '') . $mins . ' mins';

                Sportedia_DB::log_activity('employee_checkout', 'Check-out recorded for ' . $user->display_name . ' (Working Duration: ' . $dur_str . ')', $user_id);

                wp_send_json_success(array(
                    'type'    => 'checkout',
                    'message' => 'Check-out recorded successfully! Total working duration: ' . $dur_str . '.'
                ));
            }
        } else {
            // First scan of the day -> Check-in
            $schedule = get_user_meta($user_id, 'sportedia_work_schedule', true);
            $shift_start = !empty($schedule['start']) ? $schedule['start'] : '09:00';
            $shift_end   = !empty($schedule['end']) ? $schedule['end'] : '17:00';

            $scheduled_start_ts = strtotime($today . ' ' . $shift_start);
            $actual_check_in_ts = time();

            $lateness_mins = 0;
            $status = 'present';

            if ($actual_check_in_ts > $scheduled_start_ts) {
                $lateness_mins = floor(($actual_check_in_ts - $scheduled_start_ts) / 60);
                if ($lateness_mins > 0) {
                    $status = 'late';
                }
            }

            $wpdb->insert($table, array(
                'branch_id'                => $branch_id,
                'program_id'               => 0,
                'user_id'                  => $user_id,
                'user_type'                => 'employee',
                'attendance_date'          => $today,
                'check_in_time'            => $now,
                'scheduled_start'          => $shift_start,
                'scheduled_end'            => $shift_end,
                'lateness_minutes'         => $lateness_mins,
                'working_duration_minutes' => 0,
                'status'                   => $status,
                'checked_in_by'            => $user_id
            ));

            $late_msg = $lateness_mins > 0 ? ' (' . $lateness_mins . ' mins late)' : '';
            Sportedia_DB::log_activity('employee_checkin', 'Check-in recorded for ' . $user->display_name . $late_msg, $user_id);

            wp_send_json_success(array(
                'type'    => 'checkin',
                'message' => 'Check-in recorded successfully at ' . date('H:i') . $late_msg . '.'
            ));
        }
    }

    public static function get_attendance($date = '', $branch_id = 0, $program_id = 0, $user_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_attendance';

        $where = array('1=1');
        $params = array();

        if (!empty($date)) {
            $where[] = 'attendance_date = %s';
            $params[] = $date;
        }

        if ($branch_id > 0) {
            $where[] = 'branch_id = %d';
            $params[] = $branch_id;
        }

        if ($program_id > 0) {
            $where[] = 'program_id = %d';
            $params[] = $program_id;
        }

        if ($user_id > 0) {
            $where[] = 'user_id = %d';
            $params[] = $user_id;
        }

        $where_sql = implode(' AND ', $where);
        if (!empty($params)) {
            $sql = $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY id DESC", $params);
        } else {
            $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);
        $attendance = array();

        foreach ($results as $row) {
            $u = get_userdata($row['user_id']);
            $row['user_name']   = $u ? $u->display_name : 'Unknown User';
            $row['employee_id'] = get_user_meta($row['user_id'], 'sportedia_employee_id', true);

            $roles = $u ? (array) $u->roles : array();
            $role_name = !empty($roles) ? reset($roles) : 'Employee';
            $role_obj  = get_role($role_name);
            $row['role_label'] = $role_obj ? $role_obj->name : ucfirst($role_name);

            $branch_name = 'All Branches';
            if ($row['branch_id'] > 0) {
                $b = $wpdb->get_row($wpdb->prepare("SELECT branch_name FROM {$wpdb->prefix}sportedia_branches WHERE id = %d", $row['branch_id']));
                if ($b) $branch_name = $b->branch_name;
            }
            $row['branch_name'] = $branch_name;

            $checker = get_userdata($row['checked_in_by']);
            $row['checked_in_by_name'] = $checker ? $checker->display_name : 'System';

            $attendance[] = $row;
        }

        return $attendance;
    }

    public function ajax_record_attendance() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        // Strictly enforce permission rule: Only System Administrator and General Manager can manually modify
        if (!Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Unauthorized. Only System Administrator and General Manager can manually modify attendance records.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_attendance';

        $user_id    = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $branch_id  = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
        $date       = isset($_POST['attendance_date']) ? sanitize_text_field($_POST['attendance_date']) : date('Y-m-d');
        $status     = sanitize_text_field($_POST['status']);
        $user_type  = sanitize_text_field($_POST['user_type']);

        if ($user_id <= 0) {
            wp_send_json_error('Select a valid user.');
        }

        $target_user = get_userdata($user_id);
        $user_name = $target_user ? $target_user->display_name : 'User';

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND attendance_date = %s AND program_id = %d",
            $user_id, $date, $program_id
        ));

        $data = array(
            'branch_id'       => $branch_id,
            'program_id'      => $program_id,
            'user_id'         => $user_id,
            'user_type'       => !empty($user_type) ? $user_type : 'employee',
            'attendance_date' => $date,
            'status'          => $status,
            'checked_in_by'   => get_current_user_id()
        );

        if ($existing) {
            $wpdb->update($table, $data, array('id' => $existing));
            Sportedia_DB::log_activity('manual_attendance_update', 'Manually updated attendance record for ' . $user_name . ' on ' . $date);
            wp_send_json_success('Attendance updated.');
        } else {
            $wpdb->insert($table, $data);
            Sportedia_DB::log_activity('manual_attendance_create', 'Manually created attendance record for ' . $user_name . ' on ' . $date);
            wp_send_json_success('Attendance recorded.');
        }
    }

    public function ajax_delete_attendance() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        // Strictly enforce permission rule: Only System Administrator and General Manager can manually delete
        if (!Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Unauthorized. Only System Administrator and General Manager can manually delete attendance records.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_attendance';
        $att_id = isset($_POST['att_id']) ? intval($_POST['att_id']) : 0;

        if ($att_id > 0) {
            $att = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $att_id), ARRAY_A);
            $u_name = 'User #' . ($att ? $att['user_id'] : $att_id);
            if ($att && ($u = get_userdata($att['user_id']))) $u_name = $u->display_name;

            $wpdb->delete($table, array('id' => $att_id), array('%d'));
            Sportedia_DB::log_activity('manual_attendance_delete', 'Manually deleted attendance record for ' . $u_name);
            wp_send_json_success('Record deleted.');
        }

        wp_send_json_error('Invalid ID.');
    }
}
