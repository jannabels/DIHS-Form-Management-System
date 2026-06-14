<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<h2>Database Connection Successful!</h2>";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'sf1'");
if ($tableCheck->num_rows > 0) {
    echo "<p>✅ Table 'sf1' exists.</p>";
    
    // Get table structure
    $result = $conn->query("DESCRIBE sf1");
    if ($result) {
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check if table has data
    $count = $conn->query("SELECT COUNT(*) as count FROM sf1");
    if ($count) {
        $row = $count->fetch_assoc();
        echo "<p>📊 Table has " . $row['count'] . " records.</p>";
        
        if ($row['count'] > 0) {
            // Show first 5 records
            $data = $conn->query("SELECT * FROM sf1 LIMIT 5");
            if ($data) {
                echo "<h3>Sample Data (first 5 records):</h3>";
                echo "<table border='1' cellpadding='5'>";
                // Header
                echo "<tr>";
                while ($field = $data->fetch_field()) {
                    echo "<th>" . htmlspecialchars($field->name) . "</th>";
                }
                echo "</tr>";
                // Data
                while($row = $data->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
} else {
    echo "<p>❌ Table 'sf1' does not exist.</p>";
}

// Close connection
$conn->close();
?>
