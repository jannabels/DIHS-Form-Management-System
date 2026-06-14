<?php
// get_students_by_section.php
include '../db_connect.php';

header('Content-Type: application/json');

try {
    $sectionId = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
    $students = [];

    if ($sectionId > 0) {
        $query = "SELECT s.lrn 
                 FROM students s
                 JOIN student_section ss ON s.lrn = ss.lrn
                 WHERE ss.section_id = ? AND ss.sy = '2024-2025'";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }

    echo json_encode($students);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
