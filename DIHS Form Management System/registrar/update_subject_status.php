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

// Check if required parameters are provided
if (!isset($_POST['lrn']) || !isset($_POST['subject_code']) || !isset($_POST['is_completed'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

// Sanitize input
$lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
$subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
$is_completed = intval($_POST['is_completed']);

// First, check if the record exists
$check_query = "SELECT id FROM student_grades WHERE LRN = ? AND subject_code = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ss", $lrn, $subject_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing record
    $update_query = "UPDATE student_grades SET is_completed = ? WHERE LRN = ? AND subject_code = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iss", $is_completed, $lrn, $subject_code);
} else {
    // Get subject details to insert a new record
    $subject_query = "SELECT grade_level, semester FROM curriculum WHERE subject_code = ? LIMIT 1";
    $stmt = $conn->prepare($subject_query);
    $stmt->bind_param("s", $subject_code);
    $stmt->execute();
    $subject_result = $stmt->get_result();
    
    if ($subject_result->num_rows > 0) {
        $subject = $subject_result->fetch_assoc();
        $grade_level = $subject['grade_level'];
        $semester = $subject['semester'];
        
        // Insert new record
        $insert_query = "INSERT INTO student_grades 
                        (LRN, subject_code, subject_name, grade_level, semester, is_completed) 
                        SELECT ?, c.subject_code, c.subject_name, c.grade_level, c.semester, ?
                        FROM curriculum c 
                        WHERE c.subject_code = ?";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sis", $lrn, $is_completed, $subject_code);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Subject not found'
        ]);
        exit();
    }
}

// Execute the prepared statement
if ($stmt->execute()) {
    echo json_encode([
        'success' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
}

$conn->close();
?>
