<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$response = [
    'exists' => false,
    'name' => '',
    'status' => 'active',
    'grade_level' => '',
    'section' => ''
];

try {
    if (!isset($_POST['lrn']) || strlen($_POST['lrn']) !== 12) {
        throw new Exception('Invalid LRN');
    }

    $lrn = $_POST['lrn'];
    
    // Check if student exists in sf1 table
    $stmt = $conn->prepare("
        SELECT s.Name, s.status, sec.grade_level, sec.class_name as section
        FROM sf1 s
        LEFT JOIN section sec ON s.section = sec.class_name
        WHERE s.LRN = ?
    ");
    
    $stmt->bind_param('s', $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $response = [
            'exists' => true,
            'name' => $student['Name'],
            'status' => $student['status'] ?? 'active',
            'grade_level' => $student['grade_level'] ?? '',
            'section' => $student['section'] ?? ''
        ];
    }
    
    $stmt->close();
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>