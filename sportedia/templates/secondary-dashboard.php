<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$today = date('Y-m-d');

$players_table = $wpdb->prefix . 'sportedia_sec_players';
$coaches_table = $wpdb->prefix . 'sportedia_sec_coaches';
$att_table     = $wpdb->prefix . 'sportedia_sec_attendance';
$eod_table     = $wpdb->prefix . 'sportedia_sec_eod_records';
$rep_table     = $wpdb->prefix . 'sportedia_sec_daily_reports';

// Today Stats
$players_added_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $players_table WHERE DATE(created_at) = %s", $today));
$coaches_added_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $coaches_table WHERE DATE(created_at) = %s", $today));

$new_regs_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $eod_table WHERE item_date = %s AND registration_status LIKE '%%New%%'", $today));
$renewals_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $eod_table WHERE item_date = %s AND registration_status LIKE '%%Renewal%%'", $today));

$sessions_today = $wpdb->get_var($wpdb->prepare("SELECT SUM(classes_used) FROM $att_table WHERE attendance_date = %s", $today));
if (!$sessions_today) $sessions_today = 0;

$player_att_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT player_id) FROM $att_table WHERE attendance_date = %s", $today));
$coach_att_today  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT coach_name) FROM $att_table WHERE attendance_date = %s", $today));

$income_eod = $wpdb->get_var($wpdb->prepare("SELECT SUM(payment_amount) FROM $eod_table WHERE item_date = %s", $today));
if (!$income_eod) $income_eod = 0;

// Fetch Today's Activity Stream
$today_activities = $wpdb->get_results($wpdb->prepare("SELECT * FROM $att_table WHERE attendance_date = %s ORDER BY id DESC LIMIT 15", $today), ARRAY_A);
?>

<div class="sp-page-header" style="margin-bottom: 20px;">
    <h1 class="sp-page-title">Secondary Dashboard — Today's Overview</h1>
    <p class="sp-page-subtitle">Real-time daily operations overview for <?php echo esc_html($today); ?>. Data isolated from Main System.</p>
</div>

<!-- Today's Summary KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">PLAYERS ADDED TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html(intval($players_added_today)); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">COACHES ADDED TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html(intval($coaches_added_today)); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">NEW REGISTRATIONS TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: #166534;"><?php echo esc_html(intval($new_regs_today)); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">RENEWALS TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html(intval($renewals_today)); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">SESSIONS RECORDED TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: #166534;"><?php echo esc_html(intval($sessions_today)); ?> classes</div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">PLAYER ATTENDANCE TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html(intval($player_att_today)); ?> players</div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">COACH ATTENDANCE TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: var(--sp-text-main);"><?php echo esc_html(intval($coach_att_today)); ?> coaches</div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 11px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase;">TOTAL INCOME TODAY</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 6px; color: #166534;"><?php echo esc_html(Sportedia_Finance::format_price($income_eod)); ?></div>
    </div>
</div>

<!-- Today's Activity Stream -->
<div class="sp-card">
    <h3 style="margin-top:0; font-size: 18px; font-weight: 700; margin-bottom: 12px;">Today's Activity & Attendance Log</h3>
    <p style="color: var(--sp-text-muted); font-size: 13px; margin-bottom: 16px;">
        Real-time player attendance sessions and coach check-ins recorded today (<?php echo esc_html($today); ?>).
    </p>

    <?php if (!empty($today_activities)) : ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px;">
            <?php foreach ($today_activities as $act) : ?>
                <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; font-size: 12.5px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                        <div>
                            <strong style="color: #000; font-size: 14px; display: block;"><?php echo esc_html($act['player_name']); ?></strong>
                            <span style="font-family: monospace; font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html($act['player_code']); ?></span>
                        </div>
                        <span class="sp-badge sp-badge-active" style="font-size: 10px;">1 Class Used</span>
                    </div>

                    <div style="color: var(--sp-text-muted); font-size: 12px;">
                        <div><strong>Coach:</strong> <?php echo esc_html($act['coach_name']); ?></div>
                        <div><strong>Time Slot:</strong> <?php echo esc_html($act['period']); ?> (Entry: <?php echo esc_html($act['entry_time']); ?>)</div>
                        <div><strong>Remaining:</strong> <?php echo esc_html($act['remaining_classes']); ?> classes</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div style="text-align: center; color: var(--sp-text-muted); padding: 32px; background: #f8f9fa; border-radius: 8px; border: 1px dashed var(--sp-border-color);">
            No attendance or session activity recorded yet today.
        </div>
    <?php endif; ?>
</div>
