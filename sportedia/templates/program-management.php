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

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
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

<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
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

        <button type="submit" class="sp-btn sp-btn-primary">Filter</button>
    </form>
</div>

<!-- Cards Display (Sorted Newest to Oldest) -->
<?php if (!empty($programs)) : ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
        <?php foreach ($programs as $p) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 18px; border-radius: var(--sp-radius); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <strong style="font-size: 16px; color: var(--sp-text-main);"><?php echo esc_html($p['program_name']); ?></strong>
                        <span class="sp-badge <?php echo $p['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($p['status'])); ?>
                        </span>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <span class="sp-badge" style="background: #f3f4f6; border-color: #e5e7eb; color: #111;"><?php echo esc_html($p['category']); ?></span>
                    </div>

                    <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; font-size: 12px; margin-bottom: 14px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">BRANCH</span>
                                <strong><?php echo esc_html($p['branch_name']); ?></strong>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">COACH</span>
                                <strong><?php echo esc_html($p['coach_name']); ?></strong>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">SCHEDULE</span>
                                <span><?php echo esc_html($p['schedule'] ? $p['schedule'] : 'Flexible'); ?></span>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">CAPACITY</span>
                                <span><?php echo esc_html($p['capacity']); ?> members</span>
                            </div>
                        </div>

                        <?php if ($can_view_session_count) : ?>
                            <div style="margin-top: 8px; border-top: 1px dashed var(--sp-border-color); padding-top: 6px; font-weight: 600; color: #166534;">
                                <?php echo esc_html($p['sessions_count']); ?> Sessions (<?php echo esc_html($p['duration_days']); ?> Days Duration)
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (current_user_can('sportedia_manage_programs') || Sportedia_Roles::is_sys_admin()) : ?>
                    <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--sp-border-color); padding-top: 10px; margin-top: auto;">
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editProg(<?php echo json_encode($p); ?>)'>Edit</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteProg(<?php echo $p['id']; ?>)">Delete</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No training programs found</h3>
        <p>No programs match your search query.</p>
    </div>
<?php endif; ?>

<!-- Program Modal -->
<div id="spProgModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 520px;">
        <div class="sp-modal-header">
            <h3 id="spProgModalTitle" class="sp-modal-title">Add Program</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spProgModal')">&times;</button>
        </div>

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

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" id="sp_prog_sessions" name="sessions_count" class="sp-floating-input" value="12" min="1" required>
                    <label for="sp_prog_sessions" class="sp-floating-label">Number of Sessions</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" id="sp_prog_duration" name="duration_days" class="sp-floating-input" value="30" min="1" required>
                    <label for="sp_prog_duration" class="sp-floating-label">Duration (Days)</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select id="sp_prog_branch_id" name="branch_id" class="sp-floating-select">
                        <option value="0">All Branches</option>
                        <?php foreach ($branchesList as $b) : ?>
                            <option value="<?php echo esc_attr($b['id']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_prog_branch_id" class="sp-floating-label">Branch</label>
                </div>

                <div class="sp-form-group">
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

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" id="sp_prog_capacity" name="capacity" class="sp-floating-input" placeholder=" " value="20">
                    <label for="sp_prog_capacity" class="sp-floating-label">Capacity Limit</label>
                </div>

                <div class="sp-form-group">
                    <select id="sp_prog_status" name="status" class="sp-floating-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <label for="sp_prog_status" class="sp-floating-label">Status</label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
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
