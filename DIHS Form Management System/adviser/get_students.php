<?php
session_start();
require_once '../db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get the database connection
$conn->select_db('admindihs');

// Get the POST data
$section = isset($_POST['section']) ? $_POST['section'] : '';
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

// Build the base query
$query = "SELECT 
            s.lrn, 
            CONCAT(s.`First Name`, ' ', s.`Middle Name`, ' ', s.`Last Name`, ' ', s.`Suffix`) as full_name,
            s.`Grade Level` as grade_level,
            s.`Track` as track,
            s.`Status` as status,
            '' as core_values,  // This would be populated from another table if needed
            '' as subjects      // This would be populated from another table if needed
          FROM sf9 s
          WHERE 1=1";

// Add section filter if provided
if (!empty($section)) {
    $query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section) . "'";
}

// Add search condition
if (!empty($search)) {
    $query .= " AND (
        s.lrn LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR
        CONCAT(s.`First Name`, ' ', s.`Last Name`) LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR
        s.`Grade Level` LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR
        s.`Track` LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR
        s.`Status` LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
    )";
}

// Get total records count
$totalRecordsQuery = "SELECT COUNT(*) as total FROM sf9" . (!empty($section) ? " WHERE section = '" . mysqli_real_escape_string($conn, $section) . "'" : "");
$totalRecordsResult = mysqli_query($conn, $totalRecordsQuery);
$totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

// Get filtered records count
$filteredRecordsQuery = str_replace("SELECT s.lrn, CONCAT(s.`First Name`, ' ', s.`Middle Name`, ' ', s.`Last Name`, ' ', s.`Suffix`) as full_name, s.`Grade Level` as grade_level, s.`Track` as track, s.`Status` as status, '' as core_values, '' as subjects", 
                                 "SELECT COUNT(*) as count", $query);
$filteredRecordsResult = mysqli_query($conn, $filteredRecordsQuery);
$filteredRecords = mysqli_fetch_assoc($filteredRecordsResult)['count'];

// Add ordering
$orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
$orderColumnName = '';

// Map DataTables column index to database column
$columns = [
    0 => 's.lrn',
    1 => 'full_name',
    2 => 's.`Grade Level`',
    3 => 's.`Track`',
    4 => 's.`Status`',
    5 => 'core_values',
    6 => 'subjects'
];

if (isset($columns[$orderColumn])) {
    $orderColumnName = $columns[$orderColumn];
    $query .= " ORDER BY " . $orderColumnName . " " . mysqli_real_escape_string($conn, strtoupper($orderDir));
}

// Add pagination
$query .= " LIMIT " . $start . ", " . $length;

// Execute the query
$result = mysqli_query($conn, $query);

// Prepare the response
$response = [
    'draw' => $draw,
    'recordsTotal' => intval($totalRecords),
    'recordsFiltered' => intval($filteredRecords),
    'data' => []
];

// Process the results
while ($row = mysqli_fetch_assoc($result)) {
    $response['data'][] = [
        'lrn' => $row['lrn'],
        'name' => $row['full_name'],
        'grade_level' => $row['grade_level'],
        'track' => $row['track'],
        'status' => $row['status'],
        'core_values' => $row['core_values'],
        'subjects' => $row['subjects']
    ];
}

// Close the connection
mysqli_close($conn);

// Return the response as JSON
echo json_encode($response);
?>
