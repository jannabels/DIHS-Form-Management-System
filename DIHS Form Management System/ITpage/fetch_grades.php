<?php
include '../db_connect.php';

if (isset($_POST['lrn'])) {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $response = ['success' => false, 'data' => [], 'error' => ''];
    
    try {
        // Step 1: Get the student's current grade level via sf9 + section
        $gradeQuery = "
            SELECT sec.grade_level 
            FROM sf9 sf
            JOIN section sec ON sf.section = sec.class_name
            WHERE sf.LRN = ?
            LIMIT 1
        ";
        $gradeStmt = $conn->prepare($gradeQuery);
        $gradeStmt->bind_param("s", $lrn);
        $gradeStmt->execute();
        $gradeResult = $gradeStmt->get_result();
        
        if ($gradeResult->num_rows === 0) {
            throw new Exception("Student's grade level not found");
        }
        
        $currentGrade = $gradeResult->fetch_assoc()['grade_level'];
        $gradeStmt->close();
        
        // Step 2: Fetch only grades for the current grade level
        $query = "SELECT * FROM grades WHERE LRN = ? AND grade_level = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $lrn, $currentGrade);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [
            '1' => ['1' => [], '2' => [], 'final' => []],
            '2' => ['1' => [], '2' => [], 'final' => []]
        ];
        
        while ($row = $result->fetch_assoc()) {
            $sem = $row['semester'];
            $qua = $row['quarter'];
            if (in_array($sem, ['1', '2']) && in_array($qua, ['1', '2', 'final'])) {
                unset($row['id'], $row['LRN'], $row['semester'], $row['quarter'], $row['grade_level']);
                foreach ($row as $field => $value) {
                    if ($value !== null && $value !== '') {
                        $data[$sem][$qua][$field] = $value;
                    }
                }
            }
        }
        
        $response['success'] = true;
        $response['data'] = $data;
        $stmt->close();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
