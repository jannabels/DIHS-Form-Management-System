<?php
session_start();
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include '../db_connect.php';

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use DateTime;

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

    // Set the date for testing - November 13, 2025 (Thursday, 2nd week)
    $date = new DateTime('2025-11-13');
    $day_number = (int)$date->format('j');
    $day_name = $date->format('l');
    $day_short = strtoupper($date->format('D'));
    
    // Calculate the week of the month (1-5)
    $first_day_of_month = new DateTime($date->format('Y-m-01'));
    $day_of_week = (int)$first_day_of_month->format('N'); // 1 (Monday) to 7 (Sunday)
    $day_of_month = (int)$date->format('j');
    $week_of_month = floor(($day_of_month + $day_of_week - 2) / 7) + 1;
    
    // Define column mappings for each week (1-5)
    $week_columns = [
        // Week 1
        1 => [
            'MON' => 'F', 'TUE' => 'H', 'WED' => 'I', 'THU' => 'J', 'FRI' => 'K', 'SAT' => 'L'
        ],
        // Week 2
        2 => [
            'MON' => 'M', 'TUE' => 'O', 'WED' => 'P', 'THU' => 'Q', 'FRI' => 'R', 'SAT' => 'S'
        ],
        // Week 3
        3 => [
            'MON' => 'T', 'TUE' => 'V', 'WED' => 'W', 'THU' => 'X', 'FRI' => 'Z', 'SAT' => 'AB'
        ],
        // Week 4
        4 => [
            'MON' => 'AC', 'TUE' => 'AE', 'WED' => 'AF', 'THU' => 'AG', 'FRI' => 'AH', 'SAT' => 'AI'
        ],
        // Week 5
        5 => [
            'MON' => 'AJ', 'TUE' => 'AK', 'WED' => 'AM', 'THU' => 'AN', 'FRI' => 'AO', 'SAT' => 'AQ'
        ]
    ];
    
    // Get the column for the current day
    if (isset($week_columns[$week_of_month][$day_short])) {
        $column = $week_columns[$week_of_month][$day_short];
        // Set day number in row 10
        $worksheet->setCellValue($column . '10', $day_number);
        // Set day name in row 12
        $worksheet->setCellValue($column . '12', $day_name);
        
        // For testing - mark the current day with a border
        $worksheet->getStyle($column . '10')->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFFF0000']
                ]
            ]
        ]);
    }

    // Put section details in template cells
    $worksheet->setCellValue('F7', $class_name);     // Section
    $worksheet->setCellValue('AH5', $grade_level);   // Grade Level
    $worksheet->setCellValue('AV5', $track);         // Track
    $worksheet->setCellValue('F5', $semester);       // Semester

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

    // Separate males and females
    $males = [];
    $females = [];
    
    while ($student = mysqli_fetch_assoc($result_students)) {
        if (strtoupper($student['Sex']) === 'MALE') {
            $males[] = $student;
        } else {
            $females[] = $student;
        }
    }
    
    // Count male and female students
    $male_count = count($males);
    $female_count = count($females);
    $total_students = $male_count + $female_count;
    
    // Insert counts in the specified rows
    $worksheet->setCellValue('A29', 'TOTAL MALE ENROLLEES AS OF ' . strtoupper(date('F j, Y')));
    $worksheet->setCellValue('B29', $male_count);
    
    $worksheet->setCellValue('A57', 'TOTAL FEMALE ENROLLEES AS OF ' . strtoupper(date('F j, Y')));
    $worksheet->setCellValue('B57', $female_count);
    
    $worksheet->setCellValue('A58', 'TOTAL ENROLLEES AS OF ' . strtoupper(date('F j, Y')));
    $worksheet->setCellValue('B58', $total_students);
    
    // Populate males starting from row 15
    $row_m = 15;
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

    // Define column map for weeks and days
    // Updated column mapping to match the SF2 form layout
    $col_map = [
        // Week 1
        1 => [
            'Monday' => 'F',
            'Tuesday' => 'H',
            'Wednesday' => 'I',
            'Thursday' => 'J',
            'Friday' => 'K',
            'Saturday' => 'L'
        ],
        // Week 2
        2 => [
            'Monday' => 'M',
            'Tuesday' => 'O',
            'Wednesday' => 'P',
            'Thursday' => 'Q',
            'Friday' => 'R',
            'Saturday' => 'S'
        ],
        // Week 3
        3 => [
            'Monday' => 'T',
            'Tuesday' => 'V',
            'Wednesday' => 'W',
            'Thursday' => 'X',
            'Friday' => 'Z',
            'Saturday' => 'AB',
        ],
        // Week 4
        4 => [
            'Monday' => 'AC',
            'Tuesday' => 'AE',
            'Wednesday' => 'AF',
            'Thursday' => 'AG',
            'Friday' => 'AH',
            'Saturday' => 'AI',
        ],
        // Week 5
        5 => [
            'Monday' => 'AJ',
            'Tuesday' => 'AK',
            'Wednesday' => 'AM',
            'Thursday' => 'AN',
            'Friday' => 'AO',
            'Saturday' => 'AQ',
        ],
    ];

    // Fetch attendance data for the month
    $sql_att = "SELECT da.*, sf1.Name 
                FROM daily_attendance da
                JOIN sf1 ON da.LRN = sf1.LRN COLLATE utf8mb4_general_ci
                WHERE da.month = '" . mysqli_real_escape_string($conn, $month) . "'
                AND (da.present = 1 OR da.absent = 1 OR da.tardy = 1)";
    $result_att = mysqli_query($conn, $sql_att);
    if (!$result_att) {
        throw new Exception('Attendance query failed: ' . mysqli_error($conn));
    }

    // Group attendance by LRN
    $att_by_lrn = [];
    while ($row = mysqli_fetch_assoc($result_att)) {
        $att_by_lrn[$row['LRN']][] = $row;
    }

    // Process attendance for each student
    foreach ($student_rows as $lrn => $row_num) {
        $abs_count = 0;
        $tardy_count = 0;
        $present_count = 0;

        // Initialize all attendance cells to empty
        foreach ($col_map as $week => $days) {
            foreach ($days as $day => $col) {
                $worksheet->setCellValue($col . $row_num, '');
            }
        }

        if (isset($att_by_lrn[$lrn])) {
            foreach ($att_by_lrn[$lrn] as $att) {
                // Parse date - handle both DATE and string formats
                $date_obj = is_string($att['date']) ? 
                    DateTime::createFromFormat('Y-m-d', $att['date']) : 
                    new DateTime($att['date']);
                    
                if ($date_obj) {
                    $day_num = (int)$date_obj->format('j');
                    $day_of_week = (int)$date_obj->format('N'); // 1 (Monday) to 7 (Sunday)
                    
                    // Calculate the week of the month (1-5)
                    $first_day_of_month = new DateTime($date_obj->format('Y-m-01'));
                    $first_day_offset = (int)$first_day_of_month->format('N'); // 1 (Monday) to 7 (Sunday)
                    $week = floor(($day_num + $first_day_offset - 2) / 7) + 1;
                    $week = min(5, max(1, $week)); // Ensure week is between 1-5
                    
                    $day_name = $date_obj->format('l');

                    if (isset($col_map[$week][$day_name])) {
                        $col = $col_map[$week][$day_name];
                        if ($att['present'] == 1) {
                            $worksheet->setCellValue($col . $row_num, 'P');
                            $present_count++;
                        } elseif ($att['absent'] == 1) {
                            $worksheet->setCellValue($col . $row_num, 'A');
                            $abs_count++;
                        } elseif ($att['tardy'] == 1) {
                            $worksheet->setCellValue($col . $row_num, 'L');
                            $tardy_count++;
                        }
                        
                        // Debug output
                        error_log("Date: " . $date_obj->format('Y-m-d') . 
                                " | Day: $day_name" . 
                                " | Week: $week" . 
                                " | Column: $col" . 
                                " | Status: " . ($att['present'] ? 'P' : ($att['absent'] ? 'A' : 'L')));
                    }
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

    // Set headers for Excel file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="SF2_' . $month . '.xlsx"');
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