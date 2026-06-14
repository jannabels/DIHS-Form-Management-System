<?php
session_start();
require_once '../db_connect.php';

// Set JSON content type header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit();
}

// Check if LRN is provided
if (!isset($_POST['lrn']) || empty(trim($_POST['lrn']))) {
    $response['message'] = 'Student LRN is required';
    echo json_encode($response);
    exit();
}

$lrn = trim($_POST['lrn']);
$restore_remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : 'Student restored by ' . $_SESSION['username'];
$restored_by = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Check if student exists and is archived
    $check_sql = "SELECT * FROM sf1 WHERE LRN = ? AND status = 'archived' LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $lrn);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Student not found or not archived');
    }
    
    $student = $result->fetch_assoc();
    $check_stmt->close();
    
    // Update student status to active
    $update_sql = "UPDATE sf1 SET status = 'active', archived_at = NULL, last_updated = NOW() WHERE LRN = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('s', $lrn);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update student status');
    }
    $update_stmt->close();
    
    // Log the restoration
    $log_sql = "INSERT INTO student_status_history (lrn, previous_status, new_status, remarks, changed_by) 
                VALUES (?, 'archived', 'active', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param('ssi', $lrn, $restore_remarks, $restored_by);
    
    if (!$log_stmt->execute()) {
        throw new Exception('Failed to log restoration');
    }
    $log_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = 'Student restored successfully';
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

// Close connection
$conn->close();

// Return JSON response
echo json_encode($response);
?>
