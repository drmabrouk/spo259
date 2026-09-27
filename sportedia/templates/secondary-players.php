<?php
if (!defined('ABSPATH')) exit;

$search       = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$coach_filter = isset($_GET['coach_filter']) ? sanitize_text_field($_GET['coach_filter']) : '';
$sport_filter = isset($_GET['sport_filter']) ? sanitize_text_field($_GET['sport_filter']) : '';

$playersList = Sportedia_Secondary_Manager::get_players($search, $coach_filter, $sport_filter);
$coachesList = Sportedia_Secondary_Manager::get_coaches();
$sportsList  = array('Swimming', 'Football', 'Basketball', 'Tennis', 'Fitness', 'Gymnastics', 'General');
$periodsList = array('09:00', '10:00', '11:00', '12:00', '16:00', '17:00', '18:00', '19:00', '20:00');
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Player Register & Session Tracking</h1>
        <p class="sp-page-subtitle">Standalone player records and class balances (completely independent from WordPress user accounts).</p>
    </div>
    <button class="sp-btn sp-btn-primary" onclick="openSecPlayerModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New Player
    </button>
</div>

<!-- Search & Filters -->
<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="sec_module" value="players">

        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search by Player Name, Code, Sport, or Coach..." value="<?php echo esc_attr($search); ?>">
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="coach_filter" class="sp-floating-select">
                <option value="">All Coaches</option>
                <?php foreach ($coachesList as $c) : ?>
                    <option value="<?php echo esc_attr($c['coach_name']); ?>" <?php selected($coach_filter, $c['coach_name']); ?>><?php echo esc_html($c['coach_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="sport_filter" class="sp-floating-select">
                <option value="">All Sports</option>
                <?php foreach ($sportsList as $s) : ?>
                    <option value="<?php echo esc_attr($s); ?>" <?php selected($sport_filter, $s); ?>><?php echo esc_html($s); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Filter Players</button>
    </form>
</div>

<!-- Modern Player Cards (Sorted Newest to Oldest) -->
<?php if (!empty($playersList)) : ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 16px;">
        <?php foreach ($playersList as $p) :
            $total = intval($p['total_classes']);
            $rem   = intval($p['remaining_classes']);
            $used  = max(0, $total - $rem);
        ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 18px; border-radius: var(--sp-radius); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <strong style="font-size: 16px; color: var(--sp-text-main); display: block;"><?php echo esc_html($p['player_name']); ?></strong>
                            <code style="font-size: 11px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($p['player_code']); ?></code>
                        </div>
                        <span class="sp-badge <?php echo $p['calculated_status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($p['calculated_status'])); ?>
                        </span>
                    </div>

                    <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; font-size: 12px; margin-bottom: 14px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">SPORT</span>
                                <strong><?php echo esc_html($p['sport']); ?></strong>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ASSIGNED COACH</span>
                                <strong><?php echo esc_html($p['assigned_coach'] ? $p['assigned_coach'] : 'Unassigned'); ?></strong>
                            </div>
                        </div>

                        <!-- RED (USED) vs GREEN (REMAINING) Badges -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; border-top: 1px dashed var(--sp-border-color); padding-top: 8px; text-align: center;">
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">TOTAL</span>
                                <strong><?php echo $total; ?></strong>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: #dc2626; display: block; font-weight: 700;">USED (RED)</span>
                                <span class="sp-badge" style="background:#fee2e2; color:#dc2626; font-weight:bold;"><?php echo $used; ?></span>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: #16a34a; display: block; font-weight: 700;">REMAINING (GREEN)</span>
                                <span class="sp-badge" style="background:#dcfce7; color:#16a34a; font-weight:bold;"><?php echo $rem; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--sp-border-color); padding-top: 10px; margin-top: auto;">
                    <button class="sp-btn sp-btn-primary sp-btn-sm" onclick='openCheckinModal(<?php echo json_encode($p); ?>)'>
                        Record Session
                    </button>
                    <div style="display: flex; gap: 6px;">
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSecPlayer(<?php echo json_encode($p); ?>)'>Edit</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSecPlayer(<?php echo $p['id']; ?>)">Delete</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No players found</h3>
        <p>No standalone player records match your search query.</p>
    </div>
<?php endif; ?>

<!-- Record Session Attendance Modal -->
<div id="spSecCheckinModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 480px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Record Player Session Check-in</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spSecCheckinModal')">&times;</button>
        </div>

        <form id="spSecCheckinForm">
            <input type="hidden" id="chk_player_id" name="player_id" value="0">
            <input type="hidden" id="chk_force" name="force" value="0">

            <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px;">
                <div><strong>Player Name:</strong> <span id="chk_player_name">John Doe</span></div>
                <div><strong>Player Code:</strong> <span id="chk_player_code" style="font-family: monospace;">PLY-0001</span></div>
                <div><strong>Classes Remaining:</strong> <strong id="chk_player_rem" style="color: #166534;">10</strong></div>
            </div>

            <div class="sp-form-group">
                <select id="chk_coach_name" name="coach_name" class="sp-floating-select" required>
                    <option value="">Select Coach for Session</option>
                    <?php foreach ($coachesList as $c) : ?>
                        <option value="<?php echo esc_attr($c['coach_name']); ?>"><?php echo esc_html($c['coach_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="chk_coach_name" class="sp-floating-label">Confirmed Coach *</label>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="date" id="chk_attendance_date" name="attendance_date" class="sp-floating-input" value="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                    <label for="chk_attendance_date" class="sp-floating-label">Session Date *</label>
                </div>

                <div class="sp-form-group">
                    <select id="chk_period" name="period" class="sp-floating-select" required>
                        <?php foreach ($periodsList as $prd) : ?>
                            <option value="<?php echo esc_attr($prd); ?>"><?php echo esc_html($prd); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="chk_period" class="sp-floating-label">Time Slot / Period *</label>
                </div>
            </div>

            <div class="sp-form-group">
                <input type="number" id="chk_classes_used" name="classes_used" class="sp-floating-input" value="1" min="1" required>
                <label for="chk_classes_used" class="sp-floating-label">Classes to Deduct</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spSecCheckinModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Confirm & Deduct Class</button>
            </div>
        </form>
    </div>
</div>

<!-- Standalone Player Modal -->
<div id="spSecPlayerModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 540px;">
        <div class="sp-modal-header">
            <h3 id="spSecPlayerModalTitle" class="sp-modal-title">Add Secondary Player</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spSecPlayerModal')">&times;</button>
        </div>

        <form id="spSecPlayerForm">
            <input type="hidden" id="sec_player_id" name="player_id" value="0">

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" id="sec_player_name" name="player_name" class="sp-floating-input" placeholder=" " required>
                    <label for="sec_player_name" class="sp-floating-label">Player Name *</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" id="sec_player_code" name="player_code" class="sp-floating-input" placeholder=" " required>
                    <label for="sec_player_code" class="sp-floating-label">Unique Player Code *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select id="sec_sport" name="sport" class="sp-floating-select" required>
                        <?php foreach ($sportsList as $s) : ?>
                            <option value="<?php echo esc_attr($s); ?>"><?php echo esc_html($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sec_sport" class="sp-floating-label">Sport *</label>
                </div>

                <div class="sp-form-group">
                    <input type="date" id="sec_expiry_date" name="expiry_date" class="sp-floating-input" value="<?php echo esc_attr(date('Y-m-d', strtotime('+30 days'))); ?>" required>
                    <label for="sec_expiry_date" class="sp-floating-label">Expiry Date *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="number" id="sec_total_classes" name="total_classes" class="sp-floating-input" value="12" min="1" required>
                    <label for="sec_total_classes" class="sp-floating-label">Total Classes/Sessions *</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" id="sec_remaining_classes" name="remaining_classes" class="sp-floating-input" value="12" min="0">
                    <label for="sec_remaining_classes" class="sp-floating-label">Remaining Classes</label>
                </div>
            </div>

            <div class="sp-form-group">
                <select id="sec_assigned_coach" name="assigned_coach" class="sp-floating-select">
                    <option value="">Unassigned Coach</option>
                    <?php foreach ($coachesList as $c) : ?>
                        <option value="<?php echo esc_attr($c['coach_name']); ?>"><?php echo esc_html($c['coach_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="sec_assigned_coach" class="sp-floating-label">Assigned Coach</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spSecPlayerModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Player Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCheckinModal(p) {
    jQuery('#chk_player_id').val(p.id);
    jQuery('#chk_force').val('0');
    jQuery('#chk_player_name').text(p.player_name);
    jQuery('#chk_player_code').text(p.player_code);
    jQuery('#chk_player_rem').text(p.remaining_classes + ' classes');
    jQuery('#chk_coach_name').val(p.assigned_coach || '');
    jQuery('#chk_classes_used').val(1);
    spOpenModal('spSecCheckinModal');
}

function openSecPlayerModal() {
    jQuery('#spSecPlayerModalTitle').text('Add Secondary Player');
    jQuery('#sec_player_id').val('0');
    jQuery('#spSecPlayerForm')[0].reset();
    jQuery('#sec_player_code').val('PLY-' + Math.floor(1000 + Math.random() * 9000));
    spOpenModal('spSecPlayerModal');
}

function editSecPlayer(p) {
    jQuery('#spSecPlayerModalTitle').text('Edit Secondary Player');
    jQuery('#sec_player_id').val(p.id);
    jQuery('#sec_player_name').val(p.player_name);
    jQuery('#sec_player_code').val(p.player_code);
    jQuery('#sec_sport').val(p.sport);
    jQuery('#sec_expiry_date').val(p.expiry_date);
    jQuery('#sec_total_classes').val(p.total_classes);
    jQuery('#sec_remaining_classes').val(p.remaining_classes);
    jQuery('#sec_assigned_coach').val(p.assigned_coach);
    spOpenModal('spSecPlayerModal');
}

function deleteSecPlayer(playerId) {
    if (confirm('Are you sure you want to delete this player? Historical attendance records will remain preserved.')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_sec_delete_player',
            nonce: sportedia_vars.nonce,
            player_id: playerId
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'Failed to delete player.');
            }
        });
    }
}

jQuery('#spSecCheckinForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_sec_record_attendance&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(res) {
        if (res.success) {
            alert(res.data.message || 'Session recorded!');
            location.reload();
        } else if (res.data && res.data.type === 'duplicate_confirm') {
            if (confirm(res.data.message)) {
                jQuery('#chk_force').val('1');
                jQuery('#spSecCheckinForm').trigger('submit');
            }
        } else {
            alert(res.data || 'Error recording session.');
        }
    });
});

jQuery('#spSecPlayerForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_sec_save_player&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert(res.data || 'Error saving player.');
        }
    });
});
</script>
