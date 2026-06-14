<?php 
session_start();
include '../db_connect.php';

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
        $query = "SELECT month, school_days, present_days, absent_days FROM monthly_attendance WHERE LRN = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $lrn);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['month']] = [
                'school_days' => $row['school_days'],
                'present_days' => $row['present_days'],
                'absent_days' => $row['absent_days']
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

// Handle AJAX for filtering students
if (isset($_POST['selected_section'])) {
    $selected_section = mysqli_real_escape_string($conn, $_POST['selected_section']);
    $students = [];
    $student_query = "
        SELECT 
            sf9.LRN, 
            sf1.name, 
            sf9.status, 
            sf9.section,
            CASE 
                WHEN sf9.section != 'Unassigned' THEN section.grade_level 
                ELSE '' 
            END AS grade_level,
            CASE 
                WHEN sf9.section != 'Unassigned' THEN section.track 
                ELSE '' 
            END AS track
        FROM sf9
        LEFT JOIN sf1 ON sf9.LRN = sf1.LRN
        LEFT JOIN section ON sf9.section = section.class_name
    ";
    if ($selected_section) {
        $student_query .= " WHERE sf9.section = '$selected_section'";
    }
    $student_result = mysqli_query($conn, $student_query);
    if ($student_result) {
        while ($student = mysqli_fetch_assoc($student_result)) {
            $students[] = $student;
        }
    }
    echo json_encode(['success' => true, 'students' => $students]);
    exit;
}

// Check for errors and form data in session
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']); // Clear session data

// Fetch sections for dropdown
$sections_data = [];
$section_query = "SELECT class_name FROM section";
$section_result = mysqli_query($conn, $section_query);
if ($section_result === false) {
    die("Section query failed: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($section_result)) {
    $sections_data[] = $row['class_name'];
}

// Initial student query for page load
$student_query = "
    SELECT 
        sf9.LRN, 
        sf1.name, 
        sf9.status, 
        sf9.section,
        CASE 
            WHEN sf9.section != 'Unassigned' THEN section.grade_level 
            ELSE '' 
        END AS grade_level,
        CASE 
            WHEN sf9.section != 'Unassigned' THEN section.track 
            ELSE '' 
        END AS track
    FROM sf9
    LEFT JOIN sf1 ON sf9.LRN = sf1.LRN
    LEFT JOIN section ON sf9.section = section.class_name
";
$student_result = mysqli_query($conn, $student_query);
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
    </head>
    <body>
        <?php include '../sidebar_component_IT.php'; ?>
        <div id="main" class="p-4"> 
            <div class="container mt-2">
                <div class="card bg-white">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">SF9 - Academic Record</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="classSelect" class="form-label">Select Class:</label>
                            <select id="classSelect" class="form-select" onchange="filterStudents()">
                                <option value="">All Classes</option>
                                <?php foreach ($sections_data as $class_name): ?>
                                    <option value="<?php echo htmlspecialchars($class_name); ?>">
                                        <?php echo htmlspecialchars($class_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <table id="studentTable" class="display table table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Grade Level</th>
                                    <th>Track</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($student_result === false) {
                                    echo "<tr><td colspan='9'>Error fetching data: " . mysqli_error($conn) . "</td></tr>";
                                } else {
                                    while ($student = mysqli_fetch_assoc($student_result)) {
                                        echo "<tr>
                                            <td>{$student['LRN']}</td>
                                            <td>" . htmlspecialchars($student['name']) . "</td>
                                            <td>" . htmlspecialchars($student['grade_level']) . "</td>
                                            <td>" . htmlspecialchars($student['track']) . "</td>
                                            <td>" . htmlspecialchars($student['status']) . "</td>
                                            <td class='text-center'>
                                                <div class='d-flex justify-content-center gap-1'>
                                                    <button class='btn btn-sm btn-primary viewStudentBtn' data-lrn='{$student['LRN']}' title='View'>
                                                        <i class='fas fa-download'></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
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
                                foreach ($sections_data as $class_name) {
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

        <!-- Attendance Modal -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Attendance for <span id="studentName"></span> (LRN: <span id="studentLRN"></span>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>School Days</th>
                                    <th>Present Days</th>
                                    <th>Absent Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $months = ['June' => 6, 'July' => 7, 'August' => 8, 'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12, 'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,];
                                foreach ($months as $month_name => $month_num) {
                                    echo "<tr>
                                        <td>$month_name</td>
                                        <td><input type='number' class='form-control school_days' data-month='$month_num' min='0'></td>
                                        <td><input type='number' class='form-control present_days' min='0'></td>
                                        <td><input type='number' class='form-control absent_days' min='0'></td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveAttendanceBtn">Submit</button>
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
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makadiyos">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makadiyos">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makadiyos">
                                            <option value="">Select</option>
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
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makadiyos_2">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makadiyos_2">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makadiyos_2">
                                            <option value="">Select</option>
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
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makatao">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makatao">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makatao">
                                            <option value="">Select</option>
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
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makatao_2">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makatao_2">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makatao_2">
                                            <option value="">Select</option>
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
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makakalikasan">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makakalikasan">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makakalikasan">
                                            <option value="">Select</option>
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
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makabansa">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makabansa">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makabansa">
                                            <option value="">Select</option>
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
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="2" data-field="makabansa_2">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="3" data-field="makabansa_2">
                                            <option value="">Select</option>
                                            <option value="AO">AO</option>
                                            <option value="SO">SO</option>
                                            <option value="RO">RO</option>
                                            <option value="NO">NO</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" data-quarter="4" data-field="makabansa_2">
                                            <option value="">Select</option>
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
                    <table class="table table-bordered" id="subjectsTable">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Quarter 1</th>
                                <th>Quarter 2</th>
                                <th>Final Grade</th>
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
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            title: 'Student_Data',
                            exportOptions: {
                                columns: [1,2,3,4,5]
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            title: 'Student_Data',
                            exportOptions: {
                                columns: [1,2,3,4,5]
                            }
                        }
                    ],
                    pageLength: 20,
                    responsive: true,
                    search: {
                        smart: true
                    },
                    columnDefs: [
                        { orderable: false, targets: [5] }
                    ]
                });

                // Function to filter students based on selected class
                window.filterStudents = function() {
                    var selectedClass = $('#classSelect').val();
                    $.ajax({
                        url: '',
                        method: 'POST',
                        data: { selected_section: selectedClass },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                var students = response.students;
                                table.clear().rows.add(students.map(student => [
                                    student.LRN,
                                    htmlspecialchars(student.name),
                                    htmlspecialchars(student.grade_level),
                                    htmlspecialchars(student.track),
                                    htmlspecialchars(student.status),
                                    `<div class='d-flex justify-content-center gap-1'><button class='btn btn-sm btn-primary viewStudentBtn' data-lrn='${student.LRN}' title='View'><i class='fas fa-download'></i></button></div>`
                                ])).draw();
                            } else {
                                Swal.fire('Error', 'Failed to fetch students', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'An error occurred while filtering students', 'error');
                        }
                    });
                };

                // Utility function to escape HTML
                window.htmlspecialchars = function(str) {
                    return $('<div>').text(str).html();
                };

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
                                        Swal.fire('Success', 'Students assigned successfully', 'success');
                                        $('#assignModal').modal('hide');
                                        filterStudents(); // Refresh table after assignment
                                    } else {
                                        Swal.fire('Error', response, 'error');
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error', 'An error occurred', 'error');
                                }
                            });
                        }
                    });
                });

                // Attendance button click
                $(document).on('click', '.attendanceBtn', function() {
                    var lrn = $(this).data('lrn');
                    var name = $(this).data('name');
                    $('#studentName').text(name);
                    $('#studentLRN').text(lrn);
                    
                    // Clear previous inputs
                    $('.school_days, .present_days, .absent_days').val('');
                    
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
                                    $row.find('.school_days').val(data.school_days || '');
                                    $row.find('.present_days').val(data.present_days || '');
                                    $row.find('.absent_days').val(data.absent_days || '');
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

    function populateSubjects(semester) {
        var subjects = semester === '1' ? firstSemSubjects : secondSemSubjects;
        var tbody = $('#subjectsTable tbody');
        tbody.empty();
        subjects.forEach(function(sub) {
            var row = `<tr>
                <td>${sub.name}</td>
                <td><input type="number" class="form-control q1" data-field="${sub.field}" min="0" max="100"></td>
                <td><input type="number" class="form-control q2" data-field="${sub.field}" min="0" max="100"></td>
                <td><input type="text" class="form-control final" data-field="${sub.field}" readonly></td>
            </tr>`;
            tbody.append(row);
        });

        // Populate from fetched data
        var fetchedSem = window.fetchedGrades[semester] || {};
        var q1Data = fetchedSem['1'] || {};
        var q2Data = fetchedSem['2'] || {};
        var finalData = fetchedSem['final'] || {};

        $('#subjectsTable tbody tr').each(function() {
            var field = $(this).find('.q1').data('field');
            $(this).find('.q1').val(q1Data[field] || '');
            $(this).find('.q2').val(q2Data[field] || '');
            $(this).find('.final').val(finalData[field] || '');
        });
    }

    $(document).on('click', '.subjectBtn', function() {
var lrn = $(this).data('lrn');
    var name = $(this).data('name');
    var gradeLevel = $(this).data('grade-level');
    $('#subjectStudentName').text(name);
    $('#subjectStudentLRN').text(lrn);
    $('#subjectGradeLevel').text(gradeLevel || 'Not Assigned');

        // Reset semester select and hide table
        $('#semesterSelect').val('');
        $('#subjectsTableContainer').hide();

        // Fetch existing grades data
        $.ajax({
            url: 'fetch_grades.php',
            method: 'POST',
            data: { lrn: lrn },
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    window.fetchedGrades = result.data;
                    $('#subjectsModal').modal('show');
                } else {
                    Swal.fire('Error', result.error || 'Failed to fetch grades data', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'An error occurred while fetching grades data', 'error');
            }
        });
    });

    $('#semesterSelect').on('change', function() {
        var semester = $(this).val();
        if (semester) {
            populateSubjects(semester);
            $('#subjectsTableContainer').show();
        } else {
            $('#subjectsTableContainer').hide();
        }
    });

    $('#subjectsModal').on('input', '.q1, .q2', function() {
        var $row = $(this).closest('tr');
        var q1 = parseFloat($row.find('.q1').val()) || 0;
        var q2 = parseFloat($row.find('.q2').val()) || 0;
        var finalVal = '';
        if (q1 > 0 && q2 > 0) {
            finalVal = ((q1 + q2) / 2).toFixed(2);
        }
        $row.find('.final').val(finalVal);
    });

$('#saveSubjectsBtn').on('click', function() {
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
        var field = $(this).find('.q1').data('field');
        var q1 = $(this).find('.q1').val();
        var q2 = $(this).find('.q2').val();
        var final = $(this).find('.final').val();

        if (q1 !== '') gradesData['1'][field] = q1;
        if (q2 !== '') gradesData['2'][field] = q2;
        if (final !== '') gradesData['final'][field] = final;
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
            var result = JSON.parse(response);
            if (result.success) {
                Swal.fire('Success', 'Grades saved successfully', 'success');
                $('#subjectsModal').modal('hide');
            } else {
                Swal.fire('Error', result.error || 'An error occurred while saving grades', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'An error occurred', 'error');
        }
    });
});
});
</script>

    </body>
</html>