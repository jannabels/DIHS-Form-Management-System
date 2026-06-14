<?php
// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Function to log errors and display them if display_errors is on
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage);
    
    if (ini_get('display_errors')) {
        echo "<pre>ERROR: " . htmlspecialchars($message) . "</pre>\n";
    }
    return false;
}

ob_start(); // Start output buffering

// Include database connection
$db_connect_path = __DIR__ . '/../db_connect.php';
if (!file_exists($db_connect_path)) {
    die("Database connection file not found at: " . htmlspecialchars($db_connect_path));
}

include $db_connect_path;

// Verify database connection
if (!isset($conn) || !$conn) {
    die("Database connection failed: " . (isset($conn) ? mysqli_connect_error() : "No connection object"));
}

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Get LRN from GET parameter
$lrn = $_GET['lrn'] ?? '';
if (empty($lrn)) {
    logError('No LRN provided');
    exit;
}

// Fetch student personal data from sf1
$sf1_query = "SELECT Name, Birthdate, Sex FROM sf1 WHERE LRN = '$lrn'";
$sf1_result = mysqli_query($conn, $sf1_query);
if (!$sf1_result) {
    logError('SF1 query failed: ' . mysqli_error($conn));
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
    logError('SF9 query failed: ' . mysqli_error($conn));
    exit;
}
$sf9 = mysqli_fetch_assoc($sf9_result);
$section_name = $sf9['section'] ?? '';
$grade_level = $track = $semester = '';
if (!empty($section_name)) {
    $section_query = "SELECT class_name, grade_level, track, semester FROM section WHERE class_name = '$section_name'";
    $section_result = mysqli_query($conn, $section_query);
    if (!$section_result) {
        logError('Section query failed: ' . mysqli_error($conn));
        exit;
    }
    $section = mysqli_fetch_assoc($section_result);
    $section_name = $section['class_name'] ?? $section_name;
    $grade_level = $section['grade_level'] ?? '';
    $track = $section['track'] ?? '';
    $semester = $section['semester'] ?? '';
}

// Fetch grades
logError("LRN: " . $lrn);
logError("Grade Level: " . $grade_level);

// First, verify the table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_grades'");
if (mysqli_num_rows($table_check) == 0) {
    die("Error: The 'student_grades' table does not exist in the database.");
}

// Check if we have a valid grade level
if (empty($grade_level)) {
    die("Error: Could not determine grade level for the student.");
}

$grades_query = "SELECT * FROM student_grades WHERE LRN = ? AND grade_level = ?";
$stmt = mysqli_prepare($conn, $grades_query);
if (!$stmt) {
    die("Failed to prepare query: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'ss', $lrn, $grade_level);
$grades_result = mysqli_stmt_execute($stmt);

if ($grades_result === false) {
    die("Grades query failed: " . mysqli_error($conn) . "\nQuery: " . $grades_query);
}

$grades_result = mysqli_stmt_get_result($stmt);

$grades = [];
while ($row = mysqli_fetch_assoc($grades_result)) {
    $sem = $row['semester'];
    $qtr = $row['quarter'];
    $subject_code = strtolower(str_replace([' ', '-'], '_', $row['subject_code']));
    
    if (!isset($grades[$sem])) {
        $grades[$sem] = [];
    }
    if (!isset($grades[$sem][$qtr])) {
        $grades[$sem][$qtr] = [];
    }
    
    $grades[$sem][$qtr][$subject_code] = $row['grade'];
    
    // Log the grade being processed
    error_log("Processed grade - Semester: $sem, Quarter: $qtr, Subject: {$row['subject_code']} (key: $subject_code), Grade: {$row['grade']}");
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
$templatePath = '../templates/FINALNASF10.xlsx';
if (!file_exists($templatePath)) {
    die('Template file not found: ' . $templatePath);
}

try {
    $spreadsheet = IOFactory::load($templatePath);
    
    // Debug: List all sheet names
    $sheetNames = $spreadsheet->getSheetNames();
    error_log('Available sheets: ' . print_r($sheetNames, true));
    
    // Try to get sheets with different possible names
    $frontSheet = $spreadsheet->getSheetByName('FRONT') 
        ?? $spreadsheet->getSheetByName('Sheet1') 
        ?? $spreadsheet->getSheet(0);
        
    $backSheet = $spreadsheet->getSheetByName('BACK') 
        ?? $spreadsheet->getSheetByName('Sheet2') 
        ?? (count($sheetNames) > 1 ? $spreadsheet->getSheet(1) : null);
        
    if (!$frontSheet) {
        throw new Exception('Could not find the front sheet in the template');
    }
    
} catch (Exception $e) {
    error_log('Failed to load template: ' . $e->getMessage());
    die('Error loading template: ' . $e->getMessage());
}

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

// Only try to set back sheet values if back sheet exists
if ($backSheet) {
    $backSheet->setCellValue('G5', $track);
    $backSheet->setCellValue('G48', $track);
}

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

// Clean all output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Create a temporary file
$tempFile = tempnam(sys_get_temp_dir(), 'sf10_');

try {
    // Save to temporary file first
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    
    // Disable pre-calculate formulas to prevent formula errors
    $writer->setPreCalculateFormulas(false);
    
    // Save to temporary file
    $writer->save($tempFile);
    
    // Verify file exists and is not empty
    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
        throw new Exception('Failed to generate Excel file');
    }
    
    // Set headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="SF10_' . $lrn . '.xlsx"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($tempFile));
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Output the file
    readfile($tempFile);
    
    // Delete the temporary file
    @unlink($tempFile);
    
} catch (Exception $e) {
    // Log the error
    error_log('Excel generation error: ' . $e->getMessage());
    
    // Send error response if headers not sent
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Error generating Excel file: ' . $e->getMessage();
    } else {
        echo '<div style="color: red; font-weight: bold;">Error generating Excel file: ' . 
             htmlspecialchars($e->getMessage()) . '</div>';
    }
}

exit;
?>