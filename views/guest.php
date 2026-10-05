<!DOCTYPE html>
<html lang="<?= dflipEscape(str_replace('_', '-', $sysconf['default_lang'] ?? 'en')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= dflipEscape(__('Reader registration') . ' · ' . $documentTitle) ?></title>
    <link rel="stylesheet" href="<?= dflipEscape(dflipAssetUrl('assets/deflip.css')) ?>">
</head>
<body class="deflip-page deflip-guest-page" style="--df-accent: <?= dflipEscape(dflipAccent()) ?>">
<main class="deflip-guest deflip-guest--form">
    <form class="deflip-form" id="deflip-guest-form" action="<?= dflipEscape($readerUrl) ?>" method="post">
        <div class="deflip-form-heading"><div><h1><?= __('Reader details') ?></h1><p><?= __('Complete the required fields to continue reading.') ?></p></div></div>
        <?php if ($errors): ?>
            <div class="deflip-alert" role="alert"><?= dflipEscape($errors['form'] ?? __('Check the highlighted fields below.')) ?></div>
        <?php endif; ?>
        <?= \Volnix\CSRF\CSRF::getHiddenInputString() ?>
        <input type="hidden" name="saveData" value="1">
        <div class="deflip-fields">
        <?php foreach (\DeFlip\GuestAccess::FIELDS as $name => $field): ?>
            <div class="deflip-field">
                <label for="deflip-<?= $name ?>"><?= __($field['label']) ?></label>
                <input id="deflip-<?= $name ?>" name="<?= $name ?>" type="<?= $field['type'] ?>" value="<?= dflipEscape($values[$name]) ?>" maxlength="<?= $field['maxLength'] ?>" autocomplete="<?= $field['autocomplete'] ?>" required aria-invalid="<?= isset($errors[$name]) ? 'true' : 'false' ?>"<?= isset($errors[$name]) ? ' aria-describedby="deflip-' . $name . '-error"' : '' ?>>
                <?php if (isset($errors[$name])): ?><span class="deflip-field-error" id="deflip-<?= $name ?>-error"><?= dflipEscape($errors[$name]) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <details class="deflip-terms"<?= isset($errors['agree']) ? ' open' : '' ?>>
            <summary><?= __('Terms and conditions') ?></summary>
            <div class="deflip-terms-content"><?= $meta['tos'] ?></div>
        </details>
        <label class="deflip-agree" for="deflip-agree"><input id="deflip-agree" type="checkbox" name="agree" value="1" required<?= $agreed ? ' checked' : '' ?><?= isset($errors['agree']) ? ' aria-describedby="deflip-agree-error" aria-invalid="true"' : '' ?>><span><?= __('I have read and agree to the terms and conditions.') ?></span></label>
        <?php if (isset($errors['agree'])): ?><span id="deflip-agree-error" class="deflip-field-error"><?= dflipEscape($errors['agree']) ?></span><?php endif; ?>
        <div class="deflip-actions"><button class="deflip-button" type="submit" data-loading-label="<?= dflipEscape(__('Saving your details…')) ?>"><?= __('Continue to reader') ?> <span aria-hidden="true">&rarr;</span></button></div>
    </form>
</main>
<script src="<?= dflipEscape(dflipAssetUrl('assets/forms.js')) ?>"></script>
</body>
</html>
