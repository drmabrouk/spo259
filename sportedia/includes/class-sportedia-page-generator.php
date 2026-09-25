<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Page_Generator {

    public static function generate_pages() {
        // 1. Dashboard Page ('sportedia') -> <!-- sportedia_app -->
        $dash_page_id = get_option('sportedia_page_id');
        if (!$dash_page_id || !get_post($dash_page_id)) {
            $dash_by_slug = get_page_by_path('sportedia');
            if ($dash_by_slug) {
                update_option('sportedia_page_id', $dash_by_slug->ID);
                $dash_page_id = $dash_by_slug->ID;
            } else {
                $dash_data = array(
                    'post_title'     => 'Sportedia',
                    'post_name'      => 'sportedia',
                    'post_content'   => '<!-- sportedia_app -->[sportedia_app]',
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed'
                );
                $new_dash_id = wp_insert_post($dash_data);
                if ($new_dash_id && !is_wp_error($new_dash_id)) {
                    update_option('sportedia_page_id', $new_dash_id);
                    $dash_page_id = $new_dash_id;
                }
            }
        } else if (get_post_status($dash_page_id) !== 'publish') {
            wp_update_post(array('ID' => $dash_page_id, 'post_status' => 'publish'));
        }

        // 2. Verification System Page ('sportedia-kiosk' / 'sportedia-verify') -> <!-- sportedia_kiosk -->
        $kiosk_page_id = get_option('sportedia_kiosk_page_id');
        if (!$kiosk_page_id || !get_post($kiosk_page_id)) {
            $kiosk_by_slug = get_page_by_path('sportedia-kiosk');
            if (!$kiosk_by_slug) $kiosk_by_slug = get_page_by_path('sportedia-verify');
            if (!$kiosk_by_slug) $kiosk_by_slug = get_page_by_path('sportedia-scan');

            if ($kiosk_by_slug) {
                update_option('sportedia_kiosk_page_id', $kiosk_by_slug->ID);
                update_option('sportedia_verify_page_id', $kiosk_by_slug->ID);
                update_option('sportedia_scan_page_id', $kiosk_by_slug->ID);
                $kiosk_page_id = $kiosk_by_slug->ID;
            } else {
                $kiosk_data = array(
                    'post_title'     => 'Verification System',
                    'post_name'      => 'sportedia-kiosk',
                    'post_content'   => '<!-- sportedia_kiosk -->[sportedia_kiosk]',
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed'
                );
                $new_kiosk_id = wp_insert_post($kiosk_data);
                if ($new_kiosk_id && !is_wp_error($new_kiosk_id)) {
                    update_option('sportedia_kiosk_page_id', $new_kiosk_id);
                    update_option('sportedia_verify_page_id', $new_kiosk_id);
                    update_option('sportedia_scan_page_id', $new_kiosk_id);
                    $kiosk_page_id = $new_kiosk_id;
                }
            }
        } else if (get_post_status($kiosk_page_id) !== 'publish') {
            wp_update_post(array('ID' => $kiosk_page_id, 'post_status' => 'publish'));
        }

        return get_option('sportedia_page_id');
    }
}
