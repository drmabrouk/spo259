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
        <h1 class="sp-page-title">Coach Register & Player Assignment Tracking</h1>
        <p class="sp-page-subtitle">Assign players to coaches, record class session deductions, and analyze attendance rates.</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_all_coaches_report&start_date=' . $start_date . '&end_date=' . $end_date . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download All Coaches Report
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
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;">
        <?php foreach ($coachesList as $c) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 18px; border-radius: var(--sp-radius); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 16px; color: var(--sp-text-main); display: block;"><?php echo esc_html($c['coach_name']); ?></strong>
                            <span style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace;"><?php echo esc_html($c['mobile_number'] ? $c['mobile_number'] : 'No Mobile'); ?> | <?php echo esc_html($c['sport']); ?></span>
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
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">PRESENT TODAY</span>
                                <span style="color: #166534; font-weight: 700;"><?php echo esc_html($c['present_players']); ?></span>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ABSENT TODAY</span>
                                <span style="color: #991b1b; font-weight: 700;"><?php echo esc_html($c['absent_players']); ?></span>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">SESSIONS</span>
                                <span style="color: #000; font-weight: 700;"><?php echo esc_html($c['completed_sessions']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; border-top: 1px solid var(--sp-border-color); padding-top: 10px; margin-top: auto;">
                    <div style="display: flex; gap: 6px;">
                        <button class="sp-btn sp-btn-primary sp-btn-sm" style="flex: 1;" onclick='openAssignPlayerModal("<?php echo esc_js($c['coach_name']); ?>")'>
                            + Assign Player
                        </button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" style="flex: 1;" onclick='viewCoachDetails(<?php echo json_encode($c); ?>)'>
                            View / Attendance
                        </button>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_sec_export_coach_report&coach_id=' . $c['id'] . '&start_date=' . $start_date . '&end_date=' . $end_date . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
                            Report
                        </a>
                        <div style="display: flex; gap: 6px;">
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSecCoach(<?php echo json_encode($c); ?>)'>Edit</button>
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSecCoach(<?php echo $c['id']; ?>)">Delete</button>
                        </div>
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

<!-- PLAYER ASSIGNMENT SEARCH ENGINE MODAL -->
<div id="spAssignPlayerModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 560px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Assign Player to Coach</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spAssignPlayerModal')">&times;</button>
        </div>

        <div style="margin-bottom: 16px;">
            <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 14px;">
                Target Coach: <strong id="assign_target_coach_title" style="color: #0284c7;">-</strong>
            </div>

            <div class="sp-form-group" style="position: relative;">
                <input type="text" id="assign_search_input" class="sp-floating-input" placeholder=" " onkeyup="searchPlayersForAssign()" autocomplete="off">
                <label for="assign_search_input" class="sp-floating-label">Search Player by Code, ID, or Name *</label>

                <div id="assign_suggestions_box" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #ffffff; border: 1px solid var(--sp-border-color); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99; max-height: 220px; overflow-y: auto; margin-top: 4px;">
                </div>
            </div>
        </div>

        <!-- Selected Player Confirmation Details Card -->
        <div id="assign_preview_card" style="display: none; background: #fafafa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: var(--sp-primary-color);">Selected Player Preview</h4>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; margin-bottom: 12px;">
                <div>
                    <span style="color: var(--sp-text-muted); display: block;">PLAYER NAME</span>
                    <strong id="prev_p_name" style="font-size: 14px;">-</strong>
                </div>
                <div>
                    <span style="color: var(--sp-text-muted); display: block;">PLAYER CODE / ID</span>
                    <strong id="prev_p_code" style="font-family: monospace; font-size: 13px;">-</strong>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; margin-bottom: 12px;">
                <div>
                    <span style="color: var(--sp-text-muted); display: block;">SPORT</span>
                    <strong id="prev_p_sport">-</strong>
                </div>
                <div>
                    <span style="color: var(--sp-text-muted); display: block;">CURRENT ASSIGNED COACH</span>
                    <strong id="prev_p_coach">-</strong>
                </div>
            </div>

            <!-- Session Indicators Box -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; background: #ffffff; border: 1px solid var(--sp-border-color); border-radius: 6px; padding: 10px; text-align: center;">
                <div>
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">TOTAL CLASSES</span>
                    <strong id="prev_p_total" style="font-size: 13px;">-</strong>
                </div>
                <div>
                    <span style="font-size: 10px; color: #dc2626; display: block; font-weight: 700;">USED (RED)</span>
                    <span id="prev_p_used" class="sp-badge" style="background:#fee2e2; color:#dc2626; font-weight:bold;">-</span>
                </div>
                <div>
                    <span style="font-size: 10px; color: #16a34a; display: block; font-weight: 700;">REMAINING (GREEN)</span>
                    <span id="prev_p_remaining" class="sp-badge" style="background:#dcfce7; color:#16a34a; font-weight:bold;">-</span>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spAssignPlayerModal')">Cancel</button>
            <button type="button" id="btnConfirmAssign" class="sp-btn sp-btn-primary" style="display: none;" onclick="confirmPlayerAssignment()">
                OK / CONFIRM ASSIGNMENT
            </button>
        </div>
    </div>
</div>

<!-- Coach Player Tracking & Direct Attendance Modal -->
<div id="spCoachDetailsModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 780px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Coach Player List & Attendance Manager</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spCoachDetailsModal')">&times;</button>
        </div>

        <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 14px; margin-bottom: 16px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div>
                    <strong style="font-size: 16px; color: #000;" id="det_coach_name">Coach Name</strong>
                    <div style="color: var(--sp-text-muted);" id="det_coach_sub">Mobile: N/A | Branch: Main</div>
                </div>
                <div>
                    <button type="button" class="sp-btn sp-btn-primary sp-btn-sm" onclick='openAssignPlayerModal(selectedCoachName)'>
                        + Add Player to Coach
                    </button>
                </div>
            </div>
        </div>

        <h4 style="margin: 0 0 10px 0; font-size: 14px;">Assigned Players (Red = Used Sessions, Green = Remaining Sessions)</h4>
        <div class="sp-table-wrapper" style="margin-bottom: 20px; max-height: 320px; overflow-y: auto;">
            <table class="sp-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Player Name</th>
                        <th>Sport</th>
                        <th style="text-align: center;">Total</th>
                        <th style="text-align: center;">Used (Red)</th>
                        <th style="text-align: center;">Remaining (Green)</th>
                        <th>Today's Status</th>
                        <th style="text-align: right;">Action</th>
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
var selectedCoachName = '';
var selectedPlayerObject = null;
var currentCoachDetailsObj = null;

function openAssignPlayerModal(coachName) {
    selectedCoachName = coachName;
    jQuery('#assign_target_coach_title').text(coachName);
    jQuery('#assign_search_input').val('');
    jQuery('#assign_suggestions_box').hide().empty();
    jQuery('#assign_preview_card').hide();
    jQuery('#btnConfirmAssign').hide();
    selectedPlayerObject = null;
    spOpenModal('spAssignPlayerModal');
}

function searchPlayersForAssign() {
    var q = jQuery('#assign_search_input').val().trim();
    if (q.length < 1) {
        jQuery('#assign_suggestions_box').hide().empty();
        return;
    }

    jQuery.post(sportedia_vars.ajax_url, {
        action: 'sportedia_sec_search_entities',
        nonce: sportedia_vars.nonce,
        query: q
    }, function(res) {
        if (res.success && res.data.players && res.data.players.length > 0) {
            var html = '';
            res.data.players.forEach(function(p) {
                html += '<div style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick=\'selectPlayerForAssign(' + JSON.stringify(p) + ')\' onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'#ffffff\'">' +
                    '<div><strong>' + p.player_name + '</strong> <span style="font-size: 11px; color: #64748b; font-family: monospace;">ID: ' + p.player_code + '</span></div>' +
                    '<span class="sp-badge">' + p.sport + '</span>' +
                '</div>';
            });
            jQuery('#assign_suggestions_box').html(html).show();
        } else {
            jQuery('#assign_suggestions_box').html('<div style="padding: 12px; color: #94a3b8; font-size: 12px; text-align: center;">No matching players found.</div>').show();
        }
    });
}

function selectPlayerForAssign(p) {
    selectedPlayerObject = p;
    jQuery('#assign_suggestions_box').hide();
    jQuery('#assign_search_input').val(p.player_name + ' (' + p.player_code + ')');

    jQuery('#prev_p_name').text(p.player_name);
    jQuery('#prev_p_code').text(p.player_code);
    jQuery('#prev_p_sport').text(p.sport || 'Swimming');
    jQuery('#prev_p_coach').text(p.assigned_coach || 'Unassigned');
    jQuery('#prev_p_total').text(p.total_classes);
    jQuery('#prev_p_used').text('USED: ' + p.used_classes);
    jQuery('#prev_p_remaining').text('REMAINING: ' + p.remaining_classes);

    jQuery('#assign_preview_card').show();
    jQuery('#btnConfirmAssign').show();
}

function confirmPlayerAssignment() {
    if (!selectedPlayerObject || !selectedCoachName) return;

    jQuery.post(sportedia_vars.ajax_url, {
        action: 'sportedia_sec_assign_player_coach',
        nonce: sportedia_vars.nonce,
        player_id: selectedPlayerObject.id,
        coach_name: selectedCoachName,
        confirm: '1'
    }, function(res) {
        if (res.success) {
            alert(res.data.message || 'Player assigned successfully!');
            spCloseModal('spAssignPlayerModal');
            location.reload();
        } else {
            if (res.data && res.data.reassign_confirm) {
                if (confirm(res.data.message)) {
                    jQuery.post(sportedia_vars.ajax_url, {
                        action: 'sportedia_sec_assign_player_coach',
                        nonce: sportedia_vars.nonce,
                        player_id: selectedPlayerObject.id,
                        coach_name: selectedCoachName,
                        confirm: '1'
                    }, function(res2) {
                        if (res2.success) {
                            alert(res2.data.message);
                            spCloseModal('spAssignPlayerModal');
                            location.reload();
                        }
                    });
                }
            } else {
                alert(res.data.message || res.data || 'Failed to assign player.');
            }
        }
    });
}

function viewCoachDetails(c) {
    selectedCoachName = c.coach_name;
    currentCoachDetailsObj = c;
    jQuery('#det_coach_name').text(c.coach_name);
    jQuery('#det_coach_sub').text('Mobile: ' + (c.mobile_number || 'N/A') + ' | Branch: ' + c.branch + ' | Sport: ' + c.sport);

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
                var total = parseInt(p.total_classes) || 12;
                var rem   = parseInt(p.remaining_classes) || 0;
                var used  = Math.max(0, total - rem);

                var badgeClass = p.today_attendance === 'Present' ? '<span class="sp-badge sp-badge-active">Present Today</span>' : '<span class="sp-badge sp-badge-inactive">Absent Today</span>';

                html += '<tr>' +
                    '<td><code>' + p.player_code + '</code></td>' +
                    '<td><strong>' + p.player_name + '</strong></td>' +
                    '<td>' + p.sport + '</td>' +
                    '<td style="text-align: center;"><strong>' + total + '</strong></td>' +
                    '<td style="text-align: center;"><span class="sp-badge" style="background:#fee2e2; color:#dc2626; font-weight:bold;">USED: ' + used + '</span></td>' +
                    '<td style="text-align: center;"><span class="sp-badge" style="background:#dcfce7; color:#16a34a; font-weight:bold;">REMAINING: ' + rem + '</span></td>' +
                    '<td>' + badgeClass + '</td>' +
                    '<td style="text-align: right;">' +
                        '<button type="button" class="sp-btn sp-btn-primary sp-btn-sm" onclick=\'recordDirectAttendance(' + JSON.stringify(p) + ')\'>Deduct Session</button>' +
                    '</td>' +
                '</tr>';
            });
            if (!html) html = '<tr><td colspan="8" style="text-align:center; padding: 24px; color: var(--sp-text-muted);">No players currently assigned to ' + c.coach_name + '. Click "+ Add Player to Coach" to assign.</td></tr>';
            jQuery('#det_player_rows').html(html);
            spOpenModal('spCoachDetailsModal');
        }
    });
}

function recordDirectAttendance(p) {
    if (parseInt(p.remaining_classes) <= 0) {
        alert('Cannot deduct session: Player ' + p.player_name + ' has 0 remaining classes.');
        return;
    }

    var slot = prompt('Record attendance session for ' + p.player_name + ' with ' + selectedCoachName + '.\nEnter Time Slot / Period:', '09:00');
    if (!slot) return;

    jQuery.post(sportedia_vars.ajax_url, {
        action: 'sportedia_sec_record_attendance',
        nonce: sportedia_vars.nonce,
        player_id: p.id,
        coach_name: selectedCoachName,
        period: slot,
        classes_used: 1,
        attendance_date: '<?php echo date('Y-m-d'); ?>'
    }, function(res) {
        if (res.success) {
            alert('Attendance recorded! 1 class deducted. Remaining: ' + res.data.remaining_classes);
            viewCoachDetails(currentCoachDetailsObj);
        } else if (res.data && res.data.type === 'duplicate_confirm') {
            if (confirm(res.data.message)) {
                jQuery.post(sportedia_vars.ajax_url, {
                    action: 'sportedia_sec_record_attendance',
                    nonce: sportedia_vars.nonce,
                    player_id: p.id,
                    coach_name: selectedCoachName,
                    period: slot,
                    classes_used: 1,
                    attendance_date: '<?php echo date('Y-m-d'); ?>',
                    force: '1'
                }, function(res2) {
                    if (res2.success) {
                        alert('Duplicate attendance recorded!');
                        viewCoachDetails(currentCoachDetailsObj);
                    }
                });
            }
        } else {
            alert(res.data.message || res.data || 'Failed to record attendance.');
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
