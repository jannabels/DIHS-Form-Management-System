<?php
// Debug script to check section counts
include '../db_connect.php';

header('Content-Type: application/json');

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;

if (!$section_id) {
    echo json_encode(['error' => 'section_id required']);
    exit;
}

// Get section details
$section_query = "SELECT class_name, grade_level, track FROM section WHERE section_id = ?";
$stmt = $conn->prepare($section_query);
$stmt->bind_param("i", $section_id);
$stmt->execute();
$section_result = $stmt->get_result();

if ($section_result->num_rows == 0) {
    echo json_encode(['error' => 'Section not found']);
    exit;
}

$section_data = $section_result->fetch_assoc();
$class_name = $section_data['class_name'];

// Check all sections in sf1
$all_sections = "SELECT DISTINCT section, COUNT(*) as count FROM sf1 WHERE section IS NOT NULL AND section != '' GROUP BY section ORDER BY section";
$all_result = $conn->query($all_sections);
$sections_list = [];
while ($row = $all_result->fetch_assoc()) {
    $sections_list[] = $row;
}

// Check students for this specific section
$count_query = "SELECT 
                    SUM(CASE WHEN UPPER(TRIM(IFNULL(Sex, ''))) IN ('M', 'MALE') THEN 1 ELSE 0 END) as male,
                    SUM(CASE WHEN UPPER(TRIM(IFNULL(Sex, ''))) IN ('F', 'FEMALE') THEN 1 ELSE 0 END) as female,
                    COUNT(*) as total
                FROM sf1 
                WHERE section = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("s", $class_name);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$counts = $count_result->fetch_assoc();

// Check for similar sections (LIKE)
$similar_query = "SELECT section, 
                     SUM(CASE WHEN UPPER(TRIM(IFNULL(Sex, ''))) IN ('M', 'MALE') THEN 1 ELSE 0 END) as male,
                     SUM(CASE WHEN UPPER(TRIM(IFNULL(Sex, ''))) IN ('F', 'FEMALE') THEN 1 ELSE 0 END) as female,
                     COUNT(*) as total
                  FROM sf1 
                  WHERE section LIKE ? 
                  GROUP BY section";
$like_pattern = "%$class_name%";
$similar_stmt = $conn->prepare($similar_query);
$similar_stmt->bind_param("s", $like_pattern);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
$similar_sections = [];
while ($row = $similar_result->fetch_assoc()) {
    $similar_sections[] = $row;
}

echo json_encode([
    'section_id' => $section_id,
    'class_name' => $class_name,
    'exact_match' => $counts,
    'similar_sections' => $similar_sections,
    'all_sections_in_db' => $sections_list
], JSON_PRETTY_PRINT);
?>

