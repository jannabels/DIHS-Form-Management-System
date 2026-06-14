<?php
// Include database connection
include '../db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Test data
$testLrn = 'TEST' . time();
$testName = 'Test User';
$testSex = 'M';
$testSy = '2024-2025';
$testSectionId = 1; // Make sure this section ID exists in your database

// Test insert into sf1 table
try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Insert into sf1
    $stmt1 = $conn->prepare("INSERT INTO sf1 (LRN, Name, Sex, sy) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param('ssss', $testLrn, $testName, $testSex, $testSy);
    
    if (!$stmt1->execute()) {
        throw new Exception('Failed to insert into sf1: ' . $stmt1->error);
    }
    
    // Insert into student_section
    $stmt2 = $conn->prepare("INSERT INTO student_section (lrn, section_id, sy) VALUES (?, ?, ?)");
    $stmt2->bind_param('sis', $testLrn, $testSectionId, $testSy);
    
    if (!$stmt2->execute()) {
        throw new Exception('Failed to insert into student_section: ' . $stmt2->error);
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Verify the data was inserted
    $result = $conn->query("SELECT * FROM sf1 WHERE LRN = '$testLrn'");
    $student = $result->fetch_assoc();
    
    // Clean up - remove test data
    $conn->query("DELETE FROM student_section WHERE lrn = '$testLrn'");
    $conn->query("DELETE FROM sf1 WHERE LRN = '$testLrn'");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Test data inserted and verified successfully',
        'testLrn' => $testLrn,
        'student' => $student
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'testLrn' => $testLrn
    ]);
}

$conn->close();
?>
