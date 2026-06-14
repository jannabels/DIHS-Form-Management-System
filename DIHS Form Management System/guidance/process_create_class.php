<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Check if user is logged in and has the right role
    if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Guidance') {
        throw new Exception('Unauthorized access');
    }

    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate form data
    $className = trim($_POST['class_name'] ?? '');
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $track = trim($_POST['track'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $adviserId = intval($_POST['adviser_id'] ?? 0);

    // Validate required fields
    if (empty($className) || empty($gradeLevel) || empty($track) || empty($semester) || $adviserId <= 0) {
        throw new Exception('All fields are required');
    }

    // Validate class name format (e.g., "STEM 11-A")
    if (!preg_match('/^[A-Za-z]+\s\d{1,2}-[A-Za-z]$/', $className)) {
        throw new Exception('Invalid class name format. Format should be like: STEM 11-A');
    }

    // Check if class with the same name already exists
    $stmt = $conn->prepare("SELECT section_id FROM section WHERE class_name = ?");
    $stmt->bind_param("s", $className);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('A class with this section code already exists');
    }
    $stmt->close();

    // Check if adviser exists and is active
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE id = ? AND LOWER(Role) LIKE '%adviser%' AND LOWER(Status) = 'active'");
    $stmt->bind_param("i", $adviserId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Selected adviser is not valid or inactive');
    }
    $stmt->close();

    // Insert new class section
    $stmt = $conn->prepare("INSERT INTO section (class_name, adviser, grade_level, track, semester) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $className, $adviserId, $gradeLevel, $track, $semester);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Class section created successfully';
        $response['data']['section_id'] = $stmt->insert_id;
        
        // Log the action
        if (class_exists('AuditLog')) {
            $auditLog = new AuditLog($conn);
            $auditLog->log(
                $_SESSION['user_id'] ?? 0,
                'CREATE_CLASS_SECTION',
                "Created new class section: {$className} (ID: {$stmt->insert_id})",
                json_encode([
                    'class_name' => $className,
                    'grade_level' => $gradeLevel,
                    'track' => $track,
                    'semester' => $semester,
                    'adviser_id' => $adviserId
                ])
            );
        }
    } else {
        throw new Exception('Failed to create class section: ' . $conn->error);
    }
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>
