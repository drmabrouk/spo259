<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$current_page = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : 'dashboard';
$app_url = get_permalink(get_option('sportedia_page_id'));

$user_role_obj = !empty($current_user->roles) ? get_role(reset($current_user->roles)) : null;
$user_role_name = $user_role_obj ? $user_role_obj->name : 'Sportedia User';

$nav_items = array(
    'dashboard' => array(
        'label' => 'Dashboard',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
        'cap'   => 'sportedia_access'
    ),
    'subscriptions' => array(
        'label' => 'Subscriptions',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 21V5a2 2 0 00-2-2H10a2 2 0 00-2 2v16"/></svg>',
        'cap'   => 'sportedia_manage_subscriptions'
    ),
    'programs' => array(
        'label' => 'Program Management',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'cap'   => 'sportedia_manage_programs'
    ),
    'coaches' => array(
        'label' => 'Coach Management',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'cap'   => 'sportedia_manage_programs'
    ),
    'branches' => array(
        'label' => 'Branch Management',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 21h18M3 7v14M21 7v14M6 21V11m4 10V11m4 10V11m4 10V11M12 3L2 7h20L12 3z"/></svg>',
        'cap'   => 'sportedia_manage_branches'
    ),
    'users' => array(
        'label' => 'System Users',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'cap'   => 'sportedia_manage_users'
    ),
    'attendance' => array(
        'label' => 'Attendance Log',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        'cap'   => 'sportedia_manage_attendance'
    ),
    'reports' => array(
        'label' => 'Daily Reports',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'cap'   => 'sportedia_view_reports'
    ),
    'settings' => array(
        'label' => 'System Settings',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
        'cap'   => 'sportedia_manage_settings'
    )
);
?>

<button id="spMobileToggle" class="sp-mobile-toggle">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
</button>

<aside class="sp-sidebar-floating">
    <div class="sp-sidebar-brand">
        <div class="sp-brand-logo">S</div>
        <div class="sp-brand-name">Sportedia</div>
    </div>

    <nav class="sp-sidebar-nav">
        <?php foreach ($nav_items as $key => $item) : ?>
            <?php if (current_user_can($item['cap']) || Sportedia_Roles::is_sys_admin()) : ?>
                <a href="<?php echo esc_url(add_query_arg('module', $key, $app_url)); ?>"
                   class="sp-nav-item <?php echo $current_page === $key ? 'active' : ''; ?>">
                    <?php echo $item['icon']; ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sp-sidebar-footer">
        <div class="sp-user-badge">
            <div class="sp-user-avatar">
                <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 1))); ?>
            </div>
            <div class="sp-user-info">
                <span class="sp-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <span class="sp-user-role"><?php echo esc_html($user_role_name); ?></span>
            </div>
        </div>

        <a href="<?php echo esc_url(wp_logout_url($app_url)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm" style="width: 100%;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</aside>
