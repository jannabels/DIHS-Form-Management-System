<?php
// add_student.php
include '../db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

try {
    // Collect POST data
    $lrn = $_POST['lrn'] ?? null;
    if (empty($lrn)) {
        throw new Exception('LRN is required');
    }

    $data = [
        'LRN' => $lrn,
        'Name' => $_POST['name'] ?? null,
        'Sex' => $_POST['sex'] ?? null,
        'Birthdate' => $_POST['birthdate'] ?? null,
        'Age' => !empty($_POST['age']) ? intval($_POST['age']) : null,
        'Religious_Affiliation' => $_POST['religious_affiliation'] ?? null,
        'House_Street_Sitio_Purok' => $_POST['house_street_sitio_purok'] ?? null,
        'Barangay' => $_POST['barangay'] ?? null,
        'Municipality_City' => $_POST['municipality_city'] ?? null,
        'Province' => $_POST['province'] ?? null,
        'Fathers_Name' => $_POST['fathers_name'] ?? null,
        'Mothers_Maiden_Name' => $_POST['mothers_maiden_name'] ?? null,
        'Name(Guardian)' => $_POST['name_guardian'] ?? null,
        'Relationship' => $_POST['relationship'] ?? null,
        'Contact_Number' => $_POST['contact_number'] ?? null,
        'Remarks' => $_POST['remarks'] ?? null,
        'sy' => '2024-2025' // Default value
    ];

    // Prepare columns for query (without backticks here)
    $columns = array_keys($data);

    // Prepare placeholders
    $placeholders = array_fill(0, count($columns), '?');

    // Build insert query with backticks
    $insertQuery = "INSERT INTO sf1 (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = $conn->prepare($insertQuery);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Determine types
    $types = '';
    $values = array_values($data);
    foreach ($data as $key => $val) {
        if ($key === 'Age') {
            $types .= 'i';
        } else {
            $types .= 's';
        }
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>