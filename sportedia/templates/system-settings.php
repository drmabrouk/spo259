<?php
if (!defined('ABSPATH')) exit;

$site_name          = Sportedia_Settings_Manager::get_setting('site_name', 'Sportedia');
$system_desc        = Sportedia_Settings_Manager::get_setting('system_desc', 'Complete Online Sports Management System');
$system_logo        = Sportedia_Settings_Manager::get_setting('system_logo', '');
$system_icon        = Sportedia_Settings_Manager::get_setting('system_icon', '');
$support_email      = Sportedia_Settings_Manager::get_setting('support_email', 'support@sportedia.online');

$timezone           = Sportedia_Settings_Manager::get_setting('timezone', 'Asia/Dubai');
$currency           = Sportedia_Settings_Manager::get_setting('currency', 'AED');
$default_vat        = Sportedia_Settings_Manager::get_setting('default_vat_rate', '5');

$default_user_role  = Sportedia_Settings_Manager::get_setting('default_user_role', 'sportedia_customer');
$grace_days         = Sportedia_Settings_Manager::get_setting('subscription_grace_days', '3');
$enable_renewal     = Sportedia_Settings_Manager::get_setting('enable_renewal_reminders', 'yes');

$branch_hours       = Sportedia_Settings_Manager::get_setting('branch_operating_hours', '06:00 - 23:00');
$max_branches       = Sportedia_Settings_Manager::get_setting('max_branches_per_mgr', '5');

$default_capacity   = Sportedia_Settings_Manager::get_setting('default_program_capacity', '20');
$allow_overbook     = Sportedia_Settings_Manager::get_setting('allow_overbooking', 'no');

$default_att_status = Sportedia_Settings_Manager::get_setting('default_attendance_status', 'present');
$allow_past_att     = Sportedia_Settings_Manager::get_setting('allow_past_attendance', 'yes');
$lateness_allowance = Sportedia_Settings_Manager::get_setting('monthly_lateness_allowance', '30');
$checkout_threshold = Sportedia_Settings_Manager::get_setting('checkout_threshold_minutes', '30');

$active_tab         = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'branding';

$app_url = get_permalink(get_option('sportedia_page_id'));

$tabs = array(
    'branding'      => 'Branding & General',
    'location'      => 'Location & Time',
    'users'         => 'Users & Memberships',
    'branches'      => 'Branches & Facilities',
    'programs'      => 'Programs & Capacity',
    'attendance'    => 'Attendance Policy'
);

$timezones_list = array(
    'Asia/Dubai'     => 'Dubai / UAE (GST +04:00)',
    'Asia/Abu_Dhabi' => 'Abu Dhabi (GST +04:00)',
    'UTC'            => 'UTC Standard Time',
    'Europe/London'  => 'London / UK (GMT)',
    'America/New_York' => 'New York / Eastern Time'
);
?>

<div class="sp-page-header" style="margin-bottom: 20px;">
    <h1 class="sp-page-title">System Settings</h1>
    <p class="sp-page-subtitle">Configure system options, branding, timezone defaults, user settings, and application parameters.</p>
</div>

<!-- Streamlined Settings Navigation Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--sp-border-color); padding-bottom: 12px; flex-wrap: wrap;">
    <?php foreach ($tabs as $key => $label) : ?>
        <a href="<?php echo esc_url(add_query_arg(array('module' => 'settings', 'tab' => $key), $app_url)); ?>"
           class="sp-btn <?php echo $active_tab === $key ? 'sp-btn-primary' : 'sp-btn-secondary'; ?> sp-btn-sm">
            <?php echo esc_html($label); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="sp-card" style="max-width: 640px;">
    <form id="spSettingsForm">
        <?php if ($active_tab === 'branding') : ?>
            <h3 style="margin-top:0;">System Branding & General Info</h3>

            <div class="sp-form-group">
                <input type="text" id="setting_site_name" name="settings[site_name]" class="sp-floating-input" value="<?php echo esc_attr($site_name); ?>" required>
                <label for="setting_site_name" class="sp-floating-label">System Name *</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="setting_system_desc" name="settings[system_desc]" class="sp-floating-input" value="<?php echo esc_attr($system_desc); ?>">
                <label for="setting_system_desc" class="sp-floating-label">System Description</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="setting_system_logo" name="settings[system_logo]" class="sp-floating-input" value="<?php echo esc_attr($system_logo); ?>" placeholder="https://...">
                <label for="setting_system_logo" class="sp-floating-label">System Logo URL</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="setting_system_icon" name="settings[system_icon]" class="sp-floating-input" value="<?php echo esc_attr($system_icon); ?>" placeholder="https://...">
                <label for="setting_system_icon" class="sp-floating-label">System Icon URL</label>
            </div>

            <div class="sp-form-group">
                <input type="email" id="setting_support_email" name="settings[support_email]" class="sp-floating-input" value="<?php echo esc_attr($support_email); ?>" required>
                <label for="setting_support_email" class="sp-floating-label">Support Email Address *</label>
            </div>

        <?php elseif ($active_tab === 'location') : ?>
            <h3 style="margin-top:0;">Location, Timezone & Currency</h3>

            <div class="sp-form-group">
                <select id="setting_timezone" name="settings[timezone]" class="sp-floating-select">
                    <?php foreach ($timezones_list as $tz_key => $tz_label) : ?>
                        <option value="<?php echo esc_attr($tz_key); ?>" <?php selected($timezone, $tz_key); ?>><?php echo esc_html($tz_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="setting_timezone" class="sp-floating-label">System Default Timezone</label>
            </div>

            <div class="sp-form-group">
                <select id="setting_currency" name="settings[currency]" class="sp-floating-select">
                    <option value="AED" <?php selected($currency, 'AED'); ?>>AED (United Arab Emirates Dirham)</option>
                    <option value="USD" <?php selected($currency, 'USD'); ?>>USD ($)</option>
                    <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR (€)</option>
                </select>
                <label for="setting_currency" class="sp-floating-label">System Default Currency</label>
            </div>

            <div class="sp-form-group">
                <input type="number" id="setting_vat" name="settings[default_vat_rate]" class="sp-floating-input" value="<?php echo esc_attr($default_vat); ?>">
                <label for="setting_vat" class="sp-floating-label">Standard VAT Rate (%)</label>
            </div>

        <?php elseif ($active_tab === 'users') : ?>
            <h3 style="margin-top:0;">Users & Membership Rules</h3>

            <div class="sp-form-group">
                <select id="setting_default_role" name="settings[default_user_role]" class="sp-floating-select">
                    <option value="sportedia_customer" <?php selected($default_user_role, 'sportedia_customer'); ?>>Customer / Member</option>
                    <option value="sportedia_coach" <?php selected($default_user_role, 'sportedia_coach'); ?>>Coach / Trainer</option>
                </select>
                <label for="setting_default_role" class="sp-floating-label">Default New Account Role</label>
            </div>

            <div class="sp-form-group">
                <input type="number" id="setting_grace_days" name="settings[subscription_grace_days]" class="sp-floating-input" value="<?php echo esc_attr($grace_days); ?>">
                <label for="setting_grace_days" class="sp-floating-label">Expiration Grace Period (Days)</label>
            </div>

            <div class="sp-form-group">
                <select id="setting_enable_renewal" name="settings[enable_renewal_reminders]" class="sp-floating-select">
                    <option value="yes" <?php selected($enable_renewal, 'yes'); ?>>Enabled</option>
                    <option value="no" <?php selected($enable_renewal, 'no'); ?>>Disabled</option>
                </select>
                <label for="setting_enable_renewal" class="sp-floating-label">Renewal Notifications</label>
            </div>

        <?php elseif ($active_tab === 'branches') : ?>
            <h3 style="margin-top:0;">Branch & Facility Settings</h3>

            <div class="sp-form-group">
                <input type="text" id="setting_branch_hours" name="settings[branch_operating_hours]" class="sp-floating-input" value="<?php echo esc_attr($branch_hours); ?>">
                <label for="setting_branch_hours" class="sp-floating-label">Standard Operating Hours</label>
            </div>

            <div class="sp-form-group">
                <input type="number" id="setting_max_branches" name="settings[max_branches_per_mgr]" class="sp-floating-input" value="<?php echo esc_attr($max_branches); ?>">
                <label for="setting_max_branches" class="sp-floating-label">Max Branches per Facility Manager</label>
            </div>

        <?php elseif ($active_tab === 'programs') : ?>
            <h3 style="margin-top:0;">Program & Capacity Settings</h3>

            <div class="sp-form-group">
                <input type="number" id="setting_default_capacity" name="settings[default_program_capacity]" class="sp-floating-input" value="<?php echo esc_attr($default_capacity); ?>">
                <label for="setting_default_capacity" class="sp-floating-label">Default Program Capacity Limit</label>
            </div>

            <div class="sp-form-group">
                <select id="setting_allow_overbooking" name="settings[allow_overbooking]" class="sp-floating-select">
                    <option value="no" <?php selected($allow_overbook, 'no'); ?>>No (Strict Capacity Limit)</option>
                    <option value="yes" <?php selected($allow_overbook, 'yes'); ?>>Yes (Allow Overbooking)</option>
                </select>
                <label for="setting_allow_overbooking" class="sp-floating-label">Allow Overbooking</label>
            </div>

        <?php elseif ($active_tab === 'attendance') : ?>
            <h3 style="margin-top:0;">Attendance Tracking Policy</h3>

            <div class="sp-form-group">
                <input type="number" id="setting_lateness_allowance" name="settings[monthly_lateness_allowance]" class="sp-floating-input" value="<?php echo esc_attr($lateness_allowance); ?>">
                <label for="setting_lateness_allowance" class="sp-floating-label">Monthly Allowed Lateness (Minutes)</label>
            </div>

            <div class="sp-form-group">
                <input type="number" id="setting_checkout_threshold" name="settings[checkout_threshold_minutes]" class="sp-floating-input" value="<?php echo esc_attr($checkout_threshold); ?>">
                <label for="setting_checkout_threshold" class="sp-floating-label">Check-out Re-scan Threshold (Minutes)</label>
            </div>

            <div class="sp-form-group">
                <select id="setting_default_att_status" name="settings[default_attendance_status]" class="sp-floating-select">
                    <option value="present" <?php selected($default_att_status, 'present'); ?>>Present</option>
                    <option value="late" <?php selected($default_att_status, 'late'); ?>>Late</option>
                    <option value="absent" <?php selected($default_att_status, 'absent'); ?>>Absent</option>
                </select>
                <label for="setting_default_att_status" class="sp-floating-label">Default Check-in Status</label>
            </div>

            <div class="sp-form-group">
                <select id="setting_allow_past_att" name="settings[allow_past_attendance]" class="sp-floating-select">
                    <option value="yes" <?php selected($allow_past_att, 'yes'); ?>>Allowed</option>
                    <option value="no" <?php selected($allow_past_att, 'no'); ?>>Current Day Only</option>
                </select>
                <label for="setting_allow_past_att" class="sp-floating-label">Retroactive Attendance Modification</label>
            </div>
        <?php endif; ?>

        <div style="border-top: 1px solid var(--sp-border-color); padding-top: 16px; margin-top: 16px;">
            <button type="submit" class="sp-btn sp-btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<script>
jQuery('#spSettingsForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_save_settings&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            alert(response.data);
            location.reload();
        } else {
            alert(response.data || 'Failed to save settings.');
        }
    });
});
</script>
