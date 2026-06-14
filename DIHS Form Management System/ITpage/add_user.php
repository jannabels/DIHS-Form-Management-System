<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check if all required fields are provided
    if ($fname && $lname && $username && $email && $role && $department && $status && $password) {
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT Username FROM accounts WHERE Username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO accounts (`First Name`, `Last Name`, `Username`, `Email`, `Role`, `Department`, `Status`, `Phone`, `Password`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $fname, $lname, $username, $email, $role, $department, $status, $phone, $password);
        
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
