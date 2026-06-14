<?php
// check_grade.php
include '../db_connect.php';

// Query to check the Media and Information Literacy grade
$query = "
    SELECT 
        lrn, 
        subject_code, 
        subject_name, 
        quarter, 
        grade, 
        last_updated, 
        is_failed,
        NOW() as `current_time`,
        TIMESTAMPDIFF(MINUTE, last_updated, NOW()) as minutes_since_update
    FROM 
        student_grades 
    WHERE 
        subject_name LIKE '%Media and Information Literacy%'
        AND quarter = 1
    ORDER BY 
        last_updated DESC
    LIMIT 1
";

// Debug: Output the query for reference
error_log("Executing query: " . str_replace(["\n", "  "], " ", $query));

$result = $conn->query($query);

if ($result === false) {
    die("Query failed: " . $conn->error);
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<h2>Grade Information:</h2>";
    echo "<pre>";
    echo "Subject: " . htmlspecialchars($row['subject_name']) . "\n";
    echo "Quarter: " . $row['quarter'] . "\n";
    echo "Current Grade: " . $row['grade'] . "\n";
    echo "Is Failed: " . ($row['is_failed'] ? 'Yes' : 'No') . "\n";
    echo "Last Updated: " . $row['last_updated'] . "\n";
    echo "Current Time: " . $row['current_time'] . "\n";
    echo "Minutes Since Update: " . $row['minutes_since_update'] . "\n";
    echo "</pre>";
    
    // Check if the grade is locked
    $is_locked = ($row['minutes_since_update'] > 5);
    echo "<h3>Status: " . ($is_locked ? "🔒 Locked" : "🔓 Can be updated") . "</h3>";
    if ($is_locked) {
        echo "<p>This grade was last updated more than 5 minutes ago and cannot be modified.</p>";
    }
} else {
    echo "No grade found for Media and Information Literacy (Q1)";
}

$conn->close();
?>