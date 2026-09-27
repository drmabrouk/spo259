<?php
if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$coachesList = Sportedia_Secondary_Manager::get_coaches($search);
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Coach Register — Secondary Dashboard</h1>
        <p class="sp-page-subtitle">Standalone coach records for player assignment, session tracking, and attendance reports.</p>
    </div>
    <button class="sp-btn sp-btn-primary" onclick="openSecCoachModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New Coach
    </button>
</div>

<!-- Search -->
<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="sec_module" value="coaches">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search coach name or sport..." value="<?php echo esc_attr($search); ?>">
        </div>
        <button type="submit" class="sp-btn sp-btn-primary">Search Coaches</button>
    </form>
</div>

<!-- Modern Coach Cards (Sorted Newest to Oldest) -->
<?php if (!empty($coachesList)) : ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
        <?php foreach ($coachesList as $c) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 18px; border-radius: var(--sp-radius); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <strong style="font-size: 16px; color: var(--sp-text-main);"><?php echo esc_html($c['coach_name']); ?></strong>
                        <span class="sp-badge sp-badge-active">Secondary Coach</span>
                    </div>

                    <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; font-size: 12px; margin-bottom: 14px;">
                        <div><strong>Sport:</strong> <?php echo esc_html($c['sport']); ?></div>
                        <div><strong>Branch:</strong> <?php echo esc_html($c['branch']); ?></div>
                        <div style="margin-top: 6px; font-size: 11px; color: var(--sp-text-muted);">Added: <?php echo esc_html(date('Y-m-d', strtotime($c['created_at']))); ?></div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--sp-border-color); padding-top: 10px; margin-top: auto;">
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSecCoach(<?php echo json_encode($c); ?>)'>Edit</button>
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSecCoach(<?php echo $c['id']; ?>)">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No coaches found</h3>
        <p>No standalone coach records match your search query.</p>
    </div>
<?php endif; ?>

<!-- Standalone Coach Modal -->
<div id="spSecCoachModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 480px;">
        <div class="sp-modal-header">
            <h3 id="spSecCoachModalTitle" class="sp-modal-title">Add Secondary Coach</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spSecCoachModal')">&times;</button>
        </div>

        <form id="spSecCoachForm">
            <input type="hidden" id="sec_coach_id" name="coach_id" value="0">

            <div class="sp-form-group">
                <input type="text" id="sec_coach_name" name="coach_name" class="sp-floating-input" placeholder=" " required>
                <label for="sec_coach_name" class="sp-floating-label">Coach Name *</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="sec_coach_sport" name="sport" class="sp-floating-input" placeholder=" " value="Swimming">
                <label for="sec_coach_sport" class="sp-floating-label">Sport Specialty</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spSecCoachModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Coach</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSecCoachModal() {
    jQuery('#spSecCoachModalTitle').text('Add Secondary Coach');
    jQuery('#sec_coach_id').val('0');
    jQuery('#spSecCoachForm')[0].reset();
    spOpenModal('spSecCoachModal');
}

function editSecCoach(c) {
    jQuery('#spSecCoachModalTitle').text('Edit Secondary Coach');
    jQuery('#sec_coach_id').val(c.id);
    jQuery('#sec_coach_name').val(c.coach_name);
    jQuery('#sec_coach_sport').val(c.sport);
    spOpenModal('spSecCoachModal');
}

function deleteSecCoach(coachId) {
    if (confirm('Are you sure you want to delete this coach? Historical player attendance records will remain preserved.')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_sec_delete_coach',
            nonce: sportedia_vars.nonce,
            coach_id: coachId
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'Failed to delete coach.');
            }
        });
    }
}

jQuery('#spSecCoachForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_sec_save_coach&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert(res.data || 'Error saving coach.');
        }
    });
});
</script>
