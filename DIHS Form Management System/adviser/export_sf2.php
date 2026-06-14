<?php
session_start();
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if ZipArchive extension is available
if (!class_exists('ZipArchive')) {
    die('Error: ZipArchive extension is not enabled. Please enable it in your php.ini file.<br><br>
    <strong>To fix this in XAMPP:</strong><br>
    1. Open the php.ini file (usually located in C:\\xampp\\php\\php.ini)<br>
    2. Find the line: <code>;extension=zip</code><br>
    3. Remove the semicolon to uncomment it: <code>extension=zip</code><br>
    4. Save the file and restart Apache in XAMPP Control Panel<br>
    5. Refresh this page');
}

// Include database connection
include '../db_connect.php';

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Get the month from GET parameter
$month = isset($_GET['month']) ? $_GET['month'] : '';
// Get adviser section details from session
$grade_level = $_SESSION['sf2_grade_level'] ?? '';
$track       = $_SESSION['sf2_track'] ?? '';
$semester    = $_SESSION['sf2_semester'] ?? '';
$class_name  = $_SESSION['sf2_section'] ?? '';

if (empty($month)) {
    die('Month not specified.');
}

// Validate semester information - ensure it's properly formatted
if (!empty($semester)) {
    // Normalize semester format (ensure it's "1st Semester" or "2nd Semester")
    if (stripos($semester, '1') !== false && stripos($semester, 'semester') === false) {
        $semester = '1st Semester';
    } elseif (stripos($semester, '2') !== false && stripos($semester, 'semester') === false) {
        $semester = '2nd Semester';
    }
} else {
    // Try to get semester from section if not in session
    if (!empty($class_name)) {
        $semester_query = "SELECT semester FROM section WHERE class_name = ? LIMIT 1";
        $semester_stmt = $conn->prepare($semester_query);
        if ($semester_stmt) {
            $semester_stmt->bind_param("s", $class_name);
            $semester_stmt->execute();
            $semester_result = $semester_stmt->get_result();
            if ($semester_result && $semester_result->num_rows > 0) {
                $semester_row = $semester_result->fetch_assoc();
                $semester = $semester_row['semester'] ?? '';
            }
            $semester_stmt->close();
        }
    }
}

try {
    // Load the template Excel file (adjust the path to your SF2 template)
    $templatePath = '../templates/SF2-SHS.xlsx';
    if (!file_exists($templatePath)) {
        throw new Exception('Template file not found at: ' . $templatePath);
    }

    $spreadsheet = IOFactory::load($templatePath);
    $worksheet = $spreadsheet->getActiveSheet();

    // Set month in AV7
    $worksheet->setCellValue('AV7', $month);

    // Put section details in template cells
    $worksheet->setCellValue('F7', $class_name);     // Section
    $worksheet->setCellValue('AH5', $grade_level);   // Grade Level
    $worksheet->setCellValue('AV5', $track);         // Track
    $worksheet->setCellValue('F5', $semester);       // Semester
    
    // Define column map for weeks and days based on the template
    // Dates are placed in row 10, attendance data in student rows
    $col_map = [
        1 => [
            'Monday' => 'F',      // Row F10
            'Tuesday' => 'H',     // Row H10
            'Wednesday' => 'I',   // Row I10
            'Thursday' => 'J',    // Row J10
            'Friday' => 'K',      // Row K10
            'Saturday' => 'L',    // Row L10
        ],
        2 => [
            'Monday' => 'M',      // Row M10
            'Tuesday' => 'O',     // Row O10
            'Wednesday' => 'P',   // Row P10
            'Thursday' => 'Q',    // Row Q10
            'Friday' => 'R',      // Row R10
            'Saturday' => 'S',    // Row S10
        ],
        3 => [
            'Monday' => 'T',      // Row T10
            'Tuesday' => 'V',     // Row V10
            'Wednesday' => 'W',   // Row W10
            'Thursday' => 'X',    // Row X10
            'Friday' => 'Z',      // Row Z10
            'Saturday' => 'AB',   // Row AB10
        ],
        4 => [
            'Monday' => 'AC',     // Row AC10
            'Tuesday' => 'AE',    // Row AE10
            'Wednesday' => 'AF',  // Row AF10
            'Thursday' => 'AG',   // Row AG10
            'Friday' => 'AH',     // Row AH10
            'Saturday' => 'AI',    // Row AI10
        ],
        5 => [
            'Monday' => 'AJ',     // Row AJ10
            'Tuesday' => 'AK',    // Row AK10
            'Wednesday' => 'AM',  // Row AM10
            'Thursday' => 'AN',   // Row AN10
            'Friday' => 'AO',     // Row AO10
            'Saturday' => 'AQ',   // Row AQ10
        ],
    ];
    
    // Add date numbers in row 10 for each day of the month
    $year = date('Y'); // Get current year
    $month_num = date('n', strtotime($month)); // Convert month name to number
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);
    
    // First, clear any existing day numbers in row 10
    foreach ($col_map as $week) {
        foreach ($week as $day_col) {
            $worksheet->setCellValue($day_col . '10', '');
        }
    }
    
    // Get the first day of the month to calculate week offsets
    $first_day_of_month = new DateTime("$year-$month_num-01");
    $first_day_offset = (int)$first_day_of_month->format('N') - 1; // 0-6, where 0 is Monday
    
    // Process ALL days of the month (1 to days_in_month)
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = new DateTime("$year-$month_num-$day");
        $day_of_week = $date->format('l'); // Full day name (e.g., Monday)
        
        // Calculate the week number (1-5) based on the day of month and first day offset
        $week = (int)(($day + $first_day_offset - 1) / 7) + 1;
        $week = min(5, max(1, $week)); // Ensure week is between 1 and 5
        
        // Skip if day is not in our column map (e.g., Sunday)
        if (!isset($col_map[$week][$day_of_week])) {
            error_log("Skipping day $day ($day_of_week) - not in column map for week $week");
            continue;
        }
        
        $col_letter = $col_map[$week][$day_of_week];
        
        // Set the day number in row 10 (header row for dates)
        $worksheet->setCellValue($col_letter . '10', $day);
        
        // Debug information
        error_log("Date $day ($day_of_week, Week $week) placed at $col_letter" . "10");
    }

    // Fetch students from sf1, filtered by adviser's section
    $sql_students = "
        SELECT sf1.LRN, sf1.Name, sf1.Sex
        FROM sf1
        INNER JOIN sf9 ON sf1.LRN = sf9.LRN
        WHERE sf9.section = ?
    ";
    $stmt_students = $conn->prepare($sql_students);
    if (!$stmt_students) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt_students->bind_param("s", $class_name);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
    if (!$result_students) {
        throw new Exception('Student query failed: ' . $conn->error);
    }

    $males = [];
    $females = [];
    while ($row = $result_students->fetch_assoc()) {
        if ($row['Sex'] === 'M') {
            $males[] = $row;
        } elseif ($row['Sex'] === 'F') {
            $females[] = $row;
        }
    }
    $stmt_students->close();

    // Sort males and females by name
    usort($males, function($a, $b) {
        return strcmp($a['Name'], $b['Name']);
    });
    usort($females, function($a, $b) {
        return strcmp($a['Name'], $b['Name']);
    });

    // Map student LRN to row numbers
    $student_rows = [];

    // Populate males starting from row 12
    $row_m = 12;
    foreach ($males as $student) {
        $worksheet->setCellValue('B' . $row_m, $student['LRN']);
        $worksheet->setCellValue('C' . $row_m, $student['Name']);
        $student_rows[$student['LRN']] = $row_m;
        $row_m++;
    }

    // Populate females starting from row 30
    $row_f = 30;
    foreach ($females as $student) {
        $worksheet->setCellValue('B' . $row_f, $student['LRN']);
        $worksheet->setCellValue('C' . $row_f, $student['Name']);
        $student_rows[$student['LRN']] = $row_f;
        $row_f++;
    }

    // Column map is now defined at the top of the file

    // Debug: Output the month being queried
    error_log("Exporting attendance for month: " . $month);
    
    // Fetch attendance data for the month - match records where the month matches
    $sql_att = "SELECT * FROM daily_attendance WHERE month = '" . mysqli_real_escape_string($conn, $month) . "' OR date LIKE '%" . mysqli_real_escape_string($conn, $month) . "%'";
    error_log("SQL Query: " . $sql_att); // Debug log the query
    
    $result_att = mysqli_query($conn, $sql_att);
    if (!$result_att) {
        $error = 'Attendance query failed: ' . mysqli_error($conn);
        error_log($error);
        throw new Exception($error);
    }
    
    // Debug: Count the number of attendance records found
    $att_count = mysqli_num_rows($result_att);
    error_log("Found $att_count attendance records");
    mysqli_data_seek($result_att, 0); // Reset result pointer

    $att_by_lrn = [];
    $att_records = []; // Store all attendance records for debugging
    while ($row = mysqli_fetch_assoc($result_att)) {
        $att_by_lrn[$row['LRN']][] = $row;
        $att_records[] = $row; // Store for debugging
    }
    
    // Debug: Log all attendance records
    error_log("Attendance records by LRN: " . print_r($att_by_lrn, true));
    error_log("All attendance records: " . print_r($att_records, true));
    
    // Debug: Log column mapping
    error_log("Column mapping: " . print_r($col_map, true));
    
    // Debug: Log student rows
    error_log("Student rows: " . print_r($student_rows, true));

    // Process attendance for each student
    foreach ($student_rows as $lrn => $row_num) {
        $abs_count = 0;
        $tardy_count = 0;
        $processed_dates = []; // Track processed dates to avoid duplicates

        // Initialize all attendance cells to empty
        foreach ($col_map as $week => $days) {
            foreach ($days as $day => $col) {
                $worksheet->setCellValue($col . $row_num, '');
            }
        }

        if (isset($att_by_lrn[$lrn])) {
            foreach ($att_by_lrn[$lrn] as $att) {
                // Parse date - handle different date formats
                $date_str = trim($att['date']);
                
                // Skip if we've already processed this date for this student
                if (in_array($date_str, $processed_dates)) {
                    error_log("Skipping duplicate date $date_str for LRN: $lrn");
                    continue;
                }
                
                $date_obj = false;
                
                // Try common date formats
                $formats = [
                    'Y-m-d',   // 2025-11-17 (MySQL default format)
                    'm/d/Y',   // 11/17/2025
                    'd/m/Y',   // 17/11/2025
                    'F j, Y',  // November 17, 2025
                    'F d, Y'   // November 17, 2025 (with leading zero)
                ];
                
                foreach ($formats as $format) {
                    $date_obj = DateTime::createFromFormat($format, $date_str);
                    if ($date_obj !== false) {
                        break;
                    }
                }
                
                if ($date_obj === false) {
                    error_log("Failed to parse date: " . $date_str);
                    continue; // Skip this record if date can't be parsed
                }
                
                $day_num = (int)$date_obj->format('j');
                $day_name = $date_obj->format('l');
                
                // Calculate week using the same logic as date placement
                // Get the first day of the month to calculate week offsets
                $year = (int)$date_obj->format('Y');
                $month_num = (int)$date_obj->format('n');
                $first_day_of_month = new DateTime("$year-$month_num-01");
                $first_day_offset = (int)$first_day_of_month->format('N') - 1; // 0-6, where 0 is Monday
                
                // Calculate the week number (1-5) based on the day of month and first day offset
                $week = (int)(($day_num + $first_day_offset - 1) / 7) + 1;
                $week = min(5, max(1, $week)); // Ensure week is between 1 and 5
                
                // Debug: Log the date being processed
                error_log("Processing LRN: $lrn, Date: $date_str -> " . $date_obj->format('Y-m-d') . " (Day: $day_name, Week: $week)");
                error_log("Attendance data - Present: {$att['present']}, Absent: {$att['absent']}, Tardy: {$att['tardy']}");

                if (isset($col_map[$week][$day_name])) {
                    $col = $col_map[$week][$day_name];
                    $status = '';
                    
                    // Check attendance status (ensure we're checking string '1' as per database)
                    if (isset($att['present']) && $att['present'] == '1') {
                        $status = 'P';
                    } elseif (isset($att['absent']) && $att['absent'] == '1') {
                        $status = 'A';
                        $abs_count++;
                    } elseif (isset($att['tardy']) && $att['tardy'] == '1') {
                        $status = 'L';
                        $tardy_count++;
                    }
                    
                    if (!empty($status)) {
                        $worksheet->setCellValue($col . $row_num, $status);
                        $processed_dates[] = $date_str; // Mark this date as processed
                        error_log("Set cell $col$row_num to '$status' for LRN: $lrn on $date_str");
                    }
                } else {
                    error_log("No column mapping found for Week $week, Day: $day_name");
                }
            }
        }

        // Set totals
        $worksheet->setCellValue('AR' . $row_num, $abs_count);
        $worksheet->setCellValue('AT' . $row_num, $tardy_count);
    }

    mysqli_close($conn); 

    // Clean output buffer
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    // Debug: Log successful export
    error_log("Successfully generated export for $month with " . count($student_rows) . " students");
    error_log("Export details - Semester: $semester, Grade Level: $grade_level, Track: $track, Section: $class_name");

    // Set headers for Excel file download
    // Include semester in filename for better organization
    $filename_semester = !empty($semester) ? '_' . str_replace(' ', '_', $semester) : '';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="SF2_' . $month . $filename_semester . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');

    // Output the Excel file
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    if (ob_get_length()) {
        ob_end_clean();
    }
    echo 'Error: ' . $e->getMessage();
    exit;
}