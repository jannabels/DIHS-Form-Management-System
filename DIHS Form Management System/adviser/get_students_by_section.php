<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Adviser') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$adviser_id = $_SESSION['user_id'];
$section = isset($_GET['section']) ? $_GET['section'] : '';

try {
    // Get the draw counter for DataTables
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    
    // Get pagination parameters
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    
    // Get search value
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    // Build the base query
    $query = "
        SELECT 
            SQL_CALC_FOUND_ROWS
            s.LRN, 
            sf1.name as Name,
            sf1.Sex as Gender,
            sf1.Age,
            sf1.Birthdate,
            s.status
        FROM sf9 s
        INNER JOIN sf1 ON s.LRN = sf1.LRN
        INNER JOIN section ON s.section = section.class_name
        WHERE section.adviser = ?
    ";
    
    $params = [$adviser_id];
    $types = "i"; // i for integer
    
    // Add section filter if provided
    if (!empty($section)) {
        $query .= " AND s.section = ?";
        $params[] = $section;
        $types .= "s";
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (s.LRN LIKE ? OR sf1.name LIKE ? OR sf1.Age LIKE ? OR sf1.Sex LIKE ? OR sf1.Birthdate LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sssss";
    }
    
    // Add ordering
    $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 1;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';
    
    $columns = [
        0 => 's.LRN',
        1 => 'sf1.Name',
        2 => 'sf1.Age',
        3 => 'sf1.Sex',
        4 => 'sf1.Birthdate',
        5 => 's.status'
    ];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    } else {
        $query .= " ORDER BY sf1.Name ASC";
    }
    
    // Add pagination
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $length;
    $params[] = $start;
    $types .= "ii";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    $bindParams = array_merge([$types], array_map(function($param) use (&$conn) {
        return $param;
    }, $params));
    
    $bindParamsRefs = [];
    foreach($bindParams as $key => $value) {
        $bindParamsRefs[$key] = &$bindParams[$key];
    }
    
    call_user_func_array(array($stmt, 'bind_param'), $bindParamsRefs);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get total records
    $totalRecords = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    
    // Fetch all rows
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'LRN' => $row['LRN'],
            'Name' => $row['Name'],
            'Age' => $row['Age'],
            'Sex' => $row['Gender'],
            'Birthdate' => $row['Birthdate'],
            'status' => $row['status']
        ];
    }
    
    // Get total filtered records (same as total for this implementation)
    $totalFiltered = $totalRecords;
    
    // Prepare response
    $response = [
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalFiltered,
        "data" => $students
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "draw" => isset($draw) ? $draw : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => 'Error: ' . $e->getMessage()
    ]);
}