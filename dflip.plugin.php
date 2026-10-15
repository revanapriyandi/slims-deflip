<?php
/**
 * Plugin Name: DearFlip
 * Plugin URI: -
 * Description: PDF flipbook, reader registration and access reports
 * Version: 1.2.0
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

// Runs before SLiMS loads lib/contents/*, so stock core files need no changes.
$plugin->register(\SLiMS\Plugins::CONTENT_BEFORE_LOAD, function () {
    if (!isset($_GET['p'])) return;
    $page = strtolower(\SLiMS\Opac::filterPath(''));

    // Stock fstream returns any session value named by POST init; only allow PDF loader keys.
    if ($page === 'fstream' && isset($_POST['init'])) {
        $loader = $_POST['init'];
        if (!is_string($loader) || !\Ramsey\Uuid\Uuid::isValid($loader) || !is_string($_SESSION[$loader] ?? null)) {
            http_response_code(400);
            exit;
        }
        header('Cache-Control: no-store');
        echo base64_encode($_SESSION[$loader] . $loader);
        exit;
    }

    if ($page !== 'fstream-pdf') return;
    require_once __DIR__ . '/helper.php';
    \DeFlip\GuestAccess::guardPdfStream(
        isset($_GET['fid']) ? (int) $_GET['fid'] : 0,
        isset($_GET['bid']) ? (int) $_GET['bid'] : 0
    );
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
    if ($loaderInit === '') {
        // Stock SLiMS does not pass its loader key to this hook; register our own the same way.
        $loaderInit = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $_SESSION[$loaderInit] = base64_encode($file_d['file_key'] ?? '');
    }
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
