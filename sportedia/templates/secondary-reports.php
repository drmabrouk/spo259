<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$report_date = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : date('Y-m-d');
$branch_name = isset($_GET['branch']) ? sanitize_text_field($_GET['branch']) : 'ISCS MUW';

$rep_table = $wpdb->prefix . 'sportedia_sec_daily_reports';
$eod_table = $wpdb->prefix . 'sportedia_sec_eod_records';

$existing_rep = $wpdb->get_row($wpdb->prepare("SELECT * FROM $rep_table WHERE report_date = %s AND branch = %s", $report_date, $branch_name), ARRAY_A);
$eod_items    = $wpdb->get_results($wpdb->prepare("SELECT * FROM $eod_table WHERE report_date = %s ORDER BY serial_no ASC, id ASC", $report_date), ARRAY_A);

$saved_selected_notes = array();
if ($existing_rep && !empty($existing_rep['selected_notes'])) {
    $decoded = json_decode($existing_rep['selected_notes'], true);
    if (is_array($decoded)) {
        $saved_selected_notes = $decoded;
    }
}

$predefined_notes_options = array(
    'All staff and coaches were present today.',
    'All scheduled staff members were present today.',
    'All coaches were present today.',
    'Some staff members were absent today.',
    'Some coaches were absent today.',
    'Staff attendance was completed without operational issues.',
    'Several customers/parents were contacted regarding their previous subscriptions.',
    'Former customers/parents were contacted and encouraged to renew their subscriptions.',
    'Follow-up was completed with customers whose subscriptions had expired.',
    'Follow-up was completed with absent players and their parents.',
    'Parents were contacted regarding missed sessions and attendance.',
    'Follow-up was conducted with inactive players.',
    'Several new customer inquiries were received today.',
    'Customer inquiries were followed up and addressed today.',
    'Registration and renewal follow-ups were completed today.',
    'Additional follow-up is required with inactive customers.',
    'Make-up sessions were discussed with absent players/parents.',
    'No operational issues were reported today.'
);

$export_nonce = wp_create_nonce('sportedia_nonce');
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">End-of-Day Reports — Secondary Dashboard</h1>
        <p class="sp-page-subtitle">Two Independent Report Systems: Structured WhatsApp Statement & Printable 11-Column Excel Workbook.</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_eod&report_date=' . $report_date . '&export_format=xls&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-primary sp-btn-sm" style="background-color: #0284c7; border-color: #0284c7;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download Excel Report (.XLS A-K)
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_eod&report_date=' . $report_date . '&export_format=csv&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            Download CSV Export
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_attendance&attendance_date=' . $report_date . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            Attendance Export
        </a>
    </div>
</div>

<!-- Date & Branch Filter / History Access -->
<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="sec_module" value="reports">

        <div style="width: 200px;">
            <label style="font-size: 11px; font-weight: 600; color: var(--sp-text-muted); display: block; margin-bottom: 2px;">REPORT DATE</label>
            <input type="date" name="report_date" class="sp-floating-input" value="<?php echo esc_attr($report_date); ?>">
        </div>

        <div style="width: 220px;">
            <label style="font-size: 11px; font-weight: 600; color: var(--sp-text-muted); display: block; margin-bottom: 2px;">BRANCH NAME</label>
            <input type="text" name="branch" class="sp-floating-input" value="<?php echo esc_attr($branch_name); ?>" placeholder="Branch Name">
        </div>

        <div style="margin-top: 16px;">
            <button type="submit" class="sp-btn sp-btn-primary">Load Report Date & History</button>
        </div>
    </form>
</div>

<!-- ========================================================================= -->
<!-- REPORT SYSTEM 1: WHATSAPP DAILY REPORT -->
<!-- ========================================================================= -->
<div class="sp-grid-2" style="margin-bottom: 24px;">
    <!-- Form 1: WhatsApp Report Builder -->
    <div class="sp-card" style="margin-bottom:0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
            <h3 style="margin:0; font-size: 16px; font-weight: 700; color: #16a34a; display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                1. WhatsApp Daily Report Generator
            </h3>
            <span class="sp-badge" style="background:#dcfce7; color:#15803d;">Saved Record</span>
        </div>

        <form id="spSecRepForm">
            <input type="hidden" name="report_date" value="<?php echo esc_attr($report_date); ?>">
            <input type="hidden" name="branch" value="<?php echo esc_attr($branch_name); ?>">

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" id="rep_new_regs" name="new_registrations" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['new_registrations'] : ''); ?>" oninput="buildWaMessage()">
                    <label for="rep_new_regs" class="sp-floating-label">New Registrations</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" id="rep_renewals" name="renewals" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['renewals'] : ''); ?>" oninput="buildWaMessage()">
                    <label for="rep_renewals" class="sp-floating-label">Renewals Count</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" step="0.01" id="rep_cash_pay" name="cash_payments" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['cash_payments'] : ''); ?>" oninput="buildWaMessage()">
                    <label for="rep_cash_pay" class="sp-floating-label">TOTAL CASH (AED)</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" step="0.01" id="rep_card_pay" name="card_payments" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['card_payments'] : ''); ?>" oninput="buildWaMessage()">
                    <label for="rep_card_pay" class="sp-floating-label">TOTAL CARD (AED)</label>
                </div>
            </div>

            <!-- CAP QUANTITY CALCULATION SECTION -->
            <div style="background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--sp-text-muted);">SWIMMING CAP SALES (25 AED / CAP)</label>
                    <span id="caps_calc_preview" style="font-size: 12px; font-weight: bold; color: var(--sp-primary-color);">Total: 0 AED</span>
                </div>
                <div class="sp-form-group" style="margin-bottom:0;">
                    <input type="number" id="rep_caps" name="caps_count" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['caps_count'] : ''); ?>" placeholder=" " oninput="updateCapCalc(); buildWaMessage();">
                    <label for="rep_caps" class="sp-floating-label">Cap Quantity</label>
                </div>
            </div>

            <!-- DEDICATED PLAYER ABSENCE FIELDS -->
            <div style="background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; margin-bottom: 14px;">
                <label style="font-size: 11px; font-weight: 700; color: var(--sp-text-muted); display: block; margin-bottom: 8px;">PLAYER ABSENCE TRACKING</label>
                <div class="sp-grid-2">
                    <div class="sp-form-group" style="margin-bottom:0;">
                        <input type="number" id="rep_bball_abs" name="basketball_absences_count" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['basketball_absences_count'] : ''); ?>" oninput="buildWaMessage()">
                        <label for="rep_bball_abs" class="sp-floating-label">🏀 Basketball Player Absences</label>
                    </div>

                    <div class="sp-form-group" style="margin-bottom:0;">
                        <input type="number" id="rep_swim_abs" name="swimming_absences_count" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['swimming_absences_count'] : ''); ?>" oninput="buildWaMessage()">
                        <label for="rep_swim_abs" class="sp-floating-label">🏊‍♂️ Swimming Player Absences</label>
                    </div>
                </div>
            </div>

            <!-- PREDEFINED SELECTABLE NOTES (Includes Staff/Coach Status) -->
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: var(--sp-text-main); display: block; margin-bottom: 8px;">Professional Notes / Daily Status (Multi-select)</label>
                <div style="background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 10px; max-height: 180px; overflow-y: auto;">
                    <?php foreach ($predefined_notes_options as $idx => $note_text) :
                        $isChecked = in_array($note_text, $saved_selected_notes, true);
                    ?>
                        <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: var(--sp-text-main); margin-bottom: 6px; cursor: pointer;">
                            <input type="checkbox" name="selected_notes[]" class="wa-note-checkbox" value="<?php echo esc_attr($note_text); ?>" <?php checked($isChecked); ?> onchange="buildWaMessage()">
                            <span><?php echo esc_html($note_text); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sp-form-group">
                <textarea id="rep_recs" name="recommendations" class="sp-floating-input" style="height: 50px;" oninput="buildWaMessage()"><?php echo esc_textarea($existing_rep ? $existing_rep['recommendations'] : ''); ?></textarea>
                <label for="rep_recs" class="sp-floating-label">Additional Custom Notes</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="submit" class="sp-btn sp-btn-primary">Save Report</button>
            </div>
        </form>
    </div>

    <!-- Live Preview & WhatsApp Output -->
    <div class="sp-card" style="margin-bottom:0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="margin-top:0; font-size: 16px; font-weight: 700; margin-bottom: 12px; color: var(--sp-text-primary);">WhatsApp Formatted Statement Preview</h3>
            <div id="waPreviewBox" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #111827; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 12.5px; white-space: pre-wrap; line-height: 1.5; min-height: 320px; font-weight: 500;">
            </div>
        </div>

        <div style="margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="jQuery('#rep_new_regs').focus()">
                Edit Report
            </button>
            <button type="button" class="sp-btn sp-btn-primary" style="background-color: #16a34a; border-color: #16a34a;" onclick="copyWaText()">
                Copy Report
            </button>
            <button type="button" class="sp-btn sp-btn-primary" style="background-color: #25d366; border-color: #25d366;" onclick="openWhatsAppDirect()">
                Open WhatsApp
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- REPORT SYSTEM 2: EXCEL ITEMIZED DAILY TRANSACTIONS (11 COLUMNS A TO K) -->
<!-- ========================================================================= -->
<div class="sp-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0284c7; display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                2. Excel Professional Report Data (Exact Columns A–K for <?php echo esc_html($report_date); ?>)
            </h3>
            <p style="color: var(--sp-text-muted); font-size: 12px; margin: 2px 0 0 0;">
                All textual data is saved to DB and automatically exported in <strong>UPPERCASE</strong> formatted Excel workbook (.xls) and CSV.
            </p>
        </div>
        <button type="button" class="sp-btn sp-btn-primary sp-btn-sm" onclick="openEodItemModal()">
            + Add Transaction Row
        </button>
    </div>

    <div class="sp-table-wrapper">
        <table class="sp-table">
            <thead>
                <tr>
                    <th style="width: 50px;">A: Serial</th>
                    <th style="width: 100px;">B: Date</th>
                    <th style="width: 90px;">C: ID</th>
                    <th>D: Name</th>
                    <th>E: Branch</th>
                    <th>F: Academy</th>
                    <th>G: Program</th>
                    <th>H: Registration Type</th>
                    <th style="text-align: right;">I: Payment Amount</th>
                    <th>J: Payment Method</th>
                    <th>K: Notes</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($eod_items)) : ?>
                    <?php
                    $total_eod_amount = 0;
                    foreach ($eod_items as $item) :
                        $amt = floatval($item['payment_amount']);
                        $total_eod_amount += $amt;
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($item['serial_no']); ?></strong></td>
                            <td><?php echo esc_html($item['item_date']); ?></td>
                            <td><code><?php echo esc_html($item['item_id']); ?></code></td>
                            <td><strong><?php echo esc_html(strtoupper($item['item_name'])); ?></strong></td>
                            <td><?php echo esc_html(strtoupper($item['branch'])); ?></td>
                            <td><?php echo esc_html(strtoupper($item['sport'])); ?></td>
                            <td><?php echo esc_html(strtoupper($item['program'])); ?></td>
                            <td><span class="sp-badge"><?php echo esc_html(strtoupper($item['registration_status'])); ?></span></td>
                            <td style="text-align: right;"><strong><?php echo esc_html(Sportedia_Finance::format_price($amt)); ?></strong></td>
                            <td><span class="sp-badge" style="background:#f1f5f9; color:#334155;"><?php echo esc_html(strtoupper($item['payment_method'])); ?></span></td>
                            <td><?php echo esc_html(strtoupper($item['notes'])); ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editEodItem(<?php echo json_encode($item); ?>)'>Edit</button>
                                <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteEodItem(<?php echo $item['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: var(--sp-bg-subtle); font-weight: bold;">
                        <td colspan="8" style="text-align: right;">Total Day Transactions Income (AED):</td>
                        <td style="text-align: right; color: var(--sp-primary-color); font-size: 14px;"><?php echo esc_html(Sportedia_Finance::format_price($total_eod_amount)); ?></td>
                        <td colspan="3"></td>
                    </tr>
                <?php else : ?>
                    <tr>
                        <td colspan="12" style="text-align: center; color: var(--sp-text-muted); padding: 32px;">No itemized transaction rows recorded for <?php echo esc_html($report_date); ?> yet. Click "+ Add Transaction Row" above to enter records.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit EOD Item Modal (11 Columns Input) -->
<div id="spEodItemModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 580px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title" id="spEodModalTitle">Add Excel Transaction Item</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spEodItemModal')">&times;</button>
        </div>

        <form id="spEodItemForm">
            <input type="hidden" name="item_id_pk" id="eod_item_id_pk" value="0">
            <input type="hidden" name="report_date" value="<?php echo esc_attr($report_date); ?>">

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" name="serial_no" id="eod_serial_no" class="sp-floating-input" value="<?php echo count($eod_items) + 1; ?>" required>
                    <label class="sp-floating-label">Column A: Serial No. *</label>
                </div>

                <div class="sp-form-group">
                    <input type="date" name="item_date" id="eod_item_date" class="sp-floating-input" value="<?php echo esc_attr($report_date); ?>" required>
                    <label class="sp-floating-label">Column B: Date *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" name="item_id" id="eod_item_id" class="sp-floating-input" placeholder=" " required>
                    <label class="sp-floating-label">Column C: Player ID / Code *</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" name="item_name" id="eod_item_name" class="sp-floating-input" placeholder=" " required>
                    <label class="sp-floating-label">Column D: Full Name *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" name="branch" id="eod_branch" class="sp-floating-input" value="<?php echo esc_attr($branch_name); ?>">
                    <label class="sp-floating-label">Column E: Branch</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" name="sport" id="eod_sport" class="sp-floating-input" value="SWIMMING ACADEMY">
                    <label class="sp-floating-label">Column F: Academy</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" name="program" id="eod_program" class="sp-floating-input" value="SWIMMING">
                    <label class="sp-floating-label">Column G: Program</label>
                </div>

                <div class="sp-form-group">
                    <select name="registration_status" id="eod_reg_status" class="sp-floating-select">
                        <option value="NEW REGISTRATION">NEW REGISTRATION</option>
                        <option value="RENEWAL">RENEWAL</option>
                    </select>
                    <label class="sp-floating-label">Column H: Registration Type</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" step="0.01" name="payment_amount" id="eod_payment_amount" class="sp-floating-input" value="0.00">
                    <label class="sp-floating-label">Column I: Payment Amount (AED)</label>
                </div>

                <div class="sp-form-group">
                    <select name="payment_method" id="eod_payment_method" class="sp-floating-select">
                        <option value="CARD">CARD</option>
                        <option value="CASH">CASH</option>
                        <option value="ONLINE TRANSFER">ONLINE TRANSFER</option>
                    </select>
                    <label class="sp-floating-label">Column J: Payment Method</label>
                </div>
            </div>

            <div class="sp-form-group">
                <textarea name="notes" id="eod_notes" class="sp-floating-input" style="height: 50px;" placeholder=" "></textarea>
                <label class="sp-floating-label">Column K: Notes</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spEodItemModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Transaction Row</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateCapCalc() {
    var qty = parseInt(jQuery('#rep_caps').val()) || 0;
    var tot = qty * 25;
    jQuery('#caps_calc_preview').text('Total: ' + tot.toFixed(0) + ' AED');
}

function buildWaMessage() {
    var branch   = '<?php echo esc_js($branch_name); ?>';
    var dateParts= '<?php echo esc_js($report_date); ?>'.split('-');
    var formattedDate = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];

    var newRegsVal = jQuery('#rep_new_regs').val();
    var renewalsVal = jQuery('#rep_renewals').val();
    var cashVal    = jQuery('#rep_cash_pay').val();
    var cardVal    = jQuery('#rep_card_pay').val();
    var capsVal    = jQuery('#rep_caps').val();
    var bballAbsVal= jQuery('#rep_bball_abs').val();
    var swimAbsVal = jQuery('#rep_swim_abs').val();

    var newRegs  = newRegsVal !== '' && parseInt(newRegsVal) > 0 ? parseInt(newRegsVal) : 0;
    var renewals = renewalsVal !== '' && parseInt(renewalsVal) > 0 ? parseInt(renewalsVal) : 0;

    var cashPay  = cashVal !== '' && parseFloat(cashVal) > 0 ? parseFloat(cashVal) : 0;
    var cardPay  = cardVal !== '' && parseFloat(cardVal) > 0 ? parseFloat(cardVal) : 0;

    var capQty   = capsVal !== '' && parseInt(capsVal) > 0 ? parseInt(capsVal) : 0;
    var capTotal = capQty * 25;

    var bballAbs = bballAbsVal !== '' && parseInt(bballAbsVal) > 0 ? parseInt(bballAbsVal) : 0;
    var swimAbs  = swimAbsVal !== '' && parseInt(swimAbsVal) > 0 ? parseInt(swimAbsVal) : 0;

    var selectedNotes = [];
    jQuery('.wa-note-checkbox:checked').each(function() {
        selectedNotes.push(jQuery(this).val());
    });

    var customNote = jQuery('#rep_recs').val() || '';

    var totalIncome = cashPay + cardPay + capTotal;

    var lines = [];

    // Header always appears
    lines.push("📍 *" + branch + " | Sports Academy*");
    lines.push("🗓️ *" + formattedDate + "*");
    lines.push("");

    // Registrations & Renewals
    var hasReg = false;
    if (newRegs > 0) {
        lines.push("📝 NEW REGISTRATIONS: *" + newRegs + "*");
        hasReg = true;
    }
    if (renewals > 0) {
        lines.push("📝 RENEWALS: *" + renewals + "*");
        hasReg = true;
    }
    if (hasReg) lines.push("");

    // Payments
    var hasPay = false;
    if (cashPay > 0) {
        lines.push("💵 CASH: *" + cashPay.toFixed(0) + " AED*");
        hasPay = true;
    }
    if (cardPay > 0) {
        lines.push("💳 CARD: *" + cardPay.toFixed(0) + " AED*");
        hasPay = true;
    }
    if (hasPay) lines.push("");

    // Caps
    if (capQty > 0) {
        lines.push("📝 SWIMMING CAP: *" + capQty + "* (*" + capTotal.toFixed(0) + " AED*)");
        lines.push("");
    }

    // Absences
    var hasAbs = false;
    if (bballAbs > 0) {
        lines.push("🏀 *BASKETBALL PLAYER ABSENCES:* *" + bballAbs + "*");
        hasAbs = true;
    }
    if (swimAbs > 0) {
        lines.push("🏊‍♂️ *SWIMMING PLAYER ABSENCES:* *" + swimAbs + "*");
        hasAbs = true;
    }
    if (hasAbs) lines.push("");

    // Professional Notes
    if (selectedNotes.length > 0 || customNote.trim() !== '') {
        lines.push("📝 *PROFESSIONAL NOTES / DAILY STATUS:*");
        selectedNotes.forEach(function(note) {
            lines.push("• " + note);
        });
        if (customNote.trim() !== '') {
            lines.push("💡 " + customNote.trim());
        }
        lines.push("");
    }

    // Final Total & Separator
    if (totalIncome > 0 || hasPay || capQty > 0) {
        lines.push("━━━━━━━━━━━━━━━━━━━━");
        lines.push("");
        lines.push("💰 *TOTAL INCOME TODAY:* *" + totalIncome.toFixed(0) + " AED*");
    }

    // Clean trailing empty lines
    var txt = lines.join("\n").replace(/\n{3,}/g, "\n\n").trim();

    jQuery('#waPreviewBox').text(txt);
}

function copyWaText() {
    var text = jQuery('#waPreviewBox').text();
    navigator.clipboard.writeText(text).then(function() {
        alert('WhatsApp formatted report copied to clipboard!');
    });
}

function openWhatsAppDirect() {
    var text = jQuery('#waPreviewBox').text();
    var url = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(text);
    window.open(url, '_blank');
}

function openEodItemModal() {
    jQuery('#eod_item_id_pk').val(0);
    jQuery('#spEodModalTitle').text('Add Excel Transaction Item');
    jQuery('#eod_item_id').val('');
    jQuery('#eod_item_name').val('');
    jQuery('#eod_notes').val('');
    spOpenModal('spEodItemModal');
}

function editEodItem(item) {
    jQuery('#eod_item_id_pk').val(item.id);
    jQuery('#spEodModalTitle').text('Edit Excel Transaction Item');
    jQuery('#eod_serial_no').val(item.serial_no);
    jQuery('#eod_item_date').val(item.item_date);
    jQuery('#eod_item_id').val(item.item_id);
    jQuery('#eod_item_name').val(item.item_name);
    jQuery('#eod_branch').val(item.branch);
    jQuery('#eod_sport').val(item.sport);
    jQuery('#eod_program').val(item.program);
    jQuery('#eod_reg_status').val(item.registration_status);
    jQuery('#eod_payment_amount').val(item.payment_amount);
    jQuery('#eod_payment_method').val(item.payment_method);
    jQuery('#eod_notes').val(item.notes);
    spOpenModal('spEodItemModal');
}

function deleteEodItem(itemId) {
    if (confirm('Delete this transaction item from daily report?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_sec_delete_eod_item',
            nonce: sportedia_vars.nonce,
            item_id: itemId
        }, function(res) {
            if (res.success) location.reload();
        });
    }
}

updateCapCalc();
buildWaMessage();

jQuery('#spSecRepForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_sec_save_daily_report&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(res) {
        if (res.success) {
            alert(res.data.message || 'Report saved successfully!');
            location.reload();
        } else {
            alert(res.data || 'Error saving report.');
        }
    });
});

jQuery('#spEodItemForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_sec_add_eod_item&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert(res.data || 'Error adding item.');
        }
    });
});
</script>
