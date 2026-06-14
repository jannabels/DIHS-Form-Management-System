<?php
include '../db_connect.php';

// Define base URL for redirects
define('BASE_URL', '/systemngdihs/guidance/');

// Check database connection
if (!$conn) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database connection failed');
}

// Initialize error array
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture and sanitize form data
    $classname = trim($_POST['classname'] ?? '');
    $track = trim($_POST['track'] ?? '');
    $adviser_id = trim($_POST['adviser_id'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    // Validate inputs
    if (empty($classname)) {
        $errors[] = 'Class name is required';
    }
    if (empty($track)) {
        $errors[] = 'Track is required';
    }
    if (empty($adviser_id)) {
        $errors[] = 'Adviser is required';
    }
    if (empty($grade_level)) {
        $errors[] = 'Grade level is required';
    }
    if (empty($semester)) {
        $errors[] = 'Semester is required';
    }

    // Validate track against allowed values
    $allowed_tracks = ['STEM', 'HUMSS', 'ABM', 'AS', 'EIM', 'CBM', 'AFA'];
    if (!in_array($track, $allowed_tracks)) {
        $errors[] = 'Invalid track selected';
    }

    // Validate grade_level against allowed values
    $allowed_grades = ['11', '12'];
    if (!in_array($grade_level, $allowed_grades)) {
        $errors[] = 'Invalid grade level selected';
    }

    // Validate semester against allowed values
    $allowed_semesters = ['1st Semester', '2nd Semester'];
    if (!in_array($semester, $allowed_semesters)) {
        $errors[] = 'Invalid semester selected';
    }

    // Validate adviser_id exists and is not already assigned
    if (!empty($adviser_id)) {
        $query = "SELECT id FROM accounts WHERE id = ? AND `Role` = 'Adviser' AND is_set != 1";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            $errors[] = 'Failed to prepare adviser query: ' . $conn->error;
            error_log('Adviser query preparation failed: ' . $conn->error);
        } else {
            $stmt->bind_param('i', $adviser_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $errors[] = 'Invalid or already assigned adviser';
            }
            $stmt->close();
        }
    }

    // If no errors, proceed to save
    if (empty($errors)) {
        // Start transaction to ensure data consistency
        $conn->begin_transaction();

        try {
            // Insert into section table (store adviser_id in adviser column)
            $query = "INSERT INTO section (class_name, adviser, track, grade_level, semester) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception('Failed to prepare insert query: ' . $conn->error);
            }
            $stmt->bind_param('sssss', $classname, $adviser_id, $track, $grade_level, $semester);
            $stmt->execute();
            $stmt->close();

            // Update adviser's is_set status
            $query = "UPDATE accounts SET is_set = 1 WHERE id = ?";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception('Failed to prepare update query: ' . $conn->error);
            }
            $stmt->bind_param('i', $adviser_id);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();

            // Redirect to create_class.php with success flag
            header('Location: ' . BASE_URL . 'createclass.php?success=1');
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = 'Failed to save class: ' . $e->getMessage();
            error_log('Save class failed: ' . $e->getMessage());
        }
    }

    // If errors, redirect back with error messages
    if (!empty($errors)) {
        session_start();
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve form data
        header('Location: ' . BASE_URL . 'createclass.php');
        exit;
    }
} else {
    // Redirect if not a POST request
    header('Location: ' . BASE_URL . 'createclass.php');
    exit;
}

$conn->close();
?>