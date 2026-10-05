<?php
/**
 * Flipbook reader. Original plugin by Heru Subekti and Drajat Hasan.
 */
defined('INDEX_AUTH') or die('Direct access not allowed!');
$documentTitle = $file_d['title'] ?? $file_d['file_title'] ?? __('Digital collection');
?>
<!DOCTYPE html>
<html lang="<?= dflipEscape(str_replace('_', '-', $sysconf['default_lang'] ?? 'en')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= dflipEscape($documentTitle . ' · ' . __('PDF Reader')) ?></title>
    <link rel="stylesheet" href="<?= dflipEscape(dflipUrl('viewer/css/dflip.min.css')) ?>">
    <link rel="stylesheet" href="<?= dflipEscape(dflipUrl('viewer/css/themify-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= dflipEscape(dflipAssetUrl('assets/deflip.css')) ?>">
</head>
<body class="deflip-page deflip-reader-page" style="--df-accent: <?= dflipEscape(dflipAccent()) ?>">
<main class="deflip-reader-main">
    <div id="deflip-reader-status" class="deflip-reader-status" role="status" aria-live="polite" data-error-label="<?= dflipEscape(__('Unable to open the document. Reload the reader or contact the library.')) ?>" data-password-label="<?= dflipEscape(__('Unable to unlock this PDF. Contact the library for access.')) ?>"><?= __('Preparing your document…') ?></div>
    <div id="deflip-reader" data-source="<?= dflipEscape($file_loc_url) ?>" data-loader="<?= dflipEscape($loaderInit) ?>" data-download="<?= $meta['allowDownload'] ? 'true' : 'false' ?>" aria-label="<?= dflipEscape(__('PDF Reader')) ?>"></div>
    <noscript><div class="deflip-alert"><?= __('Enable JavaScript in your browser to use the PDF reader.') ?></div></noscript>
</main>
<script src="<?= dflipEscape(dflipUrl('viewer/js/libs/jquery.min.js')) ?>"></script>
<script src="<?= dflipEscape(dflipUrl('viewer/js/dflip.min.js')) ?>"></script>
<script src="<?= dflipEscape(SWB . 'js/pdfjs/build/ObjectPdf.js') ?>"></script>
<script src="<?= dflipEscape(dflipAssetUrl('assets/viewer.js')) ?>"></script>
</body>
</html>
