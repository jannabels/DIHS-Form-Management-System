<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get parameters
$gradeLevel = $_POST['grade_level'] ?? '';
$track = $_POST['track'] ?? '';
$semester = $_POST['semester'] ?? '';
$lrn = $_POST['lrn'] ?? ''; // Optional LRN for checking failed subjects

if (empty($gradeLevel) || empty($track) || empty($semester)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Grade level, track, and semester are required'
    ]);
    exit;
}

try {
    // Determine semester number (1 or 2)
    $semester_num = (strpos($semester, '1') !== false || $semester === '1') ? '1' : '2';
    
    // Prepare the query to get subjects for the given parameters
    $query = "SELECT * FROM curriculum 
              WHERE grade_level = ? 
              AND track = ? 
              AND semester = ? 
              ORDER BY subject_type, subject_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sss', $gradeLevel, $track, $semester);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    $subject_codes = []; // Track subject codes to avoid duplicates
    
    // Add curriculum subjects
    while ($row = $result->fetch_assoc()) {
        $subjects[] = [
            'id' => $row['id'],
            'subject_code' => $row['subject_code'],
            'subject_name' => $row['subject_name'],
            'subject_type' => $row['subject_type'],
            'grade_level' => $row['grade_level'],
            'track' => $row['track'],
            'semester' => $row['semester'],
            'is_failed' => false // Mark as regular curriculum subject
        ];
        $subject_codes[] = $row['subject_code'];
    }
    
    // If this is 2nd semester and LRN is provided, add failed subjects from 1st semester
    if ($semester_num == '2' && !empty($lrn)) {
        $lrn_escaped = mysqli_real_escape_string($conn, $lrn);
        
        // Get failed subjects from 1st semester (subjects with final grade < 75)
        $failedSubjectsQuery = "
            SELECT DISTINCT 
                sg.subject_code, 
                COALESCE(c.subject_name, sg.subject_name) as subject_name,
                COALESCE(c.subject_type, 'Core') as subject_type,
                AVG(CASE WHEN sg.quarter IN ('1', '2') AND sg.grade IS NOT NULL THEN sg.grade END) as avg_grade
            FROM student_grades sg
            LEFT JOIN curriculum c ON sg.subject_code = c.subject_code 
                AND c.grade_level = ? AND c.track = ?
            WHERE sg.lrn = ? 
            AND sg.grade_level = ?
            AND sg.semester = '1'
            AND sg.quarter IN ('1', '2')
            GROUP BY sg.subject_code, sg.subject_name, c.subject_name, c.subject_type
            HAVING avg_grade IS NOT NULL AND avg_grade < 75
        ";
        
        $failedStmt = $conn->prepare($failedSubjectsQuery);
        $failedStmt->bind_param("ssss", $gradeLevel, $track, $lrn_escaped, $gradeLevel);
        $failedStmt->execute();
        $failedResult = $failedStmt->get_result();
        
        // Add failed subjects to the list (only if not already present)
        while ($failedRow = $failedResult->fetch_assoc()) {
            if (!in_array($failedRow['subject_code'], $subject_codes)) {
                $subjects[] = [
                    'id' => null, // No curriculum ID for failed subjects
                    'subject_code' => $failedRow['subject_code'],
                    'subject_name' => $failedRow['subject_name'],
                    'subject_type' => $failedRow['subject_type'] ?: 'Core',
                    'grade_level' => $gradeLevel,
                    'track' => $track,
                    'semester' => $semester,
                    'is_failed' => true // Mark as failed subject from previous semester
                ];
                $subject_codes[] = $failedRow['subject_code'];
            }
        }
        $failedStmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'count' => count($subjects)
    ]);
    
} catch (Exception $e) {
    error_log('Error in fetch_curriculum.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching curriculum data',
        'debug' => $e->getMessage()
    ]);
}
