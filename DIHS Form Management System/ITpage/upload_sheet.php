<?php
// Include database connection
include '../db_connect.php';

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'error' => '', 'data' => []);

try {
    // Check if file was uploaded
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred.');
    }

    // Get the uploaded file
    $filePath = $_FILES['excelFile']['tmp_name'];

    // Load the spreadsheet
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();

    // Start reading from row 10
    $row = 10;

    while (true) {
        // Get LRN (cast to string)
        $lrn = (string) $worksheet->getCell('B' . $row)->getValue();

        // Stop if LRN is empty
        if (empty($lrn)) {
            break;
        }

        // Extract other fields
        $name = (string) $worksheet->getCell('C' . $row)->getValue();
        $sex = (string) $worksheet->getCell('G' . $row)->getValue();

        // Handle birthdate
        $birthdateVal = $worksheet->getCell('H' . $row)->getValue();
        $birthdate = null;
        if (is_numeric($birthdateVal)) {
            // Excel serial date
            $birthdate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($birthdateVal)->format('Y-m-d');
        } elseif (is_string($birthdateVal)) {
            $birthdateVal = trim($birthdateVal);
            $formats = ['m/d/Y', 'm/d/y', 'd/m/Y', 'd/m/y'];
            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $birthdateVal);
                if ($dt !== false) {
                    $birthdate = $dt->format('Y-m-d');
                    break;
                }
            }
        }

        // Handle age
        $ageVal = $worksheet->getCell('I' . $row)->getValue();
        $age = is_numeric($ageVal) ? (int)$ageVal : null;

        $religious_affiliation = (string) $worksheet->getCell('J' . $row)->getValue();
        $house_street_sitio_purok = (string) $worksheet->getCell('K' . $row)->getValue();
        $barangay = (string) $worksheet->getCell('L' . $row)->getValue();
        $municipality_city = (string) $worksheet->getCell('M' . $row)->getValue();
        $province = (string) $worksheet->getCell('N' . $row)->getValue();
        $fathers_name = (string) $worksheet->getCell('P' . $row)->getValue();
        $mothers_maiden_name = (string) $worksheet->getCell('R' . $row)->getValue();
        $name_guardian = (string) $worksheet->getCell('T' . $row)->getValue();
        $relationship = (string) $worksheet->getCell('U' . $row)->getValue();
        $contact_number = (string) $worksheet->getCell('V' . $row)->getValue();
        $remarks = (string) $worksheet->getCell('W' . $row)->getValue();

        // Check for duplicate LRN
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM sf1 WHERE LRN = ?");
        $checkStmt->bind_param("s", $lrn);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            // Skip duplicate
            if (!isset($response['debug'])) {
                $response['debug'] = [];
            }
            $response['debug'][] = "Skipped duplicate LRN: " . $lrn;
            $row++;
            continue;
        }

        // Insert new record
        $insertStmt = $conn->prepare("
            INSERT INTO sf1 (
                LRN, `Name`, Sex, Birthdate, Age, Religious_Affiliation, 
                House_Street_Sitio_Purok, Barangay, Municipality_City, Province, 
                Fathers_Name, Mothers_Maiden_Name, `Name(Guardian)`, Relationship, 
                Contact_Number, Remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param(
            "ssssisssssssssss",
            $lrn, $name, $sex, $birthdate, $age, $religious_affiliation,
            $house_street_sitio_purok, $barangay, $municipality_city, $province,
            $fathers_name, $mothers_maiden_name, $name_guardian, $relationship,
            $contact_number, $remarks
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Insert failed: " . $insertStmt->error);
        }
        $insertStmt->close();

        $row++;
    }

    // Fetch all current data from database for rendering
    $fetchQuery = "
        SELECT LRN, `Name`, Sex, Birthdate, Age, Religious_Affiliation, 
               House_Street_Sitio_Purok, Barangay, Municipality_City, Province, 
               Fathers_Name, Mothers_Maiden_Name, `Name(Guardian)`, Relationship, 
               Contact_Number, Remarks 
        FROM sf1
    ";
    $result = $conn->query($fetchQuery);

    if (!$result) {
        throw new Exception("Failed to fetch data: " . $conn->error);
    }

    $data = [];
    while ($dbRow = $result->fetch_assoc()) {
        // Format birthdate for display (mm/dd/yyyy)
        $dbRow['Birthdate'] = $dbRow['Birthdate'] ? date('m/d/Y', strtotime($dbRow['Birthdate'])) : '';

        // Push as array (matching the order)
        $data[] = [
            $dbRow['LRN'],
            $dbRow['Name'],
            $dbRow['Sex'],
            $dbRow['Birthdate'],
            $dbRow['Age'],
            $dbRow['Religious_Affiliation'],
            $dbRow['House_Street_Sitio_Purok'],
            $dbRow['Barangay'],
            $dbRow['Municipality_City'],
            $dbRow['Province'],
            $dbRow['Fathers_Name'],
            $dbRow['Mothers_Maiden_Name'],
            $dbRow['Name(Guardian)'],
            $dbRow['Relationship'],
            $dbRow['Contact_Number'],
            $dbRow['Remarks']
        ];
    }

    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>