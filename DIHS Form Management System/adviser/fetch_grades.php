<?php
include '../db_connect.php';

if (isset($_POST['lrn'])) {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $response = ['success' => false, 'data' => [], 'error' => ''];
    
    try {
        // Step 1: Get the student's current grade level via sf9 + section
        $gradeQuery = "
            SELECT sec.grade_level, sec.track 
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
        
        $studentData = $gradeResult->fetch_assoc();
        $currentGrade = $studentData['grade_level'];
        $track = $studentData['track'];
        $gradeStmt->close();
        
        // Get the semester from POST or use the current one
        $semester = isset($_POST['semester']) ? $_POST['semester'] : '1st Semester';
        $semester_num = strpos($semester, '1') !== false ? '1' : '2';
        
        // Get subjects from curriculum for this grade level and track
        $subjectsQuery = "SELECT subject_code, subject_name 
                         FROM curriculum 
                         WHERE grade_level = ? 
                         AND track = ? 
                         AND LOWER(semester) LIKE LOWER(?)";
        $subjStmt = $conn->prepare($subjectsQuery);
        $semester_like = "%$semester_num%";
        $subjStmt->bind_param("sss", $currentGrade, $track, $semester_like);
        $subjStmt->execute();
        $subjectsResult = $subjStmt->get_result();
        
        $subjects = [];
        while ($row = $subjectsResult->fetch_assoc()) {
            // Keep the original case for display, but use lowercase for the key
            $subject_key = strtolower(str_replace([' ', '-'], '_', $row['subject_code']));
            $subjects[$subject_key] = [
                'code' => $row['subject_code'],  // Keep original case
                'name' => $row['subject_name']
            ];
        }
        $subjStmt->close();
        
        // Track failed subjects from 1st semester
        $failedSubjects = [];
        
        // Get all subjects that were failed in the first semester (average of Q1 and Q2 grades < 75)
        $failedFirstSemQuery = "
            SELECT subject_code, AVG(grade) as avg_grade
            FROM student_grades 
            WHERE lrn = ? 
            AND grade_level = ? 
            AND semester = '1' 
            AND quarter IN ('1', '2')
            GROUP BY subject_code 
            HAVING avg_grade < 75";
        $failedStmt = $conn->prepare($failedFirstSemQuery);
        $failedStmt->bind_param("ss", $lrn, $currentGrade);
        $failedStmt->execute();
        $failedResult = $failedStmt->get_result();
        while ($row = $failedResult->fetch_assoc()) {
            $subject_key = strtolower(str_replace([' ', '-'], '_', $row['subject_code']));
            $failedSubjects[$subject_key] = [
                'code' => $row['subject_code'],
                'avg_grade' => $row['avg_grade']
            ];
        }
        $failedStmt->close();
        
        // If this is 2nd semester, add failed subjects from 1st semester to the list
        if ($semester_num == '2' && !empty($failedSubjects)) {
            // Get subject names for failed subjects
            $failedCodes = array_column($failedSubjects, 'code');
            $placeholders = str_repeat('?,', count($failedCodes) - 1) . '?';
            
            $failedSubjectsQuery = "
                SELECT DISTINCT 
                    c.subject_code, 
                    COALESCE(c.subject_name, sg.subject_name) as subject_name
                FROM curriculum c
                LEFT JOIN student_grades sg ON c.subject_code = sg.subject_code 
                    AND sg.lrn = ? AND sg.grade_level = ?
                WHERE c.subject_code IN ($placeholders)
                    AND c.grade_level = ? AND c.track = ?
                GROUP BY c.subject_code, c.subject_name";
            
            $failedStmt = $conn->prepare($failedSubjectsQuery);
            
            // Create an array of references for bind_param
            $params = array_merge([$lrn, $currentGrade], $failedCodes, [$currentGrade, $track]);
            $bindParams = [];
            $bindParams[] = str_repeat('s', count($params));
            foreach ($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }
            
            call_user_func_array([$failedStmt, 'bind_param'], $bindParams);
            $failedStmt->execute();
            $failedResult = $failedStmt->get_result();
            
            // Add failed subjects to the subjects list
            while ($failedRow = $failedResult->fetch_assoc()) {
                $subject_key = strtolower(str_replace([' ', '-'], '_', $failedRow['subject_code']));
                // Only add if not already in the list (to avoid duplicates)
                if (!isset($subjects[$subject_key])) {
                    $subjects[$subject_key] = $failedRow['subject_name'] . ' (Failed from 1st Sem)';
                }
            }
            $failedStmt->close();
        }
        
        // Fetch grades for the current semester with normalized subject codes
        $query = "WITH latest_grades AS (
                    SELECT 
                        sg.*,
                        ROW_NUMBER() OVER (
                            PARTITION BY LOWER(REPLACE(sg.subject_code, ' ', '_')), sg.quarter 
                            ORDER BY 
                                CASE 
                                    WHEN sg.semester = '2' THEN 2 
                                    WHEN sg.semester = '1' AND sg.is_failed = 0 THEN 1 
                                    ELSE 0 
                                END DESC,
                                sg.semester DESC
                        ) as rn
                    FROM student_grades sg
                    WHERE sg.lrn = ? 
                      AND sg.grade_level = ?
                      AND sg.semester IN ('1', '2')
                )
                SELECT 
                    lg.semester, 
                    lg.quarter, 
                    COALESCE(c.subject_code, lg.subject_code) as subject_code,
                    COALESCE(c.subject_name, lg.subject_name) as subject_name,
                    lg.grade,
                    lg.subject_code as original_subject_code,
                    c.subject_code as curriculum_subject_code,
                    lg.is_failed
                FROM latest_grades lg
                LEFT JOIN curriculum c ON LOWER(REPLACE(lg.subject_code, ' ', '_')) = LOWER(REPLACE(c.subject_code, ' ', '_'))
                    AND c.grade_level = ? AND c.track = ? AND c.semester LIKE ?
                WHERE lg.rn = 1  -- Only get the most relevant grade for each subject/quarter
                ORDER BY subject_code, lg.quarter";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $lrn, $currentGrade, $currentGrade, $track, $semester_like);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [
            '1' => [], 
            '2' => [], 
            'final' => [],
            'subjects' => $subjects  // Include the subjects list in the response
        ];
        
        // Initialize all subjects with empty grades
        foreach ($subjects as $code => $name) {
            $data['1'][$code] = '';
            $data['2'][$code] = '';
            $data['final'][$code] = '';
        }
        
        // Process existing grades
        while ($row = $result->fetch_assoc()) {
            $qua = $row['quarter'];
            
            // Use the curriculum subject code if available, otherwise use the original
            $subject_code = !empty($row['curriculum_subject_code']) 
                ? $row['curriculum_subject_code'] 
                : $row['original_subject_code'];
                
            // Normalize the subject code to match the format used in the form
            $subject_key = strtolower(str_replace([' ', '-'], '_', $subject_code));
            
            // Special handling for failed subjects in 2nd semester view
           // Special handling for failed subjects in 2nd semester
if ($semester_num == '2' && isset($failedSubjects[$subject_key])) {
    // Keep the grade but mark it as failed
    $data[$qua][$subject_key] = $row['grade'];
    continue;
}
            
            if (in_array($qua, ['1', '2'])) {
                // Only add grades if not a failed subject or if in 1st semester
                $data[$qua][$subject_key] = $row['grade'];
                
                // If this is quarter 2, also update the final grade
                if ($qua === '2' && isset($data['1'][$subject_key]) && $data['1'][$subject_key] !== '') {
                    $data['final'][$subject_key] = round(($data['1'][$subject_key] + $row['grade']) / 2);
                }
            }
        }
        
        // Calculate any remaining final grades
        foreach ($subjects as $code => $name) {
            if ($data['1'][$code] !== '' && $data['2'][$code] !== '' && $data['final'][$code] === '') {
                $data['final'][$code] = round(($data['1'][$code] + $data['2'][$code]) / 2);
            }
        }
        
        $response['success'] = true;
        $response['data'] = $data;
        $stmt->close();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>