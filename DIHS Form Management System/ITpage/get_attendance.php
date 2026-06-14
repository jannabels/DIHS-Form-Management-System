<?php
include '../db_connect.php';

$date = mysqli_real_escape_string($conn, $_GET['date']);

$sql = "SELECT LRN, present, absent, tardy FROM daily_attendance WHERE date = '$date'";

$result = mysqli_query($conn, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[$row['LRN']] = [
        'present' => $row['present'],
        'absent' => $row['absent'],
        'tardy' => $row['tardy']
    ];
}

echo json_encode($data);

mysqli_close($conn);
?>