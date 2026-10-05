<?php
/**
 * Attachment access history. Based on the SLiMS report by Arie Nugraha and Wardiyono, GPLv2 or later.
 */

use DeFlip\AccessReport;

defined('INDEX_AUTH') or die('Direct access not allowed!');
$page_title = __('DeFlip Access History');
$fileId = isset($_GET['fid']) && is_scalar($_GET['fid']) ? (int) $_GET['fid'] : 0;
if ($fileId < 1) {
    http_response_code(400);
    echo '<div class="alert alert-danger">' . __('Select a valid file to view its access history.') . '</div>';
    return;
}

if (!isset($_GET['search'])) {
    $detail = true;
    $resetUrl = getCurrentUrl(['detailList' => 'yes', 'fid' => $fileId]);
    $iframeUrl = getCurrentUrl(array_merge($report->query(), ['reportView' => 'true', 'detailList' => 'yes', 'search' => 'yes', 'fid' => $fileId]));
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
    $reportgrid = new report_datagrid();
    $reportgrid->table_attr = 'class="s-table table table-sm table-bordered"';
    $columns = [
        'b.title AS `' . __('Title') . '`',
        'f.file_title AS `' . __('File title') . '`',
        'f.mime_type AS `' . __('Type') . '`',
        "COALESCE(m.member_name, u.realname, CONCAT(frg.name, ' (Guest)'), '" . $dbs->escape_string(__('Unknown reader')) . "') AS `" . __('Reader') . "`",
        'fr.client_ip AS `' . __('IP address') . '`',
        'fr.date_read AS `' . __('Date') . '`',
    ];
    $reportgrid->setSQLColumn(...$columns);
    $reportgrid->setSQLorder('fr.date_read DESC, fr.filelog_id DESC');
    $criteria = $report->criteria($dbs, $fileId);
    $reportgrid->setSQLCriteria($criteria);
    foreach (range(0, 5) as $column) $reportgrid->modifyColumnContent($column, 'callback{dflipReportEscapeCell}');
    $reportgrid->modifyColumnContent(2, 'callback{dflipReportTypeCell}');
    $reportgrid->show_spreadsheet_export = true;
    echo '<div class="deflip-report-table">' . $reportgrid->createDataGrid($dbs, AccessReport::tables(true), $report->value('recsEachPage')) . '</div>';
    if (!$reportgrid->num_rows) echo '<div class="alert alert-info">' . __('No access records match these filters.') . '</div>';
    echo '<script>parent.$("#pagingBox").html(' . json_encode($reportgrid->paging_set, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');</script>';
    unset($_SESSION['xlsdata']);
    $_SESSION['xlsquery'] = 'SELECT ' . implode(', ', $columns) . ' FROM ' . AccessReport::tables(true) . ' WHERE ' . $criteria . ' ORDER BY fr.date_read DESC, fr.filelog_id DESC';
    $_SESSION['tblout'] = 'DeFlip_Access_History';
}
$content = '<div class="deflip-report-results">' . ob_get_clean() . '</div>';
require SB . '/admin/' . $sysconf['admin_template']['dir'] . '/printed_page_tpl.php';
