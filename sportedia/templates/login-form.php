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
        .sp-input-wrapper {
            position: relative;
        }
        .sp-input-icon-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }
        .sp-input-icon-btn:hover {
            color: #0f172a;
        }
        .sp-legal-info-text {
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.4;
            margin-top: 16px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }
        #camera_scanner_container {
            display: none;
            margin-bottom: 16px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            text-align: center;
        }
        #camera_video {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
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

            <div class="sp-form-group sp-input-wrapper">
                <input type="password" name="pwd" id="user_pass" class="sp-floating-input" placeholder=" " required style="padding-right: 42px;">
                <label for="user_pass" class="sp-floating-label">Password</label>
                <button type="button" class="sp-input-icon-btn" onclick="togglePasswordVisibility('user_pass', this)" title="Toggle password visibility">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>

            <button type="submit" name="sportedia_login_submit" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Sign In to Sportedia
            </button>

            <!-- INFORMATIONAL PARAGRAPH FOR STAFF LOGIN -->
            <p class="sp-legal-info-text">
                Sportedia operates in accordance with applicable policies, terms and conditions, and includes appropriate protection of user rights and system data.
            </p>
        </form>
    </div>

    <!-- TAB 2: MEMBER PORTAL ACCESS -->
    <div id="tab_member_content" style="display: none;">
        <!-- Camera Scanner Stream View -->
        <div id="camera_scanner_container">
            <video id="camera_video" autoplay playsinline></video>
            <button type="button" onclick="stopCameraScanner()" class="sp-btn sp-btn-secondary sp-btn-sm" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: #fff; border: none;">
                Close Camera
            </button>
        </div>

        <form method="post" action="" id="spMemberAccessForm">
            <?php wp_nonce_field('sportedia_member_action', 'sportedia_member_nonce'); ?>

            <div class="sp-form-group sp-input-wrapper">
                <input type="text" name="member_id_input" id="member_id_input" class="sp-floating-input" placeholder=" " required style="padding-right: 42px;">
                <label for="member_id_input" class="sp-floating-label">Scan Barcode / Member ID *</label>
                <button type="button" class="sp-input-icon-btn" onclick="startCameraScanner()" title="Open camera barcode scanner">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </button>
            </div>

            <div class="sp-form-group">
                <input type="date" name="member_dob" id="member_dob" class="sp-floating-input" placeholder=" ">
                <label for="member_dob" class="sp-floating-label">Date of Birth (Optional Verification)</label>
            </div>

            <button type="submit" name="sportedia_member_access_submit" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px; margin-top: 8px; background-color: #10b981; border-color: #10b981;">
                Access Member Portal
            </button>

            <!-- INFORMATIONAL NOTE FOR MEMBER ACCESS -->
            <p class="sp-legal-info-text">
                Scan your official Sportedia membership card barcode or enter your Member ID to view your active subscription balances, training schedule, and session history securely.
            </p>
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

function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.45 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
}

var cameraStream = null;
var scannerInterval = null;

function startCameraScanner() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera access is not supported by your browser.');
        return;
    }

    jQuery('#camera_scanner_container').show();
    var video = document.getElementById('camera_video');

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            cameraStream = stream;
            video.srcObject = stream;

            if ('BarcodeDetector' in window) {
                var detector = new BarcodeDetector({ formats: ['code_128', 'qr_code', 'code_39', 'ean_13'] });
                scannerInterval = setInterval(function() {
                    detector.detect(video).then(function(barcodes) {
                        if (barcodes.length > 0) {
                            var code = barcodes[0].rawValue;
                            stopCameraScanner();
                            jQuery('#member_id_input').val(code);
                            jQuery('#spMemberAccessForm').submit();
                        }
                    }).catch(function(e) {});
                }, 400);
            }
        })
        .catch(function(err) {
            alert('Unable to access camera: ' + err.message);
            stopCameraScanner();
        });
}

function stopCameraScanner() {
    if (scannerInterval) clearInterval(scannerInterval);
    if (cameraStream) {
        cameraStream.getTracks().forEach(function(track) { track.stop(); });
        cameraStream = null;
    }
    jQuery('#camera_scanner_container').hide();
}
</script>

<?php wp_footer(); ?>
</body>
</html>
