<?php
// Start the session
session_start();

// Include database connection
include '../db_connect.php';

// Check if user is logged in and is a registrar
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Registrar') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if LRN is provided
if (!isset($_GET['lrn'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'LRN is required']);
    exit();
}

$lrn = mysqli_real_escape_string($conn, $_GET['lrn']);

// Query to get all subjects for the student with their completion status
$query = "
    SELECT 
        c.subject_code,
        c.subject_name,
        c.grade_level,
        c.semester,
        COALESCE(sg.is_completed, 0) as is_completed,
        sg.final_grade_12,
        sg.final_grade_34
    FROM 
        curriculum c
    LEFT JOIN 
        student_grades sg ON c.subject_code = sg.subject_code 
        AND sg.LRN = '$lrn'
        AND c.grade_level = sg.grade_level
        AND c.semester = sg.semester
    WHERE 
        c.grade_level IN ('Grade 11', 'Grade 12')
    ORDER BY 
        c.grade_level, c.semester, c.subject_name
";

$result = $conn->query($query);

if ($result) {
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
}

$conn->close();
?>
