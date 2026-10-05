<?php
/** Print metadata shared by the collection summary and reader detail reports. */
$hasResults = isset($reportgrid) && !$errors;
if ($hasResults) {
    $totalRecords = (int) $reportgrid->num_rows;
    $pageSize = (int) $report->value('recsEachPage');
    $currentPage = max(1, (int) $reportgrid->current_page);
    $totalPages = max(1, (int) ceil($totalRecords / $pageSize));
    $firstRecord = $totalRecords ? (($currentPage - 1) * $pageSize) + 1 : 0;
    $lastRecord = min($currentPage * $pageSize, $totalRecords);
    $period = (new DateTimeImmutable($report->value('startDate')))->format('d M Y') . ' – ' . (new DateTimeImmutable($report->value('untilDate')))->format('d M Y');
    $activeFilters = [];
    foreach (['title' => __('Title/ISBN'), 'author' => __('Author'), 'publishYear' => __('Publish year')] as $field => $label) {
        if ($report->value($field) !== '') $activeFilters[] = $label . ': ' . $report->value($field);
    }
}
?>
<?php if ($hasResults): ?>
<header class="deflip-print-header">
    <div class="deflip-print-library">
        <strong><?= dflipEscape($sysconf['library_name'] ?? __('Library')) ?></strong>
        <?php if (!empty($sysconf['library_subname'])): ?><span><?= dflipEscape($sysconf['library_subname']) ?></span><?php endif; ?>
    </div>
    <dl class="deflip-print-meta">
        <div><dt><?= __('Reporting period') ?></dt><dd><?= dflipEscape($period) ?></dd></div>
        <div><dt><?= __('Total records') ?></dt><dd><?= $totalRecords ?></dd></div>
        <div><dt><?= __('Generated on') ?></dt><dd><?= dflipEscape(date('d M Y, H:i T')) ?></dd></div>
    </dl>
    <?php if ($activeFilters): ?><p class="deflip-print-filters"><?= dflipEscape(implode(' · ', $activeFilters)) ?></p><?php endif; ?>
</header>
<?php endif; ?>
<?= $reportContent ?>
<?php if ($hasResults): ?>
<footer class="deflip-print-footer">
    <p><?= __('Access records include repeat document openings.') ?></p>
    <p><?= dflipEscape(sprintf(__('Page %s of %s · Records %s–%s of %s'), $currentPage, $totalPages, $firstRecord, $lastRecord, $totalRecords)) ?></p>
</footer>
<?php endif; ?>
