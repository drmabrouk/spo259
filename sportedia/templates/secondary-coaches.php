<?php
if (!defined('ABSPATH')) exit;

$search     = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d');
$end_date   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

$coachesList  = Sportedia_Secondary_Manager::get_coaches($search, $start_date, $end_date);
$branchesList = Sportedia_Secondary_Manager::get_sec_branches();

$export_nonce = wp_create_nonce('sportedia_nonce');
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Coach Register & Player Tracking Analytics</h1>
        <p class="sp-page-subtitle">Standalone coach register with assigned player tracking, attendance rates, and multi-format reports.</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_all_coaches_report&start_date=' . $start_date . '&end_date=' . $end_date . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download All Coaches Report (Excel)
        </a>
        <button class="sp-btn sp-btn-primary" onclick="openSecCoachModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New Coach
        </button>
    </div>
</div>

<!-- Search & Date Range Filters -->
<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="sec_module" value="coaches">

        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search coach name, mobile number, sport or branch..." value="<?php echo esc_attr($search); ?>">
        </div>

        <div style="width: 170px;">
            <input type="date" name="start_date" class="sp-floating-input" value="<?php echo esc_attr($start_date); ?>">
        </div>

        <div style="width: 170px;">
            <input type="date" name="end_date" class="sp-floating-input" value="<?php echo esc_attr($end_date); ?>">
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Filter Coaches</button>
    </form>
</div>

<!-- Modern Coach Cards (Sorted Newest to Oldest) -->
<?php if (!empty($coachesList)) : ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 16px;">
        <?php foreach ($coachesList as $c) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 18px; border-radius: var(--sp-radius); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 16px; color: var(--sp-text-main); display: block;"><?php echo esc_html($c['coach_name']); ?></strong>
                            <span style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace;"><?php echo esc_html($c['mobile_number'] ? $c['mobile_number'] : 'No Mobile'); ?></span>
                        </div>
                        <span class="sp-badge sp-badge-active"><?php echo esc_html($c['branch']); ?></span>
                    </div>

                    <!-- Analytics Grid Box -->
                    <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; font-size: 12px; margin-bottom: 14px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ASSIGNED PLAYERS</span>
                                <strong><?php echo esc_html($c['total_players']); ?> Players</strong>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ATTENDANCE RATE</span>
                                <strong style="color: #166534; font-size: 13px;"><?php echo esc_html($c['attendance_rate']); ?>%</strong>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; border-top: 1px dashed var(--sp-border-color); padding-top: 8px; text-align: center;">
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">PRESENT</span>
                                <span style="color: #166534; font-weight: 700;"><?php echo esc_html($c['present_players']); ?></span>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ABSENT</span>
                                <span style="color: #991b1b; font-weight: 700;"><?php echo esc_html($c['absent_players']); ?></span>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">SESSIONS</span>
                                <span style="color: #000; font-weight: 700;"><?php echo esc_html($c['completed_sessions']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--sp-border-color); padding-top: 10px; margin-top: auto;">
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_coach_report&coach_id=' . $c['id'] . '&start_date=' . $start_date . '&end_date=' . $end_date . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
                        Download Report
                    </a>
                    <div style="display: flex; gap: 6px;">
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewCoachDetails(<?php echo json_encode($c); ?>)'>View Players</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSecCoach(<?php echo json_encode($c); ?>)'>Edit</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSecCoach(<?php echo $c['id']; ?>)">Delete</button>
                    </div>
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

<!-- Coach Player Tracking Details Modal -->
<div id="spCoachDetailsModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 680px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Coach Player Tracking & Attendance History</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spCoachDetailsModal')">&times;</button>
        </div>

        <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 14px; margin-bottom: 16px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <strong style="font-size: 16px; color: #000;" id="det_coach_name">Coach Name</strong>
                    <div style="color: var(--sp-text-muted);" id="det_coach_sub">Mobile: N/A | Branch: Main</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 11px; color: var(--sp-text-muted);">REPORT RANGE</div>
                    <strong id="det_coach_range" style="font-family: monospace;">2026-03-01 to 2026-03-01</strong>
                </div>
            </div>
        </div>

        <h4 style="margin: 0 0 10px 0; font-size: 14px;">Assigned Players Status List</h4>
        <div class="sp-table-wrapper" style="margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
            <table class="sp-table">
                <thead>
                    <tr>
                        <th>Player Code</th>
                        <th>Player Name</th>
                        <th>Sport</th>
                        <th>Classes Balance</th>
                        <th>Attendance Status</th>
                    </tr>
                </thead>
                <tbody id="det_player_rows">
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spCoachDetailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Standalone Coach Add/Edit Modal -->
<div id="spSecCoachModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 500px;">
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
                <input type="text" id="sec_coach_mobile" name="mobile_number" class="sp-floating-input" placeholder=" ">
                <label for="sec_coach_mobile" class="sp-floating-label">Mobile Number (e.g. +971 50 000 0000)</label>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select id="sec_coach_branch" name="branch" class="sp-floating-select">
                        <option value="Main">Main Branch</option>
                        <?php foreach ($branchesList as $b) : ?>
                            <option value="<?php echo esc_attr($b['branch_name']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sec_coach_branch" class="sp-floating-label">Secondary Branch</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" id="sec_coach_sport" name="sport" class="sp-floating-input" placeholder=" " value="Swimming">
                    <label for="sec_coach_sport" class="sp-floating-label">Sport Specialty</label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spSecCoachModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Coach</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewCoachDetails(c) {
    jQuery('#det_coach_name').text(c.coach_name);
    jQuery('#det_coach_sub').text('Mobile: ' + (c.mobile_number || 'N/A') + ' | Branch: ' + c.branch + ' | Sport: ' + c.sport);
    jQuery('#det_coach_range').text('<?php echo esc_js($start_date); ?> to <?php echo esc_js($end_date); ?>');

    jQuery.post(sportedia_vars.ajax_url, {
        action: 'sportedia_sec_get_coach_details',
        nonce: sportedia_vars.nonce,
        coach_id: c.id,
        start_date: '<?php echo esc_js($start_date); ?>',
        end_date: '<?php echo esc_js($end_date); ?>'
    }, function(res) {
        if (res.success) {
            var html = '';
            res.data.players.forEach(function(p) {
                var badge = p.today_attendance === 'Present' ? '<span class="sp-badge sp-badge-active">Present</span>' : '<span class="sp-badge sp-badge-inactive">Absent</span>';
                html += '<tr>' +
                    '<td><code>' + p.player_code + '</code></td>' +
                    '<td><strong>' + p.player_name + '</strong></td>' +
                    '<td>' + p.sport + '</td>' +
                    '<td>' + p.remaining_classes + ' / ' + p.total_classes + '</td>' +
                    '<td>' + badge + '</td>' +
                '</tr>';
            });
            if (!html) html = '<tr><td colspan="5" style="text-align:center; padding: 20px; color: var(--sp-text-muted);">No players assigned to this coach.</td></tr>';
            jQuery('#det_player_rows').html(html);
            spOpenModal('spCoachDetailsModal');
        }
    });
}

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
    jQuery('#sec_coach_mobile').val(c.mobile_number || '');
    jQuery('#sec_coach_branch').val(c.branch || 'Main');
    jQuery('#sec_coach_sport').val(c.sport || 'Swimming');
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
