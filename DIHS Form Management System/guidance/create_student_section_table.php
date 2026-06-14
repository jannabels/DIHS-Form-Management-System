<?php
// Include database connection
include '../db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

$response = [
    'success' => false,
    'messages' => []
];

try {
    // Check if table already exists
    $result = $conn->query("SHOW TABLES LIKE 'student_section'");
    if ($result->num_rows > 0) {
        throw new Exception('Table student_section already exists');
    }
    
    // SQL to create the student_section table
    $sql = "CREATE TABLE `student_section` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `lrn` varchar(12) NOT NULL,
        `section_id` int(11) NOT NULL,
        `sy` varchar(20) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_lrn` (`lrn`),
        KEY `idx_section_id` (`section_id`),
        KEY `idx_sy` (`sy`),
        CONSTRAINT `fk_student_section_sf1` FOREIGN KEY (`lrn`) REFERENCES `sf1` (`LRN`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_student_section_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Table student_section created successfully';
        
        // Verify the table structure
        $result = $conn->query("SHOW CREATE TABLE `student_section`");
        $response['table_structure'] = $result->fetch_assoc();
    } else {
        throw new Exception('Error creating table: ' . $conn->error);
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    
    // If table already exists, try to describe it
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $result = $conn->query("DESCRIBE `student_section`");
        $response['existing_table_structure'] = [];
        while ($row = $result->fetch_assoc()) {
            $response['existing_table_structure'][] = $row;
        }
    }
}

// Close connection
$conn->close();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
