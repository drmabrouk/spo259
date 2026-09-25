<?php
if (!defined('ABSPATH')) exit;

$error_msg = isset($_GET['login_error']) ? sanitize_text_field(urldecode($_GET['login_error'])) : '';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia – Login</title>
    <?php wp_head(); ?>
    <style>
        body.sp-login-body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: 'Google Sans Flex', -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .sp-login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            padding: 40px;
            margin: 20px;
        }
        .sp-login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .sp-login-logo {
            width: 48px;
            height: 48px;
            background: #000000;
            color: #ffffff;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .sp-login-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }
        .sp-login-subtitle {
            font-size: 13px;
            color: #6b7280;
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
    </style>
</head>
<body class="sp-login-body">

<div class="sp-login-card">
    <div class="sp-login-header">
        <div class="sp-login-logo">S</div>
        <h1 class="sp-login-title">Sportedia</h1>
        <p class="sp-login-subtitle">Online Management System Access</p>
    </div>

    <?php if (!empty($error_msg)) : ?>
        <div class="sp-alert-error">
            <?php echo esc_html($error_msg); ?>
        </div>
    <?php endif; ?>

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

<?php wp_footer(); ?>
</body>
</html>
