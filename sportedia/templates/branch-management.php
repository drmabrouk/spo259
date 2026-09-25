<?php
if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branches = Sportedia_Branch_Manager::get_branches($search);
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 class="sp-page-title">Branch Management</h1>
        <p class="sp-page-subtitle">Configure operational branches, locations, and facility settings.</p>
    </div>
    <button class="sp-btn sp-btn-primary" onclick="openBranchModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New Branch
    </button>
</div>

<div class="sp-card" style="padding: 16px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="branches">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search branch name or code..." value="<?php echo esc_attr($search); ?>">
        </div>
        <button type="submit" class="sp-btn sp-btn-secondary">Search</button>
    </form>
</div>

<div class="sp-table-wrapper">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Branch Name</th>
                <th>Code</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($branches)) : ?>
                <?php foreach ($branches as $b) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($b['branch_name']); ?></strong></td>
                        <td><code><?php echo esc_html($b['code']); ?></code></td>
                        <td><?php echo esc_html($b['phone']); ?></td>
                        <td><?php echo esc_html($b['email']); ?></td>
                        <td>
                            <span class="sp-badge <?php echo $b['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                                <?php echo esc_html(ucfirst($b['status'])); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editBranch(<?php echo json_encode($b); ?>)'>Edit</button>
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteBranch(<?php echo $b['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--sp-text-muted); padding: 32px;">No branches found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Branch Modal -->
<div id="spBranchModal" class="sp-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:2000; align-items:center; justify-content:center;">
    <div class="sp-card" style="width: 100%; max-width: 500px; margin: 20px;">
        <h3 id="spBranchModalTitle" style="margin-top:0;">Add Branch</h3>
        <form id="spBranchForm">
            <input type="hidden" id="sp_branch_id" name="branch_id" value="0">

            <div class="sp-form-group">
                <input type="text" id="sp_branch_name" name="branch_name" class="sp-floating-input" placeholder=" " required>
                <label for="sp_branch_name" class="sp-floating-label">Branch Name</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="sp_branch_code" name="code" class="sp-floating-input" placeholder=" ">
                <label for="sp_branch_code" class="sp-floating-label">Branch Code (e.g. BR-01)</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="sp_branch_phone" name="phone" class="sp-floating-input" placeholder=" ">
                <label for="sp_branch_phone" class="sp-floating-label">Phone Number</label>
            </div>

            <div class="sp-form-group">
                <input type="email" id="sp_branch_email" name="email" class="sp-floating-input" placeholder=" ">
                <label for="sp_branch_email" class="sp-floating-label">Email Address</label>
            </div>

            <div class="sp-form-group">
                <textarea id="sp_branch_address" name="address" class="sp-floating-input" style="height: 80px;" placeholder=" "></textarea>
                <label for="sp_branch_address" class="sp-floating-label">Physical Address</label>
            </div>

            <div class="sp-form-group">
                <select id="sp_branch_status" name="status" class="sp-floating-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <label for="sp_branch_status" class="sp-floating-label">Status</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spBranchModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBranchModal() {
    jQuery('#spBranchModalTitle').text('Add New Branch');
    jQuery('#sp_branch_id').val('0');
    jQuery('#spBranchForm')[0].reset();
    spOpenModal('spBranchModal');
}

function editBranch(b) {
    jQuery('#spBranchModalTitle').text('Edit Branch');
    jQuery('#sp_branch_id').val(b.id);
    jQuery('#sp_branch_name').val(b.branch_name);
    jQuery('#sp_branch_code').val(b.code);
    jQuery('#sp_branch_phone').val(b.phone);
    jQuery('#sp_branch_email').val(b.email);
    jQuery('#sp_branch_address').val(b.address);
    jQuery('#sp_branch_status').val(b.status);
    spOpenModal('spBranchModal');
}

function deleteBranch(branchId) {
    if (confirm('Are you sure you want to delete this branch?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_branch',
            nonce: sportedia_vars.nonce,
            branch_id: branchId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Failed to delete branch.');
            }
        });
    }
}

jQuery('#spBranchForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_save_branch&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data || 'Error saving branch.');
        }
    });
});
</script>
