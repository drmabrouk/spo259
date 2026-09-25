<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Program_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_categories() {
        return array(
            'Football',
            'Basketball',
            'Cricket',
            'Tennis',
            'Swimming',
            'Volleyball',
            'Table Tennis',
            'Badminton',
            'Athletics',
            'Baseball',
            'Sports Camp',
            'Recreational Games'
        );
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_program', array($this, 'ajax_save_program'));
        add_action('wp_ajax_sportedia_delete_program', array($this, 'ajax_delete_program'));
    }

    public static function get_programs($search = '', $branch_id = 0, $coach_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_programs';

        $where = array('1=1');
        $params = array();

        if ($branch_id > 0) {
            $where[] = 'branch_id = %d';
            $params[] = $branch_id;
        }

        if ($coach_id > 0) {
            $where[] = 'coach_id = %d';
            $params[] = $coach_id;
        }

        $where_sql = implode(' AND ', $where);

        if (!empty($params)) {
            $sql = $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY id DESC", $params);
        } else {
            $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);
        $programs = array();

        foreach ($results as $row) {
            if (!empty($search)) {
                if (stripos($row['program_name'], $search) === false && stripos($row['category'], $search) === false) {
                    continue;
                }
            }

            // Get Coach name
            $coach_user = $row['coach_id'] > 0 ? get_userdata($row['coach_id']) : null;
            $row['coach_name'] = $coach_user ? $coach_user->display_name : 'Unassigned';

            // Get Branch name
            $branch_name = 'All Branches';
            if ($row['branch_id'] > 0) {
                $b = $wpdb->get_row($wpdb->prepare("SELECT branch_name FROM {$wpdb->prefix}sportedia_branches WHERE id = %d", $row['branch_id']));
                if ($b) $branch_name = $b->branch_name;
            }
            $row['branch_name'] = $branch_name;

            $programs[] = $row;
        }

        return $programs;
    }

    public function ajax_save_program() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_programs') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_programs';

        $program_id     = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
        $branch_id      = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $coach_id       = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
        $program_name   = sanitize_text_field($_POST['program_name']);
        $category       = sanitize_text_field($_POST['category']);
        $schedule       = sanitize_text_field($_POST['schedule']);
        $capacity       = intval($_POST['capacity']);
        $sessions_count = isset($_POST['sessions_count']) ? intval($_POST['sessions_count']) : 12;
        $duration_days  = isset($_POST['duration_days']) ? intval($_POST['duration_days']) : 30;
        $status         = sanitize_text_field($_POST['status']);

        if (empty($program_name) || empty($category)) {
            wp_send_json_error('Program name and Sport Category are required.');
        }

        $data = array(
            'branch_id'      => $branch_id,
            'coach_id'       => $coach_id,
            'program_name'   => $program_name,
            'category'       => $category,
            'schedule'       => $schedule,
            'capacity'       => $capacity > 0 ? $capacity : 20,
            'sessions_count' => $sessions_count > 0 ? $sessions_count : 12,
            'duration_days'  => $duration_days > 0 ? $duration_days : 30,
            'status'         => $status,
        );

        $format = array('%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s');

        if ($program_id > 0) {
            $wpdb->update($table, $data, array('id' => $program_id), $format, array('%d'));
            wp_send_json_success('Program updated successfully.');
        } else {
            $wpdb->insert($table, $data, $format);
            wp_send_json_success('Program created successfully.');
        }
    }

    public function ajax_delete_program() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_programs') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_programs';
        $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;

        if ($program_id > 0) {
            $wpdb->delete($table, array('id' => $program_id), array('%d'));
            wp_send_json_success('Program deleted successfully.');
        }

        wp_send_json_error('Invalid program ID.');
    }
}
