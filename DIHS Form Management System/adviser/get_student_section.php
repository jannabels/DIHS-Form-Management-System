<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['lrn'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$lrn = trim($_POST['lrn']);

if (empty($lrn)) {
    echo json_encode(['success' => false, 'error' => 'LRN is required']);
    exit;
}

try {
    // First, get the student's current section from sf1 table
    $stmt = $conn->prepare("SELECT section, sy FROM sf1 WHERE LRN = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('s', $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        exit;
    }
    
    $student = $result->fetch_assoc();
    $sectionName = $student['section'];
    $schoolYear = $student['sy'];
    
    if (empty($sectionName)) {
        echo json_encode(['success' => false, 'error' => 'Student is not assigned to any section']);
        exit;
    }
    
    // Get section details
    $stmt = $conn->prepare("SELECT * FROM section WHERE class_name = ?");
    $stmt->bind_param('s', $sectionName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Section not found']);
        exit;
    }
    
    $section = $result->fetch_assoc();
    
    // Add school year to the section data
    $section['school_year'] = $schoolYear;
    
    echo json_encode([
        'success' => true,
        'section' => $section
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_student_section.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching section information'
    ]);
}
