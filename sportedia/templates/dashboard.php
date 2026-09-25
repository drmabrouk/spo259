<?php
if (!defined('ABSPATH')) exit;

$stats = Sportedia_Dashboard::get_stats_for_user();
$current_user = wp_get_current_user();
?>

<div class="sp-page-header">
    <h1 class="sp-page-title">Dashboard Overview</h1>
    <p class="sp-page-subtitle">Welcome back, <?php echo esc_html($current_user->display_name); ?>. Here is your system performance overview.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <?php if (current_user_can('sportedia_manage_branches') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0;">
            <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Active Branches</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_branches']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_users') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0;">
            <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">System Users</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_users']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_subscriptions') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0;">
            <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Active Subscriptions</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_subs']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_programs') || current_user_can('sportedia_view_programs') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0;">
            <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Active Programs</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html($stats['total_programs']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('sportedia_manage_attendance') || Sportedia_Roles::is_sys_admin()) : ?>
        <div class="sp-card" style="margin-bottom:0;">
            <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Today's Attendance</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html($stats['today_att']); ?></div>
        </div>
    <?php endif; ?>
</div>

<div class="sp-card">
    <h3 style="margin-top: 0; font-size: 18px; font-weight: 700;">System Status & Information</h3>
    <p style="color: var(--sp-text-muted); font-size: 14px;">
        Sportedia frontend application layer is active and operational on <strong>sportedia.online</strong>. Select options from the floating sidebar menu to manage operations.
    </p>
</div>
