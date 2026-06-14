<?php 
session_start();
include '../db_connect.php';

// Set the school year to match the database
$school_year = "2025-2026"; // Hardcoded to match the database

// Save the current connection
$original_conn = $conn;

// Create a new connection to the database
require_once '../db_connect.php';
$conn->select_db('admindihs');

// Debug: Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug: Print the school year being used
error_log("Fetching school days for year: " . $school_year);

// Fetch school days for the current school year
$school_days = [];
$school_days_query = "SELECT month_name, school_days 
                    FROM school_days 
                    WHERE school_year = ? 
                    ORDER BY FIELD(month_name, 'June', 'July', 'August', 'September', 'October', 
                                 'November', 'December', 'January', 'February', 'March', 'April', 'May')";

$stmt = $conn->prepare($school_days_query);
if ($stmt === false) {
    die('Error in prepare: ' . $conn->error);
}

$stmt->bind_param('s', $school_year);
if (!$stmt->execute()) {
    die('Error executing query: ' . $stmt->error);
}

$result = $stmt->get_result();
if ($result === false) {
    die('Error getting result: ' . $conn->error);
}

// Debug: Check if we got any results
$row_count = 0;
while ($row = $result->fetch_assoc()) {
    $school_days[$row['month_name']] = $row['school_days'];
    $row_count++;
}
$stmt->close();

// Debug: Log the number of rows found
error_log("Found $row_count months of school days data");

// Switch back to the original database
$conn = $original_conn;

// Debug: Output the school days array
error_log("School days data: " . print_r($school_days, true));

// Debug: Check if school days were fetched
if (empty($school_days)) {
    error_log("No school days found for school year: " . $school_year);
    // Try to fetch any data from the table to verify connection
    $test_query = "SHOW TABLES LIKE 'school_days'";
    $test_result = $conn->query($test_query);
    if ($test_result->num_rows === 0) {
        error_log("Error: school_days table does not exist");
    } else {
        error_log("school_days table exists");
        // Check if any data exists
        $count_query = "SELECT COUNT(*) as count FROM school_days";
        $count_result = $conn->query($count_query);
        $count = $count_result->fetch_assoc()['count'];
        error_log("Total records in school_days table: " . $count);
    }
} else {
    error_log("School days data: " . print_r($school_days, true));
}

$adviser_id = $_SESSION['user_id'] ?? null;
$role       = $_SESSION['role'] ?? null;

// Handle AJAX update for section assignment
if (isset($_POST['lrns']) && isset($_POST['section'])) {
    $lrns = $_POST['lrns'];
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    
    // Fetch grade_level and semester for the selected section
    $section_query = "SELECT grade_level, semester FROM section WHERE class_name = '$section'";
    $section_result = mysqli_query($conn, $section_query);
    if ($section_result && mysqli_num_rows($section_result) > 0) {
        $section_data = mysqli_fetch_assoc($section_result);
        $status = "Assigned to " . $section_data['grade_level'] . " - " . $section_data['semester'];
        
        // Update sf9 table for each LRN
        foreach ($lrns as $lrn) {
            $lrn = mysqli_real_escape_string($conn, $lrn);
            $update = "UPDATE sf9 SET section = '$section', status = '$status' WHERE LRN = '$lrn'";
            mysqli_query($conn, $update);
        }
        
        echo 'success';
    } else {
        echo 'error: Invalid section';
    }
    exit;
}

// Handle AJAX for fetching attendance data
if (isset($_POST['action']) && $_POST['action'] === 'fetch_attendance') {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $response = ['success' => false, 'data' => [], 'error' => ''];
    
    try {
        $query = "SELECT month, present_days, absent_days, tardy_days 
                 FROM admindihs.monthly_attendance_summary 
                 WHERE LRN = ? AND year = YEAR(CURDATE())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $lrn);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $month_name = date('F', mktime(0, 0, 0, $row['month'], 1));
            $data[$row['month']] = [
                'present_days' => $row['present_days'],
                'absent_days' => $row['absent_days'],
                'tardy_days' => $row['tardy_days']
            ];
        }
        
        $response['success'] = true;
        $response['data'] = $data;
        $stmt->close();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
// Handle AJAX for saving attendance
if (isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $attendance_data = json_decode($_POST['attendance_data'], true);

    $response = ['success' => false, 'error' => ''];

    try {
        foreach ($attendance_data as $entry) {
            $month = (int)$entry['month'];
            $school_days = (int)$entry['school_days'];
            $present_days = (int)$entry['present_days'];
            $absent_days = (int)$entry['absent_days'];

            // First, check if a record exists for this LRN and month
            $check_stmt = $conn->prepare("
                SELECT id FROM monthly_attendance
                WHERE LRN = ? AND month = ?
                LIMIT 1
            ");
            $check_stmt->bind_param("si", $lrn, $month);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                // Record exists - perform UPDATE
                $update_stmt = $conn->prepare("
                    UPDATE monthly_attendance
                    SET school_days = ?, present_days = ?, absent_days = ?
                    WHERE LRN = ? AND month = ?
                ");
                $update_stmt->bind_param("iiisi", $school_days, $present_days, $absent_days, $lrn, $month);

                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update attendance for month $month: " . $update_stmt->error);
                }

                $update_stmt->close();
            } else {
                // No record - perform INSERT
                $insert_stmt = $conn->prepare("
                    INSERT INTO monthly_attendance (LRN, month, school_days, present_days, absent_days)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert_stmt->bind_param("siiii", $lrn, $month, $school_days, $present_days, $absent_days);

                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to insert attendance for month $month: " . $insert_stmt->error);
                }

                $insert_stmt->close();
            }

            $check_stmt->close();
        }

        $response['success'] = true;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}


// Check for errors and form data in session
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']); // Clear session data

// Fetch sections for dropdown and details
$sections_data = [];
$section_query = "
    SELECT s.*, CONCAT(accounts.`First Name`, ' ', accounts.`Last Name`) as adviser_name
    FROM section s
    LEFT JOIN accounts ON s.adviser = accounts.id
";
$section_result = mysqli_query($conn, $section_query);
if ($section_result === false) {
    die("Section query failed: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($section_result)) {
    $class_name = $row['class_name'];
    
    // Get current students count
    $count_query = "SELECT COUNT(*) as count FROM sf9 WHERE section = '$class_name'";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $current_count = $count_row['count'];
    
    $sections_data[$class_name] = [
        'adviser' => $row['adviser_name'] ?: 'No Adviser',
        'track' => $row['track'],
        'grade_level' => $row['grade_level'],
        'semester' => $row['semester'],
        'current_students' => $current_count
    ];
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Student Assigning Section</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <!-- SweetAlert2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            #main {
                transition: margin-left 0.3s;
            }
            .sidebar-collapsed + #main {
                margin-left: 64px;
            }
        </style>
    </head>
    <body class="bg-gray-100">
        <?php include '../includes/unified_sidebar.php'; ?>
        <div class="flex h-screen bg-gray-100">
            <!-- Sidebar will be included here -->
            <div class="flex-1 overflow-auto">
                <div id="main" class="p-4 ml-0 md:ml-64 transition-all duration-300">
                    <div class="container-fluid">
                        <div class="card bg-white">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">SF9 - Academic Record</h5>
                                <!-- <button class="btn btn-primary" id="assignSection">Assign Section</button> -->
                            </div>
                            <div class="card-body">
                                <table id="studentTable" class="display table table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>LRN</th>
                                            <th>Name</th>
                                            <th>Grade Level</th>
                                            <th>Track</th>
                                            <th>Status</th>
                                            <th>Core Values</th>
                                            <th>Subjects & Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($role === 'Adviser' && $adviser_id) {
                                            $student_query = "
                                                SELECT 
                                                    sf9.LRN, 
                                                    sf1.name, 
                                                    sf9.status, 
                                                    sf9.section,
                                                    section.grade_level AS grade_level,
                                                    section.track AS track
                                                FROM sf9
                                                INNER JOIN sf1 ON sf9.LRN = sf1.LRN
                                                INNER JOIN section ON sf9.section = section.class_name
                                                WHERE section.adviser = ?
                                            ";
                                            $stmt = $conn->prepare($student_query);
                                            $stmt->bind_param("i", $adviser_id);
                                            $stmt->execute();
                                            $student_result = $stmt->get_result();

                                            if ($student_result->num_rows > 0) {
                                                while ($student = $student_result->fetch_assoc()) {
                                                    echo "<tr>
                                                        <td>{$student['LRN']}</td>
                                                        <td>" . htmlspecialchars($student['name']) . "</td>
                                                        <td>" . htmlspecialchars($student['grade_level']) . "</td>
                                                        <td>" . htmlspecialchars($student['track']) . "</td>
                                                        <td>" . htmlspecialchars($student['status']) . "</td>
                                                        <td><button class='btn btn-sm btn-success coreValuesBtn' data-lrn='{$student['LRN']}' data-name='" . htmlspecialchars($student['name']) . "'>Add</button></td>
                                                        <td><button class='btn btn-sm btn-success subjectBtn' data-lrn='{$student['LRN']}' data-name='" . htmlspecialchars($student['name']) . "' data-grade-level='" . htmlspecialchars($student['grade_level']) . "' title='Add'>Add</button></td>
                                                        <td class='text-center'>
                                                            <div class='d-flex justify-content-center gap-1'>
                                                                <button class='btn btn-sm btn-primary viewStudentBtn' data-lrn='{$student['LRN']}' title='View'>
                                                                    <i class='fas fa-download'></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo '<tr><td colspan="9" class="text-center">No students found for this adviser.</td></tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="9" class="text-center">Unauthorized access.</td></tr>';
                                        }
                                        // Removed the redundant $stmt->close() as it's already closed in the loop
                                        ?>
                                    </tbody>
                                </table>
                            </div>
            </div>
        </div>

        <!-- Assign Modal -->
        <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Section</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="sectionSelect" class="form-label">Section:</label>
                            <select id="sectionSelect" class="form-select">
                                <option value="">Select Section</option>
                                <?php
                                foreach ($sections_data as $class_name => $data) {
                                    echo "<option value='$class_name'>$class_name</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div id="sectionDetails" style="display: none;">
                            <p><strong>Current Students:</strong> <span id="currentStudents"></span></p>
                            <p><strong>Adviser:</strong> <span id="adviser"></span></p>
                            <p><strong>Track:</strong> <span id="track"></span></p>
                            <p><strong>Grade Level & Semester:</strong> <span id="gradeLevelSemester"></span></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="assignBtn">Assign</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Core Values Modal -->
        <div class="modal fade" id="coreValuesModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Core Values for <span id="coreStudentName"></span> (LRN: <span id="coreStudentLRN"></span>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Core Values</th>
                                    <th>Behavior Statement</th>
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan="2">Maka-Diyos</td>
                                    <td>Expresses one’s spiritual beliefs while respecting the spiritual beliefs of others</td>
                                    <td>
                                        <select class="form-control" data-quarter="1" data-field="makadiyos">
                                      
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makadiyos">
                                   
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makadiyos">
                                      
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makadiyos">
                                        
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Shows adherence to ethical principles by upholding truth</td>
                                    <td>
                                        <select class="form-control" data-quarter="1" data-field="makadiyos_2">
                                            
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makadiyos_2">
                                           
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makadiyos_2">

                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makadiyos_2">
                                           
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td rowspan="2">Makatao</td>
                                    <td>Is sensitive to individual, social, and cultural differences</td>
                                    <td>
                                        <select class="form-control" data-quarter="1" data-field="makatao">
                                            
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makatao">
                                         
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makatao">
                                    
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makatao">
                                           
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Demonstrates contributions toward solidarity</td>
                                    <td>
                                        <select class="form-control" data-quarter="1" data-field="makatao_2">
                                            
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makatao_2">
                                          
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makatao_2">
                                         
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makatao_2">
                                           
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Makakalikasan</td>
                                    <td>Cares for the environment and utilizes resources wisely, judiciously, and economically</td>
                                    <td>
                                        <select class="form-control" data-quarter="1" data-field="makakalikasan">
                                            
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makakalikasan">
                                            
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makakalikasan">
                                            
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makakalikasan">
                                         
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td rowspan="2">Makabansa</td>
                                    <td>Demonstrates pride in being a Filipino; exercises the rights and responsibilities of a Filipino citizen</td>
                                    <td>
                                        <select class="form-control" data-quarter="1" data-field="makabansa">
                                          
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makabansa">
                                          
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makabansa">
                                           
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makabansa">
                                         
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Demonstrates appropriate behavior in carrying out activities in the school, community, and country</td>
                                    <td>
                                        <select class="form-control" data-quarter="1" data-field="makabansa_2">
                                        
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makabansa_2">
                                          
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makabansa_2">
                                          
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makabansa_2">
                                           
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveCoreValuesBtn">Submit</button>
                    </div>
                </div>
            </div>
        </div>

<!-- Subjects & Grades Modal -->
<div class="modal fade" id="subjectsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
<h5 class="modal-title">Subjects & Grades for <span id="subjectStudentName"></span> (LRN: <span id="subjectStudentLRN"></span>, Grade Level: <span id="subjectGradeLevel"></span>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="semesterSelect" class="form-label">Semester:</label>
                    <select id="semesterSelect" class="form-select">
                        <option value="">Select Semester</option>
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                    </select>
                </div>
                <div id="subjectsTableContainer" style="display: none;">
                    <style>
                .passed { color: #198754; font-weight: bold; }
                .failed { color: #dc3545; font-weight: bold; }
                .grade-dropdown { min-width: 80px; }
            </style>
            <table class="table table-bordered" id="subjectsTable">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th data-quarter="1">Quarter 1</th>
                        <th data-quarter="2">Quarter 2</th>
                        <th>Final Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Subjects will be populated here -->
                </tbody>
            </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveSubjectsBtn">Submit</button>
            </div>
        </div>
    </div>
</div>

        <!-- Development Notice Modal -->
<div class="modal fade" id="devNoticeModal" tabindex="-1" aria-labelledby="devNoticeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="devNoticeModalLabel">Notice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fas fa-tools fa-3x mb-3 text-muted"></i>
        <p class="fs-5">Feature is still in development</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


        <!-- jQuery, Select2, and SweetAlert2 JS -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- DataTables JS and Buttons -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script>
            var sections = <?php echo json_encode($sections_data); ?>;

            function updateQuarterHeaders(semester) {
                var isSecondSemester = semester && semester.toString().includes('2');
                var q1Label = isSecondSemester ? 'Quarter 3' : 'Quarter 1';
                var q2Label = isSecondSemester ? 'Quarter 4' : 'Quarter 2';
                $('#subjectsTable thead th[data-quarter="1"]').text(q1Label);
                $('#subjectsTable thead th[data-quarter="2"]').text(q2Label);
            }

            // Function to adjust logo position based on sidebar state
            function adjustLogoPosition() {
                const sidebar = $('.sidebar');
                const watermarkLogo = $('.watermark-logo');
                const main = $('#main');
                
                if (!sidebar.length || !watermarkLogo.length || !main.length) return;
                
                const sidebarRect = sidebar[0].getBoundingClientRect();
                const mainRect = main[0].getBoundingClientRect();
                
                watermarkLogo.css({
                    left: '50%',
                    top: '50%',
                    transform: 'translate(-50%, -50%)'
                });
            }
            
            window.addEventListener('sidebarToggle', function(e) {
                const body = document.body;
                if (e.detail.collapsed) {
                    body.classList.add('sidebar-collapsed');
                } else {
                    body.classList.remove('sidebar-collapsed');
                }
                
                setTimeout(adjustLogoPosition, 100);
            });
            
            window.addEventListener('mobileMenuToggle', function(e) {
                const main = $('#main');
                if (e.detail.active) {
                    main.css('marginTop', '56px');
                } else {
                    main.css('marginTop', '0');
                }
                
                setTimeout(adjustLogoPosition, 100);
            });

            // Initialize DataTable
            $(document).ready(function() {
                var table = $('#studentTable').DataTable({
                    pageLength: 20,
                    responsive: true,
                    search: {
                        smart: true
                    },
                    columnDefs: [
                        { orderable: false, targets: [0, 5, 6, 7] } // Disable sorting on checkbox and new columns
                    ]
                });

                // Select All functionality
                $('#selectAll').on('click', function() {
                    $('.rowCheckbox').prop('checked', this.checked);
                });

                // Show modal on Assign Section button click
                $('#assignSection').on('click', function() {
                    if ($('.rowCheckbox:checked').length === 0) {
                        Swal.fire('Warning', 'Please select at least one student', 'warning');
                        return;
                    }
                    $('#assignModal').modal('show');
                });

                // Show section details on select change
                $('#sectionSelect').on('change', function() {
                    var selected = $(this).val();
                    if (selected && sections[selected]) {
                        var details = sections[selected];
                        $('#currentStudents').text(details.current_students);
                        $('#adviser').text(details.adviser);
                        $('#track').text(details.track);
                        $('#gradeLevelSemester').text(details.grade_level + ' ' + details.semester);
                        $('#sectionDetails').show();
                    } else {
                        $('#sectionDetails').hide();
                    }
                });

                // Assign button click
                $('#assignBtn').on('click', function() {
                    var section = $('#sectionSelect').val();
                    if (!section) {
                        Swal.fire('Warning', 'Please select a section', 'warning');
                        return;
                    }

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to assign the selected students to this section?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, assign!',
                        cancelButtonText: 'No, cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var selectedLRNs = [];
                            $('.rowCheckbox:checked').each(function() {
                                selectedLRNs.push($(this).val());
                            });

                            $.ajax({
                                url: '', // Same file
                                method: 'POST',
                                data: { lrns: selectedLRNs, section: section },
                                success: function(response) {
                                    if (response === 'success') {
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error', 'An error occurred', 'error');
                                }
                            });
                        }
                    });
                });

                // Attendance button click handler to preserve school days
                $(document).on('click', '.attendanceBtn', function() {
                    var lrn = $(this).data('lrn');
                    var name = $(this).data('name');
                    $('#studentName').text(name);
                    $('#studentLRN').text(lrn);
                    
                    // Store the current school days values before clearing
                    var schoolDaysData = {};
                    $('.school_days').each(function() {
                        var month = $(this).data('month');
                        schoolDaysData[month] = $(this).val();
                    });
                    
                    // Clear only present and absent days
                    $('.present_days, .absent_days').val('');
                    
                    // Fetch existing attendance data
                    $.ajax({
                        url: '',
                        method: 'POST',
                        data: {
                            action: 'fetch_attendance',
                            lrn: lrn
                        },
                        success: function(response) {
                            var result = JSON.parse(response);
                            if (result.success) {
                                // Populate inputs with fetched data
                                $.each(result.data, function(month, data) {
                                    var $row = $(`input.school_days[data-month="${month}"]`).closest('tr');
                                    // Only update present and absent days, keep school days from PHP
                                    $row.find('.present_days').val(data.present_days || '');
                                    $row.find('.absent_days').val(data.absent_days || '');
                                });
                                
                                // Restore school days from PHP after populating other data
                                $('.school_days').each(function() {
                                    var month = $(this).data('month');
                                    if (schoolDaysData[month]) {
                                        $(this).val(schoolDaysData[month]);
                                    }
                                });
                                
                                $('#attendanceModal').modal('show');
                            } else {
                                Swal.fire('Error', result.error || 'Failed to fetch attendance data', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'An error occurred while fetching attendance data', 'error');
                        }
                    });
                });

                // Dynamic calculation for Absent Days
                $('#attendanceModal').on('input', '.school_days, .present_days', function() {
                    var $row = $(this).closest('tr');
                    var schoolDays = parseInt($row.find('.school_days').val()) || 0;
                    var presentDays = parseInt($row.find('.present_days').val()) || 0;
                    var absentDays = schoolDays - presentDays;
                    $row.find('.absent_days').val(absentDays >= 0 ? absentDays : 0);
                });

                // Save attendance button click
                $('#saveAttendanceBtn').on('click', function() {
                    var lrn = $('#studentLRN').text();
                    var attendanceData = [];

                    $('#attendanceModal tbody tr').each(function() {
                        var month = $(this).find('.school_days').data('month');
                        var school_days = parseInt($(this).find('.school_days').val()) || 0;
                        var present_days = parseInt($(this).find('.present_days').val()) || 0;
                        var absent_days = parseInt($(this).find('.absent_days').val()) || 0;

                        if (school_days > 0 || present_days > 0 || absent_days > 0) {
                            attendanceData.push({
                                month: month,
                                school_days: school_days,
                                present_days: present_days,
                                absent_days: absent_days
                            });
                        }
                    });

                    if (attendanceData.length === 0) {
                        Swal.fire('Warning', 'Please enter attendance data for at least one month', 'warning');
                        return;
                    }

                    $.ajax({
                        url: '',
                        method: 'POST',
                        data: {
                            action: 'save_attendance', 
                            lrn: lrn,
                            attendance_data: JSON.stringify(attendanceData)
                        },
                        success: function(response) {
                            var result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire('Success', 'Attendance saved successfully', 'success');
                                $('#attendanceModal').modal('hide');
                            } else {
                                Swal.fire('Error', result.error || 'An error occurred while saving attendance', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'An error occurred', 'error');
                        }
                    });
                });

                // Core Values button click
                $(document).on('click', '.coreValuesBtn', function() {
                    var lrn = $(this).data('lrn');
                    var name = $(this).data('name');
                    $('#coreStudentName').text(name);
                    $('#coreStudentLRN').text(lrn);
                    
                    // Reset selects to default
                    $('#coreValuesModal select').val('');
                    
                    // Fetch existing core values data
                    $.ajax({
                        url: 'fetch_core_values.php',
                        method: 'POST',
                        data: { lrn: lrn },
                        success: function(response) {
                            var result = JSON.parse(response);
                            if (result.success) {
                                // Populate selects with fetched data
                                $.each(result.data, function(quarter, data) {
                                    $('#coreValuesModal select[data-quarter="' + quarter + '"]').each(function() {
                                        var field = $(this).data('field');
                                        $(this).val(data[field] || '');
                                    });
                                });
                                $('#coreValuesModal').modal('show');
                            } else {
                                Swal.fire('Error', result.error || 'Failed to fetch core values data', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'An error occurred while fetching core values data', 'error');
                        }
                    });
                });

                // Save core values button click
                $('#saveCoreValuesBtn').on('click', function() {
                    var lrn = $('#coreStudentLRN').text();
                    var coreData = {1: {}, 2: {}, 3: {}, 4: {}};

                    $('#coreValuesModal select').each(function() {
                        var quarter = $(this).data('quarter');
                        var field = $(this).data('field');
                        var value = $(this).val();
                        if (value !== '') {
                            coreData[quarter][field] = value;
                        }
                    });

                    // Filter quarters with data
                    var filteredCoreData = {};
                    $.each(coreData, function(quarter, fields) {
                        if (Object.keys(fields).length > 0) {
                            filteredCoreData[quarter] = fields;
                        }
                    });

                    if (Object.keys(filteredCoreData).length === 0) {
                        Swal.fire('Warning', 'Please select core values for at least one quarter', 'warning');
                        return;
                    }

                    $.ajax({
                        url: 'save_core_values.php',
                        method: 'POST',
                        data: {
                            lrn: lrn,
                            core_data: JSON.stringify(filteredCoreData)
                        },
                        success: function(response) {
                            var result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire('Success', 'Core values saved successfully', 'success');
                                $('#coreValuesModal').modal('hide');
                            } else {
                                Swal.fire('Error', result.error || 'An error occurred while saving core values', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'An error occurred', 'error');
                        }
                    });
                });

                // Handle download button click
                $(document).on('click', '.viewStudentBtn', function() {
                    var lrn = $(this).data('lrn');
                    window.location.href = 'export_sf9.php?lrn=' + lrn;
                });
            });


        </script> 
        <script>
$(document).ready(function () {
    // Show the modal for view, print, or subject buttons
    $(document).on('click', '.printStudentBtn', function () {
        $('#devNoticeModal').modal('show');
    });

    // Subjects & Grades logic
    function createGradeOptions(selectedValue = '') {
        let options = '<option value=""></option>';
        for (let i = 100; i >= 70; i--) {
            const selected = i == selectedValue ? 'selected' : '';
            options += `<option value="${i}" ${selected}>${i}</option>`;
        }
        return options;
    }

    var firstSemSubjects = [
        {name: 'Oral Communication', field: 'oralcom'},
        {name: 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', field: 'komunikasyon'},
        {name: 'Introduction to the Philosophy of the Human Person /Pambungad sa Pilosopiya ng Tao', field: 'intro_philosophy'},
        {name: 'Physical Education and Health 1', field: 'physical_educ1'},
        {name: 'General Mathematics', field: 'gen_math'},
        {name: 'Earth Science', field: 'earth_sci'},
        {name: 'Empowerment Technologies', field: 'empower'},
        {name: 'Pre-Calculus', field: 'precal'},
        {name: 'General Chemistry 1', field: 'gen_chem1'}
    ];

    var secondSemSubjects = [
        {name: 'Reading and Writing', field: 'read_writing'},
        {name: 'Pagbasa at Pagsusuri ng Iba’t-Ibang Teksto Tungo sa Pananaliksik', field: 'pagbasa'},
        {name: 'Personal Development/ Pansariling Kaunlaran', field: 'personal_dev'},
        {name: 'Physical Education and Health 2', field: 'physical_educ2'},
        {name: 'Statistics and Probability', field: 'stats_proba'},
        {name: 'Disaster Readiness and Risk Reduction', field: 'disaster'},
        {name: 'Practical Research 1', field: 'prac_research1'},
        {name: 'Basic Calculus', field: 'basic_cal'},
        {name: 'General Chemistry 2', field: 'gen_chem2'}
    ];

    function getRemarks(grade) {
        grade = parseFloat(grade);
        if (isNaN(grade)) return { text: '', class: '' };
        if (grade >= 75) return { text: 'Passed', class: 'passed' };
        return { text: 'Failed', class: 'failed' };
    }

    function createGradeDropdown(className, field, value = '') {
        var select = `<select class="form-select grade-dropdown ${className}" data-field="${field}">`;
        select += '<option value=""></option>';
        for (var i = 100; i >= 70; i--) {
            var selected = (i == value) ? 'selected' : '';
            select += `<option value="${i}" ${selected}>${i}</option>`;
        }
        select += '</select>';
        return select;
    }

    function updateFinalGrade($row) {
        var q1 = parseInt($row.find('.q1').val()) || 0;
        var q2 = parseInt($row.find('.q2').val()) || 0;
        var finalGrade = '';
        
        if (q1 > 0 && q2 > 0) {
            finalGrade = Math.round((q1 + q2) / 2);
        }
        
        $row.find('.final').val(finalGrade || '');
        
        // Update remarks
        var remarks = getRemarks(finalGrade);
        var $remarksCell = $row.find('.remarks');
        $remarksCell.text(remarks.text).removeClass('passed failed').addClass(remarks.class);
    }

    function populateSubjects(semester, section, lrn = null) {
        var tbody = $('#subjectsTable tbody');
        tbody.html('<tr><td colspan="5" class="text-center">Loading subjects...</td></tr>');
        
        // Prepare data for curriculum fetch
        var curriculumData = {
            grade_level: section.grade_level,
            track: section.track,
            semester: semester
        };
        
        // Include LRN if available (for fetching failed subjects in 2nd semester)
        if (lrn) {
            curriculumData.lrn = lrn;
        }
        
        // Fetch subjects from curriculum based on grade level, track, and semester
        $.ajax({
            url: 'fetch_curriculum.php',
            method: 'POST',
            data: curriculumData,
            success: function(response) {
                console.log('Curriculum response:', response);
                try {
                    // Check if response is already an object or needs to be parsed
                    var result = typeof response === 'string' 
                        ? JSON.parse(response) 
                        : response;
                    
                    if (result && result.success && result.subjects && result.subjects.length > 0) {
                        tbody.empty();
                        
                        // Group subjects by type
                        var coreSubjects = [];
                        var appliedSubjects = [];
                        
                        result.subjects.forEach(function(subject) {
                            // Mark failed subjects with a visual indicator
                            if (subject.is_failed) {
                                subject.subject_name = subject.subject_name + ' (Failed from 1st Sem)';
                            }
                            
                            if (subject.subject_type === 'Applied') {
                                appliedSubjects.push(subject);
                            } else {
                                coreSubjects.push(subject);
                            }
                        });
                        
                        // Add core subjects section
                        if (coreSubjects.length > 0) {
                            tbody.append('<tr class="table-primary"><th colspan="5">Core Subjects</th></tr>');
                            addSubjectsToTable(coreSubjects, tbody);
                        }
                        
                        // Add applied subjects section
                        if (appliedSubjects.length > 0) {
                            tbody.append('<tr class="table-info"><th colspan="5">Applied Subjects</th></tr>');
                            addSubjectsToTable(appliedSubjects, tbody);
                        }
                        
                        // Populate with existing grades if any
                        populateExistingGrades();
                        
                        // Show the table
                        $('#subjectsTableContainer').show();
                        
                    } else {
                        var errorMsg = (result && result.error) || 'No subjects found for this grade level and track.';
                        console.error('No subjects found or error:', errorMsg);
                        tbody.html(`<tr><td colspan="5" class="text-center">${errorMsg}</td></tr>`);
                    }
                } catch (e) {
                    console.error('Error processing curriculum data:', e);
                    console.error('Response type:', typeof response);
                    console.error('Response content:', response);
                    tbody.html('<tr><td colspan="5" class="text-center text-danger">Error loading subjects. Please check the console for details.</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX error
                console.error('AJAX Error:', status, error);
                tbody.html('<tr><td colspan="5" class="text-center text-danger">Error loading subjects. Please try again.</td></tr>');
            }
        });
    }
    
    function addSubjectsToTable(subjects, tbody) {
        console.log('Adding subjects to table:', subjects);
        subjects.forEach(function(subject) {
            // Create a consistent subject key format
            var field = subject.subject_code.toLowerCase().replace(/[^a-z0-9]+/g, '_');
            console.log('Adding subject:', subject.subject_code, 'Field:', field);
            
            var row = `
                <tr data-subject-code="${field}">
                    <td>${subject.subject_name} <small class="text-muted">(${subject.subject_code})</small></td>
                    <td><select class="form-select grade-dropdown q1" data-field="${field}">${createGradeOptions()}</select></td>
                    <td><select class="form-select grade-dropdown q2" data-field="${field}">${createGradeOptions()}</select></td>
                    <td><input type="text" class="form-control final" data-field="${field}" readonly></td>
                    <td class="remarks"></td>
                </tr>`;
            tbody.append(row);
        });
        
        // After adding subjects, try to populate any existing grades
        if (window.fetchedGrades) {
            console.log('Found fetched grades after adding subjects, populating...');
            populateExistingGrades();
        }
    }
    
    function populateExistingGrades() {
        if (!window.fetchedGrades) {
            console.log('No fetched grades available');
            return;
        }

        console.log('Populating grades with data:', window.fetchedGrades);
        
        // Get the current semester (1 or 2)
        var semester = $('#semesterSelect').val();
        var semesterKey = semester && semester.includes('1') ? '1' : '2';
        
        console.log('Current semester key:', semesterKey);
        
        // Get the grades data
        var q1Data = window.fetchedGrades['1'] || {};
        var q2Data = window.fetchedGrades['2'] || {};
        var finalData = window.fetchedGrades['final'] || {};
        
        console.log('Q1 data:', q1Data);
        console.log('Q2 data:', q2Data);
        console.log('Final data:', finalData);
        
        // Track if we found any grades
        var foundGrades = false;
        
        // Loop through each row in the table
        $('#subjectsTable tbody tr[data-subject-code]').each(function() {
            var $row = $(this);
            var subjectCode = $row.data('subject-code');
            
            console.log('Processing subject:', subjectCode);
            
            // Set Q1 grade if it exists
            if (q1Data[subjectCode] !== undefined && q1Data[subjectCode] !== '') {
                // Convert to integer if it's a float string (e.g., '92.00' -> 92)
                const q1Grade = parseFloat(q1Data[subjectCode]);
                if (!isNaN(q1Grade)) {
                    console.log('Setting Q1 grade for', subjectCode, 'to', Math.round(q1Grade));
                    $row.find('.q1').val(Math.round(q1Grade));
                    foundGrades = true;
                }
            }
            
            // Set Q2 grade if it exists
            if (q2Data[subjectCode] !== undefined && q2Data[subjectCode] !== '') {
                // Convert to integer if it's a float string (e.g., '92.00' -> 92)
                const q2Grade = parseFloat(q2Data[subjectCode]);
                if (!isNaN(q2Grade)) {
                    console.log('Setting Q2 grade for', subjectCode, 'to', Math.round(q2Grade));
                    $row.find('.q2').val(Math.round(q2Grade));
                    foundGrades = true;
                }
            }
            
            // Update final grade and remarks
            updateFinalGrade($row);
        });
        
        if (!foundGrades) {
            console.log('No grades were found for any subjects');
            console.log('Available fields in grades data:', 
                Object.keys({...q1Data, ...q2Data, ...finalData})
            );
        }
    }

    // Handle subject button click - using event delegation for dynamically added elements
    $(document).off('click', '.subjectBtn').on('click', '.subjectBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var lrn = $btn.data('lrn');
        var name = $btn.data('name');
        var gradeLevel = $btn.data('grade-level');
        
        // Show loading state and set up the semester dropdown
        $('#subjectStudentName').text(name);
        $('#subjectStudentLRN').text(lrn);
        $('#subjectGradeLevel').text(gradeLevel || 'Not Assigned');
        
        // Always show both semesters in the dropdown
        var semesterOptions = `
            <option value="">Select Semester</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
        `;
        $('#semesterSelect').html(semesterOptions);
        
        $('#subjectsTableContainer').hide();
        
        // Show the modal immediately
        $('#subjectsModal').modal('show');

        // First, get the student's section information
        $.ajax({
            url: 'get_student_section.php',
            method: 'POST',
            data: { lrn: lrn },
            success: function(sectionResponse) {
                console.log('Section response:', sectionResponse);
                try {
                    // Check if response is already an object or needs to be parsed
                    var sectionData = typeof sectionResponse === 'string' 
                        ? JSON.parse(sectionResponse) 
                        : sectionResponse;
                    
                    if (sectionData && sectionData.success && sectionData.section) {
                        var section = sectionData.section;
                        window.currentSection = section; // Store the section for later use
                        
                        // Set a default semester (1st Semester) and fetch grades
                        $('#semesterSelect').val('1st Semester');
                        updateQuarterHeaders('1st Semester');
                        fetchGrades(lrn, section, '1st Semester');
                        
                    } else {
                        var errorMsg = (sectionData && sectionData.error) || 'Could not find section information for this student';
                        console.error('Section data error:', errorMsg);
                        Swal.fire('Error', errorMsg, 'error');
                    }
                } catch (e) {
                    console.error('Error processing section data:', e);
                    console.error('Response type:', typeof sectionResponse);
                    console.error('Response content:', sectionResponse);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error processing section information: ' + e.message
                    });
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX error
                console.error('AJAX Error:', status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch section information: ' + (error || 'Unknown error')
                });
            }
        });
    });

    function fetchGrades(lrn, section, semester = null) {
        // Show loading state
        var tbody = $('#subjectsTable tbody');
        tbody.html('<tr><td colspan="5" class="text-center">Loading subjects...</td></tr>');
        
        // Get the selected semester if not provided
        if (!semester) {
            semester = $('#semesterSelect').val();
        }
        
        // If no semester is selected, show an error
        if (!semester) {
            tbody.html('<tr><td colspan="5" class="text-center text-warning">Please select a semester</td></tr>');
            return;
        }
        
        // Update the semester select to the current semester and header labels
        $('#semesterSelect').val(semester);
        updateQuarterHeaders(semester);
        
        // Store the current semester in the section object
        section.semester = semester;
        
        // First, populate the subjects for the selected semester (pass LRN to include failed subjects)
        populateSubjects(semester, section, lrn);
        
        // Then fetch the grades data
        console.log('Fetching grades with data:', { 
            lrn: lrn,
            grade_level: section.grade_level,
            track: section.track,
            semester: semester 
        });
        
        $.ajax({
            url: 'fetch_grades.php',
            method: 'POST',
            data: { 
                lrn: lrn,
                grade_level: section.grade_level,
                track: section.track,
                semester: semester
            },
            beforeSend: function() {
                console.log('Sending request to fetch_grades.php...');
            },
            success: function(response) {
                console.log('Fetch grades response:', response);
                
                try {
                    // First check if response is already an object (might happen if jQuery auto-parsed it)
                    var result = response;
                    if (typeof response === 'string') {
                        result = JSON.parse(response);
                    }
                    
                    if (result && result.success) {
                        window.fetchedGrades = result.data || {};
                        console.log('Fetched grades:', window.fetchedGrades);
                        
                        // Update the subjects with the fetched grades
                        if (section) {
                            populateExistingGrades();
                            $('#subjectsTableContainer').show();
                        }
                    } else {
                        var errorMsg = (result && result.error) || 'Failed to fetch grades data';
                        console.error('Error in response:', errorMsg);
                        Swal.fire('Error', errorMsg, 'error');
                    }
                } catch (e) {
                    console.error('Error processing response:', e);
                    console.error('Response type:', typeof response);
                    console.error('Response content:', response);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to process server response. Please check the console for details.'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX error
                console.error('AJAX Error:', status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch grades data: ' + (error || 'Unknown error')
                });
            }
        });
    }

    $('#semesterSelect').on('change', function() {
        var semester = $(this).val();
        var lrn = $('#subjectStudentLRN').text();
        
        if (semester && window.currentSection) {
            updateQuarterHeaders(semester);
            // Update the section's semester before fetching grades
            window.currentSection.semester = semester;
            fetchGrades(lrn, window.currentSection, semester);
        } else {
            updateQuarterHeaders('1st Semester');
            $('#subjectsTableContainer').hide();
        }
    });

    $('#subjectsTable').on('change', '.q1, .q2', function() {
        updateFinalGrade($(this).closest('tr'));
    });

// Handle save subjects button click - using event delegation for dynamically added elements
$(document).off('click', '#saveSubjectsBtn').on('click', '#saveSubjectsBtn', function() {
    var lrn = $('#subjectStudentLRN').text();
    var semester = $('#semesterSelect').val();
    var gradeLevel = $('#subjectGradeLevel').text();
    if (!semester) {
        Swal.fire('Warning', 'Please select a semester', 'warning');
        return;
    }
    if (!gradeLevel || gradeLevel === 'Not Assigned') {
        Swal.fire('Warning', 'Student grade level is not assigned', 'warning');
        return;
    }

    var gradesData = {
        '1': {},
        '2': {},
        'final': {}
    };

    $('#subjectsTable tbody tr').each(function() {
        var $row = $(this);
        var field = $row.find('.q1').data('field');
        var q1 = $row.find('.q1').val();
        var q2 = $row.find('.q2').val();
        var final = $row.find('.final').val();

        if (q1 !== '' && q1 !== null) gradesData['1'][field] = parseInt(q1);
        if (q2 !== '' && q2 !== null) gradesData['2'][field] = parseInt(q2);
        if (final !== '') gradesData['final'][field] = parseInt(final);
    });

    var hasData = false;
    for (var qua in gradesData) {
        if (Object.keys(gradesData[qua]).length > 0) {
            hasData = true;
            break;
        }
    }

    if (!hasData) {
        Swal.fire('Warning', 'Please enter at least some grades', 'warning');
        return;
    }

    $.ajax({
        url: 'save_grades.php',
        method: 'POST',
        data: {
            lrn: lrn,
            semester: semester,
            grade_level: gradeLevel,
            grades_data: JSON.stringify(gradesData)
        },
        success: function(response) {
            console.log('Raw response:', response);
            
            // If response is already an object, use it directly
            if (typeof response === 'object' && response !== null) {
                if (response.success) {
                    Swal.fire('Success', 'Grades saved successfully', 'success');
                    $('#subjectsModal').modal('hide');
                } else {
                    Swal.fire('Error', response.error || 'An error occurred while saving grades', 'error');
                }
                return;
            }
            
            // If we get here, response is a string that might be JSON
            try {
                var result = JSON.parse(response);
                if (result && result.success) {
                    Swal.fire('Success', 'Grades saved successfully', 'success');
                    $('#subjectsModal').modal('hide');
                } else {
                    Swal.fire('Error', result.error || 'An error occurred while saving grades', 'error');
                }
            } catch (e) {
                // If we get here, response is not valid JSON
                console.error('Error processing response:', e);
                console.error('Response type:', typeof response);
                console.error('Response content:', response);
                
                var errorText = 'An error occurred while processing the response';
                if (typeof response === 'string') {
                    errorText = response;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorText
                });
            }
        },
        error: function(xhr, status, error) {
            // Handle AJAX error
            console.error('AJAX Error:', status, error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to save grades: ' + (error || 'Unknown error')
            });
        }
    });
});
});
</script>

    </body>
</html>