<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start output buffering
ob_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => [],
    'debug' => []
];

try {
    // Include database connection
    require_once __DIR__ . '/../db_connect.php';
    
    // Test database connection
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed. ' . ($conn ? $conn->connect_error : 'No connection object'));
    }
    
    // Test if we can query the database
    $test = $conn->query('SELECT 1');
    if ($test === false) {
        throw new Exception('Database test query failed: ' . $conn->error);
    }
    $test->free();
    
    $response['debug']['db_connection'] = 'Database connection successful';

    try {
        // Check if request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Invalid request method. Expected POST, got ' . $_SERVER['REQUEST_METHOD']);
        }
        
        $response['debug']['request_method'] = $_SERVER['REQUEST_METHOD'];
        $response['debug']['post_data'] = $_POST;

        // Check database connection
        if (!$conn) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }

        // Capture and sanitize form data
        $classname = trim($_POST['classname'] ?? '');
        $track = trim($_POST['track'] ?? '');
        $adviser_id = (int)($_POST['adviser_id'] ?? 0);
        $grade_level = trim($_POST['grade_level'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $school_year = '2024-2025'; // You might want to make this dynamic

        // Validate inputs
        $errors = [];
        $response['debug']['input_classname'] = $classname;
        $response['debug']['input_track'] = $track;
        $response['debug']['input_grade'] = $grade_level;

        // Generate expected classname format if not provided
        if (empty($classname)) {
            if (!empty($track) && !empty($grade_level) && !empty($_POST['section_letter'] ?? '')) {
                $section_letter = strtoupper(trim($_POST['section_letter']));
                $grade_number = str_replace('Grade ', '', $grade_level);
                $classname = "$track $grade_number-$section_letter";
                $response['debug']['generated_classname'] = $classname;
            } else {
                $errors['classname'] = 'Section code is required';
            }
        }

        // Additional validation
        if (strlen($classname) > 50) {
            $errors['classname'] = 'Section code must be less than 50 characters';
        }

        $allowed_tracks = ['STEM', 'HUMSS', 'ABM', 'AS', 'EIM', 'CBM', 'AFA'];
        if (empty($track) || !in_array($track, $allowed_tracks)) {
            $errors['track'] = 'Please select a valid track';
        }

        if ($adviser_id <= 0) {
            $errors['adviser_id'] = 'Please select an adviser';
        }

        // Check if class already exists
        try {
            $check_class = $conn->prepare("SELECT section_id FROM `section` WHERE `class_name` = ?");
            if (!$check_class) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $check_class->bind_param("s", $classname);
            if (!$check_class->execute()) {
                throw new Exception('Execute failed: ' . $check_class->error);
            }
            $result = $check_class->get_result();
            if ($result->num_rows > 0) {
                $errors['classname'] = 'A class with this section code already exists';
                $response['success'] = false;
                $response['message'] = 'Class already exists';
                $response['errors'] = $errors;
                error_log('Class already exists: ' . $classname);
                sendJsonResponse($response);
            }
        } catch (Exception $e) {
            error_log('Error checking for existing class: ' . $e->getMessage());
            throw new Exception('Error checking for existing class: ' . $e->getMessage());
        }

        // Check if adviser exists and is available
        if ($adviser_id > 0 && empty($errors['adviser_id'])) {
            $check_adviser = $conn->prepare("
                SELECT id, `First Name`, `Last Name` 
                FROM accounts 
                WHERE id = ? AND LOWER(`Role`) = 'adviser' AND Status = 'active'
            ");
            $check_adviser->bind_param('i', $adviser_id);
            $check_adviser->execute();
            $adviser_result = $check_adviser->get_result();

            if ($adviser_result->num_rows === 0) {
                $errors['adviser_id'] = 'Selected adviser is not available';
            } else {
                $adviser = $adviser_result->fetch_assoc();
                $adviser_name = $adviser['First Name'] . ' ' . $adviser['Last Name'];
            }
            $check_adviser->close();

            // Check if adviser is already assigned to another class
            $check_assigned = $conn->prepare("
                SELECT s.section_id, s.class_name 
                FROM section s
                JOIN accounts a ON s.adviser = a.id
                WHERE a.id = ?
            ");
            if ($check_assigned === false) {
                throw new Exception('Failed to prepare adviser assignment check query: ' . $conn->error);
            }
            $check_assigned->bind_param('i', $adviser_id);
            $check_assigned->execute();
            $assigned_result = $check_assigned->get_result();
            if ($assigned_result->num_rows > 0) {
                $assigned = $assigned_result->fetch_assoc();
                $errors['adviser_id'] = 'This adviser is already assigned to ' . htmlspecialchars($assigned['class_name']);
            }
            $check_assigned->close();
        }

        // If there are validation errors, return them
        if (!empty($errors)) {
            $response['success'] = false;
            $response['message'] = 'Please fix the following errors:';
            $response['errors'] = $errors;
            error_log('Validation errors: ' . print_r($response, true));
            sendJsonResponse($response);
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into section table
            $query = "INSERT INTO `section` (
                `class_name`, 
                `adviser`, 
                `track`, 
                `grade_level`, 
                `semester`
            ) VALUES (?, ?, ?, ?, ?)";

            error_log("Preparing SQL: $query");
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                $error = $conn->error;
                error_log("Prepare failed: $error");
                throw new Exception('Failed to prepare insert query: ' . $error);
            }

            // Log the values being bound
            error_log("Binding values: " . print_r([$classname, $adviser_id, $track, $grade_level, $semester], true));
            
            $bindResult = $stmt->bind_param('sisss',
                $classname,
                $adviser_id,  // Using numeric ID instead of name
                $track,
                $grade_level,
                $semester
            );
            
            if ($bindResult === false) {
                $error = $stmt->error;
                error_log("Bind failed: $error");
                throw new Exception('Failed to bind parameters: ' . $error);
            }

            $executeResult = $stmt->execute();
            if ($executeResult === false) {
                $error = $stmt->error;
                error_log("Execute failed: $error");
                throw new Exception('Failed to save class: ' . $error);
            }

            $section_id = $conn->insert_id;
            $stmt->close();

            // Check if assigned_section column exists before trying to update it
            $check_column = $conn->query("SHOW COLUMNS FROM accounts LIKE 'assigned_section'");
            if ($check_column->num_rows > 0) {
                // Column exists, update it
                $update_adviser = $conn->prepare("
                    UPDATE accounts 
                    SET assigned_section = ? 
                    WHERE id = ?
                ");
                if ($update_adviser) {
                    $update_adviser->bind_param('si', $classname, $adviser_id);
                    if (!$update_adviser->execute()) {
                        error_log("Warning: Failed to update adviser assignment: " . $update_adviser->error);
                        // Don't throw exception, just log the error
                    }
                    $update_adviser->close();
                } else {
                    error_log("Warning: Failed to prepare update adviser query: " . $conn->error);
                }
            } else {
                // Column doesn't exist, log a warning
                error_log("Warning: 'assigned_section' column not found in 'accounts' table. Skipping update.");
            }

            // Commit transaction
            $conn->commit();

            // Prepare success response
            $response['success'] = true;
            $response['message'] = 'Class created successfully';
            $response['data'] = [
                'section_id' => $section_id,
                'class_name' => $classname,
                'track' => $track,
                'grade_level' => $grade_level,
                'semester' => $semester,
                'adviser' => $adviser_name,
                'school_year' => $school_year
            ];

            // Set success flag in session for the next request
            $_SESSION['class_created'] = true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();

            // Log the error
            error_log('Error creating class: ' . $e->getMessage());

            // Set error response
            $response['success'] = false;
            $response['message'] = 'An error occurred while creating the class';
            $response['error'] = $e->getMessage();
        }
    } catch (Exception $e) {
        // Handle any exceptions during the process
        error_log('Unexpected error: ' . $e->getMessage());
        $response['success'] = false;
        $response['message'] = 'An unexpected error occurred';
        $response['error'] = $e->getMessage();
    }
} catch (Exception $e) {
    // Handle any exceptions during the process
    error_log('Unexpected error: ' . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'An unexpected error occurred';
    $response['error'] = $e->getMessage();
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}

// Add debug info if not in production
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1')) {
    $response['debug']['memory_usage'] = memory_get_usage() / 1024 / 1024 . ' MB';
    $response['debug']['peak_memory_usage'] = memory_get_peak_usage() / 1024 / 1024 . ' MB';
}

// Clear any output and send JSON response
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
exit;