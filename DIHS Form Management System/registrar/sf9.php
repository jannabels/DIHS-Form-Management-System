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

// Get school days for each month from school_days table
$school_year = date('Y') . '-' . (date('Y') + 1);
$school_days_query = "SELECT month_name, school_days FROM school_days WHERE school_year = '$school_year' ORDER BY id";
$school_days_result = mysqli_query($conn, $school_days_query);

// Map month names to numbers
$monthNameToNumber = [
    'January' => 1,
    'February' => 2,
    'March' => 3,
    'April' => 4,
    'May' => 5,
    'June' => 6,
    'July' => 7,
    'August' => 8,
    'September' => 9,
    'October' => 10,
    'November' => 11,
    'December' => 12
];

// Initialize arrays to store school days, present days, and absent days
$school_days = [];
$present_days = [];
$absent_days = [];
$total_school_days = 0;
$total_present_days = 0;
$total_absent_days = 0;

// Get school days for each month
while ($row = mysqli_fetch_assoc($school_days_result)) {
    $month_name = $row['month_name'];
    if (isset($monthNameToNumber[$month_name])) {
        $month = $monthNameToNumber[$month_name];
        $school_days[$month] = (int)$row['school_days'];
        $total_school_days += $row['school_days'];
    }
}

// Get present and absent days from monthly_attendance
$att_query = "SELECT month, present_days, absent_days FROM monthly_attendance WHERE LRN = '$lrn'";
$att_result = mysqli_query($conn, $att_query);

while ($row = mysqli_fetch_assoc($att_result)) {
    $month = (int)$row['month'];
    $present_days[$month] = (int)$row['present_days'];
    $absent_days[$month] = (int)$row['absent_days'];
    $total_present_days += $row['present_days'];
    $total_absent_days += $row['absent_days'];
}

// Combine the data for easier access
$attendance = [];
for ($month = 1; $month <= 12; $month++) {
    $attendance[$month] = [
        'school_days' => $school_days[$month] ?? 0,
        'present_days' => $present_days[$month] ?? 0,
        'absent_days' => $absent_days[$month] ?? 0
    ];
}

// Get subjects from curriculum
$subjects = [
    '1' => [
        'core' => [],
        'applied' => []
    ],
    '2' => [
        'core' => [],
        'applied' => []
    ]
];

// Log the grade level and track being used
$debug_file = __DIR__ . '/export_debug.log';
$debug_content = "Grade Level: $grade_level, Track: $track\n";
if (is_writable(dirname($debug_file))) {
    file_put_contents($debug_file, $debug_content, FILE_APPEND);
}

// Fetch subjects from curriculum
$curriculum_query = "SELECT * FROM curriculum 
    WHERE grade_level = '$grade_level' 
    AND track = '$track' 
    ORDER BY semester, subject_type, subject_name";
$curriculum_result = mysqli_query($conn, $curriculum_query);

if (!$curriculum_result) {
    file_put_contents('export_debug.log', "Query failed: " . mysqli_error($conn) . "\n", FILE_APPEND);
    die('Error fetching curriculum: ' . mysqli_error($conn));
}

$subject_count = 0;
while ($subject = mysqli_fetch_assoc($curriculum_result)) {
    $subject_count++;
    // Extract the semester number (1 or 2) from the semester string
    $sem = (strpos($subject['semester'], '1') !== false) ? '1' : '2';
    $type = strtolower($subject['subject_type']);
    
    file_put_contents('export_debug.log', "Processing subject: {$subject['subject_name']} (Semester: $sem, Type: $type)\n", FILE_APPEND);
    
    // Store both original and lowercase versions of the subject code for matching
    $subjects[$sem][$type][] = [
        'code' => $subject['subject_code'],
        'code_lower' => strtolower($subject['subject_code']), // Add lowercase version for matching
        'name' => $subject['subject_name']
    ];
}

// Log subjects to file if possible
$debug_content = "Total subjects found: $subject_count\n";
$debug_content .= "Processed subjects array: " . print_r($subjects, true) . "\n";
if (is_writable(dirname($debug_file))) {
    file_put_contents($debug_file, $debug_content, FILE_APPEND);
}

// Initialize grades structure based on curriculum
$grades = [
    '1' => [
        1 => [],
        2 => [],
        'final' => []
    ],
    '2' => [
        1 => [],
        2 => [],
        3 => [], // For failed subjects from first sem (Q3)
        4 => [], // For failed subjects from second sem (Q4)
        'final' => []
    ]
];

// Array to store failed subjects from first semester
$failed_subjects_sem1 = [];

// Array to store failed subjects from second semester
$failed_subjects_sem2 = [];

// Get grades from student_grades table if they exist
$grades_query = "SELECT semester, quarter, subject_code, grade 
                FROM student_grades 
                WHERE LRN = '$lrn' AND grade_level = '$grade_level' AND 
                      (semester = '1' OR semester = '2') AND 
                      (quarter = '1' OR quarter = '2' OR quarter = '3' OR quarter = '4')";
$grades_result = mysqli_query($conn, $grades_query);

if ($grades_result) {
    while ($row = mysqli_fetch_assoc($grades_result)) {
        $sem = $row['semester'];
        $qtr = $row['quarter'];
        // Convert subject code to lowercase and replace spaces/hyphens with underscores for matching
        $subject_key = strtolower($row['subject_code']);
        $subject_key = str_replace([' ', '-'], '_', $subject_key);
        $grades[$sem][$qtr][$subject_key] = $row['grade'];
        
        // Debug log for grades being processed
        file_put_contents('export_debug.log', "Processing grade - Semester: $sem, Quarter: $qtr, Subject: {$row['subject_code']} (key: $subject_key), Grade: {$row['grade']}\n", FILE_APPEND);
        
        // Calculate final grade if both quarters have grades
        if (isset($grades[$sem][1][$subject_key]) && 
            isset($grades[$sem][2][$subject_key]) && 
            $grades[$sem][1][$subject_key] !== '' && 
            $grades[$sem][2][$subject_key] !== '') {
            $grades[$sem]['final'][$subject_key] = round(($grades[$sem][1][$subject_key] + $grades[$sem][2][$subject_key]) / 2);
        }
        
        // Store failed subjects from first semester
        if ($sem === '1' && $qtr === '2' && $row['grade'] < 75) {
            $failed_subjects_sem1[] = [
                'code' => $row['subject_code'],
                'name' => $subjects[$sem]['core'][array_search($subject_key, array_column($subjects[$sem]['core'], 'code'))]['name'] ?? $subjects[$sem]['applied'][array_search($subject_key, array_column($subjects[$sem]['applied'], 'code'))]['name'],
                'final_grade' => $row['grade']
            ];
        }
        
        // Store failed subjects from second semester
        if ($sem === '2' && $qtr === '2' && $row['grade'] < 75) {
            $failed_subjects_sem2[] = [
                'code' => $row['subject_code'],
                'name' => $subjects[$sem]['core'][array_search($subject_key, array_column($subjects[$sem]['core'], 'code'))]['name'] ?? $subjects[$sem]['applied'][array_search($subject_key, array_column($subjects[$sem]['applied'], 'code'))]['name'],
                'final_grade' => $row['grade']
            ];
        }
    }
}

// Initialize empty grades for all subjects in the curriculum
foreach ($subjects as $sem => $types) {
    foreach ($types as $type => $subj_list) {
        foreach ($subj_list as $subject) {
            $subject_key = strtolower(str_replace([' ', '-'], '_', $subject['code']));
            
            // Initialize if not already set
            if (!isset($grades[$sem][1][$subject_key])) {
                $grades[$sem][1][$subject_key] = '';
            }
            if (!isset($grades[$sem][2][$subject_key])) {
                $grades[$sem][2][$subject_key] = '';
            }
            if (!isset($grades[$sem][3][$subject_key])) {
                $grades[$sem][3][$subject_key] = '';
            }
            if (!isset($grades[$sem][4][$subject_key])) {
                $grades[$sem][4][$subject_key] = '';
            }
            if (!isset($grades[$sem]['final'][$subject_key])) {
                $grades[$sem]['final'][$subject_key] = '';
            }
        }
    }
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
$templatePath = '../templates/FINALSF9-SHS.xlsx';
if (!file_exists($templatePath)) {
    die('Template file not found');
}
$spreadsheet = IOFactory::load($templatePath);

// Get the sheets with the correct names
$frontSheet = $spreadsheet->getSheetByName('SF9 FRONT (G11)');
$backSheet = $spreadsheet->getSheetByName('SF9 BACK (G11)');

// Verify sheets exist
if (!$frontSheet || !$backSheet) {
    // For debugging - list available sheets
    $sheetNames = $spreadsheet->getSheetNames();
    die('Required sheets not found. Available sheets: ' . implode(', ', $sheetNames));
}

// Populate FRONT sheet
$frontSheet->setCellValue('T3', $lrn);

// Student Information
$name_parts = explode(' ', $sf1['Name'], 3);
$last_name = $name_parts[0] ?? '';
$first_name = $name_parts[1] ?? '';
$middle_name = $name_parts[2] ?? '';

$frontSheet->setCellValue('T16', $last_name); // Last Name
$frontSheet->setCellValue('AC16', $first_name); // First Name
$frontSheet->setCellValue('AP16', $middle_name); // Middle Name
$frontSheet->setCellValue('T18', $sf1['Age']); // Age
$frontSheet->setCellValue('AJ18', $sf1['Sex']); // Sex
$frontSheet->setCellValue('Q22', $sf1['Name']); // Full Name
$frontSheet->setCellValue('Q26', $grade_level); // Grade Level
$frontSheet->setCellValue('T26', $section); // Section
$frontSheet->setCellValue('Q29', $track); // Track/Strand

// School Year (assuming current year)
$current_year = date('Y');
$next_year = $current_year + 1;
$school_year = "$current_year-$next_year";
$frontSheet->setCellValue('W21', $school_year); // School Year
$frontSheet->setCellValue('W22', $track); // Track/Strand

// Get adviser's name from accounts table
$adviser_query = "SELECT a.`First Name` as first_name, a.`Last Name` as last_name 
                 FROM section s 
                 JOIN accounts a ON s.adviser = a.id 
                 WHERE s.class_name = '$section'";
$adviser_result = mysqli_query($conn, $adviser_query);
$adviser_name = '';

if ($adviser_row = mysqli_fetch_assoc($adviser_result)) {
    $adviser_name = $adviser_row['first_name'] . ' ' . $adviser_row['last_name'];
} else {
    // Fallback: Get adviser ID if name not found
    $adviser_id_query = "SELECT adviser FROM section WHERE class_name = '$section'";
    $adviser_id_result = mysqli_query($conn, $adviser_id_query);
    if ($adviser_id_row = mysqli_fetch_assoc($adviser_id_result)) {
        $adviser_name = $adviser_id_row['adviser'];
    }
}

$frontSheet->setCellValue('J27', $adviser_name); // Adviser
$frontSheet->setCellValue('AL19', $section); // Section
$frontSheet->setCellValue('AL33', $adviser_name); // Adviser (duplicate)

// Map month numbers to Excel columns (June=6 -> B, July=7 -> C, etc.)
$monthToCol = [
    6 => 'B',  // June
    7 => 'C',  // July
    8 => 'D',  // August
    9 => 'E',  // September
    10 => 'F', // October
    11 => 'G', // November
    12 => 'H', // December
    1 => 'I',  // January
    2 => 'J',  // February
    3 => 'K'   // March
];

// Set school days for each month (Row 3)
foreach ($monthToCol as $month => $col) {
    $days = $school_days[$month] ?? 0;
    $frontSheet->setCellValue($col . '3', $days);
}

// Set present days (Row 6) and absent days (Row 9)
foreach ($attendance as $month => $data) {
    if (isset($monthToCol[$month])) {
        $col = $monthToCol[$month];
        $frontSheet->setCellValue($col . '6', $data['present_days']);  // Present Days
        $frontSheet->setCellValue($col . '9', $data['absent_days']);  // Absent Days
    }
}

// Set totals
$frontSheet->setCellValue('N3', $total_school_days);  // Total School Days
$frontSheet->setCellValue('N6', $total_present_days); // Total Present Days
$frontSheet->setCellValue('N9', $total_absent_days);  // Total Absent Days

// Set first and last digit in AM1-AX1
// Assuming this is for some kind of ID or tracking number
// You may need to adjust this based on your specific requirements
$id_digits = str_split($lrn); // Using LRN as the ID
$start_col = 'AM';
$end_col = 'AX';
$current_col = $start_col;

for ($i = 0; $i < count($id_digits) && $current_col <= $end_col; $i++) {
    $frontSheet->setCellValue($current_col . '1', $id_digits[$i]);
    $current_col++;
}

// Function to populate subjects in the sheet
function populateSubjects($sheet, $startRow, $subjects, $grades, $quarter) {
    // Define column mapping for each quarter
    $colMap = [
        1 => 'E',  // 1st Quarter
        2 => 'F',  // 2nd Quarter
        3 => 'G',  // 3rd Quarter (failed subjects from sem 1)
        4 => 'H',  // 4th Quarter (failed subjects from sem 2)
        'final' => 'I'  // Final Grade
    ];
    
    $col = $colMap[$quarter] ?? 'C';  // Default to 1st Quarter if quarter is invalid
    $row = $startRow;
    
    // Debug: Log the subjects being processed
    $debug = "Populating subjects for quarter $quarter starting at row $row\n";
    $debug .= "Subjects to process: " . print_r($subjects, true) . "\n";
    
    foreach ($subjects as $subject) {
        $subject_key = strtolower(str_replace([' ', '-'], '_', $subject['code']));
        $debug .= "Processing subject: {$subject['name']} (key: $subject_key) at row $row\n";
        
        // Set subject name in column A (only for first quarter to avoid duplication)
        if ($quarter === 1) {
            $sheet->setCellValue('A' . $row, $subject['name']);
            $debug .= "  - Set subject name '{$subject['name']}' at A$row\n";
        }
        
        // Set grade if it exists
        if (isset($grades[$quarter][$subject_key]) && $grades[$quarter][$subject_key] !== '') {
            $sheet->setCellValue($col . $row, $grades[$quarter][$subject_key]);
            $debug .= "  - Set grade '{$grades[$quarter][$subject_key]}' at $col$row\n";
        }
        
        // Only increment row for first quarter to keep subjects aligned
        if ($quarter === 1) {
            $row++;
        }
    }
    
    // For quarters 2, 3, and 4, we need to use the same row structure as quarter 1
    if ($quarter !== 1) {
        $row = $startRow;
        foreach ($subjects as $subject) {
            $subject_key = strtolower(str_replace([' ', '-'], '_', $subject['code']));
            
            // Set grade if it exists
            if (isset($grades[$quarter][$subject_key]) && $grades[$quarter][$subject_key] !== '') {
                $sheet->setCellValue($col . $row, $grades[$quarter][$subject_key]);
                $debug .= "  - Set grade '{$grades[$quarter][$subject_key]}' at $col$row\n";
            }
            
            $row++;
        }
    }
    
    // Log debug info
    $debug_file = __DIR__ . '/export_debug.log';
    if (is_writable(dirname($debug_file))) {
        file_put_contents($debug_file, $debug, FILE_APPEND);
    }
}

// Debug: Log subjects array
file_put_contents('export_debug.log', "Subjects array: " . print_r($subjects, true) . "\n", FILE_APPEND);

// Populate BACK sheet - Grades
foreach (['1', '2'] as $semester) {
    if (!empty($subjects[$semester])) {
        $sem_grades = $grades[$semester] ?? [1 => [], 2 => [], 3 => [], 4 => [], 'final' => []];
        $sem_subs = $subjects[$semester];
        
        // Debug: Log current semester subjects
        file_put_contents('export_debug.log', "Processing semester $semester subjects: " . print_r($sem_subs, true) . "\n", FILE_APPEND);
        
        // Set starting rows based on semester and subject type
        $core_start_row = $semester === '1' ? 8 : 29;
        $applied_start_row = $semester === '1' ? 17 : 37;
        
        // Add failed subjects from previous semester if this is semester 2
        if ($semester === '2' && !empty($failed_subjects_sem1)) {
            // Add a section for failed subjects from semester 1 (Q3)
            $sem_subs['failed_sem1'] = [];
            foreach ($failed_subjects_sem1 as $failed) {
                $sem_subs['failed_sem1'][] = [
                    'code' => $failed['code'],
                    'name' => $failed['name']
                ];
                // Add to Q3 grades
                $subject_key = strtolower(str_replace([' ', '-'], '_', $failed['code']));
                $grades['2'][3][$subject_key] = $failed['final_grade'];
            }
        }
        
        // First, write all subject names (only once)
        if (!empty($sem_subs['core'])) {
            $row = $core_start_row;
            file_put_contents('export_debug.log', "Writing core subjects starting at row $row\n", FILE_APPEND);
            foreach ($sem_subs['core'] as $subject) {
                file_put_contents('export_debug.log', "Writing subject '{$subject['name']}' to A$row\n", FILE_APPEND);
                $backSheet->setCellValue('A' . $row, $subject['name']);
                $row++;
            }
        } else {
            file_put_contents('export_debug.log', "No core subjects found for semester $semester\n", FILE_APPEND);
        }
        
        if (!empty($sem_subs['applied'])) {
            $row = $applied_start_row;
            file_put_contents('export_debug.log', "Writing applied subjects starting at row $row\n", FILE_APPEND);
            foreach ($sem_subs['applied'] as $subject) {
                file_put_contents('export_debug.log', "Writing subject '{$subject['name']}' to A$row\n", FILE_APPEND);
                $backSheet->setCellValue('A' . $row, $subject['name']);
                $row++;
            }
        } else {
            file_put_contents('export_debug.log', "No applied subjects found for semester $semester\n", FILE_APPEND);
        }
        
        // Then write grades for each quarter
        foreach (['1', '2', '3', '4', 'final'] as $qtr) {
            $quarter = $qtr === 'final' ? 'final' : (int)$qtr;
            $col_map = [
                1 => 'E',  // Q1
                2 => 'F',  // Q2
                3 => 'G',  // Q3 (failed subjects from sem 1)
                4 => 'H',  // Q4 (failed subjects from sem 2)
                'final' => 'I'  // Final grade
            ];
            $col = $col_map[$quarter] ?? 'E';
            
            // Core subjects
            if (!empty($sem_subs['core'])) {
                $row = $core_start_row;
                foreach ($sem_subs['core'] as $subject) {
                    // Use the pre-computed lowercase version for matching
                    $subject_key = $subject['code_lower'];
                    $subject_key = str_replace([' ', '-'], '_', $subject_key);
                    
                    // Debug log for subject being processed
                    file_put_contents('export_debug.log', "Processing subject - Semester: $semester, Quarter: $quarter, " . 
                        "Subject: {$subject['name']} (code: {$subject['code']}, key: $subject_key)\n", FILE_APPEND);
                    
                    if (isset($sem_grades[$quarter][$subject_key]) && $sem_grades[$quarter][$subject_key] !== '') {
                        $backSheet->setCellValue($col . $row, $sem_grades[$quarter][$subject_key]);
                    }
                    $row++;
                }
            }
            
            // Applied subjects
            if (!empty($sem_subs['applied'])) {
                $row = $applied_start_row;
                foreach ($sem_subs['applied'] as $subject) {
                    // Use the pre-computed lowercase version for matching
                    $subject_key = $subject['code_lower'];
                    $subject_key = str_replace([' ', '-'], '_', $subject_key);
                    
                    // Debug log for subject being processed
                    file_put_contents('export_debug.log', "Processing subject - Semester: $semester, Quarter: $quarter, " . 
                        "Subject: {$subject['name']} (code: {$subject['code']}, key: $subject_key)\n", FILE_APPEND);
                    
                    if (isset($sem_grades[$quarter][$subject_key]) && $sem_grades[$quarter][$subject_key] !== '') {
                        $backSheet->setCellValue($col . $row, $sem_grades[$quarter][$subject_key]);
                    }
                    $row++;
                }
            }
            
            // Failed subjects from semester 1 (Q3)
            if ($semester === '2' && $quarter === 3 && !empty($sem_subs['failed_sem1'])) {
                $row = $applied_start_row + count($sem_subs['applied'] ?? []) + 2; // Add some space after applied subjects
                
                foreach ($sem_subs['failed_sem1'] as $subject) {
                    // Use the pre-computed lowercase version for matching
                    $subject_key = $subject['code_lower'];
                    $subject_key = str_replace([' ', '-'], '_', $subject_key);
                    
                    // Debug log for subject being processed
                    file_put_contents('export_debug.log', "Processing subject - Semester: $semester, Quarter: $quarter, " . 
                        "Subject: {$subject['name']} (code: {$subject['code']}, key: $subject_key)\n", FILE_APPEND);
                    
                    $backSheet->setCellValue('A' . $row, $subject['name']);
                    if (isset($sem_grades[$quarter][$subject_key]) && $sem_grades[$quarter][$subject_key] !== '') {
                        $backSheet->setCellValue($col . $row, $sem_grades[$quarter][$subject_key]);
                    }
                    $row++;
                }
            }
            
            // Failed subjects from semester 2 (Q4)
            if ($semester === '2' && $quarter === 4 && !empty($failed_subjects_sem2)) {
                $row = $applied_start_row + count($sem_subs['applied'] ?? []) + 
                       (empty($sem_subs['failed_sem1']) ? 2 : count($sem_subs['failed_sem1']) + 3);
                
                // Add a label for failed subjects
                if ($row === $applied_start_row + count($sem_subs['applied'] ?? []) + 
                    (empty($sem_subs['failed_sem1']) ? 2 : count($sem_subs['failed_sem1']) + 3)) {
                    $backSheet->setCellValue('A' . ($row-1), 'Failed Subjects from 2nd Semester (Q4):');
                }
                
                foreach ($failed_subjects_sem2 as $subject) {
                    // Use the pre-computed lowercase version for matching
                    $subject_key = $subject['code_lower'];
                    $subject_key = str_replace([' ', '-'], '_', $subject_key);
                    
                    // Debug log for subject being processed
                    file_put_contents('export_debug.log', "Processing subject - Semester: $semester, Quarter: $quarter, " . 
                        "Subject: {$subject['name']} (code: {$subject['code']}, key: $subject_key)\n", FILE_APPEND);
                    
                    $backSheet->setCellValue('A' . $row, $subject['name']);
                    if (isset($sem_grades[2][$subject_key]) && $sem_grades[2][$subject_key] !== '') {
                        // For Q4, we show the final grade from semester 2
                        $backSheet->setCellValue($col . $row, $sem_grades[2][$subject_key]);
                    }
                    $row++;
                }
            }
        }
    }
}

// Core values mapping
$quarterToCol = [1 => 'L', 2 => 'M', 3 => 'N', 4 => 'O'];
foreach ($core as $quarter => $data) {
    if (isset($quarterToCol[$quarter])) {
        $col = $quarterToCol[$quarter];
        // Quarter 1-4: Maka-Diyos - Row 7
        $backSheet->setCellValue($col . '7', $data['makadiyos']);
        // Quarter 1-4: Maka-Diyos 2 - Row 10
        $backSheet->setCellValue($col . '10', $data['makadiyos_2']);
        // Quarter 1-4: Makatao - Row 13
        $backSheet->setCellValue($col . '13', $data['makatao']);
        // Quarter 1-4: Makatao 2 - Row 16
        $backSheet->setCellValue($col . '16', $data['makatao_2']);
        // Quarter 1-4: Makakalikasan - Row 19
        $backSheet->setCellValue($col . '19', $data['makakalikasan']);
        // Quarter 1-4: Makabansa - Row 22
        $backSheet->setCellValue($col . '22', $data['makabansa']);
        // Quarter 1-4: Makabansa 2 - Row 26
        $backSheet->setCellValue($col . '26', $data['makabansa_2']);
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