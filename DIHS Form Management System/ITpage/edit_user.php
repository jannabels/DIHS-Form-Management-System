<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db_connect.php';


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($id && $fname && $lname && $username && $email && $role && $department && $status) {
        // If password is not empty, update it; otherwise, keep the old password
        if ($password !== '') {
            $stmt = $conn->prepare("UPDATE accounts SET `First Name`=?, `Last Name`=?, `Username`=?, `Email`=?, `Role`=?, `Department`=?, `Status`=?, `Phone`=?, `Password`=? WHERE `Username`=?");
            $stmt->bind_param("ssssssssss", $fname, $lname, $username, $email, $role, $department, $status, $phone, $password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET `First Name`=?, `Last Name`=?, `Username`=?, `Email`=?, `Role`=?, `Department`=?, `Status`=?, `Phone`=? WHERE `Username`=?");
            $stmt->bind_param("sssssssss", $fname, $lname, $username, $email, $role, $department, $status, $phone, $id);
        }
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => $success, 'error' => $error]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>