<?php
include '../db_connect.php';

if (isset($_POST['lrn'])) {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $response = ['success' => false, 'data' => [], 'error' => ''];
    
    try {
        $query = "SELECT * FROM core_values WHERE LRN = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $lrn);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $quarter = $row['quarter'];
            unset($row['id'], $row['LRN'], $row['quarter']); // Remove unnecessary fields
            $data[$quarter] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $data;
        $stmt->close();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'No LRN provided']);
}
?>