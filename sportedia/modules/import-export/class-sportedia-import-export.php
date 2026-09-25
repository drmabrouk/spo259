<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Import_Export {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_export_csv', array($this, 'handle_export_csv'));
        add_action('wp_ajax_sportedia_import_csv', array($this, 'handle_import_csv'));
    }

    public function handle_export_csv() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $curr_u = wp_get_current_user();
        $admin_roles = array('sportedia_sys_admin', 'sportedia_general_mgr', 'sportedia_facility_mgr', 'sportedia_ops_mgr', 'sportedia_finance_mgr', 'administrator');
        $has_perm = false;
        foreach ((array)$curr_u->roles as $r) {
            if (in_array($r, $admin_roles, true) || current_user_can('sportedia_import_export') || current_user_can('manage_options')) {
                $has_perm = true;
                break;
            }
        }

        if (!$has_perm) {
            wp_die('Unauthorized. Export functionality is restricted to authorized administrative users.');
        }

        $type          = isset($_GET['export_type']) ? sanitize_text_field($_GET['export_type']) : 'users';
        $search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $role_filter   = isset($_GET['role_filter']) ? sanitize_text_field($_GET['role_filter']) : '';

        Sportedia_DB::log_activity('csv_export', 'Exported ' . $type . ' CSV dataset with active filters.');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sportedia_' . $type . '_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        if ($type === 'branches') {
            fputcsv($output, array('Branch ID', 'Branch Name', 'Code', 'Phone', 'Email', 'Status'));
            $branches = Sportedia_Branch_Manager::get_branches($search);
            foreach ($branches as $b) {
                fputcsv($output, array($b['id'], $b['branch_name'], $b['code'], $b['phone'], $b['email'], $b['status']));
            }
        } else if ($type === 'subscriptions') {
            fputcsv($output, array('Sub ID', 'Invoice No', 'Member', 'Employee ID', 'Plan Name', 'Type', 'Start Date', 'End Date', 'Price', 'Status'));
            $subs = Sportedia_Subscription_Manager::get_subscriptions($search, $branch_filter, $status_filter);
            foreach ($subs as $s) {
                fputcsv($output, array(
                    $s['id'],
                    $s['invoice_number'],
                    $s['member_name'],
                    $s['employee_id'],
                    $s['plan_name'],
                    $s['subscription_type'],
                    $s['start_date'],
                    $s['end_date'],
                    $s['price'],
                    $s['status']
                ));
            }
        } else if ($type === 'attendance') {
            fputcsv($output, array('Record ID', 'Employee ID', 'Employee Name', 'Role', 'Branch', 'Date', 'Scheduled Start', 'Check-In Time', 'Scheduled End', 'Check-Out Time', 'Lateness (Mins)', 'Working Duration (Mins)', 'Status'));
            $att = Sportedia_Attendance_Manager::get_attendance($search, $branch_filter, 0);
            foreach ($att as $a) {
                fputcsv($output, array(
                    $a['id'],
                    $a['employee_id'],
                    $a['user_name'],
                    $a['role_label'],
                    $a['branch_name'],
                    $a['attendance_date'],
                    $a['scheduled_start'],
                    $a['check_in_time'],
                    $a['scheduled_end'],
                    $a['check_out_time'],
                    $a['lateness_minutes'],
                    $a['working_duration_minutes'],
                    $a['status']
                ));
            }
        } else {
            fputcsv($output, array('User ID', 'Employee ID', 'Name', 'Email', 'Phone', 'Role', 'Status'));
            $users = Sportedia_User_Manager::get_users($search, $role_filter, $branch_filter);
            foreach ($users as $u) {
                fputcsv($output, array($u['id'], $u['employee_id'], $u['name'], $u['email'], $u['phone'], $u['role'], $u['status']));
            }
        }

        fclose($output);
        exit;
    }

    public function handle_import_csv() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $curr_u = wp_get_current_user();
        $admin_roles = array('sportedia_sys_admin', 'sportedia_general_mgr', 'sportedia_facility_mgr', 'administrator');
        $has_perm = false;
        foreach ((array)$curr_u->roles as $r) {
            if (in_array($r, $admin_roles, true) || current_user_can('sportedia_import_export') || current_user_can('manage_options')) {
                $has_perm = true;
                break;
            }
        }

        if (!$has_perm) {
            wp_send_json_error('Unauthorized. CSV Import is restricted to authorized administrative users.');
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error('No CSV file uploaded.');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error('Unable to open uploaded file.');
        }

        $header = fgetcsv($handle);
        $imported_count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 4) {
                $employee_id  = sanitize_text_field($row[0]);
                $name         = sanitize_text_field($row[1]);
                $email        = sanitize_email($row[2]);
                $role         = sanitize_text_field($row[3]);

                if (!empty($employee_id)) {
                    $existing_emp = get_users(array(
                        'meta_key'   => 'sportedia_employee_id',
                        'meta_value' => $employee_id,
                        'number'     => 1,
                    ));
                    if (!empty($existing_emp)) {
                        continue;
                    }
                }

                if (!empty($email) && !email_exists($email)) {
                    $username = strtolower(str_replace(' ', '', $employee_id));
                    if (empty($username) || username_exists($username)) {
                        $username = 'sp_' . rand(1000, 9999);
                    }
                    $random_pass = wp_generate_password(12, true);
                    $user_id = wp_create_user($username, $random_pass, $email);

                    if (!is_wp_error($user_id)) {
                        wp_update_user(array('ID' => $user_id, 'display_name' => $name));
                        $u = new WP_User($user_id);
                        $u->set_role(!empty($role) ? $role : 'sportedia_customer');
                        update_user_meta($user_id, 'sportedia_employee_id', $employee_id);
                        update_user_meta($user_id, 'sportedia_status', 'active');
                        $imported_count++;
                    }
                }
            }
        }

        fclose($handle);
        Sportedia_DB::log_activity('csv_import', 'Imported ' . $imported_count . ' users via CSV.');
        wp_send_json_success('Successfully imported ' . $imported_count . ' users without duplicates.');
    }
}
