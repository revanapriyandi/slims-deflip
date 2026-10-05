<?php

/**
 *
 * Modified 2010  by Wardiyono (wynerst@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

// key to authenticate
define('INDEX_AUTH', '1');

// load SLiMS main system configuration
require_once __DIR__ . '/../../../sysconfig.inc.php';

// require LIB.'composer/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// start the session
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

// Make some functions for writing out an excel file. These functions do some hex writing and to be honest I got
// them from some where else but hey it works so I am not going to question it just reuse

$xlsdata = [];
if (isset($_SESSION['xlsquery'])) {
	$q = $_SESSION['xlsquery'];
	$q = $dbs->query($q);
	$xlsdata = [];
	$xlsheader = [];
  while($f = $q->fetch_field()) {
  	$xlsheader[] = $f->name;
  }
  $xlsdata[] = $xlsheader;
  while ($a = $q->fetch_row()) {
  	$xlsdata[] = $a;
  }
}
else if (isset($_SESSION['xlsdata'])) {
	$xlsdata = $_SESSION['xlsdata'];
}
$spreadsheet = new Spreadsheet();
if (in_array($_SESSION['tblout'] ?? '', ['DeFlip_Access', 'DeFlip_Access_History'], true)) {
  header('Cache-Control: private, no-store');
  // Treat collection and reader values as text; never interpret them as formulas.
  foreach ($xlsdata as $rowIndex => $row) {
    foreach ($row as $columnIndex => $value) {
      $isAccessCount = ($_SESSION['tblout'] ?? '') === 'DeFlip_Access' && $rowIndex > 0 && $columnIndex === 3;
      $spreadsheet->getActiveSheet()->setCellValueExplicitByColumnAndRow(
        $columnIndex + 1, $rowIndex + 1, $isAccessCount ? (int) $value : (string) $value,
        $isAccessCount ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING
      );
    }
  }
} else {
$spreadsheet->getActiveSheet()
  ->fromArray(
      $xlsdata,  // The data to set
      NULL,        // Array values with this value will not be set
      'A1'         // Top left coordinate of the worksheet range where
                   //    we want to set these values (default is A1)
  );
}
$writer = new Xlsx($spreadsheet);
$tblout = $_SESSION['tblout'] ?? 'spreadsheet';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=$tblout.xlsx");
header("Pragma: no-cache");
$writer->save('php://output');
