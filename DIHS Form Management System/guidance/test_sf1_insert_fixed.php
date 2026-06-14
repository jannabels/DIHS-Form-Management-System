<?php
// Include database connection
require_once '../db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Test data with LRN limited to 12 characters
$testLrn = 'T' . substr(time(), -11); // This will be 12 characters max
$testName = 'Test User';
$testSex = 'M';
$testSy = '2024-2025';

$response = [
    'success' => false,
    'messages' => [],
    'testLrn' => $testLrn,
    'testLrn_length' => strlen($testLrn),
    'table_info' => []
];

try {
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }
    $response['messages'][] = 'Database connection successful';
    
    // Get current record count
    $result = $conn->query("SELECT COUNT(*) as count FROM `sf1`");
    $response['table_info']['record_count_before'] = $result->fetch_assoc()['count'];
    
    // Begin transaction
    $conn->begin_transaction();
    $response['messages'][] = 'Transaction started';
    
    // Prepare insert statement
    $sql = "INSERT INTO `sf1` (`LRN`, `Name`, `Sex`, `sy`) VALUES (?, ?, ?, ?)";
    $response['sql'] = $sql;
    $response['params'] = [$testLrn, $testName, $testSex, $testSy];
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $response['messages'][] = 'Prepare successful';
    
    // Bind parameters
    $stmt->bind_param('ssss', $testLrn, $testName, $testSex, $testSy);
    $response['messages'][] = 'Parameters bound';
    
    // Execute the statement
    $execResult = $stmt->execute();
    if ($execResult === false) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    $response['messages'][] = 'Execute successful';
    $response['affected_rows'] = $stmt->affected_rows;
    $response['insert_id'] = $stmt->insert_id;
    
    // Get the inserted record
    $result = $conn->query("SELECT * FROM `sf1` WHERE `LRN` = '$testLrn'");
    $insertedRecord = $result->fetch_assoc();
    
    if (!$insertedRecord) {
        throw new Exception('Failed to verify inserted record');
    }
    $response['inserted_record'] = $insertedRecord;
    
    // Get the most recent records to see what's in the table
    $result = $conn->query("SELECT * FROM `sf1` ORDER BY `created_at` DESC LIMIT 5");
    $response['recent_records'] = [];
    while ($row = $result->fetch_assoc()) {
        $response['recent_records'][] = [
            'LRN' => $row['LRN'],
            'Name' => $row['Name'],
            'created_at' => $row['created_at'],
            'LRN_length' => strlen($row['LRN'])
        ];
    }
    
    // Rollback to avoid leaving test data
    $conn->rollback();
    $response['messages'][] = 'Test successful - transaction rolled back';
    
    // Get record count after rollback
    $result = $conn->query("SELECT COUNT(*) as count FROM `sf1`");
    $response['table_info']['record_count_after'] = $result->fetch_assoc()['count'];
    
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
    
    // Try to get any MySQL warnings
    if (isset($conn)) {
        $warnings = $conn->query("SHOW WARNINGS");
        if ($warnings && $warnings->num_rows > 0) {
            $response['warnings'] = [];
            while ($warning = $warnings->fetch_assoc()) {
                $response['warnings'][] = $warning;
            }
        }
    }
}

// Close connection
if (isset($conn)) {
    $conn->close();
}

// Output the response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
