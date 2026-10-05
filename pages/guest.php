<?php
/**
 * Guest access. Original plugin by Drajat Hasan, GPLv3.
 */

use DeFlip\GuestAccess;
use Volnix\CSRF\CSRF;

defined('INDEX_AUTH') or die('Direct access not allowed!');

$values = GuestAccess::values($_POST);
$errors = [];
$agreed = ($_POST['agree'] ?? null) === '1';
$readerUrl = dflipReaderUrl((int) $fileID, (int) $biblioID);
$catalogUrl = slimsUrl('index.php?' . http_build_query(['p' => 'show_detail', 'id' => (int) $biblioID]));
$documentTitle = $file_d['title'] ?? $file_d['file_title'] ?? __('Digital collection');

if (isset($_POST['saveData'])) {
    if (!is_string($_POST[CSRF::TOKEN_NAME] ?? null) || !CSRF::validate($_POST)) {
        $errors['form'] = __('Your form has expired. Please submit it again.');
    } else {
        $errors = GuestAccess::errors($values, $agreed);
        if (!$errors) {
            try {
                GuestAccess::register($values, (int) $fileID);
                redirect($readerUrl);
            } catch (Throwable $error) {
                error_log('DeFlip: unable to register guest.');
                $errors['form'] = __('Unable to save your details. Please try again.');
            }
        }
    }
    if ($errors) http_response_code(422);
}

require __DIR__ . '/../views/guest.php';
exit;
