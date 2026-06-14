<?php
include '../db_connect.php';

// Use backticks for column names with spaces and correct case
$result = mysqli_query($conn, "SELECT `Username`, `First Name`, `Last Name` FROM accounts WHERE `Role`='Adviser' AND `Status`='active'");

$advisers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $advisers[] = [
        'id' => $row['Username'],
        'name' => $row['First Name'] . ' ' . $row['Last Name']
    ];
}

header('Content-Type: application/json');
echo json_encode($advisers);
