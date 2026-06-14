<?php
include '../db_connect.php';

header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$search = isset($_POST['search']) ? $_POST['search'] : '';
$search = '%' . $search . '%';

try {
    // Use backticks for column names with spaces
    $query = "SELECT id, CONCAT(`First Name`, ' ', `Last Name`) as name 
              FROM accounts 
              WHERE `Role` = 'Adviser' 
              AND `is_set` != 1 
              AND (`First Name` LIKE ? OR `Last Name` LIKE ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $advisers = [];
    while ($row = $result->fetch_assoc()) {
        $advisers[] = $row;
    }
    
    echo json_encode($advisers);
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>