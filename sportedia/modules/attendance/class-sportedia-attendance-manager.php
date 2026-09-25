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
    }

    public static function get_attendance($date = '', $branch_id = 0, $program_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_attendance';

        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $where = array('attendance_date = %s');
        $params = array($date);

        if ($branch_id > 0) {
            $where[] = 'branch_id = %d';
            $params[] = $branch_id;
        }

        if ($program_id > 0) {
            $where[] = 'program_id = %d';
            $params[] = $program_id;
        }

        $where_sql = implode(' AND ', $where);
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY id DESC", $params);

        $results = $wpdb->get_results($sql, ARRAY_A);
        $attendance = array();

        foreach ($results as $row) {
            $u = get_userdata($row['user_id']);
            $row['user_name']   = $u ? $u->display_name : 'Unknown User';
            $row['employee_id'] = get_user_meta($row['user_id'], 'sportedia_employee_id', true);

            $checker = get_userdata($row['checked_in_by']);
            $row['checked_in_by_name'] = $checker ? $checker->display_name : 'System';

            $attendance[] = $row;
        }

        return $attendance;
    }

    public function ajax_record_attendance() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_attendance') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
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

        // Check for existing record on same date & program
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND attendance_date = %s AND program_id = %d",
            $user_id, $date, $program_id
        ));

        $data = array(
            'branch_id'       => $branch_id,
            'program_id'      => $program_id,
            'user_id'         => $user_id,
            'user_type'       => !empty($user_type) ? $user_type : 'customer',
            'attendance_date' => $date,
            'status'          => $status,
            'checked_in_by'   => get_current_user_id()
        );

        if ($existing) {
            $wpdb->update($table, $data, array('id' => $existing));
            wp_send_json_success('Attendance updated.');
        } else {
            $wpdb->insert($table, $data);
            wp_send_json_success('Attendance recorded.');
        }
    }

    public function ajax_delete_attendance() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_attendance') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_attendance';
        $att_id = isset($_POST['att_id']) ? intval($_POST['att_id']) : 0;

        if ($att_id > 0) {
            $wpdb->delete($table, array('id' => $att_id), array('%d'));
            wp_send_json_success('Record deleted.');
        }

        wp_send_json_error('Invalid ID.');
    }
}
