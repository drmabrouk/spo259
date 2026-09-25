<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Page_Generator {

    public static function generate_pages() {
        $existing_page_id = get_option('sportedia_page_id');

        if ($existing_page_id && get_post($existing_page_id)) {
            // Page exists, ensure status is publish
            if (get_post_status($existing_page_id) !== 'publish') {
                wp_update_post(array(
                    'ID'          => $existing_page_id,
                    'post_status' => 'publish'
                ));
            }
            return $existing_page_id;
        }

        // Check by slug 'sportedia' to prevent duplicate creation
        $page_by_slug = get_page_by_path('sportedia');
        if ($page_by_slug) {
            update_option('sportedia_page_id', $page_by_slug->ID);
            return $page_by_slug->ID;
        }

        // Create main app page if not existing
        if (!$existing_page_id) {
            $page_data = array(
                'post_title'     => 'Sportedia',
                'post_name'      => 'sportedia',
                'post_content'   => '<!-- sportedia_app -->',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            );

            $page_id = wp_insert_post($page_data);
            if ($page_id && !is_wp_error($page_id)) {
                update_option('sportedia_page_id', $page_id);
            }
        }

        // Generate Attendance Kiosk Scan Page ('sportedia-scan')
        $scan_page_id = get_option('sportedia_scan_page_id');
        if (!$scan_page_id || !get_post($scan_page_id)) {
            $scan_by_slug = get_page_by_path('sportedia-scan');
            if ($scan_by_slug) {
                update_option('sportedia_scan_page_id', $scan_by_slug->ID);
            } else {
                $scan_page_data = array(
                    'post_title'     => 'Sportedia Attendance Kiosk',
                    'post_name'      => 'sportedia-scan',
                    'post_content'   => '<!-- sportedia_kiosk -->',
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed'
                );
                $new_scan_id = wp_insert_post($scan_page_data);
                if ($new_scan_id && !is_wp_error($new_scan_id)) {
                    update_option('sportedia_scan_page_id', $new_scan_id);
                }
            }
        }

        // Generate Verification System Page ('sportedia-verify')
        $verify_page_id = get_option('sportedia_verify_page_id');
        if (!$verify_page_id || !get_post($verify_page_id)) {
            $verify_by_slug = get_page_by_path('sportedia-verify');
            if ($verify_by_slug) {
                update_option('sportedia_verify_page_id', $verify_by_slug->ID);
            } else {
                $verify_page_data = array(
                    'post_title'     => 'Sportedia Verification System',
                    'post_name'      => 'sportedia-verify',
                    'post_content'   => '<!-- sportedia_verify -->',
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed'
                );
                $new_verify_id = wp_insert_post($verify_page_data);
                if ($new_verify_id && !is_wp_error($new_verify_id)) {
                    update_option('sportedia_verify_page_id', $new_verify_id);
                }
            }
        }

        return get_option('sportedia_page_id');
    }
}
