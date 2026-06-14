<?php
// Include database connection
include '../db_connect.php';

// Check if user is logged in and is an admin/registrar
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Registrar')) {
    die('Unauthorized access');
}

// Add is_completed column if it doesn't exist
$alter_query = "ALTER TABLE student_grades 
                ADD COLUMN IF NOT EXISTS is_completed TINYINT(1) DEFAULT 0 
                COMMENT 'Indicates if the student has completed this subject'";

if ($conn->query($alter_query) === TRUE) {
    echo "Successfully added is_completed column to student_grades table.\n";
    
    // Update is_completed based on final grades (if final_grade_12 or final_grade_34 >= 75)
    $update_query = "UPDATE student_grades 
                    SET is_completed = 1 
                    WHERE (final_grade_12 IS NOT NULL AND final_grade_12 >= 75) 
                    OR (final_grade_34 IS NOT NULL AND final_grade_34 >= 75)";
    
    if ($conn->query($update_query) === TRUE) {
        $affected_rows = $conn->affected_rows;
        echo "Successfully updated $affected_rows records with completed status based on final grades.\n";
    } else {
        echo "Error updating records: " . $conn->error . "\n";
    }
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();
?>
