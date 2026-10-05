<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2022-03-29 07:19:21
 * @modify date 2023-01-18 14:23:25
 * @license GPLv3
 * @desc [description]
 */

require_once __DIR__ . '/src/Settings.php';
require_once __DIR__ . '/src/GuestAccess.php';
require_once __DIR__ . '/src/AccessReport.php';

if (!function_exists('dflipEscape')) {
    function dflipEscape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('dflipReaderUrl')) {
    function dflipReaderUrl(int $fileId, int $biblioId): string
    {
        return slimsUrl('index.php?' . http_build_query(['p' => 'fstream', 'fid' => $fileId, 'bid' => $biblioId]));
    }
}

if (!function_exists('dflipAccent')) {
    function dflipAccent(): string
    {
        $accent = config('admin_template.default_color', '#004db6');
        return is_string($accent) && preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $accent) ? $accent : '#004db6';
    }
}

if (!function_exists('dflipReportEscapeCell')) {
    function dflipReportEscapeCell($database, array $row, int $column): string
    {
        return dflipEscape($row[$column] ?? '');
    }
}

if (!function_exists('dflipReportDetailLink')) {
    function dflipReportDetailLink($database, array $row): string
    {
        global $report;
        $filters = $report instanceof \DeFlip\AccessReport ? $report->query() : [];
        $url = getCurrentUrl(array_merge($filters, ['detailList' => 'yes', 'fid' => (int) $row[4]]));
        return '<a class="s-btn btn btn-default notAJAX" href="' . dflipEscape($url) . '" onclick="top.$(\'#mainContent\').simbioAJAX(this.href); return false;">' . __('View history') . '</a>';
    }
}

if (!function_exists('dflipReportTypeCell')) {
    function dflipReportTypeCell($database, array $row, int $column): string
    {
        return dflipEscape(($row[$column] ?? '') === 'application/pdf' ? 'PDF' : ($row[$column] ?? ''));
    }
}

if (!function_exists('dflipUrl')) {
    function dflipUrl(string $additionalUrl = '')
    {
        return slimsUrl('plugins/' . basename(__DIR__) . '/' . $additionalUrl);
    }
}

if (!function_exists('dflipAssetUrl')) {
    function dflipAssetUrl(string $asset): string
    {
        return dflipUrl($asset) . '?v=' . filemtime(__DIR__ . '/' . $asset);
    }
}

if (!function_exists('slimsUrl')) {
    function slimsUrl(string $additionalUrl = '')
    {
        return trim(SWB . $additionalUrl);
    }
}

if (!function_exists('getCurrentUrl')) {
    function getCurrentUrl($query = [])
    {

        return $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge([
            'mod' => is_string($_GET['mod'] ?? null) ? $_GET['mod'] : null,
            'id' => is_string($_GET['id'] ?? null) ? $_GET['id'] : null,
        ], $query));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $Url)
    {
        header('Location: ' . $Url, true, 303);
        exit;
    }
}

if (!function_exists('accessCount')) {
    function accessCount($fileID, $memberID, $userID, $guestID, $clientIP)
    {
        try {
            \SLiMS\DB::getInstance()
                ->prepare('insert into files_read set file_id = ?, member_id = ?, user_id = ?, guest_id = ?, client_ip = ?')
                ->execute(func_get_args());
        } catch (\Throwable $error) {
            error_log('DeFlip: unable to record file access.');
        }
    }
}

if (!function_exists('getDefaultField')) {
    function getDefaultField()
    {
        return [
            [
                'tag' => 'input',
                'type' => 'text',
                'label' => 'Name',
                'column' => 'name',
            ],
            [
                'tag' => 'input',
                'type' => 'text',
                'label' => 'Institution',
                'column' => 'institution'
            ],
            [
                'tag' => 'input',
                'type' => 'tel',
                'label' => 'Phone Number',
                'column' => 'phonenumber'
            ]
        ];
    }
}

if (!function_exists('dd'))
{
    function dd($input)
    {
        echo '<pre>';
        var_dump($input);
        echo '</pre>';
        exit;
    }
}
