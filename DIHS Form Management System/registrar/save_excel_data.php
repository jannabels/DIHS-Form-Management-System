<?php
// Include database connection
include '../db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'error' => '');

// Get the JSON data from the request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Define the column mapping to match table headers with database columns
$columns = [
    'LRN',
    'Name',
    'Sex',
    'Birthdate',
    'Age',
    'Religious_Affiliation',
    'House_Street_Sitio_Purok',
    'Barangay',
    'Municipality_City',
    'Province',
    'Fathers_Name',
    'Mothers_Maiden_Name',
    'Name(Guardian)',
    'Relationship',
    'Contact_Number',
    'Remarks'
];

try {
    // Fetch existing data from the database
    $query = "SELECT LRN, Name, Sex, Birthdate, Age, Religious_Affiliation, House_Street_Sitio_Purok, 
                     Barangay, Municipality_City, Province, Fathers_Name, Mothers_Maiden_Name, 
                     `Name(Guardian)`, Relationship, Contact_Number, Remarks 
              FROM sf1 
              ORDER BY LRN ASC";
    $result = $conn->query($query);
    
    $existingData = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existingData[$row['LRN']] = $row;
        }
        $result->free();
    } else {
        throw new Error("Failed to fetch existing data: " . $conn->error);
    }

    // Begin transaction to ensure atomicity
    $conn->begin_transaction();

    // Process each row of submitted data
    foreach ($data as $row) {
        // Ensure row has enough columns
        if (count($row) !== count($columns)) {
            throw new Exception("Invalid number of columns in row data");
        }

        // Map row data to column names
        $rowData = array_combine($columns, array_map(function($value) {
            return $value === '' ? null : $value;
        }, $row));

        $lrn = $rowData['LRN'];

        // Skip rows with no LRN
        if (empty($lrn)) {
            continue;
        }

        if (isset($existingData[$lrn])) {
            // Update existing record
            $existingRow = $existingData[$lrn];
            $updates = [];
            $params = [];
            $types = '';

            foreach ($columns as $col) {
                if ($col === 'LRN') {
                    continue; // Skip LRN as it's the identifier
                }

                // Compare values (handle NULL cases)
                $newValue = $rowData[$col];
                $existingValue = $existingRow[$col];

                if ($newValue !== $existingValue) {
                    $updates[] = "`$col` = ?";
                    $params[] = $newValue;
                    $types .= 's'; // Treat all values as strings (NULL is handled by ?)
                }
            }

            if (!empty($updates)) {
                $updateQuery = "UPDATE sf1 SET " . implode(', ', $updates) . " WHERE LRN = ?";
                $params[] = $lrn;
                $types .= 's';

                $stmt = $conn->prepare($updateQuery);
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();
            }

            // Remove from existingData to track processed rows
            unset($existingData[$lrn]);
        } else {
            // Insert new record
            $placeholders = array_fill(0, count($columns), '?');
            $insertQuery = "INSERT INTO sf1 (" . implode(', ', array_map(function($col) {
                return "`$col`";
            }, $columns)) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $conn->prepare($insertQuery);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $params = array_values($rowData);
            $types = str_repeat('s', count($columns)); // Treat all as strings (NULL is handled)
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    // Commit transaction
    $conn->commit();
    $response['success'] = true;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['error'] = $e->getMessage();
}

// Close database connection
$conn->close();

// Output JSON response
echo json_encode($response);
?>