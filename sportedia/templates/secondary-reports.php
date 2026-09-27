<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$report_date = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : date('Y-m-d');
$branch_name = isset($_GET['branch']) ? sanitize_text_field($_GET['branch']) : 'ISCS MUW';

$rep_table = $wpdb->prefix . 'sportedia_sec_daily_reports';
$eod_table = $wpdb->prefix . 'sportedia_sec_eod_records';

$existing_rep = $wpdb->get_row($wpdb->prepare("SELECT * FROM $rep_table WHERE report_date = %s AND branch = %s", $report_date, $branch_name), ARRAY_A);
$eod_items    = $wpdb->get_results($wpdb->prepare("SELECT * FROM $eod_table WHERE report_date = %s ORDER BY serial_no ASC, id ASC", $report_date), ARRAY_A);

$export_nonce = wp_create_nonce('sportedia_nonce');
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">End-of-Day Reports — Secondary Dashboard</h1>
        <p class="sp-page-subtitle">Generate WhatsApp formatted daily statements and professional Excel/CSV exports.</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_eod&report_date=' . $report_date . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download EOD Excel (11 Cols)
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_attendance&attendance_date=' . $report_date . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download Attendance Excel
        </a>
    </div>
</div>

<!-- Date Selector -->
<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="sec_module" value="reports">

        <div style="width: 200px;">
            <input type="date" name="report_date" class="sp-floating-input" value="<?php echo esc_attr($report_date); ?>">
        </div>

        <div style="width: 200px;">
            <input type="text" name="branch" class="sp-floating-input" value="<?php echo esc_attr($branch_name); ?>" placeholder="Branch Name">
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Load Report Date</button>
    </form>
</div>

<div class="sp-grid-2" style="margin-bottom: 24px;">
    <!-- Form 1: WhatsApp Report Builder -->
    <div class="sp-card" style="margin-bottom:0;">
        <h3 style="margin-top:0; font-size: 16px; font-weight: 700; margin-bottom: 14px;">WhatsApp Daily Report Generator</h3>
        <form id="spSecRepForm">
            <input type="hidden" name="report_date" value="<?php echo esc_attr($report_date); ?>">
            <input type="hidden" name="branch" value="<?php echo esc_attr($branch_name); ?>">

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" id="rep_new_regs" name="new_registrations" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['new_registrations'] : 1); ?>" oninput="buildWaMessage()">
                    <label for="rep_new_regs" class="sp-floating-label">New Registrations</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" step="0.01" id="rep_card_pay" name="card_payments" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['card_payments'] : '2205.00'); ?>" oninput="buildWaMessage()">
                    <label for="rep_card_pay" class="sp-floating-label">Card Payments (AED)</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" id="rep_renewals" name="renewals" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['renewals'] : 2); ?>" oninput="buildWaMessage()">
                    <label for="rep_renewals" class="sp-floating-label">Renewals Count</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" id="rep_caps" name="caps_count" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['caps_count'] : 1); ?>" oninput="buildWaMessage()">
                    <label for="rep_caps" class="sp-floating-label">Swimming Caps Count</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" step="0.01" id="rep_cash_pay" name="cash_payments" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['cash_payments'] : '25.00'); ?>" oninput="buildWaMessage()">
                    <label for="rep_cash_pay" class="sp-floating-label">Cash Payments (AED)</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" step="0.01" id="rep_caps_total" name="total_caps_amount" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['total_caps_amount'] : '25.00'); ?>" oninput="buildWaMessage()">
                    <label for="rep_caps_total" class="sp-floating-label">Total Caps Amount (AED)</label>
                </div>
            </div>

            <div class="sp-form-group">
                <input type="text" id="rep_staff_status" name="staff_status" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['staff_status'] : 'All staff and coaches are present.'); ?>" oninput="buildWaMessage()">
                <label for="rep_staff_status" class="sp-floating-label">Staff & Coaches Status</label>
            </div>

            <div class="sp-form-group">
                <input type="number" id="rep_absences" name="player_absences_count" class="sp-floating-input" value="<?php echo esc_attr($existing_rep ? $existing_rep['player_absences_count'] : 9); ?>" oninput="buildWaMessage()">
                <label for="rep_absences" class="sp-floating-label">Player Absences Count</label>
            </div>

            <div class="sp-form-group">
                <textarea id="rep_recs" name="recommendations" class="sp-floating-input" style="height: 50px;" oninput="buildWaMessage()"><?php echo esc_textarea($existing_rep ? $existing_rep['recommendations'] : 'Follow up with absent players to check status and encourage session makeup.'); ?></textarea>
                <label for="rep_recs" class="sp-floating-label">Recommendations & Notes</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="submit" class="sp-btn sp-btn-primary">Save Report Values</button>
            </div>
        </form>
    </div>

    <!-- Live Preview & WhatsApp Message Output -->
    <div class="sp-card" style="margin-bottom:0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="margin-top:0; font-size: 16px; font-weight: 700; margin-bottom: 12px;">WhatsApp Formatted Message Preview</h3>
            <div id="waPreviewBox" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #111827; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 12.5px; white-space: pre-wrap; line-height: 1.5; min-height: 280px; font-weight: 500;">
            </div>
        </div>

        <div style="margin-top: 16px; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="sp-btn sp-btn-primary" style="background-color: #16a34a; border-color: #16a34a;" onclick="copyWaText()">
                Copy WhatsApp Text
            </button>
        </div>
    </div>
</div>

<!-- Structured Excel Record Entries (11 Columns) -->
<div class="sp-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Excel Itemized Daily Transactions (<?php echo esc_html($report_date); ?>)</h3>
            <p style="color: var(--sp-text-muted); font-size: 12px; margin: 2px 0 0 0;">Items exported match the exact 11-column Excel report format.</p>
        </div>
        <button type="button" class="sp-btn sp-btn-primary sp-btn-sm" onclick="openEodItemModal()">
            + Add Transaction Row
        </button>
    </div>

    <div class="sp-table-wrapper">
        <table class="sp-table">
            <thead>
                <tr>
                    <th>Sr.</th>
                    <th>Date</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Sport</th>
                    <th>Program</th>
                    <th>Reg. Status</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Notes</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($eod_items)) : ?>
                    <?php foreach ($eod_items as $item) : ?>
                        <tr>
                            <td><?php echo esc_html($item['serial_no']); ?></td>
                            <td><?php echo esc_html($item['item_date']); ?></td>
                            <td><code><?php echo esc_html($item['item_id']); ?></code></td>
                            <td><strong><?php echo esc_html($item['item_name']); ?></strong></td>
                            <td><?php echo esc_html($item['branch']); ?></td>
                            <td><?php echo esc_html($item['sport']); ?></td>
                            <td><?php echo esc_html($item['program']); ?></td>
                            <td><span class="sp-badge"><?php echo esc_html($item['registration_status']); ?></span></td>
                            <td><strong><?php echo esc_html(Sportedia_Finance::format_price($item['payment_amount'])); ?></strong></td>
                            <td><?php echo esc_html($item['payment_method']); ?></td>
                            <td><?php echo esc_html($item['notes']); ?></td>
                            <td style="text-align: right;">
                                <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteEodItem(<?php echo $item['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="12" style="text-align: center; color: var(--sp-text-muted); padding: 32px;">No itemized transaction rows recorded for <?php echo esc_html($report_date); ?> yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add EOD Item Modal -->
<div id="spEodItemModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 520px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Add Excel Transaction Item</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spEodItemModal')">&times;</button>
        </div>

        <form id="spEodItemForm">
            <input type="hidden" name="report_date" value="<?php echo esc_attr($report_date); ?>">

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" name="serial_no" class="sp-floating-input" value="<?php echo count($eod_items) + 1; ?>" required>
                    <label class="sp-floating-label">Serial No. *</label>
                </div>

                <div class="sp-form-group">
                    <input type="date" name="item_date" class="sp-floating-input" value="<?php echo esc_attr($report_date); ?>" required>
                    <label class="sp-floating-label">Date *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" name="item_id" class="sp-floating-input" placeholder=" " required>
                    <label class="sp-floating-label">Player ID / Code *</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" name="item_name" class="sp-floating-input" placeholder=" " required>
                    <label class="sp-floating-label">Player Full Name *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" name="branch" class="sp-floating-input" value="<?php echo esc_attr($branch_name); ?>">
                    <label class="sp-floating-label">Branch</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" name="sport" class="sp-floating-input" value="Swimming">
                    <label class="sp-floating-label">Sport</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select name="registration_status" class="sp-floating-select">
                        <option value="New Registration">New Registration</option>
                        <option value="Renewal">Renewal</option>
                    </select>
                    <label class="sp-floating-label">Registration Status</label>
                </div>

                <div class="sp-form-group">
                    <select name="payment_method" class="sp-floating-select">
                        <option value="Card">Card</option>
                        <option value="Cash">Cash</option>
                        <option value="Online">Online Transfer</option>
                    </select>
                    <label class="sp-floating-label">Payment Method</label>
                </div>
            </div>

            <div class="sp-form-group">
                <input type="number" step="0.01" name="payment_amount" class="sp-floating-input" value="0.00">
                <label class="sp-floating-label">Payment Amount (AED)</label>
            </div>

            <div class="sp-form-group">
                <textarea name="notes" class="sp-floating-input" style="height: 50px;" placeholder=" "></textarea>
                <label class="sp-floating-label">Transaction Notes</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spEodItemModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Add Item Row</button>
            </div>
        </form>
    </div>
</div>

<script>
function buildWaMessage() {
    var branch   = '<?php echo esc_js($branch_name); ?>';
    var dateParts= '<?php echo esc_js($report_date); ?>'.split('-');
    var formattedDate = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];

    var newRegs  = jQuery('#rep_new_regs').val() || '0';
    var cardPay  = parseFloat(jQuery('#rep_card_pay').val()) || 0;
    var renewals = jQuery('#rep_renewals').val() || '0';
    var caps     = jQuery('#rep_caps').val() || '0';
    var cashPay  = parseFloat(jQuery('#rep_cash_pay').val()) || 0;
    var capsTot  = parseFloat(jQuery('#rep_caps_total').val()) || 0;
    var staff    = jQuery('#rep_staff_status').val() || 'All staff and coaches are present.';
    var abs      = jQuery('#rep_absences').val() || '0';
    var recs     = jQuery('#rep_recs').val() || '';

    var totalInc = cardPay + cashPay + capsTot;

    var txt = "📍 *" + branch + " | Swimming Academy*\n" +
              "🗓️ *" + formattedDate + ".*\n\n" +
              "📝 NEW SWIMMING REG : *" + newRegs + "*.\n" +
              "💳 CARD Payment : *" + cardPay.toFixed(0) + " AED*.\n" +
              "📝 RENEWAL FOR SWIMMING : *" + renewals + "*.\n\n" +
              "📝 Swimming Cap : *" + caps + "*.\n" +
              "💳 CASH : *" + cashPay.toFixed(0) + " AED*.\n\n" +
              "TOTAL CAPS : *" + capsTot.toFixed(0) + " AED*.\n\n" +
              "👨‍🏫 *Staff & Coaches Status:* " + staff + "\n\n" +
              "🏊‍♂️ *Players Absences:* Total absent players: *" + abs + "*.\n\n" +
              "💡 *Recommendations:*\n" + recs + "\n\n" +
              "Total Income today : *" + totalInc.toFixed(0) + " AED*.";

    jQuery('#waPreviewBox').text(txt);
}

function copyWaText() {
    var text = jQuery('#waPreviewBox').text();
    navigator.clipboard.writeText(text).then(function() {
        alert('WhatsApp formatted message copied to clipboard!');
    });
}

function openEodItemModal() {
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

buildWaMessage();

jQuery('#spSecRepForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_sec_save_daily_report&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(res) {
        if (res.success) {
            alert(res.data.message || 'Saved!');
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
