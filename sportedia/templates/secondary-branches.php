<?php
if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branchesList = Sportedia_Secondary_Manager::get_sec_branches($search);
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Branch Register — Secondary Dashboard</h1>
        <p class="sp-page-subtitle">Configure operational branches strictly isolated for Secondary Dashboard coaches and players.</p>
    </div>
    <button class="sp-btn sp-btn-primary" onclick="openSecBranchModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Secondary Branch
    </button>
</div>

<!-- Search -->
<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="sec_module" value="branches">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search branch name or code..." value="<?php echo esc_attr($search); ?>">
        </div>
        <button type="submit" class="sp-btn sp-btn-primary">Search Branches</button>
    </form>
</div>

<!-- Modern Branch Cards (Sorted Newest to Oldest) -->
<?php if (!empty($branchesList)) : ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
        <?php foreach ($branchesList as $b) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 18px; border-radius: var(--sp-radius); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 16px; color: var(--sp-text-main); display: block;"><?php echo esc_html($b['branch_name']); ?></strong>
                            <code style="font-size: 11px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($b['code']); ?></code>
                        </div>
                        <span class="sp-badge <?php echo $b['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($b['status'])); ?>
                        </span>
                    </div>

                    <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; font-size: 12px; margin-bottom: 14px;">
                        <div><strong>Created Date:</strong> <?php echo esc_html(date('Y-m-d', strtotime($b['created_at']))); ?></div>
                        <div><strong>Last Updated:</strong> <?php echo esc_html(date('Y-m-d H:i', strtotime($b['updated_at']))); ?></div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--sp-border-color); padding-top: 10px; margin-top: auto;">
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSecBranch(<?php echo json_encode($b); ?>)'>Edit</button>
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSecBranch(<?php echo $b['id']; ?>)">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No branches found</h3>
        <p>No secondary branch records match your query.</p>
    </div>
<?php endif; ?>

<!-- Standalone Branch Modal -->
<div id="spSecBranchModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 480px;">
        <div class="sp-modal-header">
            <h3 id="spSecBranchModalTitle" class="sp-modal-title">Add Secondary Branch</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spSecBranchModal')">&times;</button>
        </div>

        <form id="spSecBranchForm">
            <input type="hidden" id="sec_branch_id" name="branch_id" value="0">

            <div class="sp-form-group">
                <input type="text" id="sec_branch_name" name="branch_name" class="sp-floating-input" placeholder=" " required>
                <label for="sec_branch_name" class="sp-floating-label">Branch Name *</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="sec_branch_code" name="code" class="sp-floating-input" placeholder=" ">
                <label for="sec_branch_code" class="sp-floating-label">Branch Code (e.g. BR-01)</label>
            </div>

            <div class="sp-form-group">
                <select id="sec_branch_status" name="status" class="sp-floating-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <label for="sec_branch_status" class="sp-floating-label">Status</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spSecBranchModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSecBranchModal() {
    jQuery('#spSecBranchModalTitle').text('Add Secondary Branch');
    jQuery('#sec_branch_id').val('0');
    jQuery('#spSecBranchForm')[0].reset();
    spOpenModal('spSecBranchModal');
}

function editSecBranch(b) {
    jQuery('#spSecBranchModalTitle').text('Edit Secondary Branch');
    jQuery('#sec_branch_id').val(b.id);
    jQuery('#sec_branch_name').val(b.branch_name);
    jQuery('#sec_branch_code').val(b.code);
    jQuery('#sec_branch_status').val(b.status);
    spOpenModal('spSecBranchModal');
}

function deleteSecBranch(branchId) {
    if (confirm('Are you sure you want to delete this secondary branch?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_sec_delete_branch',
            nonce: sportedia_vars.nonce,
            branch_id: branchId
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'Failed to delete branch.');
            }
        });
    }
}

jQuery('#spSecBranchForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_sec_save_branch&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert(res.data || 'Error saving branch.');
        }
    });
});
</script>
