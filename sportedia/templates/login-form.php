<?php
if (!defined('ABSPATH')) exit;

$error_msg = isset($_GET['login_error']) ? sanitize_text_field(urldecode($_GET['login_error'])) : '';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia – Access System</title>
    <?php wp_head(); ?>
    <style>
        body.sp-login-body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Google Sans Flex', -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .sp-login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 36px;
            margin: 20px;
        }
        .sp-login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .sp-login-logo {
            width: 48px;
            height: 48px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        .sp-login-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }
        .sp-login-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        .sp-alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .sp-login-tabs {
            display: flex;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 24px;
        }
        .sp-tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .sp-tab-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="sp-login-body">

<div class="sp-login-card">
    <div class="sp-login-header">
        <div class="sp-login-logo">S</div>
        <h1 class="sp-login-title">Sportedia</h1>
        <p class="sp-login-subtitle">System Login & Member Access Portal</p>
    </div>

    <?php if (!empty($error_msg)) : ?>
        <div class="sp-alert-error">
            <?php echo esc_html($error_msg); ?>
        </div>
    <?php endif; ?>

    <!-- LOGIN MODE TABS -->
    <div class="sp-login-tabs">
        <button type="button" class="sp-tab-btn active" id="tab_staff_btn" onclick="switchLoginTab('staff')">
            Staff Sign In
        </button>
        <button type="button" class="sp-tab-btn" id="tab_member_btn" onclick="switchLoginTab('member')">
            Member Access
        </button>
    </div>

    <!-- TAB 1: STAFF LOGIN -->
    <div id="tab_staff_content">
        <form method="post" action="">
            <?php wp_nonce_field('sportedia_login_action', 'sportedia_login_nonce'); ?>

            <div class="sp-form-group">
                <input type="text" name="log" id="user_login" class="sp-floating-input" placeholder=" " required autofocus>
                <label for="user_login" class="sp-floating-label">Employee ID or Email</label>
            </div>

            <div class="sp-form-group">
                <input type="password" name="pwd" id="user_pass" class="sp-floating-input" placeholder=" " required>
                <label for="user_pass" class="sp-floating-label">Password</label>
            </div>

            <button type="submit" name="sportedia_login_submit" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Sign In to Sportedia
            </button>
        </form>
    </div>

    <!-- TAB 2: MEMBER PORTAL ACCESS -->
    <div id="tab_member_content" style="display: none;">
        <form method="post" action="">
            <?php wp_nonce_field('sportedia_member_action', 'sportedia_member_nonce'); ?>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 12px; color: #64748b; margin-bottom: 16px;">
                Scan your Member Card Barcode or enter your Member ID below to access your member portal.
            </div>

            <div class="sp-form-group">
                <input type="text" name="member_id_input" id="member_id_input" class="sp-floating-input" placeholder=" " required autofocus>
                <label for="member_id_input" class="sp-floating-label">Scan Barcode / Member ID *</label>
            </div>

            <div class="sp-form-group">
                <input type="date" name="member_dob" id="member_dob" class="sp-floating-input" placeholder=" ">
                <label for="member_dob" class="sp-floating-label">Date of Birth (Optional Verification)</label>
            </div>

            <button type="submit" name="sportedia_member_access_submit" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px; margin-top: 8px; background-color: #10b981; border-color: #10b981;">
                Access Member Portal
            </button>
        </form>
    </div>
</div>

<script>
function switchLoginTab(mode) {
    if (mode === 'member') {
        jQuery('#tab_staff_btn').removeClass('active');
        jQuery('#tab_member_btn').addClass('active');
        jQuery('#tab_staff_content').hide();
        jQuery('#tab_member_content').show();
        jQuery('#member_id_input').focus();
    } else {
        jQuery('#tab_member_btn').removeClass('active');
        jQuery('#tab_staff_btn').addClass('active');
        jQuery('#tab_member_content').hide();
        jQuery('#tab_staff_content').show();
        jQuery('#user_login').focus();
    }
}
</script>

<?php wp_footer(); ?>
</body>
</html>
