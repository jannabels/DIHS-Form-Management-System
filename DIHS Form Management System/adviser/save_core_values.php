<?php
include '../db_connect.php';

if (isset($_POST['lrn']) && isset($_POST['core_data'])) {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $core_data = json_decode($_POST['core_data'], true);
    
    $response = ['success' => false, 'error' => ''];
    
    try {
        foreach ($core_data as $quarter => $fields) {
            // Set default empty strings for missing fields
            $makadiyos = $fields['makadiyos'] ?? '';
            $makadiyos_2 = $fields['makadiyos_2'] ?? '';
            $makatao = $fields['makatao'] ?? '';
            $makatao_2 = $fields['makatao_2'] ?? '';
            $makakalikasan = $fields['makakalikasan'] ?? '';
            $makabansa = $fields['makabansa'] ?? '';
            $makabansa_2 = $fields['makabansa_2'] ?? '';
            
            // Check if record exists
            $check_stmt = $conn->prepare("
                SELECT id FROM core_values
                WHERE LRN = ? AND quarter = ?
                LIMIT 1
            ");
            $check_stmt->bind_param("si", $lrn, $quarter);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                // Update
                $update_stmt = $conn->prepare("
                    UPDATE core_values
                    SET makadiyos = ?, makadiyos_2 = ?, makatao = ?, makatao_2 = ?, makakalikasan = ?, makabansa = ?, makabansa_2 = ?
                    WHERE LRN = ? AND quarter = ?
                ");
                $update_stmt->bind_param("ssssssssi", $makadiyos, $makadiyos_2, $makatao, $makatao_2, $makakalikasan, $makabansa, $makabansa_2, $lrn, $quarter);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update core values for quarter $quarter: " . $update_stmt->error);
                }
                $update_stmt->close();
            } else {
                // Insert
                $insert_stmt = $conn->prepare("
                    INSERT INTO core_values (LRN, makadiyos, makadiyos_2, makatao, makatao_2, makakalikasan, makabansa, makabansa_2, quarter)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->bind_param("ssssssssi", $lrn, $makadiyos, $makadiyos_2, $makatao, $makatao_2, $makakalikasan, $makabansa, $makabansa_2, $quarter);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to insert core values for quarter $quarter: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
        
        $response['success'] = true;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>