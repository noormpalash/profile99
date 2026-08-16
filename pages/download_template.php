<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Auth.php';
Auth::requirePermission('bulk_import');

// In case composer fails, this will be missing. 
// If it is missing, we can fallback to CSV or just show an error.
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    die("Composer dependencies are not installed. Please install PhpSpreadsheet first.");
}
require_once $vendorPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

require_once __DIR__ . '/../classes/LookupManager.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Personnel Import');

// Define columns
$columns = [
    'A' => ['title' => 'Personal Number', 'width' => 20, 'desc' => 'Required. Unique ID.'],
    'B' => ['title' => 'Name', 'width' => 30, 'desc' => 'Required.'],
    'C' => ['title' => 'NID', 'width' => 20, 'desc' => '10, 13 or 17 digits'],
    'D' => ['title' => 'Appointment', 'width' => 20, 'desc' => 'Select from dropdown'],
    'E' => ['title' => 'Rank', 'width' => 15, 'desc' => 'Select from dropdown'],
    'F' => ['title' => 'Unit', 'width' => 20, 'desc' => 'Select from dropdown'],
    'G' => ['title' => 'Platoon', 'width' => 15, 'desc' => 'Select from dropdown'],
    'H' => ['title' => 'Blood Group', 'width' => 15, 'desc' => 'Select from dropdown'],
    'I' => ['title' => 'Status', 'width' => 15, 'desc' => 'Select from dropdown'],
    'J' => ['title' => 'Batch', 'width' => 15, 'desc' => ''],
    'K' => ['title' => 'Mobile Number', 'width' => 20, 'desc' => ''],
    'L' => ['title' => 'District', 'width' => 20, 'desc' => ''],
    'M' => ['title' => 'Vill', 'width' => 20, 'desc' => ''],
    'N' => ['title' => 'PO', 'width' => 20, 'desc' => ''],
    'O' => ['title' => 'PS', 'width' => 20, 'desc' => ''],
    'P' => ['title' => 'Admission Date', 'width' => 15, 'desc' => 'YYYY-MM-DD'],
    'Q' => ['title' => 'Retirement Date', 'width' => 15, 'desc' => 'YYYY-MM-DD'],
    'R' => ['title' => 'UN Mission', 'width' => 20, 'desc' => ''],
    'S' => ['title' => 'Punishment Note', 'width' => 30, 'desc' => ''],
    'T' => ['title' => 'IPFT 1st', 'width' => 15, 'desc' => 'PASS, FAIL, NOT ATTENDING'],
    'U' => ['title' => 'IPFT 2nd', 'width' => 15, 'desc' => 'PASS, FAIL, NOT ATTENDING'],
    'V' => ['title' => 'RET', 'width' => 15, 'desc' => ''],
    'W' => ['title' => 'Speed March', 'width' => 15, 'desc' => ''],
    'X' => ['title' => 'Cycle 1', 'width' => 15, 'desc' => 'Select from dropdown'],
    'Y' => ['title' => 'Cycle 2', 'width' => 15, 'desc' => 'Select from dropdown'],
    'Z' => ['title' => 'Cycle 3', 'width' => 15, 'desc' => 'Select from dropdown'],
    'AA' => ['title' => 'Cycle 4', 'width' => 15, 'desc' => 'Select from dropdown'],
    'AB' => ['title' => 'Birthdate', 'width' => 15, 'desc' => 'YYYY-MM-DD'],
    'AC' => ['title' => 'Marriage Date', 'width' => 15, 'desc' => 'YYYY-MM-DD'],
    'AD' => ['title' => 'Marital Status', 'width' => 15, 'desc' => 'Select from dropdown'],
    'AE' => ['title' => 'Children Count', 'width' => 15, 'desc' => 'Integer'],
    'AF' => ['title' => 'Family Member', 'width' => 15, 'desc' => 'Select from dropdown'],
    'AG' => ['title' => 'From Date', 'width' => 15, 'desc' => 'YYYY-MM-DD'],
    'AH' => ['title' => 'To Date', 'width' => 15, 'desc' => 'YYYY-MM-DD'],
    'AI' => ['title' => 'Living Status', 'width' => 15, 'desc' => 'Select from dropdown'],
    'AJ' => ['title' => 'Currently Living Address', 'width' => 30, 'desc' => ''],
    'AK' => ['title' => 'Father Name', 'width' => 20, 'desc' => ''],
    'AL' => ['title' => 'Father Mobile', 'width' => 20, 'desc' => ''],
    'AM' => ['title' => 'Mother Name', 'width' => 20, 'desc' => ''],
    'AN' => ['title' => 'Mother Mobile', 'width' => 20, 'desc' => ''],
    'AO' => ['title' => 'Spouse Name', 'width' => 20, 'desc' => ''],
    'AP' => ['title' => 'Spouse Mobile', 'width' => 20, 'desc' => ''],
    'AQ' => ['title' => 'Medical Category', 'width' => 20, 'desc' => 'Select from dropdown'],
    'AR' => ['title' => 'Height (cm)', 'width' => 15, 'desc' => 'Number'],
    'AS' => ['title' => 'Weight (kg)', 'width' => 15, 'desc' => 'Number'],
    'AT' => ['title' => 'Any disease', 'width' => 20, 'desc' => ''],
    'AU' => ['title' => 'Special note', 'width' => 30, 'desc' => ''],
    'AV' => ['title' => 'Cadres', 'width' => 30, 'desc' => 'Comma separated. Example: Basic, Advanced'],
    'AW' => ['title' => 'Courses', 'width' => 40, 'desc' => 'Comma separated. Example: Course1:Pass, Course2:Grade A'],
    'AX' => ['title' => 'MOQs', 'width' => 40, 'desc' => 'Comma separated. Example: MOQ1:Pass, MOQ2:Fail'],
    'AY' => ['title' => 'Leaves', 'width' => 50, 'desc' => 'Comma separated. Example: 2024-01-01:2024-01-05:Weekend Leave'],
    'AZ' => ['title' => 'Social Links', 'width' => 40, 'desc' => 'Comma separated. Example: Facebook:http://..., Twitter:http://...'],
];

// Set headers and styles
$row = 1;
foreach ($columns as $col => $info) {
    $sheet->setCellValue($col . $row, $info['title']);
    $sheet->getColumnDimension($col)->setWidth($info['width']);
    if ($info['desc']) {
        $sheet->getComment($col . $row)->getText()->createTextRun($info['desc']);
    }
}

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4A5D23']],
    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
];
$sheet->getStyle('A1:AZ1')->applyFromArray($headerStyle);
$sheet->freezePane('A2');

// Date Formats
$dateCols = ['P', 'Q', 'AB', 'AC', 'AG', 'AH'];
foreach ($dateCols as $col) {
    $sheet->getStyle($col . '2:' . $col . '1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
}

// Hidden sheet for lookups to bypass 255-char limit
$lookupSheet = $spreadsheet->createSheet();
$lookupSheet->setTitle('Lookups');
$spreadsheet->setActiveSheetIndex(0);
// Hide the lookup sheet
$lookupSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

$lookups = [
    'D' => ['table' => 'appointments', 'lookupCol' => 'A'],
    'E' => ['table' => 'ranks', 'lookupCol' => 'B'],
    'F' => ['table' => 'units', 'lookupCol' => 'C'],
    'G' => ['table' => 'platoons', 'lookupCol' => 'D'],
    'H' => ['table' => 'blood_groups', 'lookupCol' => 'E'],
    'AQ' => ['table' => 'medical_categories', 'lookupCol' => 'F'],
];

foreach ($lookups as $mainCol => $info) {
    $data = LookupManager::getAll($info['table']);
    $names = array_column($data, 'name');
    $lr = 1;
    foreach ($names as $name) {
        $lookupSheet->setCellValue($info['lookupCol'] . $lr, $name);
        $lr++;
    }
    if ($lr > 1) {
        $maxRow = $lr - 1;
        $validation = $sheet->getCell($mainCol . '2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
                   ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                   ->setAllowBlank(true)
                   ->setShowDropDown(true)
                   ->setFormula1('Lookups!$' . $info['lookupCol'] . '$1:$' . $info['lookupCol'] . '$' . $maxRow);
        $sheet->setDataValidation($mainCol . '2:' . $mainCol . '1000', $validation);
    }
}

// Hardcoded Dropdowns
$hardcoded = [
    'I' => '"active,on_leave,cmh,trg,cmd,att,goc_gd,suspend,osl,awol"',
    'T' => '"PASS,FAIL,NOT ATTENDING"',
    'U' => '"PASS,FAIL,NOT ATTENDING"',
    'X' => '"Training,Administration,Pre Leave,Group Training"',
    'Y' => '"Training,Administration,Pre Leave,Group Training"',
    'Z' => '"Training,Administration,Pre Leave,Group Training"',
    'AA' => '"Training,Administration,Pre Leave,Group Training"',
    'AD' => '"single,married,widowed,divorced"',
    'AF' => '"Yes,No"',
    'AI' => '"In Living,Out Living"',
];

foreach ($hardcoded as $col => $formula) {
    $validation = $sheet->getCell($col . '2')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST)
               ->setAllowBlank(true)
               ->setShowDropDown(true)
               ->setFormula1($formula);
    $sheet->setDataValidation($col . '2:' . $col . '1000', $validation);
}

// Add Demo Data to Row 2
$demoData = [
    'A' => 'JD-12345',
    'B' => 'John Doe',
    'C' => '1234567890',
    'I' => 'active',
    'K' => '01712345678',
    'L' => 'Dhaka',
    'M' => 'Demo Village',
    'P' => '2020-01-01',
    'Q' => '2040-01-01',
    'T' => 'PASS',
    'U' => 'PASS',
    'X' => 'Training',
    'AB' => '1990-01-01',
    'AD' => 'single',
    'AF' => 'No',
    'AI' => 'In Living',
    'AV' => 'Basic, Advanced',
    'AW' => 'Basic Training:Pass',
    'AX' => 'Firing:Pass',
    'AY' => '2024-01-01:2024-01-05:Weekend Leave',
    'AZ' => 'Facebook:https://facebook.com/johndoe',
];
foreach ($demoData as $col => $val) {
    $sheet->setCellValue($col . '2', $val);
}

// Export as Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Personnel_Import_Template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
