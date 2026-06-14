<?php
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

    // Fetch students from sf1
    $sql_students = "SELECT LRN, Name, Sex FROM sf1";
    $result_students = mysqli_query($conn, $sql_students);
    if (!$result_students) {
        throw new Exception('Student query failed: ' . mysqli_error($conn));
    }

    $males = [];
    $females = [];
    while ($row = mysqli_fetch_assoc($result_students)) {
        if ($row['Sex'] === 'M') {
            $males[] = $row;
        } elseif ($row['Sex'] === 'F') {
            $females[] = $row;
        }
    }

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
    $col_map = [
        1 => [
            'Monday' => 'F',
            'Tuesday' => 'H',
            'Wednesday' => 'I',
            'Thursday' => 'J',
            'Friday' => 'K',
            'Saturday' => 'L',
        ],
        2 => [
            'Monday' => 'M',
            'Tuesday' => 'O',
            'Wednesday' => 'P',
            'Thursday' => 'Q',
            'Friday' => 'R',
            'Saturday' => 'S',
        ],
        3 => [
            'Monday' => 'T',
            'Tuesday' => 'V',
            'Wednesday' => 'W',
            'Thursday' => 'X',
            'Friday' => 'Z',
            'Saturday' => 'AB',
        ],
        4 => [
            'Monday' => 'AC',
            'Tuesday' => 'AE',
            'Wednesday' => 'AF',
            'Thursday' => 'AG',
            'Friday' => 'AH',
            'Saturday' => 'AI',
        ],
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
    $sql_att = "SELECT * FROM daily_attendance WHERE month = '" . mysqli_real_escape_string($conn, $month) . "'";
    $result_att = mysqli_query($conn, $sql_att);
    if (!$result_att) {
        throw new Exception('Attendance query failed: ' . mysqli_error($conn));
    }

    $att_by_lrn = [];
    while ($row = mysqli_fetch_assoc($result_att)) {
        $att_by_lrn[$row['LRN']][] = $row;
    }

    // Process attendance for each student
    foreach ($student_rows as $lrn => $row_num) {
        $abs_count = 0;
        $tardy_count = 0;

        // Initialize all attendance cells to empty
        foreach ($col_map as $week => $days) {
            foreach ($days as $day => $col) {
                $worksheet->setCellValue($col . $row_num, '');
            }
        }

        if (isset($att_by_lrn[$lrn])) {
            foreach ($att_by_lrn[$lrn] as $att) {
                // Parse date
                $date_obj = DateTime::createFromFormat('F j, Y', $att['date']);
                if ($date_obj) {
                    $day_num = (int)$date_obj->format('j');
                    $week = ceil($day_num / 7);
                    $day_name = $date_obj->format('l');

                    if (isset($col_map[$week][$day_name])) {
                        $col = $col_map[$week][$day_name];
                        if ($att['present'] === '1') {
                            $worksheet->setCellValue($col . $row_num, '1');
                        } elseif ($att['absent'] === '1' || $att['tardy'] === '1') {
                            $worksheet->setCellValue($col . $row_num, '0');
                        }
                    }
                }

                if ($att['absent'] === '1') {
                    $abs_count++;
                }
                if ($att['tardy'] === '1') {
                    $tardy_count++;
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