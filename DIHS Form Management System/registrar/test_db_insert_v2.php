<?php
// Include database connection
include '../db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Function to get table structure
function getTableStructure($conn, $table) {
    $result = $conn->query("DESCRIBE `$table`");
    $structure = [];
    while ($row = $result->fetch_assoc()) {
        $structure[] = $row;
    }
    return $structure;
}

// Test data
$testLrn = 'TEST' . time();
$testName = 'Test User';
$testSex = 'M';
$testSy = '2024-2025';
$testSectionId = 1; // Make sure this section ID exists in your database

$response = [
    'success' => false,
    'messages' => [],
    'testLrn' => $testLrn,
    'tables' => []
];

try {
    // Check if tables exist
    $response['tables']['sf1_exists'] = tableExists($conn, 'sf1');
    $response['tables']['student_section_exists'] = tableExists($conn, 'student_section');
    
    if (!$response['tables']['sf1_exists']) {
        throw new Exception('Table sf1 does not exist');
    }
    
    if (!$response['tables']['student_section_exists']) {
        throw new Exception('Table student_section does not exist');
    }
    
    // Get table structures
    $response['tables']['sf1_structure'] = getTableStructure($conn, 'sf1');
    $response['tables']['student_section_structure'] = getTableStructure($conn, 'student_section');
    
    // Begin transaction
    $conn->begin_transaction();
    $response['messages'][] = 'Transaction started';
    
    // Insert into sf1
    $sql1 = "INSERT INTO `sf1` (`LRN`, `Name`, `Sex`, `sy`) VALUES (?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    
    if ($stmt1 === false) {
        throw new Exception('Prepare failed for sf1: ' . $conn->error);
    }
    
    $stmt1->bind_param('ssss', $testLrn, $testName, $testSex, $testSy);
    $response['messages'][] = 'Prepared sf1 insert statement';
    
    if (!$stmt1->execute()) {
        throw new Exception('Execute failed for sf1: ' . $stmt1->error);
    }
    $response['messages'][] = 'Inserted into sf1 table';
    
    // Insert into student_section
    $sql2 = "INSERT INTO `student_section` (`lrn`, `section_id`, `sy`) VALUES (?, ?, ?)";
    $stmt2 = $conn->prepare($sql2);
    
    if ($stmt2 === false) {
        throw new Exception('Prepare failed for student_section: ' . $conn->error);
    }
    
    $stmt2->bind_param('sis', $testLrn, $testSectionId, $testSy);
    $response['messages'][] = 'Prepared student_section insert statement';
    
    if (!$stmt2->execute()) {
        throw new Exception('Execute failed for student_section: ' . $stmt2->error);
    }
    $response['messages'][] = 'Inserted into student_section table';
    
    // Verify the data was inserted
    $result = $conn->query("SELECT * FROM `sf1` WHERE `LRN` = '$testLrn'");
    $response['inserted_sf1'] = $result->fetch_assoc();
    
    $result = $conn->query("SELECT * FROM `student_section` WHERE `lrn` = '$testLrn'");
    $response['inserted_student_section'] = $result->fetch_assoc();
    
    // Commit the transaction
    $conn->commit();
    $response['messages'][] = 'Transaction committed';
    
    $response['success'] = true;
    $response['message'] = 'Test data inserted and verified successfully';
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if (isset($conn) && $conn) {
        $conn->rollback();
        $response['messages'][] = 'Transaction rolled back';
    }
    
    $response['error'] = $e->getMessage();
    $response['error_trace'] = $e->getTraceAsString();
    
    // Get last error if available
    if (isset($conn) && $conn) {
        $response['last_error'] = $conn->error;
    }
}

// Clean up - remove test data if they exist
if (isset($testLrn)) {
    $conn->query("DELETE FROM `student_section` WHERE `lrn` = '$testLrn'");
    $conn->query("DELETE FROM `sf1` WHERE `LRN` = '$testLrn'");
    $response['messages'][] = 'Cleaned up test data';
}

// Output the response
echo json_encode($response, JSON_PRETTY_PRINT);

// Close connection
if (isset($conn) && $conn) {
    $conn->close();
}
?>
