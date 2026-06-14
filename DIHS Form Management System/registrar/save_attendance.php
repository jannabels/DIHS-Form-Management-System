<?php
include '../db_connect.php';

if (isset($_POST['attendances'])) {
    $attendances = json_decode($_POST['attendances'], true);
    
    foreach ($attendances as $attendance) {
        $lrn = mysqli_real_escape_string($conn, $attendance['lrn']);
        $month = mysqli_real_escape_string($conn, $attendance['month']);
        $date = mysqli_real_escape_string($conn, $attendance['date']);
        $present = mysqli_real_escape_string($conn, $attendance['present']);
        $absent = mysqli_real_escape_string($conn, $attendance['absent']);
        $tardy = mysqli_real_escape_string($conn, $attendance['tardy']);
        
        // Check if record exists
        $sql_check = "SELECT id FROM daily_attendance WHERE LRN = '$lrn' AND date = '$date'";
        $result = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $id = $row['id'];
            $sql = "UPDATE daily_attendance SET month = '$month', present = '$present', absent = '$absent', tardy = '$tardy' WHERE id = $id";
        } else {
            $sql = "INSERT INTO daily_attendance (LRN, month, date, present, absent, tardy) 
                    VALUES ('$lrn', '$month', '$date', '$present', '$absent', '$tardy')";
        }
        
        mysqli_query($conn, $sql);
    }
    
    echo 'Success';
} else {
    echo 'Error';
}

mysqli_close($conn);