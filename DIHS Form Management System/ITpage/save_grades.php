<?php
include '../db_connect.php';

if (isset($_POST['lrn']) && isset($_POST['semester']) && isset($_POST['grade_level']) && isset($_POST['grades_data'])) {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $grade_level = mysqli_real_escape_string($conn, $_POST['grade_level']);
    $grades_data = json_decode($_POST['grades_data'], true);
    
    $response = ['success' => false, 'error' => ''];
    
    try {
        foreach (['1', '2', 'final'] as $qua) {
            if (!empty($grades_data[$qua])) {
                $fields = $grades_data[$qua];
                
                // Check if record exists
                $check_stmt = $conn->prepare("
                    SELECT id FROM grades
                    WHERE LRN = ? AND semester = ? AND quarter = ?
                    LIMIT 1
                ");
                $check_stmt->bind_param("sss", $lrn, $semester, $qua);
                $check_stmt->execute();
                $check_stmt->store_result();

                $sets = [];
                $params = [];
                $types = '';
                foreach ($fields as $field => $value) {
                    $sets[] = "$field = ?";
                    $params[] = $value;
                    $types .= 's';
                }
                // Add grade_level to the sets
                $sets[] = "grade_level = ?";
                $params[] = $grade_level;
                $types .= 's';
                $set_str = implode(', ', $sets);

                if ($check_stmt->num_rows > 0) {
                    // Update existing record
                    $types .= 'sss';
                    $params[] = $lrn;
                    $params[] = $semester;
                    $params[] = $qua;
                    $update_stmt = $conn->prepare("
                        UPDATE grades
                        SET $set_str
                        WHERE LRN = ? AND semester = ? AND quarter = ?
                    ");
                    $update_stmt->bind_param($types, ...$params);
                    
                    if (!$update_stmt->execute()) {
                        throw new Exception("Failed to update grades for quarter $qua: " . $update_stmt->error);
                    }
                    $update_stmt->close();
                } else {
                    // Insert new record
                    $fields_str = implode(', ', array_keys($fields)) . ', grade_level';
                    $placeholders = implode(', ', array_fill(0, count($fields), '?')) . ', ?';
                    $insert_types = 'sss' . $types; // LRN, semester, quarter, fields, grade_level
                    $insert_params = [$lrn, $semester, $qua, ...$params];
                    $insert_stmt = $conn->prepare("
                        INSERT INTO grades (LRN, semester, quarter, $fields_str)
                        VALUES (?, ?, ?, $placeholders)
                    ");
                    $insert_stmt->bind_param($insert_types, ...$insert_params);
                    
                    if (!$insert_stmt->execute()) {
                        throw new Exception("Failed to insert grades for quarter $qua: " . $insert_stmt->error);
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        }
        
        $response['success'] = true;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>