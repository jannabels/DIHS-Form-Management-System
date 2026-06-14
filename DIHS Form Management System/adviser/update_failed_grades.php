<?php
// Include the database connection file
include '../db_connect.php';

// SQL query to update the is_failed flag for first-semester DRRM grades
$query = "UPDATE student_grades 
          SET is_failed = 1 
          WHERE lrn = '107915130190' 
          AND subject_code LIKE '%drrm%' 
          AND semester = '1'";

// Execute the query
if ($conn->query($query) === TRUE) {
    echo "Successfully updated " . $conn->affected_rows . " row(s).";
} else {
    echo "Error updating record: " . $conn->error;
}

// Close the connection
$conn->close();