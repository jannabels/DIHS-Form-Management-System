<?php
// Set error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Include database connection
require_once '../db_connect.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'adviser' => '',
    'error' => ''
];

try {
    // Check if section is provided
    if (!isset($_POST['section']) || empty(trim($_POST['section']))) {
        throw new Exception('Section name is required');
    }

    $section = trim($_POST['section']);
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset('utf8mb4');
    
    // Prepare and execute query to get adviser for the section
    $stmt = $conn->prepare("SELECT adviser FROM section WHERE class_name = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $section);
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $adviser = $row['adviser'];
        
        // If adviser is an ID, fetch the actual name from accounts table
        if (is_numeric($adviser)) {
            $userStmt = $conn->prepare("
                SELECT CONCAT(`First Name`, ' ', `Last Name`) as adviser_name 
                FROM accounts 
                WHERE id = ?
            ") or die($conn->error);
            
            $userStmt->bind_param('i', $adviser);
            if ($userStmt->execute()) {
                $userResult = $userStmt->get_result();
                if ($userResult->num_rows > 0) {
                    $userRow = $userResult->fetch_assoc();
                    $adviser = $userRow['adviser_name'];
                }
            }
            $userStmt->close();
        }
        
        $response['success'] = true;
        $response['adviser'] = $adviser ?: 'No Adviser Assigned';
    } else {
        $response['success'] = true;
        $response['adviser'] = 'No Adviser Assigned';
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code(500);
}

// Return JSON response
echo json_encode($response);
?>
