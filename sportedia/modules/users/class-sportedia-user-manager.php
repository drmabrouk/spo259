<?php
if (!defined('ABSPATH')) exit;

class Sportedia_User_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_user', array($this, 'ajax_save_user'));
        add_action('wp_ajax_sportedia_delete_user', array($this, 'ajax_delete_user'));
    }

    public static function get_users($search = '', $role = '', $branch_id = 0) {
        global $wpdb;

        $args = array(
            'number'  => 150,
            'orderby' => 'registered',
            'order'   => 'DESC'
        );

        if (!empty($role)) {
            $args['role'] = $role;
        }

        $wp_users = get_users($args);
        $result = array();

        foreach ($wp_users as $u) {
            $user_id = $u->ID;

            // Get Employee ID
            $emp_id = get_user_meta($user_id, 'sportedia_employee_id', true);
            if (empty($emp_id)) {
                $emp_id = 'EMP-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            }

            // Search filtering across Name, Email, Username, and Employee ID
            if (!empty($search)) {
                $s = strtolower(trim($search));
                $match = (stripos(strtolower($u->display_name), $s) !== false) ||
                         (stripos(strtolower($u->user_email), $s) !== false) ||
                         (stripos(strtolower($u->user_login), $s) !== false) ||
                         (stripos(strtolower($emp_id), $s) !== false);

                if (!$match) {
                    continue;
                }
            }

            // Get Status
            $status = get_user_meta($user_id, 'sportedia_status', true);
            if (empty($status)) $status = 'active';

            // Get Assigned Branches
            $assigned_branches = self::get_user_branches($user_id);

            // Filter by branch if specified
            if ($branch_id > 0 && !in_array($branch_id, $assigned_branches, true)) {
                continue;
            }

            $user_role_names = array_map(function($r) {
                $role_obj = get_role($r);
                return $role_obj ? $role_obj->name : $r;
            }, (array) $u->roles);

            $result[] = array(
                'id'           => $user_id,
                'employee_id'  => $emp_id,
                'username'     => $u->user_login,
                'name'         => $u->display_name,
                'email'        => $u->user_email,
                'role'         => implode(', ', $user_role_names),
                'role_key'     => !empty($u->roles) ? reset($u->roles) : '',
                'status'       => $status,
                'branches'     => $assigned_branches
            );
        }

        return $result;
    }

    public static function get_user_branches($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_user_branches';
        $results = $wpdb->get_col($wpdb->prepare("SELECT branch_id FROM $table WHERE user_id = %d", $user_id));
        return array_map('intval', $results);
    }

    public static function set_user_branches($user_id, $branch_ids = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_user_branches';
        $wpdb->delete($table, array('user_id' => $user_id), array('%d'));

        if (!empty($branch_ids) && is_array($branch_ids)) {
            foreach ($branch_ids as $b_id) {
                $wpdb->insert($table, array(
                    'user_id'   => $user_id,
                    'branch_id' => intval($b_id)
                ), array('%d', '%d'));
            }
        }
    }

    public function ajax_save_user() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized');
        }

        $user_id     = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $employee_id = sanitize_text_field($_POST['employee_id']);
        $display_name= sanitize_text_field($_POST['display_name']);
        $email       = sanitize_email($_POST['email']);
        $role        = sanitize_text_field($_POST['role']);
        $status      = sanitize_text_field($_POST['status']);
        $password    = isset($_POST['password']) ? $_POST['password'] : '';
        $branch_ids  = isset($_POST['branch_ids']) && is_array($_POST['branch_ids']) ? array_map('intval', $_POST['branch_ids']) : array();

        if (empty($display_name) || empty($email) || empty($role)) {
            wp_send_json_error('Required fields missing.');
        }

        if ($user_id > 0) {
            // Update User
            $user_data = array(
                'ID'           => $user_id,
                'display_name' => $display_name,
                'user_email'   => $email,
            );
            if (!empty($password)) {
                $user_data['user_pass'] = $password;
            }
            $updated = wp_update_user($user_data);
            if (is_wp_error($updated)) {
                wp_send_json_error($updated->get_error_message());
            }

            $u = new WP_User($user_id);
            $u->set_role($role);
        } else {
            // Create New User
            if (empty($employee_id)) {
                wp_send_json_error('Employee ID is required.');
            }
            if (empty($password)) {
                wp_send_json_error('Password is required for new users.');
            }

            $username = strtolower(str_replace(' ', '', $employee_id));
            if (username_exists($username)) {
                $username = 'sp_' . $username;
            }

            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
            }

            $u = new WP_User($user_id);
            $u->set_role($role);
            wp_update_user(array('ID' => $user_id, 'display_name' => $display_name));
        }

        update_user_meta($user_id, 'sportedia_employee_id', $employee_id);
        update_user_meta($user_id, 'sportedia_status', $status);
        self::set_user_branches($user_id, $branch_ids);

        wp_send_json_success('User saved successfully.');
    }

    public function ajax_delete_user() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized');
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if ($user_id === get_current_user_id()) {
            wp_send_json_error('Cannot delete yourself.');
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');

        // Remove branch associations
        self::set_user_branches($user_id, array());

        // Clean delete WP User to prevent orphaned accounts
        $deleted = wp_delete_user($user_id);

        if ($deleted) {
            wp_send_json_success('User permanently deleted.');
        } else {
            wp_send_json_error('Failed to delete user.');
        }
    }
}
