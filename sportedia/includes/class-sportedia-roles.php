<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Roles {

    public const ROLE_SYS_ADMIN      = 'sportedia_sys_admin';
    public const ROLE_FACILITY_MGR   = 'sportedia_facility_mgr';
    public const ROLE_OPERATIONS_MGR = 'sportedia_ops_mgr';
    public const ROLE_BOOKING_MGR    = 'sportedia_booking_mgr';
    public const ROLE_COACH          = 'sportedia_coach';
    public const ROLE_CUSTOMER       = 'sportedia_customer';
    public const ROLE_FINANCE_MGR    = 'sportedia_finance_mgr';

    public static function init() {
        self::register_roles();
    }

    private static function register_roles() {
        // Capabilities map
        $roles_config = array(
            self::ROLE_SYS_ADMIN => array(
                'display_name' => 'System Administrator',
                'caps' => array(
                    'read' => true,
                    'sportedia_access' => true,
                    'sportedia_manage_users' => true,
                    'sportedia_manage_branches' => true,
                    'sportedia_manage_subscriptions' => true,
                    'sportedia_manage_programs' => true,
                    'sportedia_manage_attendance' => true,
                    'sportedia_view_reports' => true,
                    'sportedia_manage_settings' => true,
                    'sportedia_import_export' => true,
                )
            ),
            self::ROLE_FACILITY_MGR => array(
                'display_name' => 'Facility Manager',
                'caps' => array(
                    'read' => true,
                    'sportedia_access' => true,
                    'sportedia_manage_branches' => true,
                    'sportedia_manage_programs' => true,
                    'sportedia_manage_attendance' => true,
                    'sportedia_view_reports' => true,
                )
            ),
            self::ROLE_OPERATIONS_MGR => array(
                'display_name' => 'Operations Manager',
                'caps' => array(
                    'read' => true,
                    'sportedia_access' => true,
                    'sportedia_manage_users' => true,
                    'sportedia_manage_branches' => true,
                    'sportedia_manage_programs' => true,
                    'sportedia_manage_attendance' => true,
                    'sportedia_view_reports' => true,
                )
            ),
            self::ROLE_BOOKING_MGR => array(
                'display_name' => 'Booking & Reception Manager',
                'caps' => array(
                    'read' => true,
                    'sportedia_access' => true,
                    'sportedia_manage_subscriptions' => true,
                    'sportedia_manage_attendance' => true,
                    'sportedia_view_reports' => true,
                )
            ),
            self::ROLE_COACH => array(
                'display_name' => 'Coach / Trainer',
                'caps' => array(
                    'read' => true,
                    'sportedia_access' => true,
                    'sportedia_view_programs' => true,
                    'sportedia_manage_attendance' => true,
                )
            ),
            self::ROLE_CUSTOMER => array(
                'display_name' => 'Customer / Member',
                'caps' => array(
                    'read' => true,
                    'sportedia_access' => true,
                    'sportedia_view_own_data' => true,
                )
            ),
            self::ROLE_FINANCE_MGR => array(
                'display_name' => 'Finance & Accounts Manager',
                'caps' => array(
                    'read' => true,
                    'sportedia_access' => true,
                    'sportedia_manage_subscriptions' => true,
                    'sportedia_view_reports' => true,
                )
            ),
        );

        foreach ($roles_config as $role_key => $data) {
            if (!get_role($role_key)) {
                add_role($role_key, $data['display_name'], $data['caps']);
            } else {
                $role_obj = get_role($role_key);
                foreach ($data['caps'] as $cap => $grant) {
                    $role_obj->add_cap($cap, $grant);
                }
            }
        }
    }

    public static function is_sportedia_user($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) return false;

        $sportedia_roles = array(
            self::ROLE_SYS_ADMIN,
            self::ROLE_FACILITY_MGR,
            self::ROLE_OPERATIONS_MGR,
            self::ROLE_BOOKING_MGR,
            self::ROLE_COACH,
            self::ROLE_CUSTOMER,
            self::ROLE_FINANCE_MGR,
        );

        $user_roles = (array) $user->roles;
        return (bool) array_intersect($sportedia_roles, $user_roles) || user_can($user, 'sportedia_access');
    }

    public static function is_sys_admin($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) return false;

        return in_array(self::ROLE_SYS_ADMIN, (array) $user->roles, true) || user_can($user, 'manage_options');
    }
}
