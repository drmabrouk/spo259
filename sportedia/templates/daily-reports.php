<?php
if (!defined('ABSPATH')) exit;

$report_date   = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : date('Y-m-d');
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$reportData   = Sportedia_Reports_Manager::get_daily_report_data($report_date, $branch_filter);
$branchesList = Sportedia_Branch_Manager::get_branches();
$app_url      = get_permalink(get_option('sportedia_page_id'));
$export_nonce = wp_create_nonce('sportedia_nonce');
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 class="sp-page-title">Daily Reports & Data Operations</h1>
        <p class="sp-page-subtitle">Analyze daily attendance, new registrations, operational performance, and manage CSV export/import.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=users&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Users CSV
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=subscriptions&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Subscriptions CSV
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=branches&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Branches CSV
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=attendance&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Attendance CSV
        </a>
        <button class="sp-btn sp-btn-primary" onclick="openImportModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import CSV Data
        </button>
    </div>
</div>

<div class="sp-card" style="padding: 16px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="reports">

        <div style="width: 200px;">
            <input type="date" name="report_date" class="sp-floating-input" value="<?php echo esc_attr($report_date); ?>">
        </div>

        <div style="width: 220px;">
            <select name="branch_filter" class="sp-floating-select">
                <option value="0">All Branches</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-secondary">Generate Report</button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <div class="sp-card" style="margin-bottom:0;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Present Today</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: #166534;"><?php echo esc_html($reportData['present_count']); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Absent / Late Today</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: #991b1b;"><?php echo esc_html($reportData['absent_count'] + $reportData['late_count']); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">New Subscriptions</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html($reportData['new_subs']); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Day Revenue</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html(Sportedia_Finance::format_price($reportData['revenue'])); ?></div>
    </div>
</div>

<div class="sp-card">
    <h3 style="margin-top:0;">Daily Operational Details for <?php echo esc_html($report_date); ?></h3>
    <p style="color: var(--sp-text-muted); font-size: 14px;">Summary report data generated from active branches and member attendance tracking records.</p>
</div>

<!-- Import Modal -->
<div id="spImportModal" class="sp-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:2000; align-items:center; justify-content:center;">
    <div class="sp-card" style="width: 100%; max-width: 480px; margin: 20px;">
        <h3 style="margin-top:0;">Import Users CSV</h3>
        <form id="spImportForm" enctype="multipart/form-data">
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;">Select CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required style="width: 100%; border: 1px dashed var(--sp-border-color); padding: 20px; border-radius: var(--sp-radius);">
                <p style="font-size: 11px; color: var(--sp-text-muted); margin-top: 6px;">CSV columns format: Employee ID, Full Name, Email, Role Key</p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spImportModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<script>
function openImportModal() {
    spOpenModal('spImportModal');
}

jQuery('#spImportForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('action', 'sportedia_import_csv');
    formData.append('nonce', sportedia_vars.nonce);

    jQuery.ajax({
        url: sportedia_vars.ajax_url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert(response.data || 'Import failed.');
            }
        }
    });
});
</script>
