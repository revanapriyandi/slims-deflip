<?php
/**
 * Attachment access report. Based on the SLiMS report by Arie Nugraha and Wardiyono, GPLv2 or later.
 */

use DeFlip\AccessReport;

defined('INDEX_AUTH') or die('Direct access not allowed!');
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');
require SB . 'admin/default/session.inc.php';
require SB . 'admin/default/session_check.inc.php';
if (!utility::havePrivilege('reporting', 'r')) {
    http_response_code(403);
    die('<div class="errorBox">' . __('You don\'t have enough privileges to access this area!') . '</div>');
}
require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO . 'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require MDLBS . 'reporting/report_dbgrid.inc.php';
require_once __DIR__ . '/../helper.php';
require_once __DIR__ . '/../src/ReportGrid.php';
header('Cache-Control: private, no-store');

$report = new AccessReport($_GET);
if (isset($_GET['detailList'])) {
    require __DIR__ . '/dl_detail.php';
    exit;
}

$page_title = __('DeFlip Access Report');
if (!isset($_GET['reportView'])) {
    $detail = false;
    $resetUrl = getCurrentUrl();
    $iframeUrl = getCurrentUrl(array_merge($report->query(), ['reportView' => 'true']));
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
    $reportgrid->column_width = [0 => '54%', 1 => '14%', 2 => '10%', 3 => '8%', 4 => '14%'];
    $columns = [
        'MIN(b.title) AS `' . __('Title') . '`',
        'MIN(f.file_title) AS `' . __('File title') . '`',
        'MIN(f.mime_type) AS `' . __('Type') . '`',
        'COUNT(DISTINCT fr.filelog_id) AS `' . __('Access') . '`',
        'fr.file_id AS `' . __('Actions') . '`',
    ];
    $reportgrid->setSQLColumn(...$columns);
    $reportgrid->setSQLorder('MIN(b.title) ASC, fr.file_id ASC');
    $reportgrid->sql_group_by = 'fr.file_id';
    $criteria = $report->criteria($dbs);
    $reportgrid->setSQLCriteria($criteria);
    foreach (range(0, 3) as $column) $reportgrid->modifyColumnContent($column, 'callback{dflipReportEscapeCell}');
    $reportgrid->modifyColumnContent(2, 'callback{dflipReportTypeCell}');
    $reportgrid->modifyColumnContent(4, 'callback{dflipReportDetailLink}');
    $reportgrid->show_spreadsheet_export = true;
    echo '<div class="deflip-report-table">' . $reportgrid->createDataGrid($dbs, AccessReport::tables(), $report->value('recsEachPage')) . '</div>';
    if (!$reportgrid->num_rows) echo '<div class="alert alert-info">' . __('No access records match these filters.') . '</div>';
    echo '<script>parent.$("#pagingBox").html(' . json_encode($reportgrid->paging_set, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');</script>';
    unset($_SESSION['xlsdata']);
    $_SESSION['xlsquery'] = 'SELECT ' . implode(', ', array_slice($columns, 0, 4)) . ' FROM ' . AccessReport::tables() . ' WHERE ' . $criteria . ' GROUP BY fr.file_id ORDER BY MIN(b.title) ASC, fr.file_id ASC';
    $_SESSION['tblout'] = 'DeFlip_Access';
}
$reportContent = ob_get_clean();
ob_start();
require __DIR__ . '/../views/report-print-layout.php';
$content = '<div class="deflip-report-results deflip-report-summary">' . ob_get_clean() . '</div>';
require SB . '/admin/' . $sysconf['admin_template']['dir'] . '/printed_page_tpl.php';
