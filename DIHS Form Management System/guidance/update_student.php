<?php
// update_student.php
include '../db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

try {
    // Get form data
    $id = $_POST['id'] ?? null;
    $lrn = $_POST['lrn'] ?? '';
    
    // Handle name fields - combine last_name, first_name, middle_name, name_extension
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $name_extension = trim($_POST['name_extension'] ?? '');
    
    // Build name in format: "Last Name, First Name Middle Initial"
    $name_parts = [];
    if (!empty($last_name)) {
        $name_parts[] = $last_name;
    }
    if (!empty($first_name) || !empty($middle_name)) {
        $first_middle = trim($first_name . ' ' . $middle_name);
        if (!empty($first_middle)) {
            $name_parts[] = $first_middle;
        }
    }
    $name = !empty($name_parts) ? implode(', ', $name_parts) : '';
    if (!empty($name_extension)) {
        $name .= ' ' . $name_extension;
    }
    $name = trim($name);
    
    // Handle sex field - convert 'Male'/'Female' to 'M'/'F' if needed
    $sex = $_POST['sex'] ?? '';
    if ($sex === 'Male') {
        $sex = 'M';
    } elseif ($sex === 'Female') {
        $sex = 'F';
    }
    
    $birthdate = $_POST['birthdate'] ?? null;
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $religious_affiliation = $_POST['religion'] ?? $_POST['religious_affiliation'] ?? null;
    $contact_number = $_POST['contact_number'] ?? $_POST['guardian_contact'] ?? null;
    
    // Additional fields from the edit modal
    $address = $_POST['address'] ?? null;
    $barangay = $_POST['barangay'] ?? null;
    // Handle both 'city' and 'municipality' field names
    $city = $_POST['municipality'] ?? $_POST['city'] ?? null;
    $province = $_POST['province'] ?? null;
    // Handle both 'father_name' and 'fathers_name'
    $fathers_name = $_POST['father_name'] ?? $_POST['fathers_name'] ?? null;
    // Handle both 'mother_maiden_name' and 'mothers_name'
    $mothers_name = $_POST['mother_maiden_name'] ?? $_POST['mothers_name'] ?? null;
    $guardian_name = $_POST['guardian_name'] ?? null;
    $guardian_relationship = $_POST['guardian_relationship'] ?? null;
    $remarks = $_POST['remarks'] ?? null;

    // Basic validation
    if (empty($lrn)) {
        $response['errors']['lrn'] = 'LRN is required';
    }

    if (empty($name)) {
        $response['errors']['name'] = 'Name is required';
    }

    if (!empty($sex) && !in_array($sex, ['M', 'F'])) {
        $response['errors']['sex'] = 'Invalid gender';
    }

    // If there are validation errors, return them
    if (!empty($response['errors'])) {
        http_response_code(400);
        $response['message'] = 'Validation failed';
        echo json_encode($response);
        exit;
    }

    // Prepare the SQL query with all fields
    $query = "UPDATE sf1 SET 
                LRN = ?,
                `Name` = ?,
                Sex = ?,
                Birthdate = ?,
                Age = ?,
                Religious_Affiliation = ?,
                House_Street_Sitio_Purok = ?,
                Barangay = ?,
                Municipality_City = ?,
                Province = ?,
                Fathers_Name = ?,
                Mothers_Maiden_Name = ?,
                `Name(Guardian)` = ?,
                Relationship = ?,
                Contact_Number = ?,
                Remarks = ?
              WHERE LRN = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        'sssisssssssssssss',
        $lrn,
        $name,
        $sex,
        $birthdate,
        $age,
        $religious_affiliation,
        $address,
        $barangay,
        $city,
        $province,
        $fathers_name,
        $mothers_name,
        $guardian_name,
        $guardian_relationship,
        $contact_number,
        $remarks,
        $lrn // For WHERE clause
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Student updated successfully';

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'An error occurred while updating the student';
    $response['error'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>