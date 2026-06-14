<?php
echo "Testing database connection...\n";

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admindihs";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected to database successfully.\n";

// Test query to check if we can read from the sf1 table
echo "Testing read access to sf1 table...\n";
$result = $conn->query("SELECT COUNT(*) as count FROM sf1");
if ($result === false) {
    echo "Error reading from sf1 table: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "sf1 table contains " . $row['count'] . " records.\n";
}

// Test insert to check write permissions
echo "Testing write access to sf1 table...\n";
$testLrn = 'TEST' . time();
$testName = 'Test User';
$testSex = 'M';

$stmt = $conn->prepare("INSERT INTO sf1 (LRN, Name, Sex, sy) VALUES (?, ?, ?, '2024-2025')");
if ($stmt === false) {
    echo "Prepare failed: " . $conn->error . "\n";
} else {
    $stmt->bind_param("sss", $testLrn, $testName, $testSex);
    if ($stmt->execute()) {
        echo "Successfully inserted test record with LRN: $testLrn\n";
        
        // Clean up: delete the test record
        $conn->query("DELETE FROM sf1 WHERE LRN = '$testLrn'");
        echo "Cleaned up test record.\n";
    } else {
        echo "Insert failed: " . $stmt->error . "\n";
    }
    $stmt->close();
}

// Test file permissions
echo "Testing file permissions...\n";
$testFile = __DIR__ . '/test_permissions.txt';
if (file_put_contents($testFile, 'test') === false) {
    echo "Failed to write to test file. Check directory permissions.\n";
} else {
    echo "Successfully wrote to test file.\n";
    unlink($testFile);
}

// Test log file permissions
$logFile = __DIR__ . '/import_debug.log';
if (file_put_contents($logFile, 'Test log entry\n', FILE_APPEND) === false) {
    echo "Failed to write to log file. Check file permissions.\n";
} else {
    echo "Successfully wrote to log file.\n";
}

$conn->close();
echo "Tests completed.\n";
?>
