<?php
// Include database connection
include '../db_connect.php';

// Check if user is logged in and has the right permissions
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit();
}

$message = '';
$sections = [];

// Fetch sections from database
try {
    $sql = "SELECT section_id, CONCAT(grade_level, ' - ', class_name, ' (', track, ')') as section_name 
            FROM section 
            ORDER BY grade_level, class_name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
    }
} catch (Exception $e) {
    $message = "Error fetching sections: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    // Check if a file was uploaded without errors
    if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] == 0) {
        $sectionId = $_POST['sectionId'] ?? 0;
        
        if ($sectionId <= 0) {
            $message = 'Please select a valid section';
        } else {
            // Prepare the file for upload
            $fileTmpPath = $_FILES['excelFile']['tmp_name'];
            $fileName = $_FILES['excelFile']['name'];
            $fileSize = $_FILES['excelFile']['size'];
            $fileType = $_FILES['excelFile']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            // Check file extension
            $allowedfileExtensions = array('xls', 'xlsx');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // First, get the section details
                $sectionName = '';
                $sectionQuery = $conn->prepare("SELECT grade_level, class_name, track FROM section WHERE section_id = ?");
                $sectionQuery->bind_param('i', $sectionId);
                if ($sectionQuery->execute()) {
                    $sectionResult = $sectionQuery->get_result();
                    if ($sectionResult->num_rows > 0) {
                        $sectionData = $sectionResult->fetch_assoc();
                        $sectionName = $sectionData['grade_level'] . ' - ' . $sectionData['class_name'] . 
                                     (!empty($sectionData['track']) ? ' (' . $sectionData['track'] . ')' : '');
                    }
                    $sectionQuery->close();
                }
                
                // Create a cURL file
                $cfile = new CURLFile($fileTmpPath, $fileType, $fileName);
                
                // Initialize cURL session
                $ch = curl_init();
                
                // Set the URL
                curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/import_students.php');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                // Set the POST data
                $postData = [
                    'sectionId' => $sectionId,
                    'sectionName' => $sectionName,
                    'excelFile' => $cfile
                ];
                
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                
                // Execute the request
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                // Close cURL session
                curl_close($ch);
                
                // Process the response
                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if ($result['success']) {
                            // Set success message in session and redirect
                            $_SESSION['import_message'] = "Successfully imported {$result['imported']} students to section: " . $sectionName;
                            $_SESSION['back_url'] = 'guidance_sf1.php';
                            header('Location: import_success.php');
                            exit();
                        } else {
                            $message = "Error: " . ($result['error'] ?? 'Unknown error occurred');
                        }
                    } else {
                        $message = "Error parsing server response: " . json_last_error_msg();
                    }
                } else {
                    $message = "Server returned HTTP code: $httpCode";
                    if ($response) {
                        $message .= "<br>Response: " . htmlspecialchars($response);
                    }
                }
            } else {
                $message = 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions);
            }
        }
    } else {
        $message = 'Error uploading file. Error code: ' . $_FILES['excelFile']['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Student Import</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Student Import</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form action="test_import.php" method="post" enctype="multipart/form-data" id="importForm">
                    <div class="form-group">
                        <label for="sectionId" class="form-label">Select Section</label>
                        <select class="form-select" id="sectionId" name="sectionId" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['section_id']); ?>">
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="excelFile" class="form-label">Excel File</label>
                        <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xls,.xlsx" required>
                        <div class="form-text">Upload an Excel file (.xls or .xlsx) with student data.</div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">Import Students</button>
                        <a href="guidance_sf1.php" class="btn btn-secondary">Back to List</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-4">
            <h4>Instructions:</h4>
            <ol>
                <li>Select the section where you want to import students.</li>
                <li>Upload an Excel file with student data.</li>
                <li>Click "Import Students" to start the import process.</li>
                <li>Check the import results on this page after submission.</li>
            </ol>
            
            <div class="alert alert-info">
                <strong>Note:</strong> The Excel file should be in the same format as the export template.
                <a href="export_to_excel.php" class="alert-link">Download Template</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side validation
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('excelFile');
            const filePath = fileInput.value;
            const allowedExtensions = /(\.xls|\.xlsx)$/i;
            
            if (!allowedExtensions.exec(filePath)) {
                e.preventDefault();
                alert('Please upload a valid Excel file (.xls or .xlsx)');
                fileInput.value = '';
                return false;
            }
            
            const sectionId = document.getElementById('sectionId').value;
            if (!sectionId) {
                e.preventDefault();
                alert('Please select a section');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importing...';
            
            return true;
        });
    </script>
</body>
</html>