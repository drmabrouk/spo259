<?php
if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$programs       = Sportedia_Program_Manager::get_programs($search, $branch_filter);
$branchesList   = Sportedia_Branch_Manager::get_branches();
$categoriesList = Sportedia_Program_Manager::get_categories();

// Get Coaches list
$coachesList = get_users(array('role' => 'sportedia_coach'));

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
        <h1 class="sp-page-title">Program Management</h1>
        <p class="sp-page-subtitle">Configure training programs, assigned coaches, branch schedules, and capacities.</p>
    </div>
    <?php if (current_user_can('sportedia_manage_programs') || Sportedia_Roles::is_sys_admin()) : ?>
        <button class="sp-btn sp-btn-primary" onclick="openProgModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New Program
        </button>
    <?php endif; ?>
</div>

<div class="sp-card" style="padding: 16px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="programs">

        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search program name or category..." value="<?php echo esc_attr($search); ?>">
        </div>

        <div style="width: 200px;">
            <select name="branch_filter" class="sp-floating-select">
                <option value="0">All Branches</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-secondary">Filter</button>
    </form>
</div>

<div class="sp-table-wrapper">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Program Name</th>
                <th>Category</th>
                <th>Branch</th>
                <th>Assigned Coach</th>
                <?php if ($can_view_session_count) : ?><th>Sessions & Duration</th><?php endif; ?>
                <th>Schedule</th>
                <th>Capacity</th>
                <th>Status</th>
                <?php if (current_user_can('sportedia_manage_programs') || Sportedia_Roles::is_sys_admin()) : ?>
                    <th style="text-align: right;">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($programs)) : ?>
                <?php foreach ($programs as $p) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($p['program_name']); ?></strong></td>
                        <td><span class="sp-badge"><?php echo esc_html($p['category']); ?></span></td>
                        <td><?php echo esc_html($p['branch_name']); ?></td>
                        <td><?php echo esc_html($p['coach_name']); ?></td>
                        <?php if ($can_view_session_count) : ?>
                            <td><?php echo esc_html($p['sessions_count']); ?> sessions (<?php echo esc_html($p['duration_days']); ?> days)</td>
                        <?php endif; ?>
                        <td><?php echo esc_html($p['schedule']); ?></td>
                        <td><?php echo esc_html($p['capacity']); ?> members</td>
                        <td>
                            <span class="sp-badge <?php echo $p['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                                <?php echo esc_html(ucfirst($p['status'])); ?>
                            </span>
                        </td>
                        <?php if (current_user_can('sportedia_manage_programs') || Sportedia_Roles::is_sys_admin()) : ?>
                            <td style="text-align: right;">
                                <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editProg(<?php echo json_encode($p); ?>)'>Edit</button>
                                <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteProg(<?php echo $p['id']; ?>)">Delete</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--sp-text-muted); padding: 32px;">No training programs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Program Modal -->
<div id="spProgModal" class="sp-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:2000; align-items:center; justify-content:center;">
    <div class="sp-card" style="width: 100%; max-width: 500px; margin: 20px;">
        <h3 id="spProgModalTitle" style="margin-top:0;">Add Program</h3>
        <form id="spProgForm">
            <input type="hidden" id="sp_prog_id" name="program_id" value="0">

            <div class="sp-form-group">
                <input type="text" id="sp_prog_name" name="program_name" class="sp-floating-input" placeholder=" " required>
                <label for="sp_prog_name" class="sp-floating-label">Program Name</label>
            </div>

            <div class="sp-form-group">
                <select id="sp_prog_category" name="category" class="sp-floating-select" required>
                    <option value="">Select Sport Category</option>
                    <?php foreach ($categoriesList as $cat) : ?>
                        <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="sp_prog_category" class="sp-floating-label">Sport Category *</label>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <input type="number" id="sp_prog_sessions" name="sessions_count" class="sp-floating-input" value="12" min="1" required>
                    <label for="sp_prog_sessions" class="sp-floating-label">Number of Sessions</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <input type="number" id="sp_prog_duration" name="duration_days" class="sp-floating-input" value="30" min="1" required>
                    <label for="sp_prog_duration" class="sp-floating-label">Duration (Days)</label>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <select id="sp_prog_branch_id" name="branch_id" class="sp-floating-select">
                        <option value="0">All Branches</option>
                        <?php foreach ($branchesList as $b) : ?>
                            <option value="<?php echo esc_attr($b['id']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_prog_branch_id" class="sp-floating-label">Branch</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <select id="sp_prog_coach_id" name="coach_id" class="sp-floating-select">
                        <option value="0">Unassigned</option>
                        <?php foreach ($coachesList as $c) : ?>
                            <option value="<?php echo esc_attr($c->ID); ?>"><?php echo esc_html($c->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_prog_coach_id" class="sp-floating-label">Assigned Coach</label>
                </div>
            </div>

            <div class="sp-form-group">
                <input type="text" id="sp_prog_schedule" name="schedule" class="sp-floating-input" placeholder=" ">
                <label for="sp_prog_schedule" class="sp-floating-label">Schedule (e.g. Mon, Wed, Fri 4:00 PM)</label>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="sp-form-group" style="flex: 1;">
                    <input type="number" id="sp_prog_capacity" name="capacity" class="sp-floating-input" placeholder=" " value="20">
                    <label for="sp_prog_capacity" class="sp-floating-label">Capacity Limit</label>
                </div>

                <div class="sp-form-group" style="flex: 1;">
                    <select id="sp_prog_status" name="status" class="sp-floating-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <label for="sp_prog_status" class="sp-floating-label">Status</label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spProgModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Program</button>
            </div>
        </form>
    </div>
</div>

<script>
function openProgModal() {
    jQuery('#spProgModalTitle').text('Add New Program');
    jQuery('#sp_prog_id').val('0');
    jQuery('#spProgForm')[0].reset();
    spOpenModal('spProgModal');
}

function editProg(p) {
    jQuery('#spProgModalTitle').text('Edit Program');
    jQuery('#sp_prog_id').val(p.id);
    jQuery('#sp_prog_name').val(p.program_name);
    jQuery('#sp_prog_category').val(p.category);
    jQuery('#sp_prog_branch_id').val(p.branch_id);
    jQuery('#sp_prog_coach_id').val(p.coach_id);
    jQuery('#sp_prog_sessions').val(p.sessions_count || 12);
    jQuery('#sp_prog_duration').val(p.duration_days || 30);
    jQuery('#sp_prog_schedule').val(p.schedule);
    jQuery('#sp_prog_capacity').val(p.capacity);
    jQuery('#sp_prog_status').val(p.status);
    spOpenModal('spProgModal');
}

function deleteProg(progId) {
    if (confirm('Are you sure you want to delete this program?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_program',
            nonce: sportedia_vars.nonce,
            program_id: progId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Failed to delete program.');
            }
        });
    }
}

jQuery('#spProgForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_save_program&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data || 'Error saving program.');
        }
    });
});
</script>
