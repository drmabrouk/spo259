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
                        <td>$<?php echo esc_html(number_format($s['price'], 2)); ?></td>
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
    <div class="sp-card" style="width: 100%; max-width: 520px; margin: 20px;">
        <h3 id="spSubModalTitle" style="margin-top:0;">Add Subscription</h3>
        <form id="spSubForm">
            <input type="hidden" id="sp_sub_id" name="sub_id" value="0">

            <div class="sp-form-group">
                <select id="sp_sub_user_id" name="user_id" class="sp-floating-select" required>
                    <option value="">Select Customer / Member</option>
                    <?php foreach ($usersList as $u) : ?>
                        <option value="<?php echo esc_attr($u['id']); ?>"><?php echo esc_html($u['name'] . ' (' . $u['employee_id'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="sp_sub_user_id" class="sp-floating-label">Customer / Member</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="sp_plan_name" name="plan_name" class="sp-floating-input" placeholder=" " required>
                <label for="sp_plan_name" class="sp-floating-label">Plan Name (e.g. VIP Gold Access)</label>
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
                    <select id="sp_sub_branch_id" name="branch_id" class="sp-floating-select">
                        <option value="0">All Branches</option>
                        <?php foreach ($branchesList as $b) : ?>
                            <option value="<?php echo esc_attr($b['id']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_sub_branch_id" class="sp-floating-label">Associated Branch</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <select id="sp_sub_program_id" name="program_id" class="sp-floating-select">
                        <option value="0">General / None</option>
                        <?php foreach ($programsList as $p) : ?>
                            <option value="<?php echo esc_attr($p['id']); ?>"><?php echo esc_html($p['program_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_sub_program_id" class="sp-floating-label">Program (Optional)</label>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <input type="date" id="sp_start_date" name="start_date" class="sp-floating-input" required>
                    <label for="sp_start_date" class="sp-floating-label">Start Date</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <input type="date" id="sp_end_date" name="end_date" class="sp-floating-input" required>
                    <label for="sp_end_date" class="sp-floating-label">End Date</label>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <input type="number" step="0.01" id="sp_sub_price" name="price" class="sp-floating-input" placeholder=" " value="0.00">
                    <label for="sp_sub_price" class="sp-floating-label">Price ($)</label>
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

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spSubModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Subscription</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSubModal() {
    jQuery('#spSubModalTitle').text('Add New Subscription');
    jQuery('#sp_sub_id').val('0');
    jQuery('#spSubForm')[0].reset();
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
    jQuery('#sp_sub_status').val(s.status);
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
            location.reload();
        } else {
            alert(response.data || 'Error saving subscription.');
        }
    });
});
</script>
