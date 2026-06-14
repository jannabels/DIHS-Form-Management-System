<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $conn->begin_transaction();
    
    switch ($_POST['action']) {
        case 'graduate':
            if (!isset($_POST['lrn']) || !isset($_POST['graduation_date']) || !isset($_POST['school_year'])) {
                throw new Exception('Missing required fields');
            }
            
            $lrn = $_POST['lrn'];
            $graduation_date = $_POST['graduation_date'];
            $school_year = $_POST['school_year'];
            
            // Check if already graduated
            $check = $conn->prepare("SELECT id FROM graduated_students WHERE lrn = ?");
            $check->bind_param('s', $lrn);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                throw new Exception('This student is already marked as graduated');
            }
            
            // Add to graduated students
            $stmt = $conn->prepare("
                INSERT INTO graduated_students (lrn, graduation_date, school_year) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param('sss', $lrn, $graduation_date, $school_year);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to mark student as graduated');
            }
            
            // Update student status
            $update = $conn->prepare("UPDATE sf1 SET status = 'graduated', archived_at = NOW() WHERE LRN = ?");
            $update->bind_param('s', $lrn);
            
            if (!$update->execute()) {
                throw new Exception('Failed to update student status');
            }
            
            $response['success'] = true;
            $response['message'] = 'Student successfully marked as graduated';
            break;
            
        case 'unarchive':
            if (!isset($_POST['lrn'])) {
                throw new Exception('LRN is required');
            }
            
            $lrn = $_POST['lrn'];
            
            // Remove from graduated students
            $delete = $conn->prepare("DELETE FROM graduated_students WHERE lrn = ?");
            $delete->bind_param('s', $lrn);
            
            if (!$delete->execute()) {
                throw new Exception('Failed to unarchive student');
            }
            
            // Update student status back to active
            $update = $conn->prepare("UPDATE sf1 SET status = 'active', archived_at = NULL WHERE LRN = ?");
            $update->bind_param('s', $lrn);
            
            if (!$update->execute()) {
                throw new Exception('Failed to update student status');
            }
            
            $response['success'] = true;
            $response['message'] = 'Student successfully unarchived';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>