<?php
if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

$subscriptions = Sportedia_Subscription_Manager::get_subscriptions($search, $branch_filter, $status_filter);
$branchesList  = Sportedia_Branch_Manager::get_branches();
$usersList     = Sportedia_User_Manager::get_users();
$programsList  = Sportedia_Program_Manager::get_programs();
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 class="sp-page-title">Subscription Management</h1>
        <p class="sp-page-subtitle">Track customer memberships, subscription plans, start/end dates, and statuses.</p>
    </div>
    <button class="sp-btn sp-btn-primary" onclick="openSubModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Subscription
    </button>
</div>

<div class="sp-card" style="padding: 16px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="subscriptions">

        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search plan name, member name or ID..." value="<?php echo esc_attr($search); ?>">
        </div>

        <div style="width: 180px;">
            <select name="branch_filter" class="sp-floating-select">
                <option value="0">All Branches</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="width: 180px;">
            <select name="status_filter" class="sp-floating-select">
                <option value="">All Statuses</option>
                <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                <option value="expired" <?php selected($status_filter, 'expired'); ?>>Expired</option>
                <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-secondary">Filter</button>
    </form>
</div>

<div class="sp-table-wrapper">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Member</th>
                <th>Plan Name</th>
                <th>Type</th>
                <th>Dates</th>
                <th>Price</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($subscriptions)) : ?>
                <?php foreach ($subscriptions as $s) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($s['member_name']); ?></strong><br>
                            <span style="font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html($s['employee_id']); ?></span>
                        </td>
                        <td><?php echo esc_html($s['plan_name']); ?></td>
                        <td><?php echo esc_html(ucfirst($s['subscription_type'])); ?></td>
                        <td>
                            <span style="font-size: 12px; display: block;"><?php echo esc_html($s['start_date']); ?> &rarr; <?php echo esc_html($s['end_date']); ?></span>
                        </td>
                        <td><?php echo esc_html(Sportedia_Finance::format_price($s['price'])); ?></td>
                        <td>
                            <span class="sp-badge <?php echo $s['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                                <?php echo esc_html(ucfirst($s['status'])); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSub(<?php echo json_encode($s); ?>)'>Edit</button>
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSub(<?php echo $s['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--sp-text-muted); padding: 32px;">No subscription records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Subscription Modal -->
<div id="spSubModal" class="sp-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:2000; align-items:center; justify-content:center;">
    <div class="sp-card" style="width: 100%; max-width: 580px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <h3 id="spSubModalTitle" style="margin-top:0;">Add Subscription</h3>
        <form id="spSubForm">
            <input type="hidden" id="sp_sub_id" name="sub_id" value="0">
            <input type="hidden" id="sp_is_renewal" name="is_renewal" value="0">

            <!-- Toggle Mode: New Member vs Renewal -->
            <div id="spSubModeContainer" style="display: flex; gap: 8px; margin-bottom: 16px; background: var(--sp-bg-main); padding: 4px; border-radius: var(--sp-radius);">
                <button type="button" id="spModeNewBtn" class="sp-btn sp-btn-primary sp-btn-sm" style="flex: 1;" onclick="setSubMode('new')">New Member Subscription</button>
                <button type="button" id="spModeRenewalBtn" class="sp-btn sp-btn-secondary sp-btn-sm" style="flex: 1;" onclick="setSubMode('renewal')">Member Renewal</button>
            </div>

            <!-- Existing Member Selection (Renewal Mode Only) -->
            <div id="spRenewalMemberBox" class="sp-form-group" style="display: none;">
                <select id="sp_sub_user_id" name="user_id" class="sp-floating-select">
                    <option value="">Search Existing Member (by Member ID, Name, Phone)</option>
                    <?php foreach ($usersList as $u) : ?>
                        <option value="<?php echo esc_attr($u['id']); ?>"><?php echo esc_html($u['name'] . ' (ID: ' . $u['employee_id'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="sp_sub_user_id" class="sp-floating-label">Existing Member</label>
            </div>

            <!-- New Member Fields (New Member Mode) -->
            <div id="spNewMemberBox">
                <div style="display: flex; gap: 12px;">
                    <div class="sp-form-group" style="flex: 1;">
                        <input type="text" id="sp_member_name" name="member_name" class="sp-floating-input" placeholder=" ">
                        <label for="sp_member_name" class="sp-floating-label">Member Full Name *</label>
                    </div>

                    <div class="sp-form-group" style="flex: 1;">
                        <input type="text" id="sp_member_phone" name="member_phone" class="sp-floating-input" placeholder=" ">
                        <label for="sp_member_phone" class="sp-floating-label">Mobile Number *</label>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <div class="sp-form-group" style="flex: 1;">
                        <input type="text" id="sp_member_id" name="member_id" class="sp-floating-input" placeholder=" ">
                        <label for="sp_member_id" class="sp-floating-label">Member ID (Auto-generated if empty)</label>
                    </div>

                    <div class="sp-form-group" style="flex: 1; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="sp_activate_account" name="activate_account" value="1" checked onchange="toggleActivationFields()">
                        <label for="sp_activate_account" style="font-size: 13px; font-weight: 600;">Account Activation Enabled</label>
                    </div>
                </div>

                <div id="spActivationFields" style="display: flex; gap: 12px;">
                    <div class="sp-form-group" style="flex: 1;">
                        <input type="email" id="sp_member_email" name="member_email" class="sp-floating-input" placeholder=" ">
                        <label for="sp_member_email" class="sp-floating-label">Account Email</label>
                    </div>

                    <div class="sp-form-group" style="flex: 1;">
                        <input type="text" id="sp_member_password" name="member_password" class="sp-floating-input" placeholder=" ">
                        <label for="sp_member_password" class="sp-floating-label">Initial Password</label>
                    </div>
                </div>
            </div>

            <!-- Program & Subscription Details -->
            <div class="sp-form-group">
                <input type="text" id="sp_plan_name" name="plan_name" class="sp-floating-input" placeholder=" " required>
                <label for="sp_plan_name" class="sp-floating-label">Plan Name (e.g. VIP Gold Membership)</label>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <select id="sp_sub_branch_id" name="branch_id" class="sp-floating-select">
                        <option value="0">All Branches</option>
                        <?php foreach ($branchesList as $b) : ?>
                            <option value="<?php echo esc_attr($b['id']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_sub_branch_id" class="sp-floating-label">Branch</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <select id="sp_sub_program_id" name="program_id" class="sp-floating-select" onchange="onProgramChange()">
                        <option value="0" data-duration="30" data-sessions="12">General / None</option>
                        <?php foreach ($programsList as $p) : ?>
                            <option value="<?php echo esc_attr($p['id']); ?>" data-duration="<?php echo esc_attr($p['duration_days'] ?? 30); ?>" data-sessions="<?php echo esc_attr($p['sessions_count'] ?? 12); ?>">
                                <?php echo esc_html($p['program_name'] . ' (' . ($p['category'] ?? 'General') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_sub_program_id" class="sp-floating-label">Program</label>
                </div>
            </div>

            <div class="sp-form-group">
                <select id="sp_subscription_type" name="subscription_type" class="sp-floating-select">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annual">Annual</option>
                    <option value="session_pack">Session Pack</option>
                </select>
                <label for="sp_subscription_type" class="sp-floating-label">Subscription Type</label>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <input type="date" id="sp_start_date" name="start_date" class="sp-floating-input" value="<?php echo esc_attr(date('Y-m-d')); ?>" required onchange="calculateEndDate()">
                    <label for="sp_start_date" class="sp-floating-label">Start Date</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <input type="date" id="sp_end_date" name="end_date" class="sp-floating-input" required>
                    <label for="sp_end_date" class="sp-floating-label">End Date (Auto-calculated)</label>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <input type="number" step="0.01" id="sp_sub_price" name="price" class="sp-floating-input" placeholder=" " value="0.00">
                    <label for="sp_sub_price" class="sp-floating-label">Price (AED)</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <select id="sp_sub_status" name="status" class="sp-floating-select">
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <label for="sp_sub_status" class="sp-floating-label">Status</label>
                </div>
            </div>

            <div class="sp-form-group">
                <textarea id="sp_sub_notes" name="notes" class="sp-floating-input" style="height: 70px;" placeholder=" "></textarea>
                <label for="sp_sub_notes" class="sp-floating-label">Subscription Notes</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spSubModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save & Generate Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Invoice & WhatsApp Printable Modal -->
<div id="spInvoiceModal" class="sp-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:2100; align-items:center; justify-content:center;">
    <div class="sp-card" style="width: 100%; max-width: 500px; margin: 20px;" id="spPrintableInvoice">
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--sp-border-color); pb-12px; margin-bottom: 16px;">
            <div>
                <h2 style="margin: 0; font-size: 20px;">Sportedia Invoice</h2>
                <span id="inv_no" style="font-size: 12px; color: var(--sp-text-muted);">#INV-00000</span>
            </div>
            <div style="text-align: right;">
                <strong style="font-size: 14px;">Sportedia Online</strong>
                <span style="display: block; font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html(date('Y-m-d')); ?></span>
            </div>
        </div>

        <div style="margin-bottom: 16px; font-size: 13px;">
            <div><strong>Member:</strong> <span id="inv_member_name"></span> (<span id="inv_member_id"></span>)</div>
            <div><strong>Mobile:</strong> <span id="inv_member_phone"></span></div>
            <div><strong>Plan:</strong> <span id="inv_plan_name"></span></div>
            <div><strong>Period:</strong> <span id="inv_dates"></span></div>
        </div>

        <table style="width: 100%; font-size: 13px; border-collapse: collapse; margin-bottom: 16px;">
            <tr style="border-bottom: 1px solid var(--sp-border-color);">
                <td style="padding: 6px 0;">Base Amount</td>
                <td style="text-align: right;" id="inv_base_price">AED 0.00</td>
            </tr>
            <tr style="border-bottom: 1px solid var(--sp-border-color);">
                <td style="padding: 6px 0;">5% VAT</td>
                <td style="text-align: right;" id="inv_vat">AED 0.00</td>
            </tr>
            <tr style="font-weight: 700; font-size: 15px;">
                <td style="padding: 8px 0;">Total Amount</td>
                <td style="text-align: right;" id="inv_total">AED 0.00</td>
            </tr>
        </table>

        <div style="display: flex; gap: 10px; justify-content: flex-end;" class="sp-no-print">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Invoice
            </button>
            <a id="inv_whatsapp_link" href="#" target="_blank" class="sp-btn sp-btn-primary" style="background-color: #16a34a; border-color: #16a34a;">
                Send via WhatsApp
            </a>
            <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spInvoiceModal'); location.reload();">Done</button>
        </div>
    </div>
</div>

<script>
function setSubMode(mode) {
    if (mode === 'renewal') {
        jQuery('#sp_is_renewal').val('1');
        jQuery('#spModeRenewalBtn').removeClass('sp-btn-secondary').addClass('sp-btn-primary');
        jQuery('#spModeNewBtn').removeClass('sp-btn-primary').addClass('sp-btn-secondary');
        jQuery('#spRenewalMemberBox').show();
        jQuery('#spNewMemberBox').hide();
    } else {
        jQuery('#sp_is_renewal').val('0');
        jQuery('#spModeNewBtn').removeClass('sp-btn-secondary').addClass('sp-btn-primary');
        jQuery('#spModeRenewalBtn').removeClass('sp-btn-primary').addClass('sp-btn-secondary');
        jQuery('#spRenewalMemberBox').hide();
        jQuery('#spNewMemberBox').show();
    }
    if (window.spUpdateFloatingLabels) spUpdateFloatingLabels();
}

function toggleActivationFields() {
    if (jQuery('#sp_activate_account').is(':checked')) {
        jQuery('#spActivationFields').show();
    } else {
        jQuery('#spActivationFields').hide();
    }
}

function onProgramChange() {
    var $opt = jQuery('#sp_sub_program_id option:selected');
    var programName = $opt.text().trim();
    if ($opt.val() !== "0") {
        jQuery('#sp_plan_name').val(programName);
    }
    calculateEndDate();
}

function calculateEndDate() {
    var startDateVal = jQuery('#sp_start_date').val();
    if (!startDateVal) return;

    var $opt = jQuery('#sp_sub_program_id option:selected');
    var durationDays = parseInt($opt.data('duration')) || 30;

    var start = new Date(startDateVal);
    start.setDate(start.getDate() + durationDays);

    var y = start.getFullYear();
    var m = String(start.getMonth() + 1).padStart(2, '0');
    var d = String(start.getDate()).padStart(2, '0');

    jQuery('#sp_end_date').val(y + '-' + m + '-' + d);
    if (window.spUpdateFloatingLabels) spUpdateFloatingLabels();
}

function openSubModal() {
    jQuery('#spSubModalTitle').text('Add New Subscription');
    jQuery('#sp_sub_id').val('0');
    jQuery('#spSubForm')[0].reset();
    jQuery('#sp_start_date').val(new Date().toISOString().split('T')[0]);
    setSubMode('new');
    calculateEndDate();
    spOpenModal('spSubModal');
}

function editSub(s) {
    jQuery('#spSubModalTitle').text('Edit Subscription');
    jQuery('#sp_sub_id').val(s.id);
    jQuery('#sp_sub_user_id').val(s.user_id);
    jQuery('#sp_plan_name').val(s.plan_name);
    jQuery('#sp_subscription_type').val(s.subscription_type);
    jQuery('#sp_sub_branch_id').val(s.branch_id);
    jQuery('#sp_sub_program_id').val(s.program_id);
    jQuery('#sp_start_date').val(s.start_date);
    jQuery('#sp_end_date').val(s.end_date);
    jQuery('#sp_sub_price').val(s.price);
    jQuery('#sp_sub_notes').val(s.notes || '');
    jQuery('#sp_sub_status').val(s.status);
    setSubMode('renewal');
    spOpenModal('spSubModal');
}

function deleteSub(subId) {
    if (confirm('Are you sure you want to delete this subscription?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_subscription',
            nonce: sportedia_vars.nonce,
            sub_id: subId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Failed to delete subscription.');
            }
        });
    }
}

jQuery('#spSubForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_save_subscription&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            var res = response.data;
            spCloseModal('spSubModal');

            // Populate Invoice Modal
            jQuery('#inv_no').text('#' + res.invoice_number);
            jQuery('#inv_member_name').text(res.member_name);
            jQuery('#inv_member_id').text(res.member_id);
            jQuery('#inv_member_phone').text(res.member_phone);
            jQuery('#inv_plan_name').text(res.plan_name);
            jQuery('#inv_dates').text(res.start_date + ' to ' + res.end_date);
            jQuery('#inv_base_price').text(res.base_price);
            jQuery('#inv_vat').text(res.vat);
            jQuery('#inv_total').text(res.total);

            // Setup WhatsApp Link
            var cleanPhone = (res.member_phone || '').replace(/[^0-9]/g, '');
            var waText = encodeURIComponent(
                "Hello " + res.member_name + ",\n\nYour Sportedia subscription details:\n" +
                "Invoice: " + res.invoice_number + "\n" +
                "Plan: " + res.plan_name + "\n" +
                "Period: " + res.start_date + " to " + res.end_date + "\n" +
                "Total Amount: " + res.total + "\n\nThank you for choosing Sportedia!"
            );
            jQuery('#inv_whatsapp_link').attr('href', 'https://api.whatsapp.com/send?phone=' + cleanPhone + '&text=' + waText);

            spOpenModal('spInvoiceModal');
        } else {
            alert(response.data || 'Error saving subscription.');
        }
    });
});
</script>
