<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Branch_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_branch', array($this, 'ajax_save_branch'));
        add_action('wp_ajax_sportedia_delete_branch', array($this, 'ajax_delete_branch'));
    }

    public static function get_branches($search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_branches';

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $sql  = $wpdb->prepare("SELECT * FROM $table WHERE branch_name LIKE %s OR code LIKE %s ORDER BY id DESC", $like, $like);
        } else {
            $sql  = "SELECT * FROM $table ORDER BY id DESC";
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function ajax_save_branch() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_branches') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized access.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_branches';

        $branch_id   = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $branch_name = sanitize_text_field($_POST['branch_name']);
        $code        = sanitize_text_field($_POST['code']);
        $address     = sanitize_textarea_field($_POST['address']);
        $phone       = sanitize_text_field($_POST['phone']);
        $email       = sanitize_email($_POST['email']);
        $status      = sanitize_text_field($_POST['status']);

        if (empty($branch_name)) {
            wp_send_json_error('Branch name is required.');
        }

        $data = array(
            'branch_name' => $branch_name,
            'code'        => $code,
            'address'     => $address,
            'phone'       => $phone,
            'email'       => $email,
            'status'      => $status,
        );

        $format = array('%s', '%s', '%s', '%s', '%s', '%s');

        if ($branch_id > 0) {
            $wpdb->update($table, $data, array('id' => $branch_id), $format, array('%d'));
            wp_send_json_success('Branch updated successfully.');
        } else {
            $wpdb->insert($table, $data, $format);
            wp_send_json_success('Branch created successfully.');
        }
    }

    public function ajax_delete_branch() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_branches') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized access.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_branches';
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;

        if ($branch_id > 0) {
            $wpdb->delete($table, array('id' => $branch_id), array('%d'));
            wp_send_json_success('Branch deleted successfully.');
        }

        wp_send_json_error('Invalid branch ID.');
    }
}
