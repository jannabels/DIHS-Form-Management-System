
Folder highlights
PHP application files for school administration, including SF1, SF2, SF9, and SF10 exporting functionality.

<?php
include '../db_connect.php';

if (isset($_POST['attendances'])) {
    $attendances = json_decode($_POST['attendances'], true);
    $conn->begin_transaction();
    
    try {
        $current_year = date('Y');
        
        foreach ($attendances as $attendance) {
            $lrn = mysqli_real_escape_string($conn, $attendance['lrn']);
            $month = mysqli_real_escape_string($conn, $attendance['month']);
            $date = mysqli_real_escape_string($conn, $attendance['date']);
            $present = (int)$attendance['present'];
            $absent = (int)$attendance['absent'];
            $tardy = (int)$attendance['tardy'];
            
            // Get month number from month name
            $month_number = date('n', strtotime($month . ' 1, ' . $current_year));
            
            // 1. Update daily_attendance table
            $sql_check = "SELECT id FROM admindihs.daily_attendance WHERE LRN = '$lrn' AND date = '$date'";
            $result = mysqli_query($conn, $sql_check);
            
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $id = $row['id'];
                $sql = "UPDATE admindihs.daily_attendance 
                        SET month = '$month', 
                            present = '$present', 
                            absent = '$absent', 
                            tardy = '$tardy' 
                        WHERE id = $id";
                mysqli_query($conn, $sql);
            } else {
                $sql = "INSERT INTO admindihs.daily_attendance (LRN, month, date, present, absent, tardy) 
                        VALUES ('$lrn', '$month', '$date', '$present', '$absent', '$tardy')";
                mysqli_query($conn, $sql);
            }
            
            // 2. Recalculate monthly totals based on all daily records for this student and month
            $recalc_sql = "SELECT 
                            SUM(CAST(present AS UNSIGNED)) as total_present,
                            SUM(CAST(absent AS UNSIGNED)) as total_absent,
                            SUM(CAST(tardy AS UNSIGNED)) as total_tardy
                          FROM admindihs.daily_attendance 
                          WHERE LRN = '$lrn' 
                          AND month = '$month'";
            
            $recalc_result = mysqli_query($conn, $recalc_sql);
            $totals = mysqli_fetch_assoc($recalc_result);
            
            $total_present = (int)$totals['total_present'];
            $total_absent = (int)$totals['total_absent'];
            $total_tardy = (int)$totals['total_tardy'];
            
            // Check if monthly record exists
            $check_sql = "SELECT id FROM admindihs.monthly_attendance_summary 
                         WHERE LRN = '$lrn' 
                         AND year = $current_year 
                         AND month = $month_number";
            $result = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($result) > 0) {
                // Update existing record with recalculated totals
                $update_sql = "UPDATE admindihs.monthly_attendance_summary 
                              SET present_days = $total_present,
                                  absent_days = $total_absent,
                                  tardy_days = $total_tardy,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE LRN = '$lrn' 
                              AND year = $current_year 
                              AND month = $month_number";
                mysqli_query($conn, $update_sql);
            } else {
                // Insert new record with calculated totals
                $insert_sql = "INSERT INTO admindihs.monthly_attendance_summary 
                              (LRN, year, month, present_days, absent_days, tardy_days)
                              VALUES ('$lrn', $current_year, $month_number, 
                                     $total_present, $total_absent, $total_tardy)";
                mysqli_query($conn, $insert_sql);
            }
        }
        
        $conn->commit();
        echo 'Success';
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving attendance: " . $e->getMessage());
        http_response_code(500);
        echo 'Error: ' . $e->getMessage();
    }
} else {
    http_response_code(400);
    echo 'Error: No attendance data received';
}

mysqli_close($conn);