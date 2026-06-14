<?php
include '../db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for import success in session
if (isset($_GET['import_success']) && isset($_SESSION['import_result'])) {
    $importResult = $_SESSION['import_result'];
    unset($_SESSION['import_result']); // Clear the session data
}

// Check if user is logged in and is a Guidance or Registrar user
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'Guidance' && $_SESSION['role'] !== 'Registrar')) {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Initialize section variables
$section_name = 'All Sections';
$section_grade = '';

// Debug: Log the received section_id
error_log("Received section_id: " . ($_GET['section_id'] ?? 'not set'));

// Check if section_id is provided in the URL
if (isset($_GET['section_id']) && !empty($_GET['section_id'])) {
    $section_id = $conn->real_escape_string($_GET['section_id']);
    
    // Debug: Log the query
    error_log("Fetching section with ID: $section_id");
    
    // Fetch section details from the database
    $section_query = "SELECT class_name, grade_level, track FROM section WHERE section_id = '$section_id' LIMIT 1";
    error_log("SQL Query: $section_query");
    $section_result = $conn->query($section_query);
    
    if ($section_result && $section_result->num_rows > 0) {
        $section_data = $section_result->fetch_assoc();
        error_log("Section data found: " . print_r($section_data, true));
        // Get raw values from database
        $grade_level = !empty($section_data['grade_level']) ? trim($section_data['grade_level']) : '';
        $class_name = !empty($section_data['class_name']) ? trim($section_data['class_name']) : '';
        $track = !empty($section_data['track']) ? trim($section_data['track']) : '';
        
        // Build the section name: "Grade 11 - STEM A" format (grade level and class name only)
        $section_name = '';
        
        // Ensure grade_level doesn't have duplicate "Grade" prefix
        $grade_display = trim($grade_level ?? '');
        if (stripos($grade_display, 'Grade') !== 0) {
            // Extract just the number if it exists
            if (preg_match('/(\d+)/', $grade_display, $matches)) {
                $grade_display = 'Grade ' . $matches[1];
            } else {
                $grade_display = !empty($grade_display) ? 'Grade ' . $grade_display : '';
            }
        }
        
        // Build section name with multiple possible formats
        if (!empty($grade_display) || !empty($class_name)) {
            // Try multiple formats to match different section naming conventions
            $possible_formats = [];
            
            if (!empty($grade_display) && !empty($class_name)) {
                $possible_formats[] = $grade_display . ' - ' . trim($class_name);  // "Grade 11 - STEM A"
                $possible_formats[] = trim($class_name) . ' - ' . $grade_display;  // "STEM A - Grade 11"
                
                if (!empty($track)) {
                    $possible_formats[] = $grade_display . ' - ' . $track . ' ' . trim($class_name);  // "Grade 11 - STEM A"
                    $possible_formats[] = $track . ' ' . $grade_display . ' - ' . trim($class_name);  // "STEM Grade 11 - A"
                    $possible_formats[] = $track . ' ' . $grade_display . '-' . trim($class_name);    // "STEM Grade 11-A"
                    $possible_formats[] = $track . ' ' . $grade_display . ' - ' . trim($class_name);  // "STEM Grade 11 - A"
                }
            }
            
            // Also add the raw values in case they're useful
            if (!empty($grade_display)) $possible_formats[] = $grade_display;
            if (!empty($class_name)) $possible_formats[] = trim($class_name);
            if (!empty($track)) $possible_formats[] = $track;
            
            // Log the possible formats for debugging
            error_log("Possible section name formats: " . implode(", ", $possible_formats));
            
            // Use the first non-empty format
            foreach ($possible_formats as $format) {
                if (!empty(trim($format))) {
                    $section_name = trim($format);
                    error_log("Selected section name format: " . $section_name);
                    break;
                }
            }
            
            // If still empty, use a simple combination
            if (empty($section_name)) {
                $section_name = trim(implode(' ', array_filter([$track, $grade_display, $class_name])));
                error_log("Using fallback section name: " . $section_name);
            }
        }
        
        // Clean up and escape the output
        $section_name = trim($section_name);
        $section_name = htmlspecialchars($section_name);
        error_log("Final section_name: $section_name");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Guidance - School Form 1</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Arial', sans-serif;
            padding-top: 4rem; /* Add padding to account for fixed navbar */
        }
        .excel-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-top: 1.5rem;
            max-height: calc(100vh - 220px);
            border: 1px solid #e5e7eb;
        }
        .excel-frame {
            width: 100%;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
            color: #374151;
        }
        .data-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .data-table th {
            background-color: #4f46e5;
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 1rem 1.25rem;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #4338ca;
        }
        .data-table th:first-child {
            border-top-left-radius: 12px;
        }
        .data-table th:last-child {
            border-top-right-radius: 12px;
        }
        .data-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            transition: all 0.2s ease;
        }
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        .data-table tbody tr {
            transition: all 0.2s ease;
        }
        .data-table tbody tr:hover {
            background-color: #f9fafb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tbody tr:hover td {
            background-color: #f0f4ff;
        }
        .data-table tbody tr:active {
            transform: translateY(0);
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .edit-btn {
            background-color: #3b82f6;
            color: white;
        }
        .edit-btn:hover {
            background-color: #2563eb;
        }
        .delete-btn {
            background-color: #ef4444;
            color: white;
        }
        .delete-btn:hover {
            background-color: #dc2626;
        }
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6b7280;
        }
        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #111827;
        }
        .empty-state p {
            margin-bottom: 24px;
            color: #6b7280;
        }
    </style>
    <style>
        /* Ensure proper layout with sidebar */
        #mainContent {
            width: calc(100% - 16rem);
            margin-left: 16rem;
            transition: all 0.3s ease-in-out;
        }
        
        /* When sidebar is collapsed */
        .w-16 ~ #mainContent {
            width: calc(100% - 4rem);
            margin-left: 4rem;
        }
        
        /* Ensure content is properly spaced */
        main {
            padding: 1.5rem;
        }
        
        /* Adjust header spacing */
        .page-header {
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex">
    <!-- Unified Sidebar -->
    <?php include '../includes/unified_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div id="mainContent" class="flex-1 flex flex-col overflow-hidden ml-64 transition-all duration-300 ease-in-out">
        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white/90 shadow-sm border-b border-gray-200 p-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div>
                        <h1 class="text-2xl font-bold text-gray-800">School Form 1 (SF1)</h1>
                        <p class="text-gray-600">School Register for Senior High School</p>
                        <div class="section-indicator mt-1">
                            <div class="text-sm font-normal">
                                <i class="fas fa-users mr-1"></i>
                                <span class="font-semibold" id="sectionNameDisplay">
                                    <?php 
                                    // Debugging: Log the section name and ID
                                    error_log("Section ID from URL: " . ($_GET['section_id'] ?? 'not set'));
                                    error_log("Section name to display: " . $section_name);
                                    
                                    if (isset($section_name) && $section_name !== 'All Sections') {
                                        echo htmlspecialchars($section_name);
                                    } else {
                                        // If we still get "All Sections", try to get it from the URL
                                        if (isset($_GET['section_name'])) {
                                            echo htmlspecialchars(urldecode($_GET['section_name']));
                                        } else {
                                            echo 'All Sections';
                                        }
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <script>
                        // Debug: Log the section name to console
                        console.log('Section name from PHP:', '<?php echo addslashes($section_name); ?>');
                        </script>
                    </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Add Student and Import buttons removed for Guidance role (read-only view) -->
                        <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition flex items-center">
                            <i class="fas fa-download mr-2"></i> Export to Excel
                        </button>
                        <!-- <button onclick="exportToPDF()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center">
        <i class="fas fa-file-pdf mr-2"></i> Export to PDF
    </button> -->
<!-- <button onclick="saveAllData()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center" id="saveBtn">
                <i class="fas fa-save mr-2"></i> Save
            </button> -->
                    </div>
                </div>
            </div>
            <!-- Excel Sheet Container -->
            <div class="bg-white rounded-b-lg shadow p-6 mt-0">
                <div class="excel-container">
                    <div id="excelEditor" class="excel-frame bg-white border border-gray-200 rounded-lg overflow-auto"></div>
                </div>
            </div>
            </div>
        </main>
    </div>

    <!-- Add this right before the "Add Student Modal" -->
<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Import Students</h3>
            <button id="closeImportModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="importForm" action="test_import.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="form-group">
                <label for="sectionId" class="block text-sm font-medium text-gray-700 mb-2">Select Section</label>
                <select id="sectionId" name="sectionId" required
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">-- Select a section --</option>
                    <?php
                    // Get section_id from URL if available
                    $current_section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
                    
                    // Fetch sections from database
                    $sections = [];
                    try {
                        $sql = "SELECT section_id, grade_level, class_name, track, 
                                       CONCAT(grade_level, ' - ', class_name, 
                                              IF(track IS NOT NULL AND track != '', CONCAT(' (', track, ')'), '')) as section_name 
                                FROM section 
                                ORDER BY grade_level, class_name";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $selected = ($row['section_id'] == $current_section_id) ? 'selected' : '';
                                echo '<option value="' . $row['section_id'] . '" ' . $selected . '>' . 
                                     htmlspecialchars($row['section_name']) . '</option>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<option value="">Error loading sections</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="excelFile" class="block text-sm font-medium text-gray-700 mb-2">Select Excel File</label>
                <div class="mt-1 flex items-center">
                    <input type="file" 
                           id="excelFile" 
                           name="excelFile" 
                           accept=".xls,.xlsx" 
                           required
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100">
                </div>
                <p class="mt-1 text-xs text-gray-500">Supports .xls, .xlsx files</p>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" 
                        onclick="document.getElementById('importModal').classList.add('hidden')"
                        class="px-4 py-2 text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-upload mr-2"></i> Import
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Remove the duplicate cancel button at the bottom of the form -->

    <!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-green-600">
                <i class="fas fa-check-circle mr-2"></i>
                Import Successful
            </h3>
            <button id="closeSuccessModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mb-4">
            <p id="successMessage" class="text-gray-700"></p>
            <div id="warningMessages" class="mt-3"></div>
                <span id="successSection" class="text-gray-800 font-medium"></span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="font-medium text-gray-700">Adviser:</span>
                <span id="successAdviser" class="text-gray-800 font-medium">Loading...</span>
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <div class="space-x-2">
                <button id="addAnotherBtn" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Add Another
                </button>
                <button id="closeSuccessModalBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
    <div id="addStudentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 overflow-y-auto max-h-[80vh]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Add New Student</h3>
                <button id="closeAddModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addStudentForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                        <input type="text" 
                               id="currentSection" 
                               class="w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                               readonly
                               value="">
                        <input type="hidden" name="section" id="sectionInput">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">LRN (12 digits) <span class="text-red-500">*</span></label>
                        <input type="text" 
                               name="lrn" 
                               required 
                               pattern="\d{12}" 
                               title="LRN must be exactly 12 digits"
                               maxlength="12"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                               placeholder="Enter 12-digit LRN">
                        <p class="mt-1 text-xs text-gray-500" id="lrn-helper">Enter exactly 12 numbers (0-9)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name Extension (e.g. Jr, Sr, III)</label>
                        <input type="text" name="name_extension" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Leave empty if none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" name="middle_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Leave empty if none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
                        <select name="sex" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="M">M</option>
                            <option value="F">F</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birthdate</label>
                        <input type="date" name="birthdate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <input type="number" name="age" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Religious Affiliation</label>
                        <input type="text" name="religious_affiliation" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">House No./ Street/ Sitio/ Purok</label>
                        <input type="text" name="house_street_sitio_purok" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                        <input type="text" name="barangay" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Municipality/ City</label>
                        <input type="text" name="municipality_city" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                        <input type="text" name="province" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                        <input type="text" name="fathers_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Maiden Name</label>
                        <input type="text" name="mothers_maiden_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name (Guardian)</label>
                        <input type="text" name="name_guardian" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Relationship</label>
                        <input type="text" name="relationship" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" name="contact_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea name="remarks" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" id="cancelAdd" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Add Student
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 overflow-y-auto max-h-[90vh]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Edit Student</h3>
                <button id="closeEditModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editStudentForm">
                <input type="hidden" id="editLrn" name="lrn">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">LRN</label>
                        <input type="text" id="editLrnDisplay" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <input type="text" name="last_name" placeholder="Last Name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <input type="text" name="first_name" placeholder="First Name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <input type="text" name="middle_name" placeholder="M.I." class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <input type="text" name="name_extension" placeholder="Ext." class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
                        <select name="sex" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birthdate</label>
                        <input type="date" name="birthdate" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <input type="number" name="age" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                        <input type="text" name="religion" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" name="address" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                        <input type="text" name="barangay" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Municipality/City</label>
                        <input type="text" name="municipality" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                        <input type="text" name="province" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="md:col-span-2 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Parent/Guardian Information</h4>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                        <input type="text" name="father_name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Maiden Name</label>
                        <input type="text" name="mother_maiden_name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guardian's Name (if not parent)</label>
                        <input type="text" name="guardian_name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Relationship</label>
                        <input type="text" name="guardian_relationship" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" name="contact_number" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea name="remarks" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" id="cancelEdit" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar toggle logic -->
    <script>
        // Pass user role to JavaScript
        const userRole = '<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>';
    </script>
    <script>
// Global success modal function
window.showSuccessModal = function(studentData) {
    const successModal = document.getElementById('successModal');
    if (!successModal) {
        console.error('Success modal element not found');
        return;
    }

    // Populate modal with student data
    const lrnEl = document.getElementById('successLRN');
    const nameEl = document.getElementById('successName');
    const sectionEl = document.getElementById('successSection');
    const adviserEl = document.getElementById('successAdviser');
    
    if (lrnEl) lrnEl.textContent = studentData.lrn || 'N/A';
    if (nameEl) nameEl.textContent = studentData.name || 'N/A';
    if (sectionEl) sectionEl.textContent = studentData.section || 'N/A';
    if (adviserEl) adviserEl.textContent = 'Loading...';

    // Fetch adviser for the section
    if (studentData.section) {
        fetch('get_section_adviser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `section=${encodeURIComponent(studentData.section)}`
        })
        .then(response => response.json())
        .then(data => {
            if (adviserEl) {
                adviserEl.textContent = data.success && data.adviser ? data.adviser : 'Not Assigned';
            }
        })
        .catch(error => {
            console.error('Error fetching adviser:', error);
            if (adviserEl) adviserEl.textContent = 'Error loading';
        });
    } else if (adviserEl) {
        adviserEl.textContent = 'N/A';
    }

    // Show the modal
    successModal.classList.remove('hidden');
};

// Initialize success modal event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const successModal = document.getElementById('successModal');
    const closeSuccessModal = document.getElementById('closeSuccessModal');
    const closeSuccessModalBtn = document.getElementById('closeSuccessModalBtn');
    const addAnotherBtn = document.getElementById('addAnotherBtn');

    function closeSuccessModalHandler() {
        if (successModal) {
            successModal.classList.add('hidden');
        }
    }

    // Set up event listeners
    if (closeSuccessModal) closeSuccessModal.addEventListener('click', closeSuccessModalHandler);
    if (closeSuccessModalBtn) closeSuccessModalBtn.addEventListener('click', closeSuccessModalHandler);
    if (addAnotherBtn) {
        addAnotherBtn.addEventListener('click', () => {
            closeSuccessModalHandler();
            const addBtn = document.getElementById('addStudentBtn');
            if (addBtn) addBtn.click();
        });
    }
    if (successModal) {
        successModal.addEventListener('click', (e) => {
            if (e.target === successModal) closeSuccessModalHandler();
        });
    }
});

        if (lrnEl) lrnEl.textContent = studentData.lrn || 'N/A';
        if (nameEl) nameEl.textContent = studentData.name || 'N/A';
        if (sectionEl) sectionEl.textContent = studentData.section || 'N/A';
        if (adviserEl) adviserEl.textContent = 'Loading...';

        // Fetch adviser for the section
        if (studentData.section) {
            fetch('get_section_adviser.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `section=${encodeURIComponent(studentData.section)}`
            })
            .then(response => response.json())
            .then(data => {
                if (adviserEl) {
                    adviserEl.textContent = data.success && data.adviser ? data.adviser : 'Not Assigned';
                }
            })
            .catch(error => {
                console.error('Error fetching adviser:', error);
                if (adviserEl) adviserEl.textContent = 'Error loading';
            });
        } else if (adviserEl) {
            adviserEl.textContent = 'N/A';
        }

        // Show the modal
        successModal.classList.remove('hidden');
    };

    // Set up event listeners
    if (closeSuccessModal) {
        closeSuccessModal.addEventListener('click', closeSuccessModalHandler);
    }
    if (closeSuccessModalBtn) {
        closeSuccessModalBtn.addEventListener('click', closeSuccessModalHandler);
    }
    if (addAnotherBtn) {
        addAnotherBtn.addEventListener('click', () => {
            closeSuccessModalHandler();
            document.getElementById('addStudentBtn').click();
        });
    }
    if (successModal) {
        successModal.addEventListener('click', (e) => {
            if (e.target === successModal) {
                closeSuccessModalHandler();
            }
        });
    }
}

// Initialize the success modal when the DOM is loaded
document.addEventListener('DOMContentLoaded', initializeSuccessModal);

document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    const fileInput = document.getElementById('excelFile');
    const importModal = document.getElementById('importModal');
    const closeImportModal = document.getElementById('closeImportModal');
    const cancelImport = document.getElementById('cancelImport');
    const submitImport = document.getElementById('submitImport');

    // Disable submit button initially
    if (submitImport) submitImport.disabled = true;

    // Open import modal
    document.querySelectorAll('[onclick*="importModal"]').forEach(button => {
        button.addEventListener('click', () => {
            importModal.classList.remove('hidden');
        });
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === importModal) {
            importModal.classList.add('hidden');
        }
    };

    // Close modal when clicking close button
    if (closeImportModal) {
        closeImportModal.addEventListener('click', () => {
            importModal.classList.add('hidden');
        });
    }

    // Cancel import
    if (cancelImport) {
        cancelImport.addEventListener('click', () => {
            importModal.classList.add('hidden');
            if (fileInput) fileInput.value = '';
        });
    }

    // Close modal
    function closeModal() {
        if (importModal) {
            importModal.classList.add('hidden');
            if (importForm) {
                importForm.reset();
            }
            if (submitImport) submitImport.disabled = true;
        }
    }
    
    // Close modal when clicking the close button
    if (closeImportModal) {
        closeImportModal.addEventListener('click', closeModal);
    }
    
    // Close modal when clicking cancel
    if (cancelImport) {
        cancelImport.addEventListener('click', closeModal);
    }
    
    // Close modal when clicking outside the modal
    importModal.addEventListener('click', (e) => {
        if (e.target === importModal) {
            closeModal();
        }
    });

    // Handle file selection
    const fileInput = document.getElementById('excelFile');
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const fileType = file.name.split('.').pop().toLowerCase();
                const validTypes = ['xls', 'xlsx'];
                
                if (!validTypes.includes(fileType)) {
                    alert('Please select a valid Excel file (.xls or .xlsx)');
                    e.target.value = '';
                    return;
                }
                
                // Enable submit button if a valid file is selected
                const submitBtn = document.querySelector('#importForm button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
    }

    // Define showNotification in the global scope if it doesn't exist
    if (typeof window.showNotification === 'undefined') {
        window.showNotification = function(message, type = 'info') {
            // Remove any existing notifications
            document.querySelectorAll('.notification-message').forEach(el => el.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification-message fixed top-4 right-4 p-4 rounded-md shadow-lg ${
                type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' :
                type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' :
                'bg-blue-100 border-l-4 border-blue-500 text-blue-700'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Auto-remove notification after 5 seconds
            setTimeout(() => {
                notification.style.transition = 'opacity 0.5s';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
            
            // Also make it closable by clicking
            notification.addEventListener('click', () => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            });
        };
    }
    // Handle form submission
    if (!importForm) return;
    
    notification.addEventListener('click', () => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 500);
    });

    // Close modal function
    window.closeModal = function() {
        if (importModal) {
            importModal.classList.add('hidden');
            importForm.reset();
        }
    };

    // Handle form submission
    importForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!fileInput.files || fileInput.files.length === 0) {
            window.showNotification('Please select a file to upload', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('excelFile', fileInput.files[0]);
        
        const submitBtn = importForm.querySelector('button[type="submit"]');
        const originalBtnContent = submitBtn.innerHTML;

        try {
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importing...';

            const response = await fetch('import_students.php', {
                method: 'POST',
                body: formData
            });

            // First get the response as text
            const responseText = await response.text();
            
            try {
                // Try to parse as JSON
                const data = JSON.parse(responseText);
                
                if (!response.ok) {
                    throw new Error(data.error || `HTTP error! status: ${response.status}`);
                }
                
                if (data.success) {
                    let message = `Successfully imported ${data.imported || 0} student(s).`;
                    if (data.warnings && data.warnings.length > 0) {
                        message += ` ${data.warnings.length} warning(s) occurred.`;
                        console.warn('Import warnings:', data.warnings);
                    }
                    
                    window.showNotification(message, 'success');
                    window.closeModal();
                    
                    // Refresh the page to show updated data
                    window.location.reload();
                } else {
                    throw new Error(data.error || 'Import failed');
                }
            } catch (jsonError) {
                console.error('Failed to parse JSON:', responseText);
                throw new Error('Invalid server response. Please check the server logs.');
            }
        } catch (error) {
            console.error('Import error:', error);
            window.showNotification(error.message || 'Failed to import file. Please try again.', 'error');
        } finally {
            // Reset button state
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            }
        }
    });

    // Close modal handlers
    if (closeImportModal) {
        closeImportModal.addEventListener('click', closeModal);
    }
    if (cancelImport) {
        cancelImport.addEventListener('click', closeModal);
    }
    if (importModal) {
        importModal.addEventListener('click', (e) => {
            if (e.target === importModal) {
                closeModal();
            }
        });
    }
});

// Global success modal elements and functions
const successModal = document.getElementById('successModal');
const closeSuccessModal = document.getElementById('closeSuccessModal');
const closeSuccessModalBtn = document.getElementById('closeSuccessModalBtn');
const importedCountEl = document.getElementById('importedCount');
const sectionNameEl = document.getElementById('sectionName');

// Close success modal
function closeSuccessModalHandler() {
    if (successModal) {
        successModal.classList.add('hidden');
    }
}

// Show success modal
window.showSuccessModal = function(param1, param2) {
    // Make sure the modal element exists
    if (!successModal) {
        console.error('Success modal element not found');
        return;
    }

    // Handle both object and parameter-based calls
    if (typeof param1 === 'object') {
        // Object parameter (new format)
        const { lrn, name, section } = param1;
        const lrnEl = document.getElementById('successLRN');
        const nameEl = document.getElementById('successName');
        const sectionEl = document.getElementById('successSection');
        
        if (lrnEl) lrnEl.textContent = lrn || 'N/A';
        if (nameEl) nameEl.textContent = name || 'N/A';
        if (sectionEl) sectionEl.textContent = section || 'N/A';
        
        // Fetch adviser for the section
        if (section) {
            const adviserElement = document.getElementById('successAdviser');
            if (adviserElement) {
                adviserElement.textContent = 'Loading...';
                
                fetch('get_section_adviser.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `section=${encodeURIComponent(section)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.adviser) {
                        adviserElement.textContent = data.adviser;
                    } else {
                        adviserElement.textContent = 'Not Assigned';
                    }
                })
                .catch((error) => {
                    console.error('Error fetching adviser:', error);
                    adviserElement.textContent = 'Error loading';
                });
            }
        }
    } else {
        // Old format (count, section)
        const count = param1;
        const section = param2;
        if (importedCountEl) importedCountEl.textContent = count;
        if (sectionNameEl) sectionNameEl.textContent = section || 'N/A';
    }
    
    // Show the modal
    successModal.classList.remove('hidden');
};

// Add event listeners when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Success modal event listeners
    if (closeSuccessModal) {
        closeSuccessModal.addEventListener('click', closeSuccessModalHandler);
    }
    if (closeSuccessModalBtn) {
        closeSuccessModalBtn.addEventListener('click', closeSuccessModalHandler);
    }
    
    // Close modal when clicking outside
    if (successModal) {
        successModal.addEventListener('click', function(e) {
            if (e.target === successModal) {
                closeSuccessModalHandler();
            }
        });
    }
});

        // Close on outside click
        successModal.addEventListener('click', function(e) {
            if (e.target === successModal) {
                closeSuccessModalHandler();
            }
        });

        // Handle form submission
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const sectionSelect = document.getElementById('sectionId');
            
            // Get section_id from URL if available
            const urlParams = new URLSearchParams(window.location.search);
            const sectionIdFromUrl = urlParams.get('section_id');
            
            // If we have a section_id in the URL and it's not already selected, update the select
            if (sectionIdFromUrl && sectionSelect) {
                sectionSelect.value = sectionIdFromUrl;
            }
            
            const sectionName = sectionSelect.options[sectionSelect.selectedIndex].text;
            const formData = new FormData(importForm);
            
            // Make sure sectionId is included in form data
            if (sectionIdFromUrl && !formData.get('sectionId')) {
                formData.set('sectionId', sectionIdFromUrl);
            }
            
            // Show loading state
            const submitBtn = importForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...';
            submitBtn.disabled = true;

            const formDataObj = Object.fromEntries(formData);
            // Send data to server
            fetch('add_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formDataObj)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    // Get the form data
                    const formData = new FormData(this);
                    const lrn = formData.get('lrn') || '';
                    const lastName = formData.get('last_name') || '';
                    const firstName = formData.get('first_name') || '';
                    const middleName = formData.get('middle_name') || '';
                    const section = formData.get('section') || '';
                    
                    // Format the full name
                    let fullName = `${lastName}, ${firstName}`;
                    if (middleName) fullName += ` ${middleName.charAt(0)}.`;
                    
                    // Close the add student modal
                    closeAddStudentModal();
                    
                    // Show success modal with student details
                    showSuccessModal({
                        lrn: lrn,
                        name: fullName.trim(),
                        section: section
                    });
                    
                    // Reload the table
                    if (typeof createExcelEditor === 'function') {
                        createExcelEditor();
                    } else if (typeof loadDataFromDatabase === 'function') {
                        loadDataFromDatabase();
                    }
                } else {
                    const errorMsg = data.error || 'Failed to add student. Please check the form and try again.';
                    showNotification(errorMsg, 'error');
                    console.error('Server error:', data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showNotification('An error occurred: ' + error.message, 'error');
            });
        });

        // Notification function
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Handle iframe load
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.querySelector('.excel-frame');
            iframe.addEventListener('load', function() {
                console.log('Excel sheet loaded successfully');
            });
        });
    </script>

    <script>
        // Show success modal if there's an import success in the session
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['import_success'])): ?>
                const successData = <?php echo json_encode($_SESSION['import_success']); ?>;
                const successModal = document.getElementById('successModal');
                const successMessage = document.getElementById('successMessage');
                const warningMessages = document.getElementById('warningMessages');
                
                // Set success message
                successMessage.textContent = `Successfully imported ${successData.count} students to section: ${successData.section}`;
                
                // Show warnings if any
                warningMessages.innerHTML = '';
                if (successData.warnings && successData.warnings.length > 0) {
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'alert alert-warning mt-3';
                    warningDiv.innerHTML = '<strong>Warnings:</strong><br>' + successData.warnings.join('<br>');
                    warningMessages.appendChild(warningDiv);
                }
                
                // Show the modal
                const modal = new bootstrap.Modal(successModal);
                modal.show();
                
                // Clear the session data
                fetch('clear_import_session.php', { method: 'POST' });
            <?php 
                // Clear the session data
                unset($_SESSION['import_success']);
            ?>
            <?php endif; ?>
            
            // Editable Excel-like Table
            // Check if we have a section_id in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const sectionId = urlParams.get('section_id');
            
            if (sectionId) {
                console.log('Section ID from URL:', sectionId);
                // Update the section indicator
                updateSectionIndicator(sectionId);
            }
            
            createExcelEditor();
        });
        
        // Function to update section indicator
        function updateSectionIndicator(sectionId) {
            fetch(`get_section_details.php?section_id=${sectionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.section) {
                        // Format: "Grade 11 - STEM A" (grade level and class name only)
                        let grade_display = data.section.grade_level || '';
                        // Ensure grade doesn't have duplicate "Grade" prefix
                        if (grade_display && !/^Grade\s+/i.test(grade_display)) {
                            // Extract number if exists
                            const match = grade_display.match(/(\d+)/);
                            if (match) {
                                grade_display = 'Grade ' + match[1];
                            } else {
                                grade_display = 'Grade ' + grade_display;
                            }
                        }
                        
                        const class_name = data.section.class_name || '';
                        const sectionName = grade_display && class_name 
                            ? `${grade_display} - ${class_name}`
                            : (grade_display || class_name);
                        
                        const sectionIndicator = document.querySelector('.section-indicator');
                        if (sectionIndicator) {
                            sectionIndicator.innerHTML = `
                                <div class="text-sm font-normal">
                                    <i class="fas fa-users mr-1"></i> <span class="font-semibold">${sectionName}</span>
                                </div>`;
                        }
                    }
                })
                .catch(error => console.error('Error fetching section details:', error));
        }

        function createSF1Header() {
            // Return an empty div as we don't want to show any header
            const wrapper = document.createElement('div');
            return wrapper;
        }

        const dbColumns = [
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

        function createExcelEditor() {
            console.log('createExcelEditor called');
            const editor = document.getElementById('excelEditor');
            editor.innerHTML = '';
            // Table headers
            const headers = [
                'LRN',
                'NAME (Last Name, First Name, Name Extension, Middle Name)',
                'Sex',
                'BIRTHDATE',
                'AGE',
                'Religious Affiliation',
                'House No./ Street/ Sitio/ Purok',
                'Barangay',
                'Municipality/ City',
                'Province',
                "Father's Name",
                "Mother's Maiden Name",
                'Name (Guardian)',
                'Relationship',
                'Contact Number',
                'Remarks'
            ];
            // Create table with modern styling and better layout
            const table = document.createElement('table');
            table.className = 'w-full border-collapse text-sm';
            table.style.fontFamily = '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif';
            table.style.tableLayout = 'auto';
            table.style.wordWrap = 'break-word';
            
            // Add container for better scrolling, shadow, and responsive behavior
            const tableContainer = document.createElement('div');
            tableContainer.className = 'rounded-lg border border-gray-200 shadow-sm overflow-auto';
            tableContainer.style.maxHeight = 'calc(100vh - 200px)';
            tableContainer.style.overflowX = 'auto';
            tableContainer.style.scrollBehavior = 'smooth';
            tableContainer.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
            tableContainer.style.borderRadius = '0.5rem';
            
            // Add wrapper for the table with responsive behavior
            const tableWrapper = document.createElement('div');
            tableWrapper.className = 'inline-block min-w-full align-middle';
            tableWrapper.style.minWidth = 'fit-content';
            tableWrapper.style.width = '100%';
            tableWrapper.appendChild(table);
            tableContainer.appendChild(tableWrapper);
            // Header row
            const headerRow = document.createElement('tr');
            headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                th.style.border = '1px solid #ccc';
                th.style.padding = '8px';
                th.style.backgroundColor = '#f0f0f0';
                th.style.fontWeight = 'bold';
                th.style.textAlign = 'center';
                th.style.minWidth = '120px';
                headerRow.appendChild(th);
            });
            table.appendChild(headerRow);
            
            // Add empty state row
            const emptyRow = document.createElement('tr');
            emptyRow.id = 'emptyState';
            emptyRow.className = 'hidden';
            const emptyCell = document.createElement('td');
            emptyCell.colSpan = headers.length;
            emptyCell.className = 'py-8 text-center text-gray-500';
            emptyCell.colSpan = headers.length;
            emptyCell.className = 'empty-state';
            emptyCell.innerHTML = `
                <i class="fas fa-table"></i>
                <h3>No Data Available</h3>
                <p>Get started by adding a new student or importing data.</p>
                <!-- Add Student button removed for Guidance role (read-only view) -->
            `;
            emptyRow.appendChild(emptyCell);
            table.appendChild(emptyRow);
            
            // Append the table container to editor
            editor.appendChild(tableContainer);
            
            // Add custom styles for the table
            const style = document.createElement('style');
            style.textContent = `
                #excelEditor table {
                    min-width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    table-layout: fixed;
                    width: 100%;
                    white-space: nowrap;
                }
                #excelEditor table thead th {
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }
                #excelEditor table tbody tr {
                    transition: all 0.2s ease;
                }
                #excelEditor table tbody tr:nth-child(even) {
                    background-color: #f9fafb;
                }
                #excelEditor table tbody tr:nth-child(odd) {
                    background-color: #ffffff;
                }
                #excelEditor table tbody tr:hover {
                    background-color: #f0f9ff;
                }
                #excelEditor table td {
                    padding: 10px 12px;
                    border-bottom: 1px solid #e5e7eb;
                    border-right: 1px solid #e5e7eb;
                    vertical-align: middle;
                    transition: all 0.2s ease;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 200px;
                }
                #excelEditor table td:first-child {
                    border-left: 1px solid #e5e7eb;
                }
                #excelEditor table td[contenteditable="true"] {
                    position: relative;
                }
                #excelEditor table td[contenteditable="true"]:hover {
                    background-color: #f0f9ff;
                    outline: 1px solid #0ea5e9;
                    outline-offset: -1px;
                    z-index: 2;
                }
                #excelEditor table td[contenteditable="true"]:focus {
                    background-color: #ffffff;
                    box-shadow: 0 0 0 2px #bae6fd;
                    outline: 2px solid #0ea5e9;
                    z-index: 3;
                    position: relative;
                }
                /* Custom scrollbar */
                #excelEditor ::-webkit-scrollbar {
                    width: 10px;
                    height: 10px;
                }
                #excelEditor ::-webkit-scrollbar-track {
                    background: #f1f5f9;
                    border-radius: 4px;
                }
                #excelEditor ::-webkit-scrollbar-thumb {
                    background: #cbd5e1;
                    border-radius: 4px;
                    border: 2px solid #f1f5f9;
                }
                #excelEditor ::-webkit-scrollbar-thumb:hover {
                    background: #94a3b8;
                }
                #excelEditor ::-webkit-scrollbar-corner {
                    background: #f1f5f9;
                }
            `;
            document.head.appendChild(style);
            
            // Load data from database
            loadDataFromDatabase(table, headers);
        }
        
        function loadDataFromDatabase(table, headers) {
            console.log('Loading data from database...');
            // Get section_id from URL if it exists
            const urlParams = new URLSearchParams(window.location.search);
            const sectionId = urlParams.get('section_id');
            
            // Build the URL with section_id if it exists
            let url = 'fetch_sf1_data.php';
            if (sectionId) {
                url += `?section_id=${sectionId}`;
                console.log('Loading data for section_id:', sectionId);
            }
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(result => {
                    console.log('Fetch result:', result);
                    if (result.success) {
                        console.log('Data loaded successfully, rows:', result.data.length);
                        populateTableWithData(table, headers, result.data);
                    } else {
                        console.error('Error loading data:', result.error);
                        // Create empty table if no data
                        const editor = document.getElementById('excelEditor');
                        editor.appendChild(table);
                        addTableControls(editor);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    // Create empty table if error
                    const editor = document.getElementById('excelEditor');
                    editor.appendChild(table);
                    addTableControls(editor);
                });
        }
        
        function populateTableWithData(table, headers, data) {
            console.log('Populating table with data:', data.length, 'rows');
            
            // Remove empty state row if it exists
            const emptyState = document.getElementById('emptyState');
            if (emptyState) emptyState.remove();
            
            // Clear existing rows except header
            while (table.rows.length > 1) {
                table.deleteRow(1);
            }
            
            // Add data rows
            data.forEach((rowData, rowIndex) => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-blue-50 transition-colors cursor-pointer';
                row.setAttribute('data-student', JSON.stringify(rowData));
                row.dataset.rowIndex = rowIndex;
                
                // Add click handler for Registrar role to open edit modal
                if (userRole === 'Registrar') {
                    row.addEventListener('click', function(e) {
                        // Get LRN from the first cell (index 0)
                        const lrn = rowData[0];
                        if (!lrn) {
                            console.error('LRN not found in row data');
                            return;
                        }
                        
                        // Fetch complete student data from server
                        fetch('get_student_by_lrn.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'lrn=' + encodeURIComponent(lrn)
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success && result.data) {
                                openEditModal(result.data);
                            } else {
                                console.error('Error fetching student data:', result.error);
                                showNotification('Error loading student data: ' + (result.error || 'Unknown error'), 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Error loading student data: ' + error.message, 'error');
                        });
                    });
                }
                
                // Add cells for each column
                for (let j = 0; j < headers.length; j++) {
                    const cell = document.createElement('td');
                    cell.className = 'border-b border-gray-200';
                    cell.style.padding = '10px 12px';
                    cell.style.minHeight = '40px';
                    cell.style.position = 'relative';
                    cell.textContent = rowData[j] || '';
                    
                    // Make all cells read-only for Guidance role (view only)
                    // Registrar can view but editing is done through modal
                    cell.contentEditable = false;
                    cell.style.cursor = userRole === 'Registrar' ? 'pointer' : 'default';
                    if (j === 0) {
                        cell.className += ' bg-gray-50';
                    }
                    
                    row.appendChild(cell);
                }
                
                // No action buttons for Guidance role (read-only view)
                // Action buttons removed - view only mode
                
                // No editing event listeners for Guidance role (read-only view)
                
                table.appendChild(row);
            });
            
            // No actions column header for Guidance role (read-only view)
            
            const editor = document.getElementById('excelEditor');
            editor.appendChild(table);
            addTableControls(editor);
        }
        
        function addTableControls(editor) {
            // No controls below the table
        }

        // Table zoom logic
        let tableZoom = 1;
        function changeTableZoom(value, isAbsolute = false) {
            if (isAbsolute) {
                tableZoom = Math.max(0.5, Math.min(2, value));
            } else {
                tableZoom = Math.max(0.5, Math.min(2, tableZoom + value));
            }
            const table = document.querySelector('#excelEditor table');
            if (table) {
                table.style.transform = `scale(${tableZoom})`;
                table.style.transformOrigin = 'top left';
            }
        }

        function saveRowData(row) {
            const cells = row.querySelectorAll('td:not(:last-child)');
            const rowData = Array.from(cells).map(cell => cell.textContent.trim() || null);
            const lrn = rowData[0]; // LRN is the first column
            
            if (!lrn) {
                alert('LRN cannot be empty');
                return;
            }
            
            // Get the section_id from URL
            const urlParams = new URLSearchParams(window.location.search);
            const sectionId = urlParams.get('section_id');
            
            // Prepare data for saving
            const data = {
                lrn: lrn,
                section_id: sectionId,
                last_name: rowData[1]?.split(',')[0]?.trim() || '',
                first_name: rowData[1]?.split(',')[1]?.trim() || '',
                middle_name: rowData[1]?.split(',')[2]?.trim() || '',
                name_extension: rowData[1]?.split(',')[3]?.trim() || '',
                sex: rowData[2] || '',
                birthdate: rowData[3] || '',
                age: rowData[4] || '',
                religion: rowData[5] || '',
                address: rowData[6] || '',
                barangay: rowData[7] || '',
                municipality: rowData[8] || '',
                province: rowData[9] || '',
                father_name: rowData[10] || '',
                mother_maiden_name: rowData[11] || '',
                guardian_name: rowData[12] || '',
                guardian_relationship: rowData[13] || '',
                contact_number: rowData[14] || '',
                remarks: rowData[15] || ''
            };
            
            // Show loading state
            const saveBtn = row.querySelector('.save-row-btn');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
            
            // Send data to server
            fetch('save_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Show success message
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 p-4 bg-green-500 text-white rounded-md shadow-lg z-50';
                    notification.textContent = 'Student data saved successfully';
                    document.body.appendChild(notification);
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                    
                    // Reset row state
                    row.classList.remove('editing');
                    saveBtn.classList.add('hidden');
                } else {
                    throw new Error(result.message || 'Failed to save data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving data: ' + error.message);
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            });
            if (colIndex === 4) { // Age column
                value = parseInt(value) || null;
            }

            const column = dbColumns[colIndex];

            fetch('update_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lrn, column, value })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('Update saved!', 'success');
                } else {
                    showNotification('Error updating: ' + result.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Update failed: ' + error.message, 'error');
            });
        }

        // Function to open edit modal with student data
        function openEditModal(studentData) {
            console.log('Opening edit modal for student:', studentData);
            const modal = document.getElementById('editStudentModal');
            const form = document.getElementById('editStudentForm');
            
            // Function to safely set form field value
            function setFormValue(fieldName, value) {
                const element = form.elements[fieldName];
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Form element '${fieldName}' not found`);
                }
            }
            
            // Populate form fields
            setFormValue('lrn', studentData.lrn);
            setFormValue('editLrn', studentData.lrn);
            
            // Special handling for elements that might not be direct form elements
            const lrnDisplay = document.getElementById('editLrnDisplay');
            if (lrnDisplay) {
                lrnDisplay.value = studentData.lrn || '';
            }
            
            // Set other form fields
            const fields = [
                'last_name', 'first_name', 'middle_name', 'name_extension',
                'sex', 'birthdate', 'age', 'religion', 'address', 'barangay',
                'municipality', 'province', 'father_name', 'mother_maiden_name',
                'guardian_name', 'guardian_relationship', 'contact_number',
                'last_grade_completed', 'last_school_attended', 'last_school_year_attended',
                'school_id', 'school_name', 'school_address', 'school_classification',
                'school_region'
            ];
            
            // Also handle guardian_contact as contact_number
            if (studentData.guardian_contact && !studentData.contact_number) {
                setFormValue('contact_number', studentData.guardian_contact);
            }
            
            fields.forEach(field => {
                setFormValue(field, studentData[field]);
            });
            
            // Show the modal
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        
        // Close edit modal
        document.getElementById('closeEditModal').addEventListener('click', function() {
            document.getElementById('editStudentModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        });
        
        // Close modal when clicking outside
        document.getElementById('editStudentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
        
        // Handle edit form submission
        document.getElementById('editStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            // Send data to server
            fetch('update_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    document.getElementById('editStudentModal').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    
                    // Show success message
                    showNotification('Student updated successfully!', 'success');
                    
                    // Refresh the table
                    const table = document.querySelector('#excelEditor table');
                    loadDataFromDatabase(table, headers);
                } else {
                    throw new Error(data.message || 'Failed to update student');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Failed to update student', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
        
        function saveAllData() {
    const table = document.querySelector('#excelEditor table');
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.textContent;
    
    // Show loading state
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    const data = [];
    for (let i = 1; i < table.rows.length; i++) { // Skip header row
        const rowData = [];
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            rowData.push(table.rows[i].cells[j].textContent);
        }
        data.push(rowData);
    }

    console.log('Saving data:', data); // Debug log to verify data being sent

    fetch('save_excel_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Save response status:', response.status); // Debug log
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(result => {
        console.log('Save result:', result); // Debug log
        if (result.success) {
            showNotification('Data saved successfully!', 'success');
        } else {
            showNotification('Error saving data: ' + result.error, 'error');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showNotification('Error saving data: ' + error.message, 'error');
    })
    .finally(() => {
        // Reset button state
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
    });
}
        function exportToExcel() {
    const table = document.querySelector('#excelEditor table');
    const data = [];
    console.log('Table found:', table);
    console.log('Number of rows:', table ? table.rows.length : 0);
    for (let i = 1; i < table.rows.length; i++) {
        const rowData = [];
        let hasData = false;
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            const cellValue = table.rows[i].cells[j].textContent.trim();
            rowData.push(cellValue);
            if (cellValue !== '') {
                hasData = true;
            }
        }
        if (hasData) {
            data.push(rowData);
        }
    }
    console.log('Collected data:', data);
    console.log('Number of data rows:', data.length);
    if (data.length === 0) {
        console.log('No data found in table, using sample data for testing');
        data.push([
            '123456789012', 'Doe, John A. Smith', 'M', '01/15/2005', '18', 'Catholic',
            '123 Main St.', 'Barangay 1', 'Dasmariñas City', 'Cavite', 'John Doe Sr.',
            'Jane Smith', 'John Doe Sr.', 'Father', '09123456789', 'Active Student'
        ]);
        showNotification('No data found in table. Using sample data for export test.', 'info');
    }
    const exportBtn = document.querySelector('button[onclick="exportToExcel()"]');
    const originalText = exportBtn.textContent;
    exportBtn.textContent = 'Exporting...';
    exportBtn.disabled = true;
    fetch('export_to_excel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Excel export response status:', response.status);
        console.log('Excel export response headers:', response.headers);
        if (!response.ok) {
            return response.text().then(errorText => {
                console.error('Excel export error response:', errorText);
                try {
                    const errorData = JSON.parse(errorText);
                    throw new Error('Excel export failed: ' + (errorData.error || 'Unknown error'));
                } catch (e) {
                    throw new Error('Excel export failed: ' + errorText);
                }
            });
        }
        const contentType = response.headers.get('content-type');
        console.log('Excel export content type:', contentType);
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(errorData => {
                throw new Error('Excel export failed: ' + (errorData.error || 'Unknown error'));
            });
        }
        return response.blob();
    })
    .then(blob => {
        console.log('Excel export blob received:', blob);
        console.log('Excel export blob size:', blob.size);
        if (blob.size === 0) {
            throw new Error('Excel export returned empty file');
        }
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `School_Form_1_SF1_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        showNotification('Excel file exported successfully!', 'success');
    })
    .catch(error => {
        console.error('Export error:', error);
        showNotification('Error during Excel export: ' + error.message, 'error');
    })
    .finally(() => {
        exportBtn.textContent = originalText;
        exportBtn.disabled = false;
    });
}

function exportToPDF() {
    const table = document.querySelector('#excelEditor table');
    const data = [];
    console.log('Table found:', table);
    console.log('Number of rows:', table ? table.rows.length : 0);
    for (let i = 1; i < table.rows.length; i++) {
        const rowData = [];
        let hasData = false;
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            const cellValue = table.rows[i].cells[j].textContent.trim();
            rowData.push(cellValue);
            if (cellValue !== '') {
                hasData = true;
            }
        }
        if (hasData) {
            data.push(rowData);
        }
    }
    console.log('Collected data for PDF:', data);
    console.log('Number of data rows:', data.length);
    if (data.length === 0) {
        console.log('No data found in table, using sample data for testing');
        data.push([
            '123456789012', 'Doe, John A. Smith', 'M', '01/15/2005', '18', 'Catholic',
            '123 Main St.', 'Barangay 1', 'Dasmariñas City', 'Cavite', 'John Doe Sr.',
            'Jane Smith', 'John Doe Sr.', 'Father', '09123456789', 'Active Student'
        ]);
        showNotification('No data found in table. Using sample data for PDF export test.', 'info');
    }
    const exportBtn = document.querySelector('button[onclick="exportToPDF()"]');
    const originalText = exportBtn.textContent;
    exportBtn.textContent = 'Exporting...';
    exportBtn.disabled = true;
    fetch('export_to_pdf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('PDF export response status:', response.status);
        console.log('PDF export response headers:', response.headers);
        if (!response.ok) {
            return response.text().then(errorText => {
                console.error('PDF export error response:', errorText);
                try {
                    const errorData = JSON.parse(errorText);
                    throw new Error('PDF export failed: ' + (errorData.error || 'Unknown error'));
                } catch (e) {
                    throw new Error('PDF export failed: ' + errorText);
                }
            });
        }
        const contentType = response.headers.get('content-type');
        console.log('PDF export content type:', contentType);
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(errorData => {
                throw new Error('PDF export failed: ' + (errorData.error || 'Unknown error'));
            });
        }
        return response.blob();
    })
    .then(blob => {
        console.log('PDF export blob received:', blob);
        console.log('PDF export blob size:', blob.size);
        if (blob.size === 0) {
            throw new Error('PDF export returned empty file');
        }
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `School_Form_1_SF1_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        showNotification('PDF file exported successfully!', 'success');
    })
    .catch(error => {
        console.error('Export error:', error);
        showNotification('Error during PDF export: ' + error.message, 'error');
    })
    .finally(() => {
        exportBtn.textContent = originalText;
        exportBtn.disabled = false;
    });
}
    </script>
    <script>
        function renderImportedTable(data) {
            const editor = document.getElementById('excelEditor');
            editor.innerHTML = '';
            // Add custom header above the table
            editor.appendChild(createSF1Header());
            if (!data || !Array.isArray(data) || data.length === 0) {
                editor.innerHTML += '<p class="text-gray-500">No data found in the imported sheet.</p>';
                return;
            }
            const table = document.createElement('table');
            table.className = 'data-table';

            // Header row
            const headerRow = document.createElement('tr');
            data[0].forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                th.style.border = '1px solid #ccc';
                th.style.padding = '8px';
                th.style.backgroundColor = '#f0f0f0';
                th.style.fontWeight = 'bold';
                th.style.textAlign = 'center';
                th.style.minWidth = '120px';
                headerRow.appendChild(th);
            });
            table.appendChild(headerRow);

            // Data rows
            for (let i = 1; i < data.length; i++) {
                const row = document.createElement('tr');
                data[i].forEach((cellData, j) => {
                    const cell = document.createElement('td');
                    cell.textContent = cellData;
                    cell.style.border = '1px solid #ccc';
                    cell.style.padding = '6px';
                    cell.style.minHeight = '30px';
                    cell.style.position = 'relative';
                    if (j !== 0 && j !== 1) {
                        cell.contentEditable = true;
                        cell.style.cursor = 'text';
                        cell.addEventListener('focus', function() {
                            this.style.backgroundColor = '#fff3cd';
                        });
                        cell.addEventListener('blur', function() {
                            this.style.backgroundColor = '';
                            const rowIndex = this.parentElement.rowIndex - 1;
                            updateCellData(rowIndex, j, this.textContent);
                        });
                        cell.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                this.blur();
                            }
                        });
                    } else {
                        cell.style.cursor = 'default';
                    }
                    row.appendChild(cell);
                });
                table.appendChild(row);
            }
            editor.appendChild(table);
            addTableControls(editor); // Reuse your existing controls 
        }
    </script>
    <script>
        function saveImportedDataToDatabase(tableData) {
            fetch('save_excel_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(tableData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('Imported data saved to database!', 'success');
                } else {
                    showNotification('Error saving imported data: ' + result.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving imported data', 'error');
            });
        }
    </script>
    <!-- Add this style for notifications -->
    <style>
        .notification-message {
            z-index: 10000;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
            max-width: 350px;
            cursor: pointer;
        }
        .notification-message i {
            font-size: 1.25rem;
        }
    </style>
    
    <script>
        // Function to calculate age from birthdate
        function calculateAge(birthdate) {
            const birthDate = new Date(birthdate);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age;
        }
        
        // Add event listener for birthdate input
        document.addEventListener('DOMContentLoaded', function() {
            const birthdateInput = document.querySelector('input[name="birthdate"]');
            const ageInput = document.querySelector('input[name="age"]');
            
            if (birthdateInput && ageInput) {
                birthdateInput.addEventListener('change', function() {
                    if (this.value) {
                        const age = calculateAge(this.value);
                        if (!isNaN(age) && age > 0) {
                            ageInput.value = age;
                        }
                    }
                });
            }
        });
        
        // Add Student modal functionality - DISABLED for Guidance role (read-only)
        // const addStudentBtn = document.getElementById('addStudentBtn');
        // const addStudentModal = document.getElementById('addStudentModal');
        // const closeAddModal = document.getElementById('closeAddModal');
        // const cancelAdd = document.getElementById('cancelAdd');
        // const addForm = document.getElementById('addStudentForm');

        // Open add student modal - DISABLED
        // if (addStudentBtn) {
        //     addStudentBtn.addEventListener('click', function() {
        //         addStudentModal.classList.remove('hidden');
        //     });
        // }

        // Close add student modal - DISABLED for Guidance role
        // function closeAddStudentModal() {
        //     if (addStudentModal) {
        //         addStudentModal.classList.add('hidden');
        //     }
        //     if (addForm) {
        //         addForm.reset();
        //     }
        // }

        // if (closeAddModal) closeAddModal.addEventListener('click', closeAddStudentModal);
        // if (cancelAdd) cancelAdd.addEventListener('click', closeAddStudentModal);

        // Close modal when clicking outside - DISABLED
        // if (addStudentModal) {
        //     addStudentModal.addEventListener('click', function(e) {
        //         if (e.target === addStudentModal) {
        //             closeAddStudentModal();
        //         }
        //     });
        // }

        // Validate LRN format
        function validateLRN(lrn) {
            const lrnRegex = /^\d{12}$/;
            return lrnRegex.test(lrn);
        }

        // Handle add student form submission - DISABLED for Guidance role (read-only)
        // All add student functionality removed - view only mode
        /*
        const addForm = document.getElementById('addStudentForm');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get the submit button and set loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!submitBtn) {
                    console.error('Submit button not found');
                    return;
                }
                
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Get all form fields
            const formElements = this.elements;
            const formData = new FormData();
            
            // Manually collect all form data to ensure we get all fields
            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];
                if (element.name && element.type !== 'file' && element.type !== 'submit' && element.type !== 'button') {
                    if (element.type === 'checkbox' || element.type === 'radio') {
                        if (element.checked) {
                            formData.append(element.name, element.value || 'on');
                        }
                    } else {
                        formData.append(element.name, element.value || '');
                    }
                }
            }
            
            // Add section ID and name to form data if available
            const urlParams = new URLSearchParams(window.location.search);
            const sectionId = urlParams.get('section_id');
            if (sectionId) {
                formData.append('section_id', sectionId);
                // Get the section name from the page header
                const sectionHeader = document.querySelector('.section-indicator span') || 
                                    document.getElementById('currentSection');
                if (sectionHeader) {
                    formData.append('section', sectionHeader.textContent || sectionHeader.value);
                }
            }
            
            const lrn = formData.get('lrn') ? formData.get('lrn').toString().trim() : '';
            
            // Show notification function (if not defined)
            if (typeof showNotification !== 'function') {
                window.showNotification = function(message, type = 'info') {
                    const notification = document.createElement('div');
                    notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg ${
                        type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' :
                        type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' :
                        'bg-blue-100 border-l-4 border-blue-500 text-blue-700'
                    }`;
                    notification.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas ${
                                type === 'error' ? 'fa-exclamation-circle' : 
                                type === 'success' ? 'fa-check-circle' : 'fa-info-circle'
                            } mr-2"></i>
                            <span>${message}</span>
                        </div>
                    `;
                    document.body.appendChild(notification);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        notification.style.transition = 'opacity 0.5s';
                        notification.style.opacity = '0';
                        setTimeout(() => notification.remove(), 500);
                    }, 5000);
                };
            }

            // Validate required fields
            const requiredFields = ['lrn', 'last_name', 'first_name', 'birthdate'];
            const missingFields = [];
            
            for (const field of requiredFields) {
                if (!formData.get(field)?.toString().trim()) {
                    const fieldName = field.replace('_', ' ');
                    missingFields.push(fieldName);
                }
            }
            
            if (missingFields.length > 0) {
                const errorMessage = `Please fill in all required fields: ${missingFields.join(', ')}`;
                window.showNotification(errorMessage, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                return;
            }
            
            // Validate LRN format
            if (!validateLRN(lrn)) {
                window.showNotification('LRN must be exactly 12 digits', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                return;
            }
            
            // Create form data object with all fields
            const formDataObj = Object.fromEntries(formData.entries());
            
            // Ensure all required fields have values
            formDataObj.last_name = formDataObj.last_name || '';
            formDataObj.first_name = formDataObj.first_name || '';
            formDataObj.middle_name = formDataObj.middle_name || '';
            formDataObj.name_extension = formDataObj.name_extension || '';
            
            // Log the data being sent for debugging
            console.log('Submitting form data:', formDataObj);
            
            // Ensure loading state is set
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            // Convert FormData to URL-encoded format for better compatibility
            const formBody = new URLSearchParams();
            Object.entries(formDataObj).forEach(([key, value]) => {
                formBody.append(key, value);
            });
            
            // Send form data to server
            fetch('add_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formBody.toString()
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(text || 'Failed to add student');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Format the full name
                    const lastName = formData.get('last_name') || '';
                    const firstName = formData.get('first_name') || '';
                    const middleName = formData.get('middle_name') || '';
                    const section = formData.get('section') || '';
                    
                    let fullName = `${lastName}, ${firstName}`;
                    if (middleName) fullName += ` ${middleName.charAt(0)}.`;
                    
                    // Close the add student modal
                    closeAddStudentModal();
                    
                    // Show success modal with student details
                    showSuccessModal({
                        lrn: formData.get('lrn') || '',
                        name: fullName.trim(),
                        section: section
                    });
                    
                    // Reload the table
                    if (typeof createExcelEditor === 'function') {
                        createExcelEditor();
                    } else if (typeof loadDataFromDatabase === 'function') {
                        loadDataFromDatabase();
                    }
                } else {
                    throw new Error(data.error || 'Add failed');
                }
            })
            .catch(error => {
                console.error('Add error:', error);
                try {
                    // Try to parse error as JSON
                    const errorData = JSON.parse(error.message);
                    window.showNotification(errorData.error || 'Add failed. Please try again.', 'error');
                } catch (e) {
                    // If not JSON, show the raw error message
                    window.showNotification(error.message || 'Add failed. Please try again.', 'error');
                }
                
                // Reset button state
                if (submitBtn) {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
            });
        }
        */
    </script>
    <script>
        const importForm = document.getElementById('importForm');
            
            const notification = document.createElement('div');
            notification.className = `notification-message fixed top-4 right-4 p-4 rounded-md shadow-lg ${
                type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' :
                type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' :
                'bg-blue-100 border-l-4 border-blue-500 text-blue-700'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Auto-remove notification after 5 seconds
            setTimeout(() => {
                notification.style.transition = 'opacity 0.5s';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
            
            // Make it closable by clicking
            notification.addEventListener('click', () => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            });
        };

        // Close modal function
        function closeImportModal() {
            const importModal = document.getElementById('importModal');
            if (importModal) {
                importModal.classList.add('hidden');
                const importForm = document.getElementById('importForm');
                if (importForm) importForm.reset();
            }
        }

        // Handle import form submission
        document.addEventListener('DOMContentLoaded', function() {
            const importForm = document.getElementById('importForm');
            if (!importForm) return;

            // Remove any existing event listeners to prevent duplicates
            const newImportForm = importForm.cloneNode(true);
            importForm.parentNode.replaceChild(newImportForm, importForm);

            newImportForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const fileInput = document.getElementById('excelFile');
                if (!fileInput.files || fileInput.files.length === 0) {
                    window.showNotification('Please select a file to upload', 'error');
                    return;
                }

                const formData = new FormData(newImportForm);
                const submitBtn = newImportForm.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn.innerHTML;

                try {
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importing...';

                    const response = await fetch('import_students.php', {
                        method: 'POST',
                        body: formData
                    });

                    // First get the response as text
                    const responseText = await response.text();
                    
                    try {
                        // Try to parse as JSON
                        const data = JSON.parse(responseText);
                        
                        if (data.success) {
                            let message = `Successfully imported ${data.imported || 0} student(s).`;
                            if (data.warnings && data.warnings.length > 0) {
                                message += ` ${data.warnings.length} warning(s) occurred.`;
                                console.warn('Import warnings:', data.warnings);
                            }
                            
if (data.success) {
                                console.log('Success:', data);
                                // Show success modal with imported data
                                const successModal = document.getElementById('successModal');
                                const successMessage = document.getElementById('successMessage');
                                const sectionName = '<?php echo isset($section_name) ? addslashes($section_name) : ""; ?>';
                                
                                if (successModal && successMessage) {
                                    // Format the success message
                                    let message = `
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-center text-green-500 mb-4">
                                                <i class="fas fa-check-circle text-4xl mr-2"></i>
                                                <span class="text-xl font-semibold">Import Successful!</span>
                                            </div>
                                            <div class="bg-green-50 p-4 rounded-lg">
                                                <p class="text-center mb-2">
                                                    <span class="font-semibold">${data.imported || 0} students</span> have been successfully imported to:
                                                </p>
                                                <div class="text-center">
                                                    <p class="text-lg font-medium">${sectionName || 'Selected Section'}</p>
                                                </div>
                                            </div>
                                    `;
                                    
                                    // Add warnings if any
                                    if (data.warnings && data.warnings.length > 0) {
                                        message += `
                                            <div class="bg-yellow-50 p-3 rounded-lg mt-4">
                                                <p class="text-yellow-700 font-medium mb-2">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> ${data.warnings.length} warning(s) occurred:
                                                </p>
                                                <ul class="text-sm text-yellow-600 list-disc pl-5">
                                                    ${data.warnings.map(warning => `<li>${warning}</li>`).join('')}
                                                </ul>
                                            </div>
                                        `;
                                    }
                                    
                                    message += `
                                        <div class="mt-6 flex justify-center">
                                            <button onclick="location.reload()" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                                <i class="fas fa-check mr-2"></i> Done
                                            </button>
                                        </div>
                                    </div>`;
                                    
                                    successMessage.innerHTML = message;
                                    successModal.classList.remove('hidden');
                                    
                                    // Close the import modal
                                    const importModal = document.getElementById('importModal');
                                    if (importModal) {
                                        importModal.classList.add('hidden');
                                    }
                                    
                                    // Reset the form
                                    const importForm = document.getElementById('importForm');
                                    if (importForm) importForm.reset();
                                }
                            showSuccessModal(data.imported || 0, sectionName);
                            // Close import modal
                            const importModal = document.getElementById('importModal');
                            importModal.classList.add('hidden');
                            // Refresh the table data
                            if (typeof createExcelEditor === 'function') {
                                createExcelEditor();
                            }
                        } else {
                            throw new Error(data.error || 'Import failed');
                        }
                    } catch (jsonError) {
                        console.error('Failed to parse JSON:', responseText);
                        throw new Error('Invalid server response. Please check the server logs.');
                    }
                } catch (error) {
                    console.error('Import error:', error);
                    window.showNotification(error.message || 'Failed to import file. Please try again.', 'error');
                } finally {
                    // Reset button state
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnContent;
                    }
                }
            });
        });
    </script>
    <script>
    // Section update for add student form
    document.addEventListener('DOMContentLoaded', function() {
        // Function to update section in the add student form
        function updateSectionInForm() {
            const sectionHeader = document.querySelector('.section-indicator .font-semibold');
            const currentSectionInput = document.getElementById('currentSection');
            const sectionInput = document.getElementById('sectionInput');
            
            if (sectionHeader && currentSectionInput) {
                const sectionText = sectionHeader.textContent.trim();
                currentSectionInput.value = sectionText;
                sectionInput.value = sectionText;
            } else {
                console.log('Section elements not found');
                console.log('Section header:', sectionHeader);
                console.log('Current section input:', currentSectionInput);
            }
        }

        // Update section when the add student button is clicked - DISABLED for Guidance role
        // const addStudentBtn = document.getElementById('addStudentBtn');
        // if (addStudentBtn) {
        //     addStudentBtn.addEventListener('click', function() {
        //         // Small delay to ensure the modal is open before updating
        //         setTimeout(updateSectionInForm, 100);
        //     });
        // }

        // Also update when the modal is shown (in case it's opened in another way)
        const addStudentModal = document.getElementById('addStudentModal');
        if (addStudentModal) {
            // For Bootstrap modals
            addStudentModal.addEventListener('shown.bs.modal', updateSectionInForm);
            
            // For non-Bootstrap modals
            addStudentModal.addEventListener('show', updateSectionInForm);
            
            // Also add a click event to the modal open button if it exists
            const modalOpenButtons = document.querySelectorAll('[data-modal-toggle="addStudentModal"]');
            modalOpenButtons.forEach(button => {
                button.addEventListener('click', function() {
                    setTimeout(updateSectionInForm, 100);
                });
            });
        }

        // Update on page load if modal is already open
        if (addStudentModal && !addStudentModal.classList.contains('hidden')) {
            updateSectionInForm();
        }
    });
    </script>

    <!-- Import Success Modal -->
    <div id="importSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-green-600">
                    <i class="fas fa-check-circle mr-2"></i>
                    Import Successful
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="hideImportSuccessModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4">
                <p id="importSuccessMessage" class="text-gray-700"></p>
                <div id="importWarningMessages" class="mt-3"></div>
            </div>
            <div class="flex justify-end">
                <button type="button" 
                        class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                        onclick="hideImportSuccessModal()">
                    OK
                </button>
            </div>
        </div>
    </div>

    <script>
    // Function to show the import success modal
    window.showImportSuccess = function(data) {
        const modal = document.getElementById('importSuccessModal');
        const message = document.getElementById('importSuccessMessage');
        const warnings = document.getElementById('importWarningMessages');
        
        // Set success message
        message.textContent = `Successfully imported ${data.count} students to section: ${data.section}`;
        
        // Show warnings if any
        warnings.innerHTML = '';
        if (data.warnings && data.warnings.length > 0) {
            const warningDiv = document.createElement('div');
            warningDiv.className = 'alert alert-warning mt-3';
            warningDiv.innerHTML = '<strong>Warnings:</strong><br>' + data.warnings.join('<br>');
            warnings.appendChild(warningDiv);
        }
        
        // Show the modal
        modal.classList.remove('hidden');
        
        // Reload the page after 2 seconds
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    };

    // Function to hide the import success modal
    function hideImportSuccessModal() {
        const modal = document.getElementById('importSuccessModal');
        modal.classList.add('hidden');
        window.location.reload();
    }
    </script>
    <!-- Import Success Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="importSuccessModal">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-green-600">
                    <i class="fas fa-check-circle mr-2"></i>
                    Import Successful
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="hideImportSuccessModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <p class="text-gray-700">
                    Successfully imported <span id="importedCount" class="font-semibold">0</span> students to:
                </p>
                <div class="bg-gray-50 p-4 rounded-md">
                    <h4 id="importSectionName" class="font-medium text-gray-900"></h4>
                </div>
                <div id="importWarnings" class="mt-4 hidden">
                    <h5 class="text-sm font-medium text-yellow-700 mb-2">Warnings:</h5>
                    <ul id="warningList" class="text-sm text-yellow-600 space-y-1"></ul>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="hideImportSuccessModal()" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Close
                </button>
            </div>
        </div>
    </div>
    <script>
        // Debug: Client-side debugging and section name handling
        document.addEventListener('DOMContentLoaded', function() {
            // Debug: Log the section name from the DOM
            const sectionNameElement = document.getElementById('sectionNameDisplay');
            if (sectionNameElement) {
                console.log('Section name in DOM:', sectionNameElement.textContent.trim());
            } else {
                console.error('Section name element not found!');
            }
            
            // Debug: Check URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const sectionId = urlParams.get('section_id');
            console.log('URL section_id parameter:', sectionId);
            
            // Check if we have a section_id but the section name is still "All Sections"
            if (sectionId && sectionNameElement && sectionNameElement.textContent.trim() === 'All Sections') {
                console.log('Section ID exists but name is "All Sections" - attempting to fix...');
                
                // Try to get the section name from the page title or other elements
                const possibleSectionNames = [];
                
                // Check for common section name patterns in the page
                document.querySelectorAll('h1, h2, h3, .section-title, .class-name, td, th, .text-xl, .text-lg').forEach(el => {
                    const text = el.textContent.trim();
                    if (text && text !== 'All Sections' && text.length < 50 && !text.includes('School Form')) {
                        possibleSectionNames.push(text);
                    }
                });
                
                // If we found possible section names, update the display
                if (possibleSectionNames.length > 0) {
                    sectionNameElement.textContent = possibleSectionNames[0];
                    console.log('Updated section name to:', possibleSectionNames[0]);
                    
                    // Also update the page title if it contains 'All Sections'
                    if (document.title.includes('All Sections')) {
                        document.title = document.title.replace('All Sections', possibleSectionNames[0]);
                    }
                } else {
                    // If we couldn't find a section name, try to construct it from the URL
                    const urlSegments = window.location.pathname.split('/');
                    const lastSegment = urlSegments[urlSegments.length - 1];
                    if (lastSegment.includes('section_id=')) {
                        const sectionPart = lastSegment.split('section_id=')[1];
                        if (sectionPart) {
                            const cleanName = sectionPart
                                .replace(/%20/g, ' ')  // Replace %20 with space
                                .replace(/[^a-zA-Z0-9\s-]/g, '')  // Remove special chars
                                .trim();
                            if (cleanName) {
                                sectionNameElement.textContent = cleanName;
                                console.log('Set section name from URL parameter:', cleanName);
                            }
                        }
                    }
                }
            }
            
            // Debug: Check if section name is being overridden by JavaScript
            console.log('Is section name being overridden?', 
                window.getComputedStyle(sectionNameElement, '::before') || 
                window.getComputedStyle(sectionNameElement, '::after'));
        });
    </script>
</body>
</html>