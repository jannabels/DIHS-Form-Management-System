<?php
// add_student.php

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Ensure no output before headers
while (ob_get_level()) ob_end_clean();

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type'); 

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize response array
$response = [
    'success' => false, 
    'error' => '',
    'debug' => []
];

// Function to send JSON response and exit
function sendJsonResponse($response) {
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set content type again in case it was modified
    header('Content-Type: application/json; charset=utf-8');
    
    // Encode the response
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if ($json_response === false) {
        // Fallback error response if json_encode fails
        $response = [
            'success' => false,
            'error' => 'Failed to encode response: ' . json_last_error_msg(),
            'original_data' => $response
        ];
        $json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // If still failing, send minimal response
        if ($json_response === false) {
            $json_response = '{"success":false,"error":"Critical error: Failed to generate response"}';
        }
    }
    
    // Set content length header
    header('Content-Length: ' . strlen($json_response));
    
    // Output the JSON response
    echo $json_response;
    exit();
}

try {
    // Log incoming request data for debugging
    error_log('Incoming POST data: ' . print_r($_POST, true));
    
    // Create database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "admindihs";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset('utf8mb4');
    
    // Start transaction
    $conn->begin_transaction();
    
    // Validate required fields
    if (empty($_POST['lrn'])) {
        throw new Exception('LRN is required');
    }
    
    // Get and sanitize POST data
    $lrn = trim($_POST['lrn']);
    $section_id = isset($_POST['section']) ? intval($_POST['section']) : null;
    $section_name = 'Unassigned';
    
    // Check if LRN is 12 digits
    if (!preg_match('/^\d{12}$/', $lrn)) {
        throw new Exception('LRN must be exactly 12 digits');
    }
    
    // If section is provided, get the section details
    if ($section_id) {
        $section_check = $conn->prepare("SELECT class_name, grade_level, track FROM section WHERE section_id = ?");
        if (!$section_check) {
            throw new Exception('Failed to prepare section check statement: ' . $conn->error);
        }
        
        $section_check->bind_param('i', $section_id);
        if (!$section_check->execute()) {
            throw new Exception('Failed to execute section check: ' . $section_check->error);
        }
        
        $section_result = $section_check->get_result();
        if ($section_row = $section_result->fetch_assoc()) {
            $section_name = $section_row['class_name'];
            // You can also store additional section info if needed
            // $grade_level = $section_row['grade_level'];
            // $track = $section_row['track'];
        } else {
            // If section_id is provided but not found, log a warning but continue
            error_log("Warning: Section ID $section_id not found in database");
            $section_name = 'Unassigned';
        }
        $section_check->close();
    }
    
    // Check if LRN already exists
    $check_sql = "SELECT LRN FROM sf1 WHERE LRN = ? FOR UPDATE";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception('Failed to prepare LRN check statement: ' . $conn->error);
    }
    
    $check_stmt->bind_param('s', $lrn);
    if (!$check_stmt->execute()) {
        throw new Exception('Failed to execute LRN check: ' . $check_stmt->error);
    }
    
    $result = $check_stmt->get_result();
    $is_update = ($result->num_rows > 0);
    $check_stmt->close();

    // Add debug logging for incoming POST data
error_log('Received POST data: ' . print_r($_POST, true));

// Validate required fields
$required_fields = ['lrn', 'last_name', 'first_name', 'sex', 'birthdate', 'age'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $response['error'] = "The field '$field' is required.";
        $response['debug']['missing_field'] = $field;
        sendJsonResponse($response);
    }
}

// Combine name fields
$lastName = trim($_POST['last_name'] ?? '');
$firstName = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$extension = trim($_POST['name_extension'] ?? '');

// Format the full name
$fullName = $lastName;
if (!empty($firstName)) $fullName .= ', ' . $firstName;
if (!empty($middleName)) $fullName .= ' ' . $middleName;
if (!empty($extension)) $fullName .= ' ' . $extension;

// Prepare data for database
$data = [
    'LRN' => $lrn,
    'Name' => $fullName,
    'Sex' => strtoupper($_POST['sex'] ?? 'M'),
    'Birthdate' => !empty($_POST['birthdate']) ? date('Y-m-d', strtotime($_POST['birthdate'])) : null,
    'Age' => !empty($_POST['age']) ? intval($_POST['age']) : 0,
    'Religious_Affiliation' => $_POST['religious_affiliation'] ?? '',
    'House_Street_Sitio_Purok' => $_POST['house_street_sitio_purok'] ?? '',
    'Barangay' => $_POST['barangay'] ?? '',
    'Municipality_City' => $_POST['municipality_city'] ?? '',
    'Province' => $_POST['province'] ?? '',
    'Fathers_Name' => $_POST['fathers_name'] ?? '',
    'Mothers_Maiden_Name' => $_POST['mothers_maiden_name'] ?? '',
    'Name(Guardian)' => $_POST['name_guardian'] ?? '',
    'Relationship' => $_POST['relationship'] ?? '',
    'Contact_Number' => $_POST['contact_number'] ?? '',
    'Remarks' => $_POST['remarks'] ?? '',
    'section' => $section_name,
    'sy' => '2024-2025' // Default value
];

// Log the data being prepared for database
error_log('Prepared data for database: ' . print_r($data, true));
    
    // Log the data being prepared for insertion
    error_log('Prepared data for database: ' . print_r($data, true));
    
    // Log the operation
    error_log(($is_update ? 'Updating' : 'Creating') . ' student record for LRN: ' . $lrn);

    // Prepare SQL for insert or update
    if ($is_update) {
        $sql = "UPDATE sf1 SET ";
        $types = '';
        $values = [];
        
        foreach ($data as $field => $value) {
            $sql .= "`$field` = ?, ";
            $types .= 's';
            $values[] = $value;
        }
        
        $sql = rtrim($sql, ', ') . " WHERE LRN = ?";
        $types .= 's';
        $values[] = $lrn;
    } else {
        $fields = implode('`, `', array_keys($data));
        $placeholders = rtrim(str_repeat('?,', count($data)), ',');
        $sql = "INSERT INTO sf1 (`$fields`) VALUES ($placeholders)";
        $types = str_repeat('s', count($data));
        $values = array_values($data);
        $sf1_types = '';
        $sf1_values = [];
        
        foreach ($data as $column => $value) {
            $sf1_columns[] = "`$column`";
            $sf1_placeholders[] = '?';
            $sf1_types .= ($column === 'Age') ? 'i' : 's';
            $sf1_values[] = $value;
        }
        
        // Check for duplicate LRN before insert
        $check_dup = $conn->prepare("SELECT LRN FROM sf1 WHERE LRN = ?");
        $check_dup->bind_param('s', $lrn);
        $check_dup->execute();
        $dup_result = $check_dup->get_result();
        
        if ($dup_result->num_rows > 0) {
            throw new Exception('A student with this LRN already exists');
        }
        $check_dup->close();
        
        // Use INSERT IGNORE to prevent auto-increment issues
        $sf1_query = "INSERT IGNORE INTO sf1 (" . implode(', ', $sf1_columns) . ") 
                     VALUES (" . implode(', ', $sf1_placeholders) . ")";
    }
    
    // Log the SQL query for debugging
    error_log('SQL Query: ' . $sf1_query);
    error_log('Parameters: ' . print_r($sf1_values, true));
    
    // Prepare and execute the statement
    $sf1_stmt = $conn->prepare($sf1_query);
    if ($sf1_stmt === false) {
        throw new Exception("Failed to prepare SF1 query: " . $conn->error);
    }
    
    // Bind parameters
    $bind_names[] = $sf1_types;
    for ($i = 0; $i < count($sf1_values); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $sf1_values[$i];
        $bind_names[] = &$$bind_name;
    }
    
    // Prepare the bind parameters array with references
    $bind_params = array_merge([$sf1_types], $sf1_values);
    $bind_names = [];
    
    // Create references for bind_param
    foreach ($bind_params as $key => $value) {
        $bind_name = 'bind' . $key;
        $$bind_name = $value;
        $bind_names[] = &$$bind_name;
    }
    
    // Log the bind parameters for debugging
    error_log('Bind parameters: ' . print_r($bind_params, true));
    
    // Bind parameters using call_user_func_array
    if (!empty($bind_names)) {
        $bind_result = call_user_func_array([$sf1_stmt, 'bind_param'], $bind_names);
        if ($bind_result === false) {
            throw new Exception("Failed to bind parameters: " . $sf1_stmt->error);
        }
    }
    
    // Execute the statement
    $execute_result = $sf1_stmt->execute();
    if ($execute_result === false) {
        throw new Exception("Failed to execute SF1 query: " . $sf1_stmt->error . "\nQuery: " . $sf1_query);
    }
    
    // For inserts, we don't need to get the insert_id since we're using LRN as primary key
    if (!$is_update) {
        $student_id = $lrn;
        
        // Create SF9 record for new student
        try {
            // Extract grade level from section name (e.g., 'Grade 11 - D STEM' -> '11')
            $grade_level = '11'; // Default to 11 if not found
            if (preg_match('/Grade\s+(\d+)/i', $section_name, $matches)) {
                $grade_level = $matches[1];
            }
            
            $sf9_query = "INSERT INTO sf9 
                         (LRN, section, status, grade_level, sy, created_at, updated_at) 
                         VALUES (?, ?, 'Enrolled', ?, '2024-2025', NOW(), NOW())";
            
            $sf9_stmt = $conn->prepare($sf9_query);
            if (!$sf9_stmt) {
                throw new Exception('Failed to prepare SF9 query: ' . $conn->error);
            }
            
            $sf9_stmt->bind_param('sss', $lrn, $section_name, $grade_level);
            if (!$sf9_stmt->execute()) {
                throw new Exception('Failed to create SF9 record: ' . $sf9_stmt->error);
            }
            $sf9_stmt->close();
            
            error_log("Successfully created SF9 record for LRN: $lrn");
            
        } catch (Exception $e) {
            error_log('Error creating SF9 record: ' . $e->getMessage());
            // Don't fail the entire operation if SF9 record creation fails
            // as it's not critical for the main student record
        }
    }
    
// Handle section assignment
$section_assigned = false;
$section_name = 'Unassigned'; // Default section name

// Check if section_id is provided in the form
$section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;

if ($section_id > 0) {
    // Get section details
    $section_check = $conn->prepare("SELECT class_name, grade_level, semester FROM section WHERE section_id = ?");
    if ($section_check) {
        $section_check->bind_param('i', $section_id);
        if ($section_check->execute()) {
            $section_result = $section_check->get_result();
            if ($section_row = $section_result->fetch_assoc()) {
                $section_name = $section_row['class_name'];
                $grade_level = $section_row['grade_level'] ?? '11'; // Default to 11 if not set
                
                // Update sf1 table with section
                $update_sf1 = $conn->prepare("UPDATE sf1 SET section = ? WHERE LRN = ?");
                if ($update_sf1) {
                    $update_sf1->bind_param('ss', $section_name, $lrn);
                    if ($update_sf1->execute()) {
                        $section_assigned = true;
                        error_log("Successfully assigned student $lrn to section $section_name");
                        
                        // Check if sf9 record exists
                        $check_sf9 = $conn->prepare("SELECT LRN FROM sf9 WHERE LRN = ?");
                        if ($check_sf9) {
                            $check_sf9->bind_param('s', $lrn);
                            $check_sf9->execute();
                            $sf9_exists = $check_sf9->get_result()->num_rows > 0;
                            $check_sf9->close();
                            
                            $current_year = date('Y');
                            $school_year = $current_year . '-' . ($current_year + 1);
                            
                            if ($sf9_exists) {
                                // Update existing sf9 record
                                $sf9_update = $conn->prepare("UPDATE sf9 SET 
                                    section = ?, 
                                    status = 'Enrolled',
                                    grade_level = ?,
                                    sy = ?,
                                    updated_at = NOW() 
                                    WHERE LRN = ?");
                                if ($sf9_update) {
                                    $sf9_update->bind_param('ssss', 
                                        $section_name, 
                                        $grade_level,
                                        $school_year,
                                        $lrn
                                    );
                                    if (!$sf9_update->execute()) {
                                        error_log('Failed to update SF9 record: ' . $sf9_update->error);
                                    }
                                    $sf9_update->close();
                                }
                            } else {
                                // Create new sf9 record
                                $sf9_insert = $conn->prepare("INSERT INTO sf9 
                                    (LRN, section, status, grade_level, sy, created_at, updated_at) 
                                    VALUES (?, ?, 'Enrolled', ?, ?, NOW(), NOW())");
                                if ($sf9_insert) {
                                    $sf9_insert->bind_param('ssss', 
                                        $lrn, 
                                        $section_name, 
                                        $grade_level,
                                        $school_year
                                    );
                                    if (!$sf9_insert->execute()) {
                                        error_log('Failed to create SF9 record: ' . $sf9_insert->error);
                                    }
                                    $sf9_insert->close();
                                }
                            }
                        }
                    } else {
                        error_log('Failed to update student section in sf1: ' . $update_sf1->error);
                    }
                    $update_sf1->close();
                }
            } else {
                error_log("Section ID $section_id not found in section table");
            }
        } else {
            error_log('Failed to fetch section details: ' . $section_check->error);
        }
        $section_check->close();
    } else {
        error_log('Failed to prepare section check: ' . $conn->error);
    }
}
    
    // Commit the transaction
    $conn->commit();
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => $is_update ? 'Student updated successfully' : 'Student added successfully',
        'lrn' => $lrn,
        'section_assigned' => $section_assigned,
        'section_name' => $section_name ?? 'Unassigned',
        'name' => $fullName
    ];
    
    // Log success
    error_log('Student record ' . ($is_update ? 'updated' : 'created') . ' successfully for LRN: ' . $lrn);
    
    // Send success response
    sendJsonResponse($response);
    
    // Exit to prevent any further output
    exit();
    
    // The parameters are already bound and executed above
    
    // Handle SF9 record if section is provided (for both new and existing students)
    if (!empty($_POST['section'] ?? '')) {
        $section = trim($_POST['section']);
        $school_year = '2024-2025';
        
        // Get section details for status message
        $section_check = $conn->prepare("SELECT grade_level, semester FROM section WHERE class_name = ?");
        if ($section_check === false) {
            throw new Exception('Failed to prepare section check: ' . $conn->error);
        }
        
        $section_check->bind_param('s', $section);
        if (!$section_check->execute()) {
            throw new Exception('Failed to get section details: ' . $section_check->error);
        }
        
        $section_result = $section_check->get_result();
        if ($section_result->num_rows === 0) {
            throw new Exception('Invalid section selected');
        }
        
        $section_data = $section_result->fetch_assoc();
        $status = "Assigned to " . $section_data['grade_level'] . " - " . $section_data['semester'];
        $section_check->close();
        
        // Check if SF9 record already exists
        $check_sf9_sql = "SELECT LRN FROM sf9 WHERE LRN = ?";
        $check_sf9 = $conn->prepare($check_sf9_sql);
        if ($check_sf9 === false) {
            throw new Exception('Failed to prepare SF9 check statement: ' . $conn->error);
        }
        
        $check_sf9->bind_param('s', $lrn);
        if (!$check_sf9->execute()) {
            throw new Exception('Failed to check SF9 record: ' . $check_sf9->error);
        }
        
        $sf9_result = $check_sf9->get_result();
        $sf9_exists = ($sf9_result->num_rows > 0);
        $check_sf9->close();
        
        // Prepare SF9 query - removed 'Name' from the query as it doesn't exist in the table
        if ($sf9_exists) {
            // Update existing SF9 record
            $sf9_sql = "UPDATE sf9 SET section = ?, status = ?, sy = ? WHERE LRN = ?";
        } else {
            // Insert new SF9 record
            $sf9_sql = "INSERT INTO sf9 (LRN, section, status, sy) VALUES (?, ?, ?, ?)";
        }
        
        $sf9_stmt = $conn->prepare($sf9_sql);
        if ($sf9_stmt === false) {
            throw new Exception('Failed to prepare SF9 statement: ' . $conn->error);
        }
        
        if ($sf9_exists) {
            $sf9_stmt->bind_param('ssss', $section, $status, $school_year, $lrn);
        } else {
            $sf9_stmt->bind_param('ssss', $lrn, $section, $status, $school_year);
        }
        
        if (!$sf9_stmt->execute()) {
            throw new Exception('Failed to update SF9 record: ' . $sf9_stmt->error);
        }
        
        // Update the section in SF1 as well
        $update_sf1_section = $conn->prepare("UPDATE sf1 SET section = ? WHERE LRN = ?");
        if ($update_sf1_section === false) {
            throw new Exception('Failed to prepare SF1 section update: ' . $conn->error);
        }
        
        $update_sf1_section->bind_param('ss', $section, $lrn);
        if (!$update_sf1_section->execute()) {
            // If the section column doesn't exist in SF1, we'll just log it and continue
            error_log('Warning: Could not update SF1 section - column may not exist: ' . $update_sf1_section->error);
        }
        $update_sf1_section->close();
    }
    
    // Log success
    error_log('Student record ' . ($is_update ? 'updated' : 'created') . ' successfully for LRN: ' . $lrn);
    
    // Send success response
    $response['success'] = true;
    $response['message'] = $is_update ? 'Student record updated successfully' : 'Student record created successfully';
    $response['lrn'] = $lrn;
    
    sendJsonResponse($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log the error with full stack trace
    error_log('Error in add_student.php: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Close connections if they exist
    if (isset($sf1_stmt)) {
        $sf1_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    
    // Return error response with HTTP 500 status
    http_response_code(500);
    $response['error'] = 'An error occurred: ' . $e->getMessage();
    $response['debug'] = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    // Send error response
    sendJsonResponse($response);
    
    // Log the error
    error_log('Error in add_student.php: ' . $e->getMessage());
    error_log('Error details: ' . print_r([
        'post_data' => $_POST,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], true));
    
    exit();
} finally {
    // Close statements and connection
    if (isset($sf1_stmt) && $sf1_stmt) $sf1_stmt->close();
    if (isset($check_stmt) && $check_stmt) $check_stmt->close();
    if (isset($sf9_stmt) && $sf9_stmt) $sf9_stmt->close();
    if (isset($check_sf9) && $check_sf9) $check_sf9->close();
    if (isset($conn) && $conn) $conn->close();
}
