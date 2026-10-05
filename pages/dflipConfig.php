<?php
/**
 * DeFlip settings. Original plugin by Drajat Hasan, GPLv3.
 */

use DeFlip\Settings;

defined('INDEX_AUTH') or die('Direct access not allowed!');

// Preserve rich text before the admin session sanitizes ordinary text fields.
$input = $_POST;
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-system');
require SB . 'admin/default/session.inc.php';
require SB . 'admin/default/session_check.inc.php';
require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require_once __DIR__ . '/../helper.php';

$canRead = utility::havePrivilege('system', 'r');
$canWrite = utility::havePrivilege('system', 'w');
if (!$canRead) {
    http_response_code(403);
    die('<div class="errorBox">' . __('You don\'t have enough privileges to access this area!') . '</div>');
}

if (isset($input['saveData'])) {
    $success = false;
    try {
        if (!$canWrite) {
            http_response_code(403);
            throw new InvalidArgumentException(__('You do not have permission to change these settings.'));
        }
        if (($_POST['form_name'] ?? null) !== 'deflipSettings' || !is_string($_POST['csrf_token'] ?? null) || !simbio_form_maker::isTokenValid()) {
            http_response_code(422);
            throw new InvalidArgumentException(__('Your form has expired. Reload the settings and try again.'));
        }
        $settings = Settings::validate($input);
        if (!Settings::save($settings)) {
            throw new RuntimeException('Settings could not be saved.');
        }
        $success = true;
        $message = __('DeFlip settings saved.');
    } catch (InvalidArgumentException $error) {
        $message = $error->getMessage();
    } catch (Throwable $error) {
        http_response_code(500);
        error_log('DeFlip: unable to save settings.');
        $message = __('Unable to save settings. Please try again.');
    }
    $response = json_encode(['success' => $success, 'message' => $message, 'token' => simbio_form_maker::getLatestToken('deflipSettings')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo '<script>parent.document.dispatchEvent(new CustomEvent("deflip:settings-saved", {detail:' . $response . '}));</script>';
    exit;
}

$meta = Settings::get();
?>
<link rel="stylesheet" href="<?= dflipEscape(dflipAssetUrl('assets/deflip.css')) ?>">
<div class="menuBox">
    <div class="menuBoxInner systemIcon"><div class="per_title"><h2><?= __('DeFlip Settings') ?></h2></div></div>
</div>
<div class="deflip-admin">
    <p class="deflip-admin-note"><?= __('Manage how readers access digital collections and the terms shown before reading.') ?></p>
    <div id="deflip-settings-feedback" role="status" aria-live="polite" hidden></div>
<?php
$form = new simbio_form_table_AJAX('deflipSettings', getCurrentUrl(), 'post');
$form->add_form_attributes = ' data-readonly="' . ($canWrite ? 'false' : 'true') . '"';
$form->submit_button_attr = 'name="saveData" value="' . dflipEscape(__('Save settings')) . '" class="s-btn btn btn-primary"' . ($canWrite ? '' : ' disabled');
$form->table_attr = 'class="s-table table"';
$form->table_header_attr = 'class="alterCell"';
$form->table_content_attr = 'class="alterCell2"';
$disabled = $canWrite ? '' : ' disabled';
$form->addSelectList('guestForm', __('Guest registration'), [['0', __('Not required')], ['1', __('Required before reading')]], (int) $meta['guestForm'], 'class="form-control"' . $disabled, __('Logged-in members can continue without filling in the guest form.'));
$form->addSelectList('allowDownload', __('Download button'), [['1', __('Show')], ['0', __('Hide')]], (int) $meta['allowDownload'], 'class="form-control"' . $disabled, __('Controls the download button in the reader. PDF content is still sent to the browser for reading.'));
$editor = '<div id="deflip-tos-rich" hidden><div id="deflip-tos-toolbar"></div><div id="deflip-tos-editor" class="deflip-editor">' . $meta['tos'] . '</div></div>';
$editor .= '<textarea id="tos" name="tos" class="form-control deflip-terms-fallback" rows="10" aria-label="' . dflipEscape(__('Terms and conditions')) . '"' . ($canWrite ? '' : ' readonly') . '>' . dflipEscape($meta['tos']) . '</textarea>';
$editor .= '<p class="deflip-settings-help">' . __('These terms appear in the guest registration form before the reader opens.') . '</p>';
$form->addAnything(__('Terms and conditions'), $editor);
echo $form->printOut();
?>
</div>
<script src="<?= dflipEscape(dflipAssetUrl('assets/forms.js')) ?>"></script>
