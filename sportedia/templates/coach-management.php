<?php
if (!defined('ABSPATH')) exit;

$search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$coachesList  = Sportedia_Coach_Manager::get_coaches_summary($search, $branch_filter);
$branchesList = Sportedia_Branch_Manager::get_branches();

$curr_u = wp_get_current_user();
$admin_roles = array('sportedia_sys_admin', 'sportedia_general_mgr', 'sportedia_facility_mgr', 'sportedia_ops_mgr', 'sportedia_finance_mgr', 'administrator');
$can_view_session_count = false;
foreach ((array)$curr_u->roles as $r) {
    if (in_array($r, $admin_roles, true) || current_user_can('manage_options')) {
        $can_view_session_count = true;
        break;
    }
}
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 class="sp-page-title">Coach Management</h1>
        <p class="sp-page-subtitle">Track assigned sports coaches, active member allocations, and verified completed sessions.</p>
    </div>
</div>

<div class="sp-card" style="padding: 16px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="coaches">

        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search coach name, ID or email..." value="<?php echo esc_attr($search); ?>">
        </div>

        <button type="submit" class="sp-btn sp-btn-secondary">Filter</button>
    </form>
</div>

<div class="sp-table-wrapper">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Coach Name</th>
                <th>Contact</th>
                <th>Assigned Programs</th>
                <th>Active Members</th>
                <?php if ($can_view_session_count) : ?><th>Completed Verified Sessions</th><?php endif; ?>
                <th style="text-align: right;">History</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($coachesList)) : ?>
                <?php foreach ($coachesList as $c) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($c['name']); ?></strong><br>
                            <span style="font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html($c['employee_id']); ?></span>
                        </td>
                        <td>
                            <span style="font-size: 13px; display: block;"><?php echo esc_html($c['email']); ?></span>
                            <span style="font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html($c['phone']); ?></span>
                        </td>
                        <td><span class="sp-badge"><?php echo esc_html($c['assigned_programs']); ?> Programs</span></td>
                        <td><span class="sp-badge"><?php echo esc_html($c['assigned_members']); ?> Members</span></td>
                        <?php if ($can_view_session_count) : ?>
                            <td>
                                <strong style="font-size: 15px; color: #166534;"><?php echo esc_html($c['completed_sessions']); ?></strong> sessions
                            </td>
                        <?php endif; ?>
                        <td style="text-align: right;">
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewCoachStats(<?php echo json_encode($c); ?>)'>Session History</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--sp-text-muted); padding: 32px;">No coaches found in system.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Coach Session Stats Modal -->
<div id="spCoachModal" class="sp-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:2000; align-items:center; justify-content:center;">
    <div class="sp-card" style="width: 100%; max-width: 500px; margin: 20px;">
        <h3 id="spCoachModalTitle" style="margin-top:0;">Coach Performance Statistics</h3>
        <div style="font-size: 13px; margin-bottom: 16px;">
            <div><strong>Coach:</strong> <span id="coach_modal_name">Coach Name</span></div>
            <div><strong>Assigned Active Programs:</strong> <span id="coach_modal_progs">0</span></div>
            <div><strong>Active Members Managed:</strong> <span id="coach_modal_mems">0</span></div>
            <div><strong>Total Completed Sessions:</strong> <strong id="coach_modal_sess" style="color:#166534;">0</strong></div>
        </div>
        <p style="font-size: 12px; color: var(--sp-text-muted);">
            Completed sessions are calculated automatically whenever member barcodes are verified and deducted in the Verification System.
        </p>
        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spCoachModal')">Close</button>
        </div>
    </div>
</div>

<script>
function viewCoachStats(c) {
    jQuery('#coach_modal_name').text(c.name + ' (' + c.employee_id + ')');
    jQuery('#coach_modal_progs').text(c.assigned_programs);
    jQuery('#coach_modal_mems').text(c.assigned_members);
    jQuery('#coach_modal_sess').text(c.completed_sessions);
    spOpenModal('spCoachModal');
}
</script>
