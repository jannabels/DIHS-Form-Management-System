<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db_connect.php';

$adviser_id = $_SESSION['user_id'] ?? null;
$role       = $_SESSION['role'] ?? null;

// Redirect if not logged in as adviser
if (!$adviser_id || $role !== 'Adviser') {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Helper: compute Regular/Irregular status based on grades for a given LRN
function getStudentStatus($conn, $lrn, $passing_grade = 75) {
    $status = 'Regular';

    // Temporarily disable mysqli exception reporting
    $old_report_mode = mysqli_report(MYSQLI_REPORT_OFF);
    
    try {
        // Check if student_grades table exists - use information_schema for more reliable check
        $table_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'student_grades'");
        if (!$table_check) {
            // If query fails, return default status
            mysqli_report($old_report_mode);
            return $status;
        }
        $table_row = $table_check->fetch_assoc();
        $table_check->free();
        
        if (!$table_row || $table_row['cnt'] == 0) {
            // Table doesn't exist, return default status
            mysqli_report($old_report_mode);
            return $status;
        }

        // Query the student_grades table - check if any grade is below passing grade
        $sql = "SELECT grade FROM student_grades WHERE lrn = ? AND grade IS NOT NULL AND grade != ''";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // If prepare fails, return default status
            mysqli_report($old_report_mode);
            return $status;
        }
        
        $stmt->bind_param("s", $lrn);
        if (!$stmt->execute()) {
            // If execute fails, return default status
            $stmt->close();
            mysqli_report($old_report_mode);
            return $status;
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            // If get_result fails, return default status
            $stmt->close();
            mysqli_report($old_report_mode);
            return $status;
        }

        // Check for mysqli errors before trying to fetch
        if ($conn->error) {
            $stmt->close();
            mysqli_report($old_report_mode);
            return $status;
        }

        // Check each grade - if any is below passing grade, student is Irregular
        while (($row = @$result->fetch_assoc()) !== false && $row !== null) {
            if (isset($row['grade']) && $row['grade'] !== null && $row['grade'] !== '') {
                $grade_value = floatval($row['grade']);
                if ($grade_value > 0 && $grade_value < $passing_grade) {
                    $stmt->close();
                    mysqli_report($old_report_mode);
                    return 'Irregular';
                }
            }
        }
        
        // Check for errors after fetching
        if ($conn->error) {
            $stmt->close();
            mysqli_report($old_report_mode);
            return $status;
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        // If any mysqli exception occurs (like table doesn't exist), return default status
        // Restore original report mode
        mysqli_report($old_report_mode);
        return $status;
    } catch (Exception $e) {
        // If any other exception occurs, return default status
        // Restore original report mode
        mysqli_report($old_report_mode);
        return $status;
    }
    
    // Restore original report mode
    mysqli_report($old_report_mode);
    
    return $status;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch all sections assigned to the adviser
$sections = [];
$sec_sql = "
    SELECT section_id, grade_level, track, semester, class_name 
    FROM section 
    WHERE adviser = ? 
    ORDER BY grade_level, class_name
";

// Debug: Log the SQL query and parameters
error_log("Section SQL: " . $sec_sql);
error_log("Adviser ID: " . $adviser_id);

$sec_stmt = $conn->prepare($sec_sql);
if ($sec_stmt) {
    $sec_stmt->bind_param("i", $adviser_id);
    if ($sec_stmt->execute()) {
        $sec_result = $sec_stmt->get_result();
        if ($sec_result) {
            while ($row = $sec_result->fetch_assoc()) {
                $sections[] = array(
                    'id' => $row['section_id'],
                    'grade_level' => $row['grade_level'],
                    'track' => $row['track'],
                    'semester' => $row['semester'],
                    'class_name' => $row['class_name']
                );
            }
        } else {
            error_log("Error getting result set: " . $conn->error);
        }
    } else {
        error_log("Error executing query: " . $sec_stmt->error);
    }
    $sec_stmt->close();
} else {
    error_log("Error preparing section query: " . $conn->error);
}

// Debug: Log the number of sections found
error_log("Number of sections found: " . count($sections));

// Get selected section from GET parameter or default to first section
$selected_section_id = $_GET['section_id'] ?? ($sections[0]['id'] ?? null);
$grade_level = null;
$track = null;
$semester = null;
$class_name = null;

// Find the selected section
$selected_section = null;
foreach ($sections as $section) {
    if ($section['id'] == $selected_section_id) {
        $selected_section = $section;
        $grade_level = $section['grade_level'];
        $track = $section['track'];
        $semester = $section['semester'];
        $class_name = $section['class_name'];
        break;
    }
}

// If no section is selected but there are sections, use the first one
if (!$selected_section && !empty($sections)) {
    $selected_section = $sections[0];
    $grade_level = $selected_section['grade_level'];
    $track = $selected_section['track'];
    $semester = $selected_section['semester'];
    $class_name = $selected_section['class_name'];
    $selected_section_id = $selected_section['id'];
}

// Preload students list and gender counts for the selected section
$students    = [];
$male_count  = 0;
$female_count= 0;
$total_count = 0;

// Debug: Log the selected section
error_log("Selected Section ID: " . $selected_section_id);
error_log("Class Name: " . $class_name);

if ($class_name && $selected_section_id) {
    // First, let's check if we have a direct section mapping in sf9
    $sql = "
        SELECT sf1.LRN, sf1.Name, sf1.Sex, sf1.Birthdate, sf1.Age
        FROM sf1
        INNER JOIN sf9 ON sf1.LRN = sf9.LRN
        WHERE sf9.section = ?
        ORDER BY sf1.Name
    ";
    
    // Debug: Log the student query
    error_log("Student Query: " . $sql);
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $class_name);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            // Debug: Log number of students found
            $num_rows = $result->num_rows;
            error_log("Number of students found: " . $num_rows);
            
            while ($row = $result->fetch_assoc()) {
                $row['status'] = getStudentStatus($conn, $row['LRN']);
                $students[] = $row;

                $sex = strtoupper(trim($row['Sex'] ?? ''));
                if ($sex === 'M' || $sex === 'MALE') {
                    $male_count++;
                } elseif ($sex === 'F' || $sex === 'FEMALE') {
                    $female_count++;
                }
            }
            $total_count = count($students);
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>SF1 - School Register</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body {
                background: #f8f9fa !important;
                margin: 0;
                padding: 0;
                font-family: 'Roboto', sans-serif;
                font-size: 14px;
            }
            #mainContent {
                min-height: 100vh;
                padding: 20px;
                transition: all 0.3s ease;
            }
            .floating-card {
                background: #ffffff;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
                border: 1px solid rgba(0, 0, 0, 0.05);
            }
            .table {
                font-size: 14px;
            }
            .btn {
                font-size: 14px;
            }
            .form-label {
                font-size: 14px;
            }
            .form-control, .form-select {
                font-size: 14px;
            }
        </style>
    </head>
    <body class="bg-gray-100">
        <div class="flex h-screen overflow-hidden">
            <!-- Include Unified Sidebar -->
            <?php include '../includes/unified_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="flex-1 overflow-auto ml-0 md:ml-16 lg:ml-64 transition-all duration-300">
                <div class="p-4 md:p-6 lg:p-8">
                <div class="container mt-2">
                    <div class="card bg-white floating-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">SF1 - School Register</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($sections)): ?>
                                <?php if ($class_name): ?>
                                    <div class="alert alert-info">
                                        <strong>Section:</strong> <?php echo htmlspecialchars($class_name); ?>
                                        <br>
                                        <strong>Grade Level:</strong> <?php echo htmlspecialchars($grade_level); ?>
                                        <?php if ($track): ?>
                                            <br><strong>Track:</strong> <?php echo htmlspecialchars($track); ?>
                                        <?php endif; ?>
                                        <?php if ($semester): ?>
                                            <br><strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?>
                                        <?php endif; ?>
                                        <div class="mt-3 d-flex flex-wrap gap-3">
                                            <div class="px-3 py-2 bg-light border rounded">
                                                <strong>Male:</strong> <?php echo number_format($male_count); ?>
                                            </div>
                                            <div class="px-3 py-2 bg-light border rounded">
                                                <strong>Female:</strong> <?php echo number_format($female_count); ?>
                                            </div>
                                            <div class="px-3 py-2 bg-light border rounded">
                                                <strong>Total:</strong> <?php echo number_format($total_count); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                    <table id="sf1Table" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>LRN</th>
                                                <th>Name</th>
                                                <th>Age</th>
                                                <th>Gender</th>
                                                <th>Birthdate</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($students)): ?>
                                                <?php foreach ($students as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['LRN']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['Name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['Age']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['Sex']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['Birthdate']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center">No students found in this section.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        No students found in the selected section.
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    No sections assigned to you. Please contact the administrator.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Initialize Tailwind -->
        <script>
            tailwind.config = {
                theme: {
                    extend: {}
                }
            }
        </script>

        <!-- Sidebar Toggle Script -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.fixed.left-0');
            const toggleBtn = document.getElementById('toggleSidebar');
            const mainContent = document.getElementById('mainContent');
            const leftArrows = document.querySelectorAll('.fa-chevron-left');
            const rightArrows = document.querySelectorAll('.fa-chevron-right');
            
            // Check if sidebar is collapsed from localStorage
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            // Set initial state
            if (isCollapsed) {
                sidebar.classList.add('w-16');
                mainContent.classList.remove('ml-64');
                mainContent.classList.add('ml-16');
                leftArrows.forEach(arrow => arrow.classList.add('hidden'));
                rightArrows.forEach(arrow => arrow.classList.remove('hidden'));
            } else {
                sidebar.classList.remove('w-16');
                mainContent.classList.remove('ml-16');
                mainContent.classList.add('ml-64');
                leftArrows.forEach(arrow => arrow.classList.remove('hidden'));
                rightArrows.forEach(arrow => arrow.classList.add('hidden'));
            }
            
            // Toggle sidebar
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isCollapsing = !sidebar.classList.contains('w-16');
                    
                    if (isCollapsing) {
                        // Collapsing
                        sidebar.classList.add('w-16');
                        mainContent.classList.remove('ml-64');
                        mainContent.classList.add('ml-16');
                        leftArrows.forEach(arrow => arrow.classList.add('hidden'));
                        rightArrows.forEach(arrow => arrow.classList.remove('hidden'));
                    } else {
                        // Expanding
                        sidebar.classList.remove('w-16');
                        mainContent.classList.remove('ml-16');
                        mainContent.classList.add('ml-64');
                        leftArrows.forEach(arrow => arrow.classList.remove('hidden'));
                        rightArrows.forEach(arrow => arrow.classList.add('hidden'));
                    }
                    
                    // Save state to localStorage
                    localStorage.setItem('sidebarCollapsed', isCollapsing);
                });
            }
        });
        </script>

        <!-- jQuery and other scripts -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        
        <script>
            $(document).ready(function() {
                // Initialize DataTable with proper column definitions
                $('#sf1Table').DataTable({
                    pageLength: 25,
                    responsive: true,
                    // Disable auto-width to prevent column count issues
                    autoWidth: false,
                    // Disable any column reordering
                    colReorder: false,
                    // Add export buttons
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ],
                    // Disable any other features that might interfere
                    deferRender: true,
                    scrollCollapse: true,
                    scroller: false,
                    stateSave: false
                });
            });
        </script>
    </body>
</html>
<?php $conn->close(); ?>