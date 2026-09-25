<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Auth {

    public function __construct() {
        add_action('init', array($this, 'handle_login_submission'));
        add_action('wp_logout', array($this, 'handle_logout_redirect'));
        add_filter('authenticate', array($this, 'authenticate_employee_id_or_email'), 20, 3);
    }

    public function authenticate_employee_id_or_email($user, $username, $password) {
        if (empty($username) || empty($password)) {
            return $user;
        }

        // 1. Try finding user by Employee ID custom meta
        $users = get_users(array(
            'meta_key'   => 'sportedia_employee_id',
            'meta_value' => $username,
            'number'     => 1,
        ));

        if (!empty($users)) {
            $matched_user = $users[0];
            if (wp_check_password($password, $matched_user->user_pass, $matched_user->ID)) {
                return $matched_user;
            }
        }

        // 2. Try finding user by email
        if (is_email($username)) {
            $user_by_email = get_user_by('email', $username);
            if ($user_by_email && wp_check_password($password, $user_by_email->user_pass, $user_by_email->ID)) {
                return $user_by_email;
            }
        }

        return $user;
    }

    public function handle_login_submission() {
        if (isset($_POST['sportedia_login_submit'])) {
            if (!isset($_POST['sportedia_login_nonce']) || !wp_verify_nonce($_POST['sportedia_login_nonce'], 'sportedia_login_action')) {
                wp_die('Security verification failed.');
            }

            $login_identifier = sanitize_text_field($_POST['log']);
            $password         = $_POST['pwd'];

            $creds = array(
                'user_login'    => $login_identifier,
                'user_password' => $password,
                'remember'      => true,
            );

            $user = wp_signon($creds, false);

            if (is_wp_error($user)) {
                $page_id = get_option('sportedia_page_id');
                $redirect_url = add_query_arg('login_error', urlencode($user->get_error_message()), get_permalink($page_id));
                wp_safe_redirect($redirect_url);
                exit;
            } else {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, true);
                $page_id = get_option('sportedia_page_id');
                wp_safe_redirect(get_permalink($page_id));
                exit;
            }
        }
    }

    public function handle_logout_redirect() {
        $page_id = get_option('sportedia_page_id');
        $redirect_url = $page_id ? get_permalink($page_id) : home_url('/sportedia/');
        wp_safe_redirect($redirect_url);
        exit;
    }
}
