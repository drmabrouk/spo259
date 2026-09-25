<?php
if (!defined('ABSPATH')) exit;

$site_name    = Sportedia_Settings_Manager::get_setting('site_name', 'Sportedia Online');
$currency     = Sportedia_Settings_Manager::get_setting('currency', 'USD');
$support_email= Sportedia_Settings_Manager::get_setting('support_email', 'support@sportedia.online');
$session_pack = Sportedia_Settings_Manager::get_setting('session_pack_limit', '10');
?>

<div class="sp-page-header">
    <h1 class="sp-page-title">System Settings</h1>
    <p class="sp-page-subtitle">Configure core Sportedia application options and preferences directly from the frontend interface.</p>
</div>

<div class="sp-card" style="max-width: 600px;">
    <form id="spSettingsForm">
        <div class="sp-form-group">
            <input type="text" id="setting_site_name" name="settings[site_name]" class="sp-floating-input" value="<?php echo esc_attr($site_name); ?>" required>
            <label for="setting_site_name" class="sp-floating-label">Application System Title</label>
        </div>

        <div class="sp-form-group">
            <input type="email" id="setting_support_email" name="settings[support_email]" class="sp-floating-input" value="<?php echo esc_attr($support_email); ?>" required>
            <label for="setting_support_email" class="sp-floating-label">System Support Email</label>
        </div>

        <div class="sp-form-group">
            <select id="setting_currency" name="settings[currency]" class="sp-floating-select">
                <option value="USD" <?php selected($currency, 'USD'); ?>>USD ($)</option>
                <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR (€)</option>
                <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP (£)</option>
                <option value="SAR" <?php selected($currency, 'SAR'); ?>>SAR (SR)</option>
                <option value="AED" <?php selected($currency, 'AED'); ?>>AED (AED)</option>
            </select>
            <label for="setting_currency" class="sp-floating-label">Default Currency</label>
        </div>

        <div class="sp-form-group">
            <input type="number" id="setting_session_pack" name="settings[session_pack_limit]" class="sp-floating-input" value="<?php echo esc_attr($session_pack); ?>">
            <label for="setting_session_pack" class="sp-floating-label">Default Session Pack Capacity</label>
        </div>

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
