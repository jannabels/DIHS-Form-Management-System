<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'section' => null];

try {
    // Check if section_id is provided
    if (!isset($_GET['section_id']) || empty($_GET['section_id'])) {
        throw new Exception('Section ID is required');
    }

    $section_id = intval($_GET['section_id']);
    
    // Prepare and execute query to fetch section details
    $query = "SELECT section_id, class_name, grade_level, track, semester 
              FROM section 
              WHERE section_id = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $bind_result = $stmt->bind_param("i", $section_id);
    if ($bind_result === false) {
        throw new Exception('Bind param failed: ' . $stmt->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['success'] = true;
        $response['section'] = $result->fetch_assoc();
    } else {
        throw new Exception('Section not found');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Don't close the connection as it might be reused
// $conn->close();

// Output JSON response
echo json_encode($response);
?>

