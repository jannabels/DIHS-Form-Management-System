<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/db_connect.php';

header('Content-Type: text/plain');

echo "Testing database connection...\n";

// Test connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✓ Connected to database successfully\n";

// Test querying the section table
echo "\nTesting section table...\n";
$result = $conn->query("SHOW COLUMNS FROM section");
if ($result) {
    echo "✓ Section table exists with columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    $result->free();
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test querying the accounts table
echo "\nTesting accounts table...\n";
$result = $conn->query("SHOW COLUMNS FROM accounts");
if ($result) {
    echo "✓ Accounts table exists with columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    $result->free();
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test inserting a test record (will be rolled back)
echo "\nTesting insert into section table...\n";
$conn->begin_transaction();
$test_class = 'TEST' . time();
$sql = "INSERT INTO section (class_name, adviser, track, grade_level, semester) 
        VALUES (?, 'Test Adviser', 'STEM', 'Grade 11', '1st Semester')";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param('s', $test_class);
    if ($stmt->execute()) {
        echo "✓ Successfully inserted test record\n";
    } else {
        echo "Error inserting test record: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error . "\n";
}

// Rollback the test transaction
$conn->rollback();

$conn->close();

echo "\nTest complete. No changes were made to the database.\n";
?>
