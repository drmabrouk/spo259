<?php
if (!defined('ABSPATH')) exit;

$site_name     = Sportedia_Settings_Manager::get_setting('site_name', 'Sportedia Online');
$currency      = Sportedia_Settings_Manager::get_setting('currency', 'AED');
$support_email = Sportedia_Settings_Manager::get_setting('support_email', 'support@sportedia.online');
$session_pack  = Sportedia_Settings_Manager::get_setting('session_pack_limit', '10');
$default_vat   = Sportedia_Settings_Manager::get_setting('default_vat_rate', '5');
$active_tab    = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

$app_url = get_permalink(get_option('sportedia_page_id'));

$tabs = array(
    'general'       => 'General Settings',
    'users'         => 'User / Role Settings',
    'branches'      => 'Branch Settings',
    'programs'      => 'Program Settings',
    'subscriptions' => 'Subscription Settings',
    'attendance'    => 'Attendance Settings',
    'reporting'     => 'Reporting Settings',
    'system'        => 'System Configuration',
);
?>

<div class="sp-page-header">
    <h1 class="sp-page-title">System Settings</h1>
    <p class="sp-page-subtitle">Configure system options, branch defaults, user settings, and application parameters.</p>
</div>

<!-- Settings Navigation Tabs -->
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
        <?php if ($active_tab === 'general') : ?>
            <h3 style="margin-top:0;">General Application Settings</h3>
            <div class="sp-form-group">
                <input type="text" id="setting_site_name" name="settings[site_name]" class="sp-floating-input" value="<?php echo esc_attr($site_name); ?>" required>
                <label for="setting_site_name" class="sp-floating-label">Application System Title</label>
            </div>
            <div class="sp-form-group">
                <input type="email" id="setting_support_email" name="settings[support_email]" class="sp-floating-input" value="<?php echo esc_attr($support_email); ?>" required>
                <label for="setting_support_email" class="sp-floating-label">Support Email Address</label>
            </div>
        <?php elseif ($active_tab === 'users') : ?>
            <h3 style="margin-top:0;">User & Role Configuration</h3>
            <div class="sp-form-group">
                <select id="setting_default_role" name="settings[default_user_role]" class="sp-floating-select">
                    <option value="sportedia_customer">Customer / Member</option>
                    <option value="sportedia_coach">Coach / Trainer</option>
                </select>
                <label for="setting_default_role" class="sp-floating-label">Default New Account Role</label>
            </div>
        <?php elseif ($active_tab === 'system') : ?>
            <h3 style="margin-top:0;">System Configuration</h3>
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
        <?php else : ?>
            <h3 style="margin-top:0;"><?php echo esc_html($tabs[$active_tab]); ?></h3>
            <p style="color: var(--sp-text-muted); font-size: 14px;">Operational parameters for this module are active under default system policy.</p>
            <div class="sp-form-group">
                <input type="number" id="setting_session_pack" name="settings[session_pack_limit]" class="sp-floating-input" value="<?php echo esc_attr($session_pack); ?>">
                <label for="setting_session_pack" class="sp-floating-label">Default Capacity / Session Limit</label>
            </div>
        <?php endif; ?>

        <button type="submit" class="sp-btn sp-btn-primary">Save Settings</button>
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
