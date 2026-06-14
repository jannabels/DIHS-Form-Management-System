<?php
// update_cell.php - Update individual cell data
include '../db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['error'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

try {
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $lrn = $data['lrn'] ?? '';
    $field = $data['field'] ?? '';
    $value = $data['value'] ?? null;
    
    // Validate LRN
    if (empty($lrn)) {
        throw new Exception('LRN is required');
    }
    
    // Validate field name
    $allowedFields = [
        'Name', 'Sex', 'Birthdate', 'Age', 'Religious_Affiliation',
        'House_Street_Sitio_Purok', 'Barangay', 'Municipality_City', 'Province',
        'Fathers_Name', 'Mothers_Maiden_Name', 'Name(Guardian)',
        'Relationship', 'Contact_Number', 'Remarks'
    ];
    
    if (!in_array($field, $allowedFields)) {
        throw new Exception('Invalid field name');
    }
    
    // Escape field name for query (handle special characters)
    $escapedField = '`' . str_replace('`', '``', $field) . '`';
    
    // Prepare update query
    $query = "UPDATE sf1 SET $escapedField = ? WHERE LRN = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ss', $value, $lrn);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Cell updated successfully';
    
} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>

