<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/attendance_errors.log');

error_reporting(E_ALL);
header('Content-Type: application/json');

include '../db_connect.php';

// Function to validate date format
function validateDate($date, $format = 'F j, Y') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Function to log errors
function logError($message, $data = []) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    if (!empty($data)) {
        $log .= 'Data: ' . print_r($data, true) . "\n";
    }
    error_log($log, 3, '../logs/attendance_errors.log');
}

$response = [
    'success' => false,
    'message' => 'An error occurred',
    'data' => []
];

try {
    // Check if required parameters are provided
    if (!isset($_GET['date']) || !isset($_GET['class'])) {
        throw new Exception('Date and class are required parameters');
    }

    // Get and sanitize input
    $date = trim($_GET['date']);
    $class = trim($_GET['class']);
    
    // Validate date format
    if (!validateDate($date, 'F j, Y')) {
        throw new Exception('Invalid date format. Expected format: "F j, Y" (e.g., "March 20, 2023")');
    }
    
    // Format the date for SQL query
    $formatted_date = date('Y-m-d', strtotime($date));
    
    // Validate class format (alphanumeric, spaces, hyphens, underscores)
    if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $class)) {
        throw new Exception('Invalid class format');
    }
    
    // Start transaction for consistent data retrieval
    mysqli_begin_transaction($conn);
    
    // Get all students in the class with their current attendance status
    $sql = "
        SELECT 
            sf1.LRN, 
            sf1.Name, 
            sf1.Sex,
            COALESCE(da.present, 0) as present,
            COALESCE(da.absent, 0) as absent,
            COALESCE(da.tardy, 0) as tardy
        FROM sf1 
        INNER JOIN sf9 ON sf1.LRN = sf9.LRN 
        LEFT JOIN daily_attendance da ON sf1.LRN = da.LRN 
            AND da.date = ?
        WHERE sf9.section = ?
        ORDER BY 
            sf1.Sex DESC, 
            sf1.Name ASC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $formatted_date, $class);
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Failed to get result set: ' . $conn->error);
    }
    
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['LRN']] = [
            'name' => $row['Name'],
            'sex' => $row['Sex'],
            'present' => (int)$row['present'],
            'absent' => (int)$row['absent'],
            'tardy' => (int)$row['tardy']
        ];
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Attendance data retrieved successfully',
        'data' => [
            'date' => $date,
            'class' => $class,
            'total_students' => count($attendance),
            'attendance' => $attendance
        ]
    ];
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn) {
        mysqli_rollback($conn);
    }
    
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error' => [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ];
    
    logError($e->getMessage(), [
        'trace' => $e->getTraceAsString(),
        'get' => $_GET
    ]);
} finally {
    // Close connection
    if (isset($conn) && $conn) {
        $conn->close();
    }
    
    // Send JSON response
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>