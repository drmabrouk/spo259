<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Coach_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Module hooks if needed
    }

    public static function get_coaches_summary($search = '', $branch_id = 0) {
        global $wpdb;

        $coaches = get_users(array('role' => 'sportedia_coach'));
        $summary = array();

        $subs_table = $wpdb->prefix . 'sportedia_subscriptions';
        $prog_table = $wpdb->prefix . 'sportedia_programs';
        $att_table  = $wpdb->prefix . 'sportedia_attendance';

        foreach ($coaches as $c) {
            $coach_id = $c->ID;
            $name     = $c->display_name;
            $email    = $c->user_email;
            $phone    = get_user_meta($coach_id, 'sportedia_phone', true);
            $emp_id   = get_user_meta($coach_id, 'sportedia_employee_id', true);

            if (!empty($search)) {
                if (stripos($name, $search) === false && stripos($email, $search) === false && stripos($emp_id, $search) === false) {
                    continue;
                }
            }

            // Get assigned programs count
            $assigned_programs = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $prog_table WHERE coach_id = %d AND status = 'active'",
                $coach_id
            ));

            // Get assigned members count
            $assigned_members = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM $subs_table WHERE coach_id = %d AND status = 'active'",
                $coach_id
            ));

            // Get total verified completed sessions conducted by coach
            $completed_sessions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT a.id) FROM $att_table a
                 INNER JOIN $subs_table s ON a.program_id = s.program_id AND a.user_id = s.user_id
                 WHERE s.coach_id = %d AND a.status = 'present'",
                $coach_id
            ));

            $summary[] = array(
                'id'                 => $coach_id,
                'name'               => $name,
                'email'              => $email,
                'phone'              => $phone ? $phone : 'N/A',
                'employee_id'        => $emp_id ? $emp_id : 'COACH-' . $coach_id,
                'assigned_programs'  => intval($assigned_programs),
                'assigned_members'   => intval($assigned_members),
                'completed_sessions' => intval($completed_sessions)
            );
        }

        return $summary;
    }

    public static function get_coach_history($coach_id) {
        global $wpdb;
        $subs_table = $wpdb->prefix . 'sportedia_subscriptions';
        $att_table  = $wpdb->prefix . 'sportedia_attendance';

        $sql = $wpdb->prepare(
            "SELECT DISTINCT a.*, s.plan_name FROM $att_table a
             INNER JOIN $subs_table s ON a.program_id = s.program_id AND a.user_id = s.user_id
             WHERE s.coach_id = %d ORDER BY a.id DESC LIMIT 50",
            $coach_id
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }
}
