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
    'testLrn' => $testLrn,
    'table_info' => []
];

try {
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }
    $response['messages'][] = 'Database connection successful';
    
    // Get table structure
    $result = $conn->query("DESCRIBE `sf1`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['table_info']['structure'][] = $row;
        }
    }
    
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
    
    // Try to get the inserted record using different methods
    $response['verification'] = [];
    
    // Method 1: Direct query with LRN
    $result = $conn->query("SELECT * FROM `sf1` WHERE `LRN` = '$testLrn'");
    $response['verification']['direct_query'] = [
        'num_rows' => $result->num_rows,
        'record' => $result->fetch_assoc()
    ];
    
    // Method 2: Using LAST_INSERT_ID()
    $result = $conn->query("SELECT * FROM `sf1` WHERE `LRN` = LAST_INSERT_ID()");
    $response['verification']['last_insert_id'] = [
        'num_rows' => $result->num_rows,
        'record' => $result->fetch_assoc(),
        'last_insert_id' => $conn->insert_id
    ];
    
    // Method 3: Get all records with the test LRN pattern
    $result = $conn->query("SELECT * FROM `sf1` WHERE `LRN` LIKE 'TEST%' ORDER BY `LRN` DESC LIMIT 5");
    $response['verification']['recent_test_records'] = [];
    while ($row = $result->fetch_assoc()) {
        $response['verification']['recent_test_records'][] = $row;
    }
    
    // Check if any verification method found the record
    $recordFound = false;
    foreach ($response['verification'] as $method) {
        if (isset($method['num_rows']) && $method['num_rows'] > 0) {
            $recordFound = true;
            break;
        }
        if (isset($method[0]) && !empty($method[0])) {
            $recordFound = true;
            break;
        }
    }
    
    if (!$recordFound) {
        throw new Exception('Failed to verify inserted record using any method');
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
