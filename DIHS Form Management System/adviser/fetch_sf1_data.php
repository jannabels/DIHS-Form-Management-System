<?php
// Include database connection
include '../db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'data' => array(), 'error' => '');

try {
    // Prepare and execute query to fetch all records from sf1 table
    $query = "SELECT LRN, Name, Sex, Birthdate, Age, Religious_Affiliation, House_Street_Sitio_Purok, 
                     Barangay, Municipality_City, Province, Fathers_Name, Mothers_Maiden_Name, 
                     `Name(Guardian)`, Relationship, Contact_Number, Remarks 
              FROM sf1 
              ORDER BY LRN ASC";
    
    $result = $conn->query($query); 
    
    if ($result) {
        $data = array();
        while ($row = $result->fetch_assoc()) {
            // Map database columns to table columns in the same order as headers
            $data[] = array(
                $row['LRN'] ?? '',
                $row['Name'] ?? '',
                $row['Sex'] ?? '',
                $row['Birthdate'] ?? '',
                $row['Age'] ?? '',
                $row['Religious_Affiliation'] ?? '',
                $row['House_Street_Sitio_Purok'] ?? '',
                $row['Barangay'] ?? '',
                $row['Municipality_City'] ?? '',
                $row['Province'] ?? '',
                $row['Fathers_Name'] ?? '',
                $row['Mothers_Maiden_Name'] ?? '',
                $row['Name(Guardian)'] ?? '',
                $row['Relationship'] ?? '',
                $row['Contact_Number'] ?? '',
                $row['Remarks'] ?? ''
            );
        }
        
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['error'] = "Query failed: " . $conn->error;
    }
    
    // Free result set
    if ($result) {
        $result->free();
    }
    
} catch (Exception $e) {
    $response['error'] = "Exception: " . $e->getMessage();
}

// Close database connection
$conn->close();

// Output JSON response
echo json_encode($response);
?>