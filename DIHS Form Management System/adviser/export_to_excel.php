<?php
// Include database connection
include '../db_connect.php';

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Set headers for JSON response (in case of error)
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'error' => '');

try {
    // Get the JSON data from the request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !is_array($data)) {
        throw new Exception('Invalid or empty data received');
    }

    // Load the template Excel file
    $templatePath = '../templates/SF1-SHS.xlsx';
    if (!file_exists($templatePath)) {
        throw new Exception('Template file not found at: ' . $templatePath);
    }

    $spreadsheet = IOFactory::load($templatePath);
    $worksheet = $spreadsheet->getActiveSheet();

    // Define header fields (based on createSF1Header)
    $headerData = [
        'M3' => '107921120657', // School ID
        'AF3'  => 'IV-A',
        'Z3' => '107921120657', // Division
        'U3' => '107921120657', // District
        'F3' => 'DASMARIÑAS INTEGRATED HIGH SCHOOL', // School Name
        'L6' => date('Y'), // School Year (current year)
        // 'B8' => '', // Grade Level (empty as per your code)
        // 'B9' => '' // Section (empty as per your code)
    ];

    // Populate header fields
    foreach ($headerData as $cell => $value) {
        $worksheet->setCellValue($cell, $value);
    }

    // Define starting row and column mapping for table data
    $startRow = 11; // Table data starts at row 10
    $columns = [
        0 => 'B', // LRN
        1 => 'C', // Name
        2 => 'G', // Sex
        3 => 'H', // Birthdate
        4 => 'J', // Age
        5 => 'L', // Religious Affiliation
        6 => 'M', // House No./Street/Sitio/Purok
        7 => 'N', // Barangay
        8 => 'R', // Municipality/City
        9 => 'U', // Province
        10 => 'W', // Father's Name
        11 => 'X', // Mother's Maiden Name
        12 => 'Z', // Name (Guardian)
        13 => 'AC', // Relationship
        14 => 'AD', // Contact Number
        15 => 'AE' // Remarks
    ]; 

    // Populate table data
    foreach ($data as $rowIndex => $rowData) {
        if (count($rowData) !== count($columns)) {
            throw new Exception('Invalid number of columns in row ' . ($rowIndex + 1));
        }

        $currentRow = $startRow + $rowIndex;
        foreach ($rowData as $colIndex => $value) {
            $cell = $columns[$colIndex] . $currentRow;
            $worksheet->setCellValue($cell, $value ?: '');
        }
    }

    // Set headers for Excel file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="School_Form_1_SF1_' . date('Y-m-d_H-i-s') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Output the Excel file
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    echo json_encode($response);
    exit;
}
?>