<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Settings_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_settings', array($this, 'ajax_save_settings'));
    }

    public static function get_setting($key, $default = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_settings';
        $val = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM $table WHERE setting_key = %s", $key));
        return $val !== null ? $val : $default;
    }

    public static function update_setting($key, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_settings';
        $wpdb->replace($table, array(
            'setting_key'   => sanitize_key($key),
            'setting_value' => sanitize_text_field($value)
        ), array('%s', '%s'));
    }

    public function ajax_save_settings() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_settings') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $val) {
                self::update_setting($key, $val);
            }
            wp_send_json_success('System settings saved successfully.');
        }

        wp_send_json_error('No settings provided.');
    }
}
