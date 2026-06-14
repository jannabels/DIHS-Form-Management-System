<?php
// Start the session
session_start();

// Include database connection
include '../db_connect.php';

// Check if user is logged in and is a registrar
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Registrar') {
    header("Location: /systemdihs/login/index.php");
    exit();
}

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

// Handle AJAX for filtering students
if (isset($_POST['selected_section'])) {
    $selected_section = mysqli_real_escape_string($conn, $_POST['selected_section']);
    $students = [];
    $student_query = "
        SELECT 
            sf9.LRN, 
            sf1.Name, 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SF10 - Student Progress</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .sidebar-collapsed {
            width: 4rem !important;
        }
        .sidebar-collapsed + #mainContent {
            margin-left: 4rem !important;
            width: calc(100% - 4rem) !important;
        }
        
        /* Ensure main content is properly positioned */
        #mainContent {
            transition: all 0.3s ease-in-out;
            width: calc(100% - 16rem);
            margin-left: 16rem;
            min-height: 100vh;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            #mainContent {
                width: 100%;
                margin-left: 0;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .sidebar-collapsed + #mainContent {
                width: 100% !important;
                margin-left: 0 !important;
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
        
        <!-- Custom Navbar Styles -->
        <style>
            /* Navbar Styling */
            .navbar {
                background: #ffffff;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                padding: 0.4rem 1.5rem;
                height: 56px;
                z-index: 1030;
                position: fixed;
                width: 100%;
                top: 0;
                left: 0;
                display: flex;
                align-items: center;
            }
            
            .navbar-container {
                width: 100%;
                max-width: 1400px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 1rem;
            }
            
            .nav-links {
                display: flex;
                gap: 1rem;
                align-items: center;
                margin: 0;
                padding: 0;
                list-style: none;
            }
            
            .nav-link {
                color: rgba(255, 255, 255, 0.9) !important;
                text-decoration: none;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-weight: 500;
            }
            
            .nav-link:hover, .nav-link.active {
                background-color: rgba(255, 255, 255, 0.15);
                color: white !important;
            }
            
            .nav-link i {
                font-size: 1.1rem;
            }
            
            .navbar-brand {
                color: white !important;
                font-weight: 600;
                font-size: 1.25rem;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .navbar-brand i {
                font-size: 1.5rem;
            }
            
            /* User menu */
            .user-menu {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: white;
                cursor: pointer;
            }
            
            .user-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background-color: #4b5563;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                color: white;
                font-size: 0.875rem;
            }

            /* Page Container */
            .sf10-page {
                min-height: calc(100vh - 6rem);
            }
            .sf10-container {
                background: #ffffff;
                border-radius: 1.5rem;
                padding: 2rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                border: 1px solid #e5e7eb;
            }

            .sf10-header h5 {
                font-size: 1.5rem;
                font-weight: 600;
                color: #64748b;
                margin-bottom: 0.25rem;
            }

            .sf10-header p {
                color: #64748b;
                font-size: 0.95rem;
            }

            .sf10-filters {
                background: #f9fafb;
                border-radius: 1rem;
                border: 1px solid #e5e7eb;
                padding: 1.25rem;
            }

            .sf10-filters label {
                font-weight: 600;
                color: #0f172a;
                margin-bottom: 0.4rem;
            }

            .sf10-filters select {
                border-radius: 0.9rem;
                border: 1px solid #d1d5db;
                padding: 0.65rem 1rem;
                font-weight: 500;
            }

            .table-wrapper {
                margin-top: 1.5rem;
                border-radius: 1.25rem;
                border: 1px solid #e5e7eb;
                overflow: hidden;
            }

            table.sf10-table thead {
                background: #374151;
                color: #fff;
            }

            table.sf10-table thead th {
                border: none;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                font-size: 0.78rem;
            }

            table.sf10-table tbody tr {
                transition: background 0.25s ease, transform 0.2s ease;
            }

            table.sf10-table tbody tr:hover {
                background: #f3f4f6;
            }

            table.sf10-table td {
                vertical-align: middle;
                color: #0f172a;
            }

            .downloadBtn {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                color: #4b5563 !important;
                font-size: 1.15rem;
                transition: color 0.2s ease, transform 0.2s ease;
            }

            .downloadBtn i {
                font-size: inherit;
            }

            .downloadBtn:hover {
                color: #1f2937 !important;
                transform: translateY(-1px);
            }
        </style>
    </head>
    <body class="bg-white">
        <!-- Include Unified Sidebar -->
        <?php include '../includes/unified_sidebar.php'; ?>

        <!-- Logout Confirmation Modal -->
        <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg p-6 w-full max-w-sm">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Logout Confirmation</h3>
                    <p class="text-sm text-gray-500 mb-6">Are you sure you want to log out?</p>
                    <div class="flex justify-center space-x-4">
                        <button type="button" id="cancelLogout" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Cancel
                        </button>
                        <a href="/systemdihs/logout.php" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-gray-50 min-h-screen" id="mainContent">
            <main class="container mx-auto p-4 md:p-6 lg:p-8">
                <div class="sf10-page">
                    <div class="sf10-container">
                        <div class="sf10-header flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <h5 class="mb-1">SF10 - Student Progress</h5>
                        </div>

                        <div class="sf10-filters mt-6">
                            <div class="flex flex-col max-w-md">
                                <label for="classSelect">Select Class</label>
                                <select id="classSelect" class="form-select" onchange="filterStudents()">
                                    <option value="">All Classes</option>
                                    <?php
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
                                    foreach ($sections_data as $class_name): ?>
                                        <option value="<?php echo htmlspecialchars($class_name); ?>">
                                            <?php echo htmlspecialchars($class_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="table-wrapper">
                            <table id="studentTable" class="display sf10-table table w-full mb-0">
                                <thead>
                                    <tr>
                                        <th>LRN</th>
                                        <th>Name</th>
                                        <th>Grade Level</th>
                                        <th>Track</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Initial student query for page load
                                    $student_query = "
                                        SELECT 
                                            sf9.LRN, 
                                            sf1.Name, 
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
                                    if ($student_result === false) {
                                        echo "<tr><td colspan='5'>Error fetching data: " . mysqli_error($conn) . "</td></tr>";
                                    } else {
                                        while ($student = mysqli_fetch_assoc($student_result)) {
                                            echo "<tr>
                                                <td>{$student['LRN']}</td>
                                                <td>" . htmlspecialchars($student['Name']) . "</td>
                                                <td>" . htmlspecialchars($student['grade_level']) . "</td>
                                                <td>" . htmlspecialchars($student['track']) . "</td>
                                                <td class='text-center'>
                                                    <button class='btn btn-sm btn-primary downloadBtn' data-lrn='{$student['LRN']}' title='Download'>
                                                        <i class='fas fa-download'></i>
                                                    </button>
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
            </main>
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
        </script>
        
        <script>
            $(document).ready(function() {
                var table = $('#studentTable').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            title: 'SF10_Student_Data',
                            exportOptions: {
                                columns: [0,1,2,3] // Exclude Action column
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            title: 'SF10_Student_Data',
                            exportOptions: {
                                columns: [0,1,2,3] // Exclude Action column
                            }
                        }
                    ],
                    pageLength: 20,
                    responsive: true,
                    search: {
                        smart: true
                    },
                    columnDefs: [
                        { orderable: false, targets: [4] } // Disable sorting on Action
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
                                    htmlspecialchars(student.Name),
                                    htmlspecialchars(student.grade_level),
                                    htmlspecialchars(student.track),
                                    `<button class='btn btn-sm btn-primary downloadBtn' data-lrn='${student.LRN}' title='Download'><i class='fas fa-download'></i></button>`
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

                // Handle download button click
                $(document).on('click', '.downloadBtn', function() {
                    var lrn = $(this).data('lrn');
                    window.location.href = 'export_sf10.php?lrn=' + lrn;
                });
            });
        </script>
        <script>
            // Toggle sidebar
            document.getElementById('toggleSidebar').addEventListener('click', function() {
                document.querySelector('.fixed.left-0').classList.toggle('sidebar-collapsed');
                document.getElementById('mainContent').classList.toggle('lg:pl-64');
                document.getElementById('mainContent').classList.toggle('lg:pl-16');
            });

            // Logout confirmation
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('logoutModal').classList.remove('hidden');
            });

            document.getElementById('cancelLogout').addEventListener('click', function() {
                document.getElementById('logoutModal').classList.add('hidden');
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === document.getElementById('logoutModal')) {
                    document.getElementById('logoutModal').classList.add('hidden');
                }
            });
        </script>
    </body>
</html>