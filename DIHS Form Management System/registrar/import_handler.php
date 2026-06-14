<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $sectionId = $_POST['sectionId'] ?? 0;
    
    if ($sectionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please select a valid section']);
        exit();
    }
    
    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, ['xls', 'xlsx'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Please upload an Excel file (.xls or .xlsx)']);
        exit();
    }
    
    require_once 'import_students.php';
    
    try {
        $result = importStudentsFromExcel($fileTmpPath, $sectionId);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error processing file: ' . $e->getMessage()]);
    }
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid request']);
