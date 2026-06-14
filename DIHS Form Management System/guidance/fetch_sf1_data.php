<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'data' => array(), 'error' => '');

try {
    // Check database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get section_id from query parameters if provided
    $section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;

    // Prepare and execute query to fetch records from sf1 table
    $query = "SELECT s.LRN, s.Name, s.Sex, s.Birthdate, s.Age, s.Religious_Affiliation, 
                     s.House_Street_Sitio_Purok, s.Barangay, s.Municipality_City, s.Province, 
                     s.Fathers_Name, s.Mothers_Maiden_Name, s.`Name(Guardian)`, 
                     s.Relationship, s.Contact_Number, s.Remarks, s.section,
                     sec.section_id, sec.class_name, sec.grade_level
              FROM sf1 s
              LEFT JOIN section sec ON s.section = sec.class_name
              WHERE 1=1";
    
    // Add section filter if section_id is provided
    if ($section_id) {
        // Get the section details
        $section_query = "SELECT class_name, grade_level, track, semester 
                         FROM section 
                         WHERE section_id = ?";
        $stmt = $conn->prepare($section_query);
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $bind_result = $stmt->bind_param("i", $section_id);
        if ($bind_result === false) {
            throw new Exception('Bind param failed: ' . $stmt->error);
        }
        
        $execute_result = $stmt->execute();
        if ($execute_result === false) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $section_result = $stmt->get_result();
        
        if ($section_result->num_rows > 0) {
            $section_data = $section_result->fetch_assoc();
            $section_name = $section_data['class_name'];
            
            // Debug info
            error_log("Filtering for Section - ID: $section_id, Name: $section_name");
            
            // Prepare the main query with parameter binding
            $query = "SELECT s.LRN, s.Name, s.Sex, s.Birthdate, s.Age, s.Religious_Affiliation, 
                             s.House_Street_Sitio_Purok, s.Barangay, s.Municipality_City, s.Province, 
                             s.Fathers_Name, s.Mothers_Maiden_Name, s.`Name(Guardian)`, 
                             s.Relationship, s.Contact_Number, s.Remarks, s.section,
                             sec.section_id, sec.class_name, sec.grade_level
                      FROM sf1 s
                      LEFT JOIN section sec ON s.section = sec.class_name
                      WHERE s.section = ?
                      ORDER BY s.Name ASC";
            
            // Prepare and execute the main query
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            $bind_result = $stmt->bind_param("s", $section_name);
            if ($bind_result === false) {
                throw new Exception('Bind param failed: ' . $stmt->error);
            }
            
            $execute_result = $stmt->execute();
            if ($execute_result === false) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            error_log("Successfully retrieved " . $result->num_rows . " students for section: " . $section_name);
        } else {
            error_log("Section not found for ID: $section_id");
            $response['error'] = "Section not found";
            echo json_encode($response);
            exit();
        }
        $stmt->close();
    } else {
        // If no section_id provided, order all records by name
        $query .= " ORDER BY s.Name ASC";
        $result = $conn->query($query);
        
        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }
        error_log("Retrieved " . $result->num_rows . " students (all sections)");
    }
    
    $data = array();
    if ($result->num_rows > 0) {
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
        $response['debug'] = [
            'query' => $query,
            'num_rows' => $result->num_rows,
            'data_count' => count($data)
        ];
    } else {
        $response['success'] = true;
        $response['data'] = [];
        $response['message'] = 'No records found for this section';
    }
    
    // Free result set
    if ($result) {
        $result->free();
    }
    
} catch (Exception $e) {
    $response['error'] = "Exception: " . $e->getMessage();
    error_log("Error in fetch_sf1_data.php: " . $e->getMessage());
}

// Close database connection
$conn->close();

// Output JSON response
echo json_encode($response);
?>