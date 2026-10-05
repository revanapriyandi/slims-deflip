<link rel="stylesheet" href="<?= dflipEscape(dflipAssetUrl('assets/deflip.css')) ?>">
<div class="per_title"><h2><?= dflipEscape($page_title) ?></h2></div>
<div class="deflip-admin deflip-report">
    <p class="deflip-admin-note"><?= __('Document openings by collection and reader, including repeat visits.') ?></p>
    <?php if ($detail): ?><a class="s-btn btn btn-default notAJAX" href="<?= dflipEscape(getCurrentUrl()) ?>" onclick="top.$('#mainContent').simbioAJAX(this.href); return false;"><?= __('Back to access report') ?></a><?php endif; ?>
    <?php if (!$detail): ?><a class="s-btn btn btn-default notAJAX deflip-reader-details-link" href="<?= dflipEscape(getCurrentUrl(array_merge($report->query(), ['detailList' => 'yes']))) ?>" onclick="top.$('#mainContent').simbioAJAX(this.href); return false;"><?= __('View all reader details') ?></a><?php endif; ?>
    <form method="get" action="<?= dflipEscape($_SERVER['PHP_SELF']) ?>" target="reportView" id="deflip-report-filter">
        <div class="deflip-filter-heading"><h3><?= __('Filter access') ?></h3><span><?= __('Narrow the results by collection or date.') ?></span></div>
        <input type="hidden" name="id" value="<?= dflipEscape(is_string($_GET['id'] ?? null) ? $_GET['id'] : '') ?>">
        <input type="hidden" name="mod" value="<?= dflipEscape(is_string($_GET['mod'] ?? null) ? $_GET['mod'] : '') ?>">
        <input type="hidden" name="reportView" value="true">
        <?php if ($detail): ?><input type="hidden" name="detailList" value="yes"><input type="hidden" name="search" value="yes"><?php if ($fileId !== null): ?><input type="hidden" name="fid" value="<?= $fileId ?>"><?php endif; ?><?php endif; ?>
        <div class="deflip-filter-grid">
            <?php foreach (['title' => __('Title/ISBN'), 'author' => __('Author'), 'publishYear' => __('Publish year')] as $field => $label): ?>
                <div><label for="deflip-filter-<?= $field ?>"><?= $label ?></label><input class="form-control" id="deflip-filter-<?= $field ?>" type="text" name="<?= $field ?>" maxlength="255" value="<?= dflipEscape($report->value($field)) ?>"></div>
            <?php endforeach; ?>
            <div><label for="deflip-filter-start"><?= __('Access from') ?></label><input class="form-control" id="deflip-filter-start" type="date" name="startDate" value="<?= dflipEscape($report->value('startDate')) ?>" required></div>
            <div><label for="deflip-filter-until"><?= __('Access until') ?></label><input class="form-control" id="deflip-filter-until" type="date" name="untilDate" value="<?= dflipEscape($report->value('untilDate')) ?>" required></div>
            <div><label for="deflip-filter-size"><?= __('Rows per page') ?></label><input class="form-control" id="deflip-filter-size" type="number" name="recsEachPage" min="<?= \DeFlip\AccessReport::MIN_PAGE_SIZE ?>" max="<?= \DeFlip\AccessReport::MAX_PAGE_SIZE ?>" value="<?= $report->value('recsEachPage') ?>"></div>
        </div>
        <div class="deflip-filter-actions"><button type="submit" class="s-btn btn btn-primary"><?= __('Apply Filter') ?></button><a href="<?= dflipEscape($resetUrl) ?>" onclick="top.$('#mainContent').simbioAJAX(this.href); return false;" class="s-btn btn btn-default notAJAX"><?= __('Reset filters') ?></a></div>
    </form>
    <div class="paging-area"><div class="pt-3 pr-3" id="pagingBox"></div></div>
    <iframe name="reportView" id="reportView" class="deflip-report-frame" title="<?= dflipEscape(__('Access report results')) ?>" src="<?= dflipEscape($iframeUrl) ?>"></iframe>
</div>
<script src="<?= dflipEscape(dflipAssetUrl('assets/report.js')) ?>"></script>
