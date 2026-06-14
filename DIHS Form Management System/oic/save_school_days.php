<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has OIC role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'OIC') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['changes']) || !is_array($data['changes'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO school_days (date, is_school_day, description) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           is_school_day = VALUES(is_school_day), 
                           description = VALUES(description)");
    
    $success = true;
    $message = '';
    
    foreach ($data['changes'] as $change) {
        $date = $change['date'];
        $is_school_day = (int)$change['is_school_day'];
        $description = $change['description'];
        
        $stmt->bind_param('sis', $date, $is_school_day, $description);
        if (!$stmt->execute()) {
            $success = false;
            $message = 'Error updating record: ' . $stmt->error;
            break;
        }
    }
    
    if ($success) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'School days updated successfully']);
    } else {
        $conn->rollback();
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => $message]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
