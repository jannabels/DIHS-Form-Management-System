<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Set JSON header
header('Content-Type: application/json');

// Check if LRN is provided
if (!isset($_GET['lrn']) || empty(trim($_GET['lrn']))) {
    http_response_code(400);
    echo json_encode(['error' => 'LRN is required']);
    exit();
}

$lrn = trim($_GET['lrn']);

// Validate LRN format (12 digits)
if (!preg_match('/^\d{12}$/', $lrn)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid LRN format']);
    exit();
}

try {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "admindihs";

    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Check if LRN exists
    $stmt = $conn->prepare("SELECT LRN FROM sf1 WHERE LRN = ?");
    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exists = $result->num_rows > 0;
    
    $stmt->close();
    $conn->close();
    
    // Return the result
    echo json_encode([
        'exists' => $exists,
        'lrn' => $lrn
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error in check_lrn.php: ' . $e->getMessage());
    echo json_encode([
        'error' => 'An error occurred while checking LRN',
        'debug' => $e->getMessage()
    ]);
}
