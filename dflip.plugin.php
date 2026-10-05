<?php
/**
 * Plugin Name: DearFlip
 * Plugin URI: -
 * Description: PDF flipbook, reader registration and access reports
 * Version: 1.1.1
 * Author: Heru Subekti
 * Author URI: https://www.facebook.com/heroe.soebekti
 * Plugin Packager: Drajat Hasan & Arif Syamsudin
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// Config menu
$plugin->registerMenu('system', 'DeFlip Settings', __DIR__ . '/pages/dflipConfig.php');

// Reporting Menu
$plugin->registerMenu('reporting', 'DeFlip Access Report', __DIR__ . '/pages/dl_counter.php');

$plugin->register('fstream_pdf_before_stream', function ($data) {
    require_once __DIR__ . '/helper.php';
    if (\DeFlip\GuestAccess::needsRegistration(\DeFlip\Settings::get()['guestForm'])) {
        http_response_code(403);
        header('Cache-Control: no-store');
        header('Content-Type: text/plain; charset=UTF-8');
        echo __('Complete the reader registration form before opening this document.');
        exit;
    }
});

// Hook for force SLiMS PDF Viewer to use DearFlip
$plugin->register('fstream_pdf_before_download', function($data){
    global $sysconf;
    $fileID = (int) $data['fileID'];
    $biblioID = (int) $data['biblioID'];
    $memberID = $data['memberID'];
    $userID = $data['userID'];
    $file_d = $data['file_d'];
    $loaderInit = $data['loader_init'] ?? '';
    // Set global file location url
    $file_loc_url = SWB . 'index.php?p=fstream-pdf&fid=' . $fileID . '&bid=' . $biblioID;

    // Meta
    // Require helper
    require_once __DIR__ . '/helper.php';
    $meta = \DeFlip\Settings::get();

    // Reset latest session for guest
    if (\utility::isMemberLogin())
    {
        unset($_SESSION['guestReadEbook']);
        $_SESSION['memberReadBook'] = [
            'books' => [
                $fileID => ['startread' => date('Y-m-d H:i:s')]
            ]
        ];
    }

    // Guest checking
    $guest = \DeFlip\GuestAccess::needsRegistration($meta['guestForm']);

    if ($guest)
    {
        include __DIR__ . '/pages/guest.php';
    }

    $guestId = 0;
    if (isset($_SESSION['guestReadEbook'])) $guestId = $_SESSION['guestReadEbook']['id']??0;

    if ($guestId > 0 && !isset($_SESSION['guestReadEbook']['books'][$fileID])) {
        $_SESSION['guestReadEbook']['books'][$fileID] = ['startread' => date('Y-m-d H:i:s')];
    }

    // Counting access
    accessCount($fileID, $memberID, $userID, $guestId, (string) ip());
    include __DIR__ . '/viewer/index.php';
    exit;
});
