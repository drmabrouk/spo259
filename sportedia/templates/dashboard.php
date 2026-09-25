<?php
if (!defined('ABSPATH')) exit;

$stats = Sportedia_Dashboard::get_stats_for_user();
$current_user = wp_get_current_user();

$user_id = get_current_user_id();
$user_subs = Sportedia_Subscription_Manager::get_subscriptions('', 0, '');
$has_expired = false;
$has_active  = false;
foreach ($user_subs as $ms) {
    if (intval($ms['user_id']) === $user_id) {
        if ($ms['status'] === 'expired') $has_expired = true;
        if ($ms['status'] === 'active') $has_active = true;
    }
}

$kiosk_page_id = get_option('sportedia_kiosk_page_id');
$kiosk_url = $kiosk_page_id ? get_permalink($kiosk_page_id) : home_url('/sportedia-kiosk/');
?>

<?php if ($has_expired && !$has_active) : ?>
    <div class="sp-card" style="background-color: #fef2f2; border-color: #fecaca; padding: 16px 20px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; color: #991b1b;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
                <strong style="font-size: 16px; display: block;">Subscription Expired</strong>
                <span style="font-size: 13px;">Your membership subscription has expired. Please contact reception or your facility administrator to renew your membership.</span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Dashboard Header -->
<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 class="sp-page-title">Dashboard Overview</h1>
        <p class="sp-page-subtitle">Welcome back, <?php echo esc_html($current_user->display_name); ?>. Here is your system performance overview.</p>
    </div>
    <a href="<?php echo esc_url($kiosk_url); ?>" target="_blank" class="sp-btn sp-btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 12h10"/><path d="M12 7v10"/></svg>
        Launch Verification System
    </a>
</div>

<!-- Key Statistics KPI Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <?php if (current_user_can('sportedia_manage_branches') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0; padding: 18px;">
            <div style="font-size: 12px; color: var(--sp-text-muted); font-weight: 600; text-transform: uppercase;">ACTIVE BRANCHES</div>
            <div style="font-size: 30px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_branches']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_users') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0; padding: 18px;">
            <div style="font-size: 12px; color: var(--sp-text-muted); font-weight: 600; text-transform: uppercase;">SYSTEM USERS</div>
            <div style="font-size: 30px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_users']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_subscriptions') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0; padding: 18px;">
            <div style="font-size: 12px; color: var(--sp-text-muted); font-weight: 600; text-transform: uppercase;">ACTIVE SUBSCRIPTIONS</div>
            <div style="font-size: 30px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_subs']); ?></div>
        </div>

        <div class="sp-card" style="margin-bottom:0; padding: 18px;">
            <div style="font-size: 12px; color: var(--sp-text-muted); font-weight: 600; text-transform: uppercase;">REMAINING SESSIONS</div>
            <div style="font-size: 30px; font-weight: 700; margin-top: 6px; color: #166534;"><?php echo esc_html($stats['total_sessions_remaining']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_programs') || current_user_can('sportedia_view_programs') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0; padding: 18px;">
            <div style="font-size: 12px; color: var(--sp-text-muted); font-weight: 600; text-transform: uppercase;">ACTIVE PROGRAMS</div>
            <div style="font-size: 30px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_programs']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_attendance') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0; padding: 18px;">
            <div style="font-size: 12px; color: var(--sp-text-muted); font-weight: 600; text-transform: uppercase;">TODAY'S ATTENDANCE</div>
            <div style="font-size: 30px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html($stats['today_att']); ?></div>
        </div>
    <?php endif; ?>
</div>

<?php
$my_member_subs = array_filter($user_subs, function($s) use ($user_id) {
    return intval($s['user_id']) === $user_id;
});
?>

<?php if (!empty($my_member_subs)) : ?>
    <div class="sp-card" style="margin-bottom: 24px;">
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; margin-bottom: 16px;">My Active Programs & Subscriptions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
            <?php foreach ($my_member_subs as $ms) : ?>
                <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: var(--sp-radius); padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <strong style="font-size: 15px; color: #000; display: block;"><?php echo esc_html($ms['plan_name']); ?></strong>
                            <code style="font-size: 11px; background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #e5e7eb;">#<?php echo esc_html($ms['invoice_number'] ? $ms['invoice_number'] : 'INV-OLD'); ?></code>
                        </div>
                        <span class="sp-badge <?php echo $ms['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($ms['status'])); ?>
                        </span>
                    </div>

                    <div style="font-size: 12px; color: var(--sp-text-muted); margin-bottom: 8px;">
                        <div>Coach: <?php echo esc_html($ms['coach_name']); ?></div>
                        <div>Branch: <?php echo esc_html($ms['branch_name']); ?></div>
                        <div>Period: <?php echo esc_html($ms['start_date'] . ' to ' . $ms['end_date']); ?></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--sp-border-color); padding-top: 8px; font-size: 12px;">
                        <span>Sessions Used:</span>
                        <strong style="color: #166534;"><?php echo esc_html(intval($ms['sessions_used'])); ?> / <?php echo esc_html(intval($ms['sessions_count'])); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- System Status & Operations -->
<div class="sp-card">
    <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; margin-bottom: 8px;">System Status & Operational Scope</h3>
    <p style="color: var(--sp-text-muted); font-size: 13.5px; margin: 0; line-height: 1.6;">
        The Sportedia online management system is active and operational. Use the interactive left sidebar navigation menu to manage system users, branch locations, training programs, customer subscriptions, daily attendance logs, and financial reports.
    </p>
</div>
