<?php
if (!defined('ABSPATH')) exit;

$initial_token = Sportedia_Attendance_Manager::generate_attendance_qr_token();
$ajax_url      = admin_url('admin-ajax.php');
$nonce         = wp_create_nonce('sportedia_nonce');
$current_user  = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia – Verification System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:wght@400;600;700&display=swap">
    <style>
        :root {
            --sp-bg: #f8f9fa;
            --sp-card: #ffffff;
            --sp-text: #111827;
            --sp-muted: #6b7280;
            --sp-border: #e5e7eb;
            --sp-radius: 12px;
            --sp-primary: #000000;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--sp-bg);
            font-family: 'Google Sans Flex', -apple-system, sans-serif;
            color: var(--sp-text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            direction: ltr;
        }

        .sp-verify-card {
            width: 100%;
            max-width: 480px;
            background: var(--sp-card);
            border: 1px solid var(--sp-border);
            border-radius: var(--sp-radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 32px 24px;
            box-sizing: border-box;
            text-align: center;
            margin: 20px;
        }

        .sp-verify-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .sp-verify-logo {
            width: 38px;
            height: 38px;
            background: var(--sp-primary);
            color: #ffffff;
            border-radius: var(--sp-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }

        .sp-toggle-bar {
            display: flex;
            background: var(--sp-bg);
            padding: 4px;
            border-radius: var(--sp-radius);
            margin-bottom: 24px;
            gap: 6px;
        }

        .sp-toggle-btn {
            flex: 1;
            padding: 10px 12px;
            border: none;
            border-radius: var(--sp-radius);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
            color: var(--sp-muted);
        }

        .sp-toggle-btn.active {
            background: var(--sp-primary);
            color: #ffffff;
        }

        .sp-qr-box {
            background: #ffffff;
            border: 2px solid var(--sp-border);
            border-radius: var(--sp-radius);
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        .sp-barcode-lines {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            height: 90px;
            width: 100%;
            max-width: 260px;
            margin-bottom: 12px;
        }

        .sp-barcode-line {
            background: var(--sp-primary);
            height: 100%;
            border-radius: 1px;
        }

        .sp-code-badge {
            font-family: monospace;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 2px;
            background: #f3f4f6;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .sp-timer-bar {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .sp-timer-progress {
            height: 100%;
            background: var(--sp-primary);
            width: 100%;
            transition: width 0.1s linear;
        }

        .sp-input {
            width: 100%;
            padding: 14px;
            font-size: 14px;
            border: 1px solid var(--sp-border);
            border-radius: var(--sp-radius);
            box-sizing: border-box;
            outline: none;
            margin-bottom: 12px;
            text-align: center;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .sp-input:focus {
            border-color: var(--sp-primary);
        }

        .sp-btn {
            width: 100%;
            padding: 14px;
            background: var(--sp-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--sp-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .sp-btn:hover {
            background: #222222;
        }

        /* Result Alerts */
        .sp-alert {
            padding: 16px;
            border-radius: var(--sp-radius);
            font-size: 13px;
            text-align: left;
            margin-top: 16px;
            display: none;
        }

        .sp-alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .sp-alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-weight: 700;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="sp-verify-card">
    <div class="sp-verify-header">
        <div class="sp-verify-logo">S</div>
        <div style="text-align: left;">
            <h2 style="margin: 0; font-size: 18px;">Verification System</h2>
            <span style="font-size: 12px; color: var(--sp-muted);">Sportedia Access & Verification</span>
        </div>
    </div>

    <!-- Toggle Modes -->
    <div class="sp-toggle-bar">
        <button type="button" id="tabAttendanceBtn" class="sp-toggle-btn active" onclick="switchVerifyTab('attendance')">
            Attendance System
        </button>
        <button type="button" id="tabMemberBtn" class="sp-toggle-btn" onclick="switchVerifyTab('member')">
            Member Verification
        </button>
    </div>

    <!-- MODE 1: Attendance System (Employee Dynamic QR) -->
    <div id="verifyAttendanceMode">
        <div class="sp-qr-box">
            <div id="spBarcodeContainer" class="sp-barcode-lines"></div>
            <div id="spCodeText" class="sp-code-badge"><?php echo esc_html($initial_token); ?></div>
        </div>

        <div class="sp-timer-bar">
            <div id="spTimerProgress" class="sp-timer-progress"></div>
        </div>
        <div id="spTimerText" style="font-size: 12px; color: var(--sp-muted); margin-bottom: 16px;">Refreshing code in 5.0s</div>

        <?php if (is_user_logged_in()) : ?>
            <button type="button" class="sp-btn" onclick="scanEmployeeToken()">
                Scan Staff Attendance Code
            </button>
        <?php else : ?>
            <p style="font-size: 12px; color: var(--sp-muted); margin: 0;">Log in to submit employee attendance scan.</p>
        <?php endif; ?>
    </div>

    <!-- MODE 2: Member Verification (Session Deduction) -->
    <div id="verifyMemberMode" style="display: none;">
        <p style="font-size: 13px; color: var(--sp-muted); margin-top: 0; margin-bottom: 16px;">
            Scan or enter permanent Member ID / Barcode to verify membership and deduct 1 session.
        </p>

        <form id="spMemberVerifyForm">
            <input type="text" id="sp_member_barcode" class="sp-input" placeholder="Scan or Enter Member ID / Barcode..." required autofocus>
            <button type="submit" class="sp-btn">
                Verify & Deduct Session
            </button>
        </form>

        <div id="spVerifySuccessAlert" class="sp-alert sp-alert-success">
            <div style="font-weight: 700; font-size: 15px; margin-bottom: 4px;" id="spResMemberName">Member Name</div>
            <div><strong>Member ID:</strong> <span id="spResMemberId">MEM-0000</span></div>
            <div><strong>Plan:</strong> <span id="spResPlanName">Gold Membership</span></div>
            <div><strong>Action:</strong> 1 Session Deducted</div>
            <div style="margin-top: 8px; font-weight: 700; font-size: 14px; border-top: 1px solid #bbf7d0; pt-6px;">
                Remaining Sessions: <span id="spResRemaining">11</span>
            </div>
        </div>

        <div id="spVerifyDangerAlert" class="sp-alert sp-alert-danger">
            No sessions remaining for this member.
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var currentToken = "<?php echo esc_js($initial_token); ?>";
var ajaxUrl = "<?php echo esc_js($ajax_url); ?>";
var nonce = "<?php echo esc_js($nonce); ?>";
var refreshInterval = 5000;
var lastFetchTime = Date.now();

function switchVerifyTab(mode) {
    if (mode === 'member') {
        $('#tabMemberBtn').addClass('active');
        $('#tabAttendanceBtn').removeClass('active');
        $('#verifyAttendanceMode').hide();
        $('#verifyMemberMode').show();
        $('#sp_member_barcode').focus();
    } else {
        $('#tabAttendanceBtn').addClass('active');
        $('#tabMemberBtn').removeClass('active');
        $('#verifyMemberMode').hide();
        $('#verifyAttendanceMode').show();
    }
}

function generateBarcodeSVG(text) {
    var container = $('#spBarcodeContainer');
    container.empty();
    var hash = 0;
    for (var i = 0; i < text.length; i++) {
        hash = text.charCodeAt(i) + ((hash << 5) - hash);
    }
    for (var j = 0; j < 34; j++) {
        var width = (Math.abs(hash + j * 11) % 4) + 1;
        var bar = $('<div>').addClass('sp-barcode-line').css('width', width + 'px');
        container.append(bar);
    }
}

function updateCode() {
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: { action: 'sportedia_get_kiosk_qr' },
        success: function(res) {
            if (res.success && res.data.token) {
                currentToken = res.data.token;
                $('#spCodeText').text(currentToken);
                generateBarcodeSVG(currentToken);
            }
        }
    });
}

generateBarcodeSVG(currentToken);

setInterval(function() {
    var now = Date.now();
    var elapsed = now - lastFetchTime;
    var remaining = Math.max(0, refreshInterval - elapsed);

    var pct = (remaining / refreshInterval) * 100;
    $('#spTimerProgress').css('width', pct + '%');
    $('#spTimerText').text('Refreshing code in ' + (remaining / 1000).toFixed(1) + 's');

    if (remaining <= 0) {
        lastFetchTime = Date.now();
        updateCode();
    }
}, 100);

function scanEmployeeToken() {
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'sportedia_process_employee_scan',
            nonce: nonce,
            qr_token: currentToken
        },
        success: function(res) {
            alert(res.data.message || (res.success ? 'Attendance recorded!' : res.data));
        }
    });
}

$('#spMemberVerifyForm').on('submit', function(e) {
    e.preventDefault();
    $('#spVerifySuccessAlert, #spVerifyDangerAlert').hide();

    var barcode = $('#sp_member_barcode').val().trim();
    if (!barcode) return;

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'sportedia_verify_member_session',
            nonce: nonce,
            member_barcode: barcode
        },
        success: function(res) {
            if (res.success) {
                var d = res.data;
                $('#spResMemberName').text(d.member_name);
                $('#spResMemberId').text(d.member_id);
                $('#spResPlanName').text(d.plan_name);
                $('#spResRemaining').text(d.sessions_remaining);
                $('#spVerifySuccessAlert').slideDown(200);
            } else {
                $('#spVerifyDangerAlert').text(res.data || 'No sessions remaining for this member.').slideDown(200);
            }
            $('#sp_member_barcode').val('').focus();
        },
        error: function() {
            $('#spVerifyDangerAlert').text('Network error during verification.').slideDown(200);
        }
    });
});
</script>
</body>
</html>
