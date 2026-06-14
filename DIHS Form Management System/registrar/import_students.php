<?php
// Ensure no output is sent before headers
if (ob_get_level()) ob_clean();

// Set headers to prevent caching and ensure JSON response
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json; charset=utf-8');

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to user, log them instead
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/import_errors.log');

// Include Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Use statements must be at the top of the file
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Start output buffering to catch any unwanted output
ob_start();

// Function to log messages with timestamp and backtrace
function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/import_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = isset($backtrace[1]) ? 
        basename($backtrace[1]['file']) . ':' . $backtrace[1]['line'] . ' - ' . 
        ($backtrace[1]['function'] ?? '') : 'global';
    
    $logMessage = "[$timestamp] [$caller] $message\n";
    error_log($logMessage, 3, $logFile);
    
    // Also log to PHP error log for visibility
    error_log(trim($logMessage));
}

// Function to send JSON response
function sendJsonResponse($success, $data = [], $error = '') {
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Prepare response
    $response = ['success' => $success];
    if ($success) {
        $response = array_merge($response, $data);
    } else {
        $response['error'] = $error;
    }
    
    // Encode and output
    $json = json_encode($response);
    if ($json === false) {
        // Fallback error if json_encode fails
        $response = [
            'success' => false,
            'error' => 'Failed to encode response: ' . json_last_error_msg()
        ];
        $json = json_encode($response);
    }
    
    // Log the response
    logMessage('Sending JSON response: ' . $json);
    
    echo $json;
    exit;
}

// Enable error logging for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/import_errors.log');

try {
    // Include database connection
    logMessage('=== Starting Import Process ===');
    logMessage('Including database connection');
    include '../db_connect.php';
    
    // Verify database connection
    if ($conn->connect_error) {
        $error = 'Database connection failed: ' . $conn->connect_error;
        logMessage($error);
        throw new Exception($error);
    }
    logMessage('Database connection successful');
    
    // Log POST and FILES data for debugging
    logMessage('POST data: ' . print_r($_POST, true));
    logMessage('FILES data: ' . print_r($_FILES, true));
    
    // Check if file was uploaded and section is selected
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        $error = 'No file uploaded or file upload error: ' . ($_FILES['excelFile']['error'] ?? 'No file');
        logMessage($error);
        throw new Exception($error);
    }
    logMessage('File upload check passed');
    
    // Get section ID from form data
    $sectionId = isset($_POST['sectionId']) ? trim($_POST['sectionId']) : '';
    if (empty($sectionId)) {
        throw new Exception('Section ID is required');
    }
    logMessage("Section ID from form: $sectionId");

    // Get section name from database
    $sectionStmt = $conn->prepare("SELECT class_name FROM section WHERE section_id = ?");
    if ($sectionStmt === false) {
        throw new Exception('Failed to prepare section statement: ' . $conn->error);
    }
    $sectionStmt->bind_param('i', $sectionId);
    if (!$sectionStmt->execute()) {
        throw new Exception('Failed to fetch section: ' . $sectionStmt->error);
    }
    $sectionResult = $sectionStmt->get_result();
    if ($sectionResult->num_rows === 0) {
        throw new Exception('Section not found with ID: ' . $sectionId);
    }
    $sectionData = $sectionResult->fetch_assoc();
    $sectionName = $sectionData['class_name'];
    $sectionStmt->close();
    logMessage("Section name resolved: " . $sectionName);

    // Verify section exists
    logMessage('Checking if section exists: ' . $sectionId);
    $stmt = $conn->prepare("SELECT section_id FROM section WHERE section_id = ?");
    if ($stmt === false) {
        $error = 'Failed to prepare section check statement: ' . $conn->error;
        logMessage($error);
        throw new Exception($error);
    }
    
    $bound = $stmt->bind_param('i', $sectionId);
    if ($bound === false) {
        $error = 'Failed to bind section ID parameter: ' . $stmt->error;
        logMessage($error);
        throw new Exception($error);
    }
    
    $executed = $stmt->execute();
    if ($executed === false) {
        $error = 'Failed to execute section check query: ' . $stmt->error;
        logMessage($error);
        throw new Exception($error);
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        $error = 'Failed to get result set: ' . $stmt->error;
        logMessage($error);
        throw new Exception($error);
    }
    
    if ($result->num_rows === 0) {
        $error = 'Selected section does not exist (ID: ' . $sectionId . ')';
        logMessage($error);
        throw new Exception($error);
    }
    
    logMessage('Section verified successfully');

    $file = $_FILES['excelFile']['tmp_name'];
    $fileType = pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION);
    $allowedTypes = ['xls', 'xlsx', 'csv'];
    
    if (!in_array(strtolower($fileType), $allowedTypes)) {
        throw new Exception('Invalid file type. Only Excel and CSV files are allowed.');
    }

    // Load the spreadsheet with detailed error handling
    logMessage('Loading spreadsheet file: ' . $file);
    logMessage('File exists: ' . (file_exists($file) ? 'Yes' : 'No'));
    logMessage('File readable: ' . (is_readable($file) ? 'Yes' : 'No'));
    logMessage('File size: ' . filesize($file) . ' bytes');
    
    try {
        // Check if file is readable
        if (!is_readable($file)) {
            throw new Exception('File is not readable. Check file permissions.');
        }
        
        // Check file size
        $maxSize = 10 * 1024 * 1024; // 10MB
        $fileSize = filesize($file);
        if ($fileSize > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size of 10MB');
        }
        
        // Try to load the file
        logMessage('Attempting to load spreadsheet with IOFactory...');
        $spreadsheet = IOFactory::load($file);
        logMessage('Successfully loaded spreadsheet');
        
        // Get active sheet
        $worksheet = $spreadsheet->getActiveSheet();
        logMessage('Active sheet: ' . $worksheet->getTitle());
        
        // Log some basic info about the spreadsheet
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        logMessage("Spreadsheet dimensions: $highestRow rows x $highestColumn columns");
        
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        $error = 'PhpSpreadsheet Reader Error: ' . $e->getMessage();
        logMessage($error);
        throw new Exception('Error reading spreadsheet: ' . $e->getMessage());
    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
        $error = 'PhpSpreadsheet Error: ' . $e->getMessage();
        logMessage($error);
        throw new Exception('Error processing spreadsheet: ' . $e->getMessage());
    } catch (Exception $e) {
        $error = 'Unexpected error: ' . $e->getMessage();
        logMessage($error);
        throw new Exception('Failed to process spreadsheet: ' . $e->getMessage());
    }
    
    // Define column mapping based on export template
    $columnMap = [
        'LRN' => 0,  // Column A
        'Name' => 2, // Column C (skipping B which is for numbering)
        'Sex' => 6,  // Column G (skipping D,E,F which are empty)
        'Birthdate' => 7,  // Column H
        'Age' => 9,  // Column J (skipping I which is empty)
        'Religious Affiliation' => 11,  // Column L (skipping K which is empty)
        'House No./ Street/ Sitio/ Purok' => 12,  // Column M
        'Barangay' => 13,  // Column N
        'Municipality/ City' => 17,  // Column R (skipping O,P,Q which are empty)
        'Province' => 20,  // Column U (skipping S,T which are empty)
        'Father\'s Name' => 22,  // Column W (skipping V which is empty)
        'Mother\'s Maiden Name' => 23,  // Column X
        'Name (Guardian)' => 25,  // Column Z (skipping Y which is empty)
        'Relationship' => 28,  // Column AC (skipping AA,AB which are empty)
        'Contact Number' => 29,  // Column AD
        'Remarks' => 30  // Column AE (0-based index 30)
    ];
    
    // Process male students (rows 11-51)
    $maleRows = [];
    logMessage('Processing male students (rows 11-51)');
    for ($row = 11; $row <= 51; $row++) {
        $rowData = array_fill(0, 31, ''); // Initialize with empty strings for all columns
        $hasData = false;
        
        // Only process if the row has data in the name column (column C, index 2)
        $nameCell = $worksheet->getCellByColumnAndRow(3, $row); // Column C is 1-based index 3
        if ($nameCell && trim($nameCell->getValue()) !== '') {
            $hasData = true;
            
            // Process each mapped column
            foreach ($columnMap as $col) {
                try {
                    $cell = $worksheet->getCellByColumnAndRow($col + 1, $row); // +1 because getCellByColumnAndRow is 1-based
                    $cellValue = $cell->getValue();
                    $rowData[$col] = $cellValue !== null ? trim($cellValue) : '';
                } catch (Exception $e) {
                    logMessage("Error reading cell at column " . ($col + 1) . " row $row: " . $e->getMessage());
                    $rowData[$col] = '';
                }
            }
            
            // Ensure sex is set to Male for this section
            $rowData[$columnMap['Sex']] = 'M';
            
            // Log the student being processed
            logMessage("Found male student at row $row: " . $rowData[$columnMap['Name']]);
        }
        
        if ($hasData) {
            $maleRows[] = $rowData;
        }
    }
    logMessage('Found ' . count($maleRows) . ' male students');
    
    // Process female students (rows 52-92)
    $femaleRows = [];
    logMessage('Processing female students (rows 52-92)');
    for ($row = 52; $row <= 92; $row++) {
        $rowData = array_fill(0, 31, ''); // Initialize with empty strings for all columns
        $hasData = false;
        
        // Only process if the row has data in the name column (column C, index 2)
        $nameCell = $worksheet->getCellByColumnAndRow(3, $row); // Column C is 1-based index 3
        if ($nameCell && trim($nameCell->getValue()) !== '') {
            $hasData = true;
            
            // Process each mapped column
            foreach ($columnMap as $col) {
                try {
                    $cell = $worksheet->getCellByColumnAndRow($col + 1, $row); // +1 because getCellByColumnAndRow is 1-based
                    $cellValue = $cell->getValue();
                    $rowData[$col] = $cellValue !== null ? trim($cellValue) : '';
                } catch (Exception $e) {
                    logMessage("Error reading cell at column " . ($col + 1) . " row $row: " . $e->getMessage());
                    $rowData[$col] = '';
                }
            }
            
            // Ensure sex is set to Female for this section
            $rowData[$columnMap['Sex']] = 'F';
            
            // Log the student being processed
            logMessage("Found female student at row $row: " . $rowData[$columnMap['Name']]);
        }
        
        if ($hasData) {
            $femaleRows[] = $rowData;
        }
    }
    logMessage('Found ' . count($femaleRows) . ' female students');
    
    // Combine all rows
    $rows = array_merge($maleRows, $femaleRows);
    logMessage('Total rows to process: ' . count($rows));
    
    if (empty($rows)) {
        logMessage('No data found in the spreadsheet. Please check the file format.');
        throw new Exception('No data found in the spreadsheet. Please check the file format.');
    }
    
    // If no data found, try to read in a more flexible way
    if (empty($rows)) {
        logMessage('No rows found in the Excel file');
        $rows = $worksheet->toArray();
        // Remove header rows if they exist
        while (!empty($rows) && (empty($rows[0][0]) || stripos($rows[0][0], 'LRN') === false)) {
            array_shift($rows);
        }
        
        if (!empty($rows)) {
            $header = array_shift($rows);
            // Try to map columns based on header
            $headerMap = [];
            foreach ($header as $i => $col) {
                $col = strtolower(trim($col));
                if (strpos($col, 'lrn') !== false) $headerMap['LRN'] = $i;
                elseif (strpos($col, 'name') !== false && strpos($col, 'father') === false && strpos($col, 'mother') === false) $headerMap['Name'] = $i;
                elseif (strpos($col, 'sex') !== false || strpos($col, 'gender') !== false) $headerMap['Sex'] = $i;
                elseif (strpos($col, 'birth') !== false) $headerMap['Birthdate'] = $i;
                // Add more mappings as needed
            }
            
            if (count($headerMap) >= 3) { // At least LRN, Name, and Sex
                $mappedRows = [];
                foreach ($rows as $row) {
                    if (empty(implode('', $row))) continue;
                    
                    $mappedRow = array_fill(0, 16, ''); // Initialize with empty values
                    foreach ($headerMap as $field => $index) {
                        $mappedRow[array_search($field, array_keys($columnMap))] = $row[$index] ?? '';
                    }
                    $mappedRows[] = $mappedRow;
                }
                $rows = $mappedRows;
            }
        }
    }
    
    logMessage('Found ' . count($rows) . ' rows to process');

    // First, check if table exists and has the correct structure
    $tableCheck = $conn->query("SHOW TABLES LIKE 'sf1'");
    if ($tableCheck->num_rows === 0) {
        throw new Exception('The sf1 table does not exist in the database.');
    }
    
    // Get column information
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM sf1");
    while ($column = $result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    logMessage('Database columns: ' . implode(', ', $columns));
    
    // Process each row
    $imported = 0;
    $errors = [];
    $rowCount = 0;
    
    // Get column information once
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM sf1");
    while ($column = $result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    logMessage('Database columns: ' . implode(', ', $columns));
    
    foreach ($rows as $index => $row) {
        $rowCount++;
        
        // Skip specific rows (51, 92, 93)
        if ($rowCount === 51 || $rowCount === 92 || $rowCount === 93) {
            logMessage("Skipping row $rowCount: Explicitly skipped row");
            continue;
        }
        
        try {
            // Check if this is a summary row or contains TOTAL FEMALE
            $rowContent = implode(' ', array_map('trim', $row));
            if (stripos($rowContent, 'TOTAL FEMALE') !== false || 
                stripos($rowContent, 'TOTAL MALE') !== false ||
                stripos($rowContent, 'TOTAL') !== false && (stripos($rowContent, 'FEMALE') !== false || stripos($rowContent, 'MALE') !== false)) {
                logMessage("Skipping summary row: " . substr($rowContent, 0, 100));
                continue;
            }
            
            $lrn = !empty($row[$columnMap['LRN']]) ? trim($row[$columnMap['LRN']]) : null;
            if (!$lrn) {
                logMessage("Skipping row $rowCount: Empty LRN");
                continue;
            }
            
            // Process name
            $name = !empty($row[$columnMap['Name']]) ? trim($row[$columnMap['Name']]) : '';
            
            // Process sex (M/F)
            $sex = !empty($row[$columnMap['Sex']]) ? strtoupper(substr(trim($row[$columnMap['Sex']]), 0, 1)) : '';
            
            // Process birthdate
            $birthdate = null;
            if (!empty($row[$columnMap['Birthdate']])) {
                $bd = $row[$columnMap['Birthdate']];
                if (is_numeric($bd)) {
                    $date = Date::excelToDateTimeObject($bd);
                    $birthdate = $date->format('Y-m-d');
                } else {
                    $date = date_create($bd);
                    $birthdate = $date ? $date->format('Y-m-d') : null;
                }
                logMessage("Processed birthdate: $bd -> $birthdate");
            }
            
            // Prepare data for insertion
            $data = [
                $lrn,
                $name,
                $sex,
                $birthdate,
                !empty($row[$columnMap['Age']]) ? intval($row[$columnMap['Age']]) : null,
                !empty($row[$columnMap['Religious Affiliation']]) ? trim($row[$columnMap['Religious Affiliation']]) : null,
                !empty($row[$columnMap['House No./ Street/ Sitio/ Purok']]) ? trim($row[$columnMap['House No./ Street/ Sitio/ Purok']]) : null,
                !empty($row[$columnMap['Barangay']]) ? trim($row[$columnMap['Barangay']]) : null,
                !empty($row[$columnMap['Municipality/ City']]) ? trim($row[$columnMap['Municipality/ City']]) : null,
                !empty($row[$columnMap['Province']]) ? trim($row[$columnMap['Province']]) : null,
                !empty($row[$columnMap["Father's Name"]]) ? trim($row[$columnMap["Father's Name"]]) : null,
                !empty($row[$columnMap["Mother's Maiden Name"]]) ? trim($row[$columnMap["Mother's Maiden Name"]]) : null,
                !empty($row[$columnMap['Name (Guardian)']]) ? trim($row[$columnMap['Name (Guardian)']]) : null,
                !empty($row[$columnMap['Relationship']]) ? trim($row[$columnMap['Relationship']]) : null,
                !empty($row[$columnMap['Contact Number']]) ? trim($row[$columnMap['Contact Number']]) : null,
                !empty($row[$columnMap['Remarks']]) ? trim($row[$columnMap['Remarks']]) : null,
                '2024-2025', // sy
                $sectionName, // section
            ];
            
            // Prepare SQL query
            $sql = "INSERT INTO sf1 (
                LRN, Name, Sex, Birthdate, Age, Religious_Affiliation, 
                House_Street_Sitio_Purok, Barangay, Municipality_City, Province, 
                Fathers_Name, Mothers_Maiden_Name, `Name(Guardian)`, Relationship, 
                Contact_Number, Remarks, sy, section
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                Name = VALUES(Name),
                Sex = VALUES(Sex),
                Birthdate = VALUES(Birthdate),
                Age = VALUES(Age),
                Religious_Affiliation = VALUES(Religious_Affiliation),
                House_Street_Sitio_Purok = VALUES(House_Street_Sitio_Purok),
                Barangay = VALUES(Barangay),
                Municipality_City = VALUES(Municipality_City),
                Province = VALUES(Province),
                Fathers_Name = VALUES(Fathers_Name),
                Mothers_Maiden_Name = VALUES(Mothers_Maiden_Name),
                `Name(Guardian)` = VALUES(`Name(Guardian)`),
                Relationship = VALUES(Relationship),
                Contact_Number = VALUES(Contact_Number),
                Remarks = VALUES(Remarks),
                sy = VALUES(sy),
                section = VALUES(section),
                updated_at = NOW()";
                
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert/update student in sf1 table
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    throw new Exception('Failed to prepare student statement: ' . $conn->error);
                }
                
                // Bind parameters for student data
                $types = str_repeat('s', count($data));
                $stmt->bind_param($types, ...$data);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to execute student statement: ' . $stmt->error);
                }
                
                // First, check for any SF9 record for this LRN (regardless of SY)
                $checkSf9Sql = "SELECT sf9_id, sy FROM sf9 WHERE LRN = ? ORDER BY 
                               CASE WHEN sy IS NOT NULL THEN 0 ELSE 1 END, created_at DESC LIMIT 1";
                $checkStmt = $conn->prepare($checkSf9Sql);
                if ($checkStmt === false) {
                    $error = 'Failed to prepare SF9 check statement: ' . $conn->error;
                    logMessage($error);
                    throw new Exception($error);
                }
                
                $checkStmt->bind_param('s', $lrn);
                if (!$checkStmt->execute()) {
                    $error = 'Failed to execute SF9 check: ' . $checkStmt->error;
                    logMessage($error);
                    $checkStmt->close();
                    throw new Exception($error);
                }
                
                $sf9Result = $checkStmt->get_result();
                $sf9Record = $sf9Result->fetch_assoc();
                $checkStmt->close();
                
                if ($sf9Record) {
                    // If a record exists (regardless of SY), update it
                    $sf9Id = $sf9Record['sf9_id'];
                    $currentSy = $sf9Record['sy'];
                    
                    logMessage("Updating existing SF9 record ID: $sf9Id for LRN: $lrn (current SY: " . ($currentSy ?? 'NULL') . ")");
                    
                    // Update the existing record with the new section and SY
                    $sf9Sql = "UPDATE sf9 SET section = ?, sy = '2024-2025', updated_at = NOW() WHERE sf9_id = ?";
                    $sf9Stmt = $conn->prepare($sf9Sql);
                    if ($sf9Stmt === false) {
                        $error = 'Failed to prepare SF9 update statement: ' . $conn->error;
                        logMessage($error);
                        throw new Exception($error);
                    }
                    
                    $bound = $sf9Stmt->bind_param('si', $sectionName, $sf9Id);
                } else {
                    // No record exists, insert a new one
                    logMessage("No SF9 record found for LRN: $lrn, inserting new record");
                    
                    $sf9Sql = "INSERT INTO sf9 (LRN, section, sy, status, grade_level) 
                              VALUES (?, ?, '2024-2025', 'New Student', 'Grade 11')";
                    $sf9Stmt = $conn->prepare($sf9Sql);
                    if ($sf9Stmt === false) {
                        $error = 'Failed to prepare SF9 insert statement: ' . $conn->error;
                        logMessage($error);
                        throw new Exception($error);
                    }
                    
                    $bound = $sf9Stmt->bind_param('ss', $lrn, $sectionName);
                }
                
                if ($bound === false) {
                    $error = 'Failed to bind SF9 parameters: ' . ($sf9Stmt->error ?? 'Unknown error');
                    logMessage($error);
                    $sf9Stmt->close();
                    throw new Exception($error);
                }
                
                $executed = $sf9Stmt->execute();
                if ($executed === false) {
                    $error = 'Failed to ' . ($sf9Exists ? 'update' : 'insert') . ' SF9 record: ' . $sf9Stmt->error;
                    logMessage($error);
                    $sf9Stmt->close();
                    throw new Exception($error);
                }
                
                $affectedRows = $sf9Stmt->affected_rows;
                logMessage("SF9 " . ($sf9Exists ? 'update' : 'insert') . " affected $affectedRows rows for LRN: $lrn");
                
                $sf9Stmt->close();
                
                // Commit the transaction
                $conn->commit();
                
                $imported++;
                logMessage("Successfully imported row $rowCount (LRN: $lrn) with section: $sectionName");
                
            } catch (Exception $e) {
                // Rollback the transaction in case of error
                $conn->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            $errorMsg = "Row $rowCount: " . $e->getMessage();
            $errors[] = $errorMsg;
            logMessage("Error processing row $rowCount: " . $e->getMessage());
        }
    }
    
    // After processing all rows
    // Get section name for the response
    $sectionName = '';
    $sectionQuery = $conn->prepare("SELECT CONCAT(grade_level, ' - ', class_name, ' (', track, ')') as section_name FROM section WHERE section_id = ?");
    if ($sectionQuery) {
        $sectionQuery->bind_param('i', $sectionId);
        $sectionQuery->execute();
        $sectionResult = $sectionQuery->get_result();
        if ($sectionResult->num_rows > 0) {
            $sectionData = $sectionResult->fetch_assoc();
            $sectionName = $sectionData['section_name'];
        }
        $sectionQuery->close();
    }
    
    $response = [
        'success' => true,
        'imported' => $imported,
        'total_processed' => $rowCount,
        'skipped' => $rowCount - $imported - count($errors),
        'section_id' => $sectionId,
        'sectionName' => $sectionName
    ];
    
    if (!empty($errors)) {
        $response['warnings'] = $errors;
        logMessage("Import completed with " . count($errors) . " warnings");
    } else {
        logMessage("Import completed successfully. Imported $imported records.");
    }
    
    // Send the response
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    // Log the full error with backtrace for debugging
    $errorMessage = 'Import Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
    error_log($errorMessage);
    
    // Ensure we send valid JSON even in case of errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred during import: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}