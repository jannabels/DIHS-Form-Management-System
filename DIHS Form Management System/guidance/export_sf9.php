<?php
// export_sf9.php
// Include database connection
include '../db_connect.php';

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Get LRN from GET parameter
$lrn = $_GET['lrn'] ?? '';
if (empty($lrn)) {
    die('No LRN provided');
}

// Fetch data from database
// School info (assuming single row)
$school_query = "SELECT region, division, school_name FROM school LIMIT 1";
$school_result = mysqli_query($conn, $school_query);
$school = mysqli_fetch_assoc($school_result) ?: ['region' => '', 'division' => '', 'school_name' => ''];

// Student info from sf1
$sf1_query = "SELECT Name, Age, Sex FROM sf1 WHERE LRN = '$lrn'";
$sf1_result = mysqli_query($conn, $sf1_query);
$sf1 = mysqli_fetch_assoc($sf1_result) ?: ['Name' => '', 'Age' => '', 'Sex' => ''];

// Section from sf9, then grade_level and track from section
$sf9_query = "SELECT section FROM sf9 WHERE LRN = '$lrn'";
$sf9_result = mysqli_query($conn, $sf9_query);
$sf9 = mysqli_fetch_assoc($sf9_result);
$section = $sf9['section'] ?? '';
$grade_level = '';
$track = '';
if (!empty($section)) {
    $section_query = "SELECT grade_level, track FROM section WHERE class_name = '$section'";
    $section_result = mysqli_query($conn, $section_query);
    $sect = mysqli_fetch_assoc($section_result);
    $grade_level = $sect['grade_level'] ?? '';
    $track = $sect['track'] ?? '';
}

// Monthly attendance
$att_query = "SELECT month, school_days, present_days, absent_days FROM monthly_attendance WHERE LRN = '$lrn'";
$att_result = mysqli_query($conn, $att_query);
$attendance = [];
$total_school_days = 0;
$total_present_days = 0;
$total_absent_days = 0;
while ($row = mysqli_fetch_assoc($att_result)) {
    $month = (int)$row['month'];
    $attendance[$month] = [
        'school_days' => (int)$row['school_days'],
        'present_days' => (int)$row['present_days'],
        'absent_days' => (int)$row['absent_days']
    ];
    $total_school_days += $row['school_days'];
    $total_present_days += $row['present_days'];
    $total_absent_days += $row['absent_days'];
}

// Grades
$grades_query = "SELECT semester, quarter, oralcom, komunikasyon, intro_philosophy, physical_educ1, gen_math, earth_sci, empower, precal, gen_chem1, read_writing, pagbasa, personal_dev, physical_educ2, stats_proba, disaster, prac_research1, basic_cal, gen_chem2 FROM grades WHERE LRN = '$lrn'";
$grades_result = mysqli_query($conn, $grades_query);
$grades = ['1' => [], '2' => []];
while ($row = mysqli_fetch_assoc($grades_result)) {
    $sem = $row['semester'];
    $qtr = $row['quarter']; // Could be 1, 2, or 'final'
    $grades[$sem][$qtr] = $row;
}

// Core values
$core_query = "SELECT quarter, makadiyos, makadiyos_2, makatao, makatao_2, makakalikasan, makabansa, makabansa_2 FROM core_values WHERE LRN = '$lrn'";
$core_result = mysqli_query($conn, $core_query);
$core = [];
while ($row = mysqli_fetch_assoc($core_result)) {
    $qtr = $row['quarter'];
    $core[$qtr] = $row;
}

// Load the template Excel file
$templatePath = '../templates/SF9-SHS.xlsx';
if (!file_exists($templatePath)) {
    die('Template file not found');
}
$spreadsheet = IOFactory::load($templatePath);
$frontSheet = $spreadsheet->getSheetByName('FRONT');
$backSheet = $spreadsheet->getSheetByName('BACK');

// Populate FRONT sheet
$frontSheet->setCellValue('T3', $lrn);
$frontSheet->setCellValue('Q7', $school['region']);
$frontSheet->setCellValue('Q10', $school['division']);
$frontSheet->setCellValue('P12', $school['school_name']);
$frontSheet->setCellValue('Q22', $sf1['Name']);
$frontSheet->setCellValue('Q24', $sf1['Age']);
$frontSheet->setCellValue('T24', $sf1['Sex']);
$frontSheet->setCellValue('Q26', $grade_level);
$frontSheet->setCellValue('T26', $section);
$frontSheet->setCellValue('Q29', $track);

// Attendance mapping
$monthToCol = [
    6 => 'B', 7 => 'C', 8 => 'D', 9 => 'E', 10 => 'F',
    11 => 'G', 12 => 'H', 1 => 'I', 2 => 'J', 3 => 'K', 4 => 'L'
];
foreach ($attendance as $month => $data) {
    if (isset($monthToCol[$month])) {
        $col = $monthToCol[$month];
        $frontSheet->setCellValue($col . '7', $data['school_days']);
        $frontSheet->setCellValue($col . '9', $data['present_days']);
        $frontSheet->setCellValue($col . '12', $data['absent_days']);
    }
}
$frontSheet->setCellValue('M7', $total_school_days);
$frontSheet->setCellValue('M9', $total_present_days);
$frontSheet->setCellValue('M12', $total_absent_days);

// Populate BACK sheet - Grades Semester 1
if (isset($grades['1'])) {
    $sem1 = $grades['1'];
    // Quarter 1
    if (isset($sem1['1'])) {
        $q1 = $sem1['1'];
        $backSheet->setCellValue('E7', $q1['oralcom']);
        $backSheet->setCellValue('E8', $q1['komunikasyon']);
        $backSheet->setCellValue('E9', $q1['intro_philosophy']);
        $backSheet->setCellValue('E11', $q1['physical_educ1']);
        $backSheet->setCellValue('E12', $q1['gen_math']);
        $backSheet->setCellValue('E13', $q1['earth_sci']);
        $backSheet->setCellValue('E15', $q1['empower']);
        $backSheet->setCellValue('E16', $q1['precal']);
        $backSheet->setCellValue('E17', $q1['gen_chem1']);
    }
    // Quarter 2
    if (isset($sem1['2'])) {
        $q2 = $sem1['2'];
        $backSheet->setCellValue('F7', $q2['oralcom']);
        $backSheet->setCellValue('F8', $q2['komunikasyon']);
        $backSheet->setCellValue('F9', $q2['intro_philosophy']);
        $backSheet->setCellValue('F11', $q2['physical_educ1']);
        $backSheet->setCellValue('F12', $q2['gen_math']);
        $backSheet->setCellValue('F13', $q2['earth_sci']);
        $backSheet->setCellValue('F15', $q2['empower']);
        $backSheet->setCellValue('F16', $q2['precal']);
        $backSheet->setCellValue('F17', $q2['gen_chem1']);
    }
    // Final
    if (isset($sem1['final'])) {
        $final = $sem1['final'];
        $backSheet->setCellValue('G7', $final['oralcom']);
        $backSheet->setCellValue('G8', $final['komunikasyon']);
        $backSheet->setCellValue('G9', $final['intro_philosophy']);
        $backSheet->setCellValue('G11', $final['physical_educ1']);
        $backSheet->setCellValue('G12', $final['gen_math']);
        $backSheet->setCellValue('G13', $final['earth_sci']);
        $backSheet->setCellValue('G15', $final['empower']);
        $backSheet->setCellValue('G16', $final['precal']);
        $backSheet->setCellValue('G17', $final['gen_chem1']);
    }
}

// Grades Semester 2
if (isset($grades['2'])) {
    $sem2 = $grades['2'];
    // Quarter 1
    if (isset($sem2['1'])) {
        $q1 = $sem2['1'];
        $backSheet->setCellValue('E23', $q1['read_writing']);
        $backSheet->setCellValue('E24', $q1['pagbasa']);
        $backSheet->setCellValue('E25', $q1['personal_dev']);
        $backSheet->setCellValue('E26', $q1['physical_educ2']);
        $backSheet->setCellValue('E27', $q1['stats_proba']);
        $backSheet->setCellValue('E28', $q1['disaster']);
        $backSheet->setCellValue('E30', $q1['prac_research1']);
        $backSheet->setCellValue('E31', $q1['basic_cal']);
        $backSheet->setCellValue('E32', $q1['gen_chem2']);
    }
    // Quarter 2
    if (isset($sem2['2'])) {
        $q2 = $sem2['2'];
        $backSheet->setCellValue('F23', $q2['read_writing']);
        $backSheet->setCellValue('F24', $q2['pagbasa']);
        $backSheet->setCellValue('F25', $q2['personal_dev']);
        $backSheet->setCellValue('F26', $q2['physical_educ2']);
        $backSheet->setCellValue('F27', $q2['stats_proba']);
        $backSheet->setCellValue('F28', $q2['disaster']);
        $backSheet->setCellValue('F30', $q2['prac_research1']);
        $backSheet->setCellValue('F31', $q2['basic_cal']);
        $backSheet->setCellValue('F32', $q2['gen_chem2']);
    }
    // Final
    if (isset($sem2['final'])) {
        $final = $sem2['final'];
        $backSheet->setCellValue('G23', $final['read_writing']);
        $backSheet->setCellValue('G24', $final['pagbasa']);
        $backSheet->setCellValue('G25', $final['personal_dev']);
        $backSheet->setCellValue('G26', $final['physical_educ2']);
        $backSheet->setCellValue('G27', $final['stats_proba']);
        $backSheet->setCellValue('G28', $final['disaster']);
        $backSheet->setCellValue('G30', $final['prac_research1']);
        $backSheet->setCellValue('G31', $final['basic_cal']);
        $backSheet->setCellValue('G32', $final['gen_chem2']);
    }
}

// Core values mapping
$quarterToCol = [1 => 'J', 2 => 'K', 3 => 'L', 4 => 'M'];
foreach ($core as $quarter => $data) {
    if (isset($quarterToCol[$quarter])) {
        $col = $quarterToCol[$quarter];
        $backSheet->setCellValue($col . '6', $data['makadiyos']);
        $backSheet->setCellValue($col . '8', $data['makadiyos_2']);
        $backSheet->setCellValue($col . '9', $data['makatao']);
        $backSheet->setCellValue($col . '11', $data['makatao_2']);
        $backSheet->setCellValue($col . '13', $data['makakalikasan']);
        $backSheet->setCellValue($col . '15', $data['makabansa']);
        $backSheet->setCellValue($col . '18', $data['makabansa_2']);
    }
}

// Set headers for Excel file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="SF9_' . $lrn . '_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Cache-Control: max-age=0');

// Output the Excel file
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
?>