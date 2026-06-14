<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Include database connection
require_once 'db_connect.php';

// Test connection
if ($conn->connect_error) {
    die("<p style='color:red'>❌ Connection failed: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green'>✓ Connected to database successfully</p>";

// Test querying the section table
echo "<h3>Section Table Structure:</h3>";
$result = $conn->query("SHOW COLUMNS FROM section");
if ($result) {
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . (isset($row['Default']) ? htmlspecialchars($row['Default']) : 'NULL') . "</td>";
        echo "<td>" . (isset($row['Extra']) ? htmlspecialchars($row['Extra']) : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    $result->free();
} else {
    echo "<p style='color:red'>❌ Error showing section table: " . $conn->error . "</p>";
}

// Test querying the accounts table
echo "<h3>Active Advisers:</h3>";
$sql = "SELECT id, `First Name`, `Last Name`, `Role`, `Status` 
        FROM accounts 
        WHERE LOWER(`Role`) = 'adviser' 
        AND `Status` = 'active' 
        LIMIT 5";
        
$result = $conn->query($sql);
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Role</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['First Name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Last Name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Role']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No active advisers found.</p>";
    }
    $result->free();
} else {
    echo "<p style='color:red'>❌ Error querying accounts: " . $conn->error . "</p>";
}

// Test inserting a sample section
echo "<h3>Test Insert into Section:</h3>";
$test_class = 'TEST' . time();
$test_adviser = 'Test Adviser';
$test_track = 'STEM';
$test_grade = 'Grade 11';
$test_semester = '1st Semester';

$sql = "INSERT INTO section (class_name, adviser, track, grade_level, semester) 
        VALUES (?, ?, ?, ?, ?)";
        
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('sssss', $test_class, $test_adviser, $test_track, $test_grade, $test_semester);
    if ($stmt->execute()) {
        echo "<p style='color:green'>✓ Successfully inserted test record</p>";
        // Delete the test record
        $conn->query("DELETE FROM section WHERE class_name = '" . $conn->real_escape_string($test_class) . "'");
    } else {
        echo "<p style='color:red'>❌ Error inserting test record: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color:red'>❌ Error preparing test insert: " . $conn->error . "</p>";
}

// Close connection
$conn->close();

echo "<h3>Test Complete</h3>";
?>
