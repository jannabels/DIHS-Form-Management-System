<?php
// Suppress display of errors/warnings (log them instead)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Logs to a file in the same directory

ob_start(); // Start output buffering

// Include database connection
include '../db_connect.php';

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Get LRN from GET parameter
$lrn = $_GET['lrn'] ?? '';
if (empty($lrn)) {
    error_log('No LRN provided'); // Log instead of die
    exit;
}

// Fetch student personal data from sf1
$sf1_query = "SELECT Name, Birthdate, Sex FROM sf1 WHERE LRN = '$lrn'";
$sf1_result = mysqli_query($conn, $sf1_query);
if (!$sf1_result) {
    error_log('SF1 query failed: ' . mysqli_error($conn));
    exit;
}
$sf1 = mysqli_fetch_assoc($sf1_result) ?: ['Name' => '', 'Birthdate' => '', 'Sex' => ''];

// Parse Name: Format "Last Name, First Name Middle Initial"
$name = $sf1['Name'];
$last = $first = $middle = '';
if (!empty($name)) {
    $parts = explode(',', $name, 2);
    $last = trim($parts[0] ?? '');
    if (isset($parts[1])) {
        $first_middle = trim($parts[1]);
        $fm_parts = preg_split('/\s+/', $first_middle);
        $middle = array_pop($fm_parts);
        $first = implode(' ', $fm_parts);
    }
}

// Fetch section, grade_level, track, semester from sf9 and section
$sf9_query = "SELECT section FROM sf9 WHERE LRN = '$lrn'";
$sf9_result = mysqli_query($conn, $sf9_query);
if (!$sf9_result) {
    error_log('SF9 query failed: ' . mysqli_error($conn));
    exit;
}
$sf9 = mysqli_fetch_assoc($sf9_result);
$section_name = $sf9['section'] ?? '';
$grade_level = $track = $semester = '';
if (!empty($section_name)) {
    $section_query = "SELECT class_name, grade_level, track, semester FROM section WHERE class_name = '$section_name'";
    $section_result = mysqli_query($conn, $section_query);
    if (!$section_result) {
        error_log('Section query failed: ' . mysqli_error($conn));
        exit;
    }
    $section = mysqli_fetch_assoc($section_result);
    $section_name = $section['class_name'] ?? $section_name;
    $grade_level = $section['grade_level'] ?? '';
    $track = $section['track'] ?? '';
    $semester = $section['semester'] ?? '';
}

// Fetch grades
$grades_query = "SELECT * FROM grades WHERE LRN = '$lrn'";
$grades_result = mysqli_query($conn, $grades_query);
if (!$grades_result) {
    error_log('Grades query failed: ' . mysqli_error($conn));
    exit;
}
$grades = [];
while ($row = mysqli_fetch_assoc($grades_result)) {
    $sem = $row['semester'];
    $qtr = $row['quarter'];
    if (!isset($grades[$sem])) $grades[$sem] = [];
    $grades[$sem][$qtr] = $row;
}

// Define subjects (unchanged)
$subjects_sem1 = [
    'oralcom' => ['name' => 'Oral Communication', 'cat' => 'Core'],
    'komunikasyon' => ['name' => 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'cat' => 'Core'],
    'intro_philosophy' => ['name' => 'Introduction to the Philosophy of the Human Person /Pambungad sa Pilosopiya ng Tao', 'cat' => 'Core'],
    'physical_educ1' => ['name' => 'Physical Education and Health 1', 'cat' => 'Core'],
    'gen_math' => ['name' => 'General Mathematics', 'cat' => 'Core'],
    'earth_sci' => ['name' => 'Earth Science', 'cat' => 'Core'],
    'empower' => ['name' => 'Empowerment Technologies', 'cat' => 'Applied'],
    'precal' => ['name' => 'Pre-Calculus', 'cat' => 'Applied'],
    'gen_chem1' => ['name' => 'General Chemistry 1', 'cat' => 'Applied'],
];

$subjects_sem2 = [
    'read_writing' => ['name' => 'Reading and Writing', 'cat' => 'Core'],
    'pagbasa' => ['name' => 'Pagbasa at Pagsusuri ng Iba’t-Ibang Teksto Tungo sa Pananaliksik', 'cat' => 'Core'],
    'personal_dev' => ['name' => 'Personal Development/ Pansariling Kaunlaran', 'cat' => 'Core'],
    'physical_educ2' => ['name' => 'Physical Education and Health 2', 'cat' => 'Core'],
    'stats_proba' => ['name' => 'Statistics and Probability', 'cat' => 'Core'],
    'disaster' => ['name' => 'Disaster Readiness and Risk Reduction', 'cat' => 'Core'],
    'prac_research1' => ['name' => 'Practical Research 1', 'cat' => 'Applied'],
    'basic_cal' => ['name' => 'Basic Calculus', 'cat' => 'Applied'],
    'gen_chem2' => ['name' => 'General Chemistry 2', 'cat' => 'Applied'],
];

// Load the template Excel file
$templatePath = '../templates/SF10-SHS.xlsx'; // Adjust if needed
if (!file_exists($templatePath)) {
    error_log('Template file not found: ' . $templatePath);
    exit;
}
try {
    $spreadsheet = IOFactory::load($templatePath);
} catch (Exception $e) {
    error_log('Failed to load template: ' . $e->getMessage());
    exit;
}
$frontSheet = $spreadsheet->getSheetByName('FRONT');
$backSheet = $spreadsheet->getSheetByName('BACK');

// Populate personal data and grades (unchanged from your code)
// Populate personal data on FRONT
$frontSheet->setCellValue('F8', $last);
$frontSheet->setCellValue('Y8', $first);
$frontSheet->setCellValue('AZ8', $middle);
$frontSheet->setCellValue('C9', $lrn);
$frontSheet->setCellValue('AA9', $sf1['Birthdate']);
$frontSheet->setCellValue('AN9', $sf1['Sex']);
$frontSheet->setCellValue('AS25', $section_name);
$frontSheet->setCellValue('G25', $track);
$frontSheet->setCellValue('G68', $track);
$backSheet->setCellValue('G5', $track);
$backSheet->setCellValue('G48', $track);

// Populate grades based on grade_level
if ($grade_level === 'Grade 11') {
    // Sem 1
    $row = 31;
    foreach ($subjects_sem1 as $field => $info) {
        $frontSheet->setCellValue('A' . $row, $info['cat']);
        $frontSheet->setCellValue('I' . $row, $info['name']);
        $frontSheet->setCellValue('AT' . $row, $grades['1']['1'][$field] ?? '');
        $frontSheet->setCellValue('AY' . $row, $grades['1']['2'][$field] ?? '');
        $row++;
    }
    // Sem 2
    $row = 74;
    foreach ($subjects_sem2 as $field => $info) {
        $frontSheet->setCellValue('A' . $row, $info['cat']);
        $frontSheet->setCellValue('I' . $row, $info['name']);
        $frontSheet->setCellValue('AT' . $row, $grades['2']['1'][$field] ?? '');
        $frontSheet->setCellValue('AY' . $row, $grades['2']['2'][$field] ?? '');
        $row++;
    }
} elseif ($grade_level === 'Grade 12') {
    // Assuming same subjects for Grade 12; adjust if different subjects are provided later
    // Sem 1
    $row = 11;
    foreach ($subjects_sem1 as $field => $info) {
        $backSheet->setCellValue('A' . $row, $info['cat']);
        $backSheet->setCellValue('I' . $row, $info['name']);
        $backSheet->setCellValue('AT' . $row, $grades['1']['1'][$field] ?? '');
        $backSheet->setCellValue('AY' . $row, $grades['1']['2'][$field] ?? '');
        $row++;
    }
    // Sem 2
    $row = 54;
    foreach ($subjects_sem2 as $field => $info) {
        $backSheet->setCellValue('A' . $row, $info['cat']);
        $backSheet->setCellValue('I' . $row, $info['name']);
        $backSheet->setCellValue('AT' . $row, $grades['2']['1'][$field] ?? '');
        $backSheet->setCellValue('AY' . $row, $grades['2']['2'][$field] ?? '');
        $row++;
    }
}

ob_end_clean(); // Clean buffer

// Set headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="SF10_' . $lrn . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');
header('Expires: 0');

// Output the Excel file
try {
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
} catch (Exception $e) {
    error_log('Failed to save XLSX: ' . $e->getMessage());
}
exit;
?>