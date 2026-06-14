<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
error_reporting(E_ALL);
header('Content-Type: application/json');
include '../db_connect.php';

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    ob_end_flush();
    exit;
}

$usernames = isset($_POST['username']) ? $_POST['username'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$bulk = isset($_POST['bulk']) ? $_POST['bulk'] : '';

if (!$usernames || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    ob_end_flush();
    exit;
}

if ($bulk) {
    // Bulk update
    $userArr = explode(',', $usernames);
    $userArr = array_map('trim', $userArr);
    $userArr = array_filter($userArr, fn($u) => $u !== '');
    if (count($userArr) === 0) {
        echo json_encode(['success' => false, 'error' => 'No users selected']);
        ob_end_flush();
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($userArr), '?'));
    $sql = "UPDATE accounts SET `Status`=? WHERE `Username` IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        ob_end_flush();
        exit;
    }
    // Prepare types and params for binding
    $types = str_repeat('s', count($userArr) + 1); // +1 for status
    $params = array_merge([$status], $userArr);
    $bind_names = [];
    $bind_names[] = $types;
    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    $success = $stmt->execute();
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    ob_end_flush();
    exit;
} else {
    // Single update
    $sql = "UPDATE accounts SET `Status`=? WHERE `Username`=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        ob_end_flush();
        exit;
    }
    $stmt->bind_param('ss', $status, $usernames);
    $success = $stmt->execute();
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    ob_end_flush();
    exit;
}
?>