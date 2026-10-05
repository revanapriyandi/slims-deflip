<?php
/**
 * Attachment access history. Based on the SLiMS report by Arie Nugraha and Wardiyono, GPLv2 or later.
 */

use DeFlip\AccessReport;

defined('INDEX_AUTH') or die('Direct access not allowed!');
require_once __DIR__ . '/../src/ReportGrid.php';
$fileId = isset($_GET['fid']) && is_scalar($_GET['fid']) ? (int) $_GET['fid'] : null;
$page_title = $fileId === null ? __('DeFlip Reader Details') : __('DeFlip Access History');
if (array_key_exists('fid', $_GET) && ($fileId === null || $fileId < 1)) {
    http_response_code(400);
    echo '<div class="alert alert-danger">' . __('Select a valid file to view its access history.') . '</div>';
    return;
}

if (!isset($_GET['search'])) {
    $detail = true;
    $detailQuery = ['detailList' => 'yes'];
    if ($fileId !== null) $detailQuery['fid'] = $fileId;
    $resetUrl = getCurrentUrl($detailQuery);
    $iframeUrl = getCurrentUrl(array_merge($report->query(), $detailQuery, ['reportView' => 'true', 'search' => 'yes']));
    require __DIR__ . '/../views/report-filters.php';
    return;
}

ob_start();
echo '<link rel="stylesheet" href="' . dflipEscape(dflipAssetUrl('assets/deflip.css')) . '">';
$errors = $report->errors();
if ($errors) {
    unset($_SESSION['xlsdata'], $_SESSION['xlsquery']);
    echo '<div class="alert alert-danger" role="alert">' . dflipEscape($errors[0]) . '</div>';
    echo '<script>parent.$("#pagingBox").empty();</script>';
} else {
    $reportgrid = new \DeFlip\ReportGrid();
    $reportgrid->table_attr = 'class="s-table table table-sm table-bordered"';
    $reportgrid->column_width = [0 => '26%', 1 => '8%', 2 => '5%', 3 => '14%', 4 => '16%', 5 => '12%', 6 => '8%', 7 => '11%'];
    $columns = [
        'b.title AS `' . __('Title') . '`',
        'f.file_title AS `' . __('File title') . '`',
        'f.mime_type AS `' . __('Type') . '`',
        "COALESCE(m.member_name, u.realname, CONCAT(frg.name, ' (Guest)'), '" . $dbs->escape_string(__('Unknown reader')) . "') AS `" . __('Reader') . "`",
        "COALESCE(CASE WHEN m.member_id IS NOT NULL THEN NULLIF(TRIM(m.inst_name), '') WHEN u.user_id IS NOT NULL THEN NULL ELSE NULLIF(TRIM(frg.institution), '') END, '-') AS `" . __('Institution') . "`",
        "COALESCE(CASE WHEN m.member_id IS NOT NULL THEN NULLIF(TRIM(m.member_phone), '') WHEN u.user_id IS NOT NULL THEN NULL ELSE NULLIF(TRIM(frg.phonenumber), '') END, '-') AS `" . __('Phone Number') . "`",
        'fr.client_ip AS `' . __('IP address') . '`',
        'fr.date_read AS `' . __('Date') . '`',
    ];
    $reportgrid->setSQLColumn(...$columns);
    $reportgrid->setSQLorder('fr.date_read DESC, fr.filelog_id DESC');
    $criteria = $report->criteria($dbs, $fileId);
    $reportgrid->setSQLCriteria($criteria);
    foreach (array_keys($columns) as $column) $reportgrid->modifyColumnContent($column, 'callback{dflipReportEscapeCell}');
    $reportgrid->modifyColumnContent(2, 'callback{dflipReportTypeCell}');
    $reportgrid->show_spreadsheet_export = true;
    echo '<div class="deflip-report-table">' . $reportgrid->createDataGrid($dbs, AccessReport::tables(true), $report->value('recsEachPage')) . '</div>';
    if (!$reportgrid->num_rows) echo '<div class="alert alert-info">' . __('No access records match these filters.') . '</div>';
    echo '<script>parent.$("#pagingBox").html(' . json_encode($reportgrid->paging_set, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');</script>';
    unset($_SESSION['xlsdata']);
    $_SESSION['xlsquery'] = 'SELECT ' . implode(', ', $columns) . ' FROM ' . AccessReport::tables(true) . ' WHERE ' . $criteria . ' ORDER BY fr.date_read DESC, fr.filelog_id DESC';
    $_SESSION['tblout'] = 'DeFlip_Access_History';
}
$reportContent = ob_get_clean();
ob_start();
require __DIR__ . '/../views/report-print-layout.php';
$content = '<div class="deflip-report-results deflip-report-detail">' . ob_get_clean() . '</div>';
require SB . '/admin/' . $sysconf['admin_template']['dir'] . '/printed_page_tpl.php';
