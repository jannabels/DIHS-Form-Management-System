<?php
// get_student_by_lrn.php
include '../db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => null, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $response['error'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

try {
    // Get LRN from POST or GET
    $lrn = $_POST['lrn'] ?? $_GET['lrn'] ?? '';
    
    if (empty($lrn)) {
        $response['error'] = 'LRN is required';
        echo json_encode($response);
        exit;
    }
    
    // Fetch student data from sf1 table
    $stmt = $conn->prepare("
        SELECT * FROM sf1 
        WHERE LRN = ?
    ");
    $stmt->bind_param('s', $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['error'] = 'Student not found';
        echo json_encode($response);
        exit;
    }
    
    $student = $result->fetch_assoc();
    
    // Parse Name field: Format is typically "Last Name, First Name Middle Initial"
    $name = $student['Name'] ?? '';
    $last_name = '';
    $first_name = '';
    $middle_name = '';
    $name_extension = '';
    
    if (!empty($name)) {
        // Split by comma to separate last name
        $nameParts = explode(',', $name, 2);
        $last_name = trim($nameParts[0] ?? '');
        
        if (isset($nameParts[1])) {
            $firstMiddle = trim($nameParts[1]);
            // Split by spaces
            $fmParts = preg_split('/\s+/', $firstMiddle);
            
            // Last part might be extension (Jr., Sr., II, III, etc.) or middle initial
            // Check if last part looks like an extension
            $lastPart = end($fmParts);
            if (preg_match('/^(Jr\.?|Sr\.?|II|III|IV|V|VI|VII|VIII|IX|X)$/i', $lastPart)) {
                $name_extension = $lastPart;
                array_pop($fmParts); // Remove extension from array
            }
            
            // First part is first name, rest is middle name
            if (count($fmParts) > 0) {
                $first_name = array_shift($fmParts);
                $middle_name = implode(' ', $fmParts);
            }
        }
    }
    
    // Convert sex from 'M'/'F' to 'Male'/'Female' for form
    $sex = $student['Sex'] ?? '';
    if ($sex === 'M') {
        $sex = 'Male';
    } elseif ($sex === 'F') {
        $sex = 'Female';
    }
    
    // Map database fields to form field names
    $formData = [
        'lrn' => $student['LRN'] ?? '',
        'last_name' => $last_name,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'name_extension' => $name_extension,
        'sex' => $sex,
        'birthdate' => $student['Birthdate'] ?? '',
        'age' => $student['Age'] ?? '',
        'religion' => $student['Religious_Affiliation'] ?? '',
        'address' => $student['House_Street_Sitio_Purok'] ?? '',
        'barangay' => $student['Barangay'] ?? '',
        'municipality' => $student['Municipality_City'] ?? '',
        'province' => $student['Province'] ?? '',
        'father_name' => $student['Fathers_Name'] ?? '',
        'mother_maiden_name' => $student['Mothers_Maiden_Name'] ?? '',
        'guardian_name' => $student['Name(Guardian)'] ?? '',
        'guardian_relationship' => $student['Relationship'] ?? '',
        'guardian_contact' => $student['Contact_Number'] ?? '',
        'contact_number' => $student['Contact_Number'] ?? '',
        'remarks' => $student['Remarks'] ?? ''
    ];
    
    $response['success'] = true;
    $response['data'] = $formData;
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = 'Error fetching student data: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>

