<?php
if (!defined('ABSPATH')) exit;

$sec_module = isset($_GET['sec_module']) ? sanitize_text_field($_GET['sec_module']) : 'dashboard';
$sec_url    = get_permalink(get_option('sportedia_secondary_page_id'));
if (empty($sec_url)) {
    $sec_url = home_url('/sportedia-secondary/');
}

$current_user   = wp_get_current_user();
$user_id        = $current_user->ID;
$user_role_obj  = !empty($current_user->roles) ? get_role(reset($current_user->roles)) : null;
$user_role_name = $user_role_obj ? $user_role_obj->name : 'Secondary Manager';

$branding_name  = Sportedia_Settings_Manager::get_setting('site_name', 'Sportedia');
$branding_logo  = Sportedia_Settings_Manager::get_setting('system_logo', '');
$user_avatar    = get_user_meta($user_id, 'sportedia_avatar', true);

$sec_nav_items = array(
    'dashboard' => array(
        'label' => 'Dashboard Overview',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>'
    ),
    'reports' => array(
        'label' => 'End-of-Day Reports',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>'
    ),
    'players' => array(
        'label' => 'Player Register & Session Tracking',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>'
    ),
    'coaches' => array(
        'label' => 'Coach Register',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>'
    ),
    'branches' => array(
        'label' => 'Branch Register',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 21h18M3 7v14M21 7v14M6 21V11m4 10V11m4 10V11m4 10V11M12 3L2 7h20L12 3z"/></svg>'
    )
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia Secondary Dashboard</title>
    <?php wp_head(); ?>
</head>
<body class="sportedia-app-body">

<div class="sp-app-layout">
    <!-- Floating Left Sidebar Navigation -->
    <button id="spMobileToggle" class="sp-mobile-toggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <aside class="sp-sidebar-floating">
        <div class="sp-sidebar-brand">
            <?php if (!empty($branding_logo)) : ?>
                <img src="<?php echo esc_url($branding_logo); ?>" style="width: 38px; height: 38px; border-radius: var(--sp-radius); object-fit: cover;">
            <?php else : ?>
                <div class="sp-brand-logo"><?php echo esc_html(strtoupper(substr($branding_name, 0, 1))); ?></div>
            <?php endif; ?>
            <div>
                <div class="sp-brand-name"><?php echo esc_html($branding_name); ?></div>
                <span style="font-size: 10px; color: var(--sp-text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Secondary System</span>
            </div>
        </div>

        <nav class="sp-sidebar-nav">
            <?php foreach ($sec_nav_items as $key => $item) : ?>
                <a href="<?php echo esc_url(add_query_arg('sec_module', $key, $sec_url)); ?>"
                   class="sp-nav-item <?php echo $sec_module === $key ? 'active' : ''; ?>">
                    <?php echo $item['icon']; ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div style="padding: 12px; background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: var(--sp-radius); margin-bottom: 12px; font-size: 11px; color: var(--sp-text-muted);">
            <strong style="display: block; color: var(--sp-text-main); margin-bottom: 2px;">Data Isolation Active</strong>
            Secondary Dashboard records operate in strict data isolation from Main Dashboard.
        </div>

        <!-- Merged Interactive Profile Badge -->
        <div class="sp-sidebar-footer">
            <div class="sp-user-badge" style="position: relative; padding-right: 36px;">
                <?php if (!empty($user_avatar)) : ?>
                    <img src="<?php echo esc_url($user_avatar); ?>" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid var(--sp-border-color); flex-shrink: 0;">
                <?php else : ?>
                    <div class="sp-user-avatar">
                        <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 1))); ?>
                    </div>
                <?php endif; ?>

                <div class="sp-user-info">
                    <span class="sp-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                    <span class="sp-user-role"><?php echo esc_html($user_role_name); ?></span>
                </div>

                <button type="button"
                        onclick="window.location.href='<?php echo esc_url(wp_logout_url($sec_url)); ?>';"
                        title="Logout"
                        style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; color: var(--sp-text-muted); padding: 4px;"
                        onmouseover="this.style.color='#dc2626'"
                        onmouseout="this.style.color='var(--sp-text-muted)'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="sp-main-content">
        <?php
        switch ($sec_module) {
            case 'reports':
                include SPORTEDIA_PLUGIN_DIR . 'templates/secondary-reports.php';
                break;
            case 'players':
                include SPORTEDIA_PLUGIN_DIR . 'templates/secondary-players.php';
                break;
            case 'coaches':
                include SPORTEDIA_PLUGIN_DIR . 'templates/secondary-coaches.php';
                break;
            case 'branches':
                include SPORTEDIA_PLUGIN_DIR . 'templates/secondary-branches.php';
                break;
            case 'dashboard':
            default:
                include SPORTEDIA_PLUGIN_DIR . 'templates/secondary-dashboard.php';
                break;
        }
        ?>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
