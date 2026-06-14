<?php
// Include database connection
require_once '../db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Test data
$testLrn = 'TEST' . time();
$testName = 'Test User';
$testSex = 'M';
$testSy = '2024-2025';

$response = [
    'success' => false,
    'messages' => [],
    'testLrn' => $testLrn
];

try {
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }
    $response['messages'][] = 'Database connection successful';
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'sf1'");
    if ($result->num_rows === 0) {
        throw new Exception('Table sf1 does not exist');
    }
    $response['messages'][] = 'Table sf1 exists';
    
    // Begin transaction
    $conn->begin_transaction();
    $response['messages'][] = 'Transaction started';
    
    // Prepare insert statement
    $sql = "INSERT INTO `sf1` (`LRN`, `Name`, `Sex`, `sy`) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $response['messages'][] = 'Prepare successful';
    
    // Bind parameters
    $stmt->bind_param('ssss', $testLrn, $testName, $testSex, $testSy);
    $response['messages'][] = 'Parameters bound';
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    $response['messages'][] = 'Execute successful';
    
    // Get the inserted record
    $result = $conn->query("SELECT * FROM `sf1` WHERE `LRN` = '$testLrn'");
    $insertedRecord = $result->fetch_assoc();
    
    if (!$insertedRecord) {
        throw new Exception('Failed to verify inserted record');
    }
    $response['inserted_record'] = $insertedRecord;
    
    // Rollback to avoid leaving test data
    $conn->rollback();
    $response['messages'][] = 'Test successful - transaction rolled back';
    
    $response['success'] = true;
    $response['message'] = 'Test completed successfully';
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn) {
        $conn->rollback();
    }
    
    $response['error'] = $e->getMessage();
    $response['error_code'] = $e->getCode();
    
    // Add more debug info
    if (isset($stmt)) {
        $response['stmt_error'] = $stmt->error;
    }
    if (isset($conn)) {
        $response['conn_error'] = $conn->error;
    }
}

// Close connection
if (isset($conn)) {
    $conn->close();
}

// Output the response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
