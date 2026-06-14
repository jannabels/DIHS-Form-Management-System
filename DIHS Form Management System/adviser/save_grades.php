<?php
include '../db_connect.php';

// Function to check if grade can be updated
function canUpdateGrade($conn, $lrn, $semester, $quarter, $subject_code, $new_grade) {
    $check_stmt = $conn->prepare("
        SELECT grade, last_updated 
        FROM student_grades 
        WHERE lrn = ? AND semester = ? AND quarter = ? AND subject_code = ?
        LIMIT 1
    ");
    
    $semester_value = strpos($semester, '1') !== false ? '1' : '2';
    $check_stmt->bind_param("ssss", $lrn, $semester_value, $quarter, $subject_code);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        return true; // No existing grade, can insert new one
    }
    
    $row = $result->fetch_assoc();
    $check_stmt->close();
    
    // If grade exists and is being lowered, don't allow the update
    if ($row['grade'] > 0 && $new_grade < $row['grade']) {
        return false;
    }
    
    return true;
}

if (isset($_POST['lrn']) && isset($_POST['semester']) && isset($_POST['grade_level']) && isset($_POST['grades_data'])) {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $grade_level = mysqli_real_escape_string($conn, $_POST['grade_level']);
    $grades_data = json_decode($_POST['grades_data'], true);
    
    $response = ['success' => false, 'error' => ''];
    
    try {
        // Get the section for the student
        $section_query = "SELECT section FROM sf9 WHERE LRN = ? LIMIT 1";
        $section_stmt = $conn->prepare($section_query);
        $section_stmt->bind_param("s", $lrn);
        $section_stmt->execute();
        $section_result = $section_stmt->get_result();
        
        if ($section_result->num_rows === 0) {
            throw new Exception("Student section not found");
        }
        
        $section = $section_result->fetch_assoc()['section'];
        $section_stmt->close();
        
        // Get subject names from curriculum
        $curriculum_query = "SELECT subject_code, subject_name FROM curriculum 
                            WHERE grade_level = ? AND semester LIKE ?";
        $curriculum_stmt = $conn->prepare($curriculum_query);
        $semester_like = "%$semester%";
        $curriculum_stmt->bind_param("ss", $grade_level, $semester_like);
        $curriculum_stmt->execute();
        $curriculum_result = $curriculum_stmt->get_result();
        
        $subject_names = [];
        while ($row = $curriculum_result->fetch_assoc()) {
            $subject_key = strtolower(str_replace([' ', '-'], '_', $row['subject_code']));
            $subject_names[$subject_key] = $row['subject_name'];
        }
        $curriculum_stmt->close();
        
        // Process grades for each quarter (only 1 and 2, final is calculated)
        foreach (['1', '2'] as $quarter) {
            if (!empty($grades_data[$quarter])) {
                $fields = $grades_data[$quarter];
                
                // Log the fields being processed
                error_log("Processing fields for quarter $quarter: " . print_r($fields, true));
                
                foreach ($fields as $subject_key => $grade) {
                    if (empty($subject_key) || $subject_key === 'undefined') {
                        error_log("Invalid subject key found: " . $subject_key);
                        continue;
                    }
                    
                    // Get the subject name from curriculum
                    $subject_name = $subject_names[$subject_key] ?? '';
                    
                    if (empty($subject_name)) {
                        error_log("Subject name not found for key: " . $subject_key);
                        continue;
                    }
                    
                    // Check if record exists
                    $semester_value = strpos($semester, '1') !== false ? '1' : '2';
                    $check_stmt = $conn->prepare("
                        SELECT id FROM student_grades 
                        WHERE lrn = ? AND semester = ? AND quarter = ? AND subject_code = ?
                        LIMIT 1
                    ");
                    $check_stmt->bind_param("ssss", $lrn, $semester_value, $quarter, $subject_key);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    
                    if ($check_stmt->num_rows > 0) {
                        // Check if we can update the grade
                        if (!canUpdateGrade($conn, $lrn, $semester, $quarter, $subject_key, $grade)) {
                            $response['error'] = "Cannot lower existing grade for $subject_name (Q$quarter).";
                            $check_stmt->close();
                            echo json_encode($response);
                            exit;
                        }
                        
                        // Update existing record with the new grade
                        $update_stmt = $conn->prepare("
                            UPDATE student_grades
                            SET grade = ?, grade_level = ?, updated_at = NOW(), last_updated = NOW()
                            WHERE lrn = ? AND semester = ? AND quarter = ? AND subject_code = ?
                        ");
                        
                        $types = 'dsssss';
                        $params = [
                            $grade,
                            $grade_level,
                            $lrn,
                            strpos($semester, '1') !== false ? '1' : '2', // Convert '1st Semester' to '1' or '2nd Semester' to '2'
                            $quarter,
                            $subject_key
                        ];
                        
                        $update_stmt->bind_param($types, ...$params);
                        
                        if (!$update_stmt->execute()) {
                            throw new Exception("Failed to update grade for $subject_key (Q$quarter): " . $update_stmt->error);
                        }
                        $update_stmt->close();
                    } else {
                        // Insert new record
                        $insert_stmt = $conn->prepare("
                            INSERT INTO student_grades 
                            (lrn, semester, quarter, grade_level, subject_code, subject_name, grade, remarks, created_at, updated_at, last_updated)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW(), NOW(), NOW())
                        ");
                        
                        $types = 'ssssssd'; // 7 parameters: s (lrn) + s (semester) + s (quarter) + s (grade_level) + s (subject_code) + s (subject_name) + d (grade)
                        $params = [
                            $lrn,
                            strpos($semester, '1') !== false ? '1' : '2', // Convert '1st Semester' to '1' or '2nd Semester' to '2'
                            $quarter,
                            $grade_level,
                            $subject_key,
                            $subject_name,
                            $grade
                        ];
                        
                        $insert_stmt->bind_param($types, ...$params);
                        
                        if (!$insert_stmt->execute()) {
                            throw new Exception("Failed to insert grade for $subject_key (Q$quarter): " . $insert_stmt->error);
                        }
                        $insert_stmt->close();
                    }
                    $check_stmt->close();
                }
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