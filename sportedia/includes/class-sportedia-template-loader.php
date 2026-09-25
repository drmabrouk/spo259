<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Template_Loader {

    public function __construct() {
        add_filter('template_include', array($this, 'load_sportedia_template'), 99);
    }

    public static function is_sportedia_page() {
        $sportedia_page_id = get_option('sportedia_page_id');
        return is_page($sportedia_page_id) || is_page('sportedia');
    }

    public static function is_scan_page() {
        $scan_page_id = get_option('sportedia_scan_page_id');
        return is_page($scan_page_id) || is_page('sportedia-scan');
    }

    public function load_sportedia_template($template) {
        if (self::is_scan_page()) {
            return SPORTEDIA_PLUGIN_DIR . 'templates/kiosk-attendance.php';
        }

        if (self::is_sportedia_page()) {
            if (!is_user_logged_in()) {
                return SPORTEDIA_PLUGIN_DIR . 'templates/login-form.php';
            }
            return SPORTEDIA_PLUGIN_DIR . 'templates/app-layout.php';
        }
        return $template;
    }
}
