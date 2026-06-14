<?php 
session_start();
include '../db_connect.php';

// Handle AJAX update
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
    </head>
    <body>
        <?php include '../sidebar_component_guidance.php'; ?>
        <div id="main" class=" p-4">
            <div class="container mt-2">
                <div class="card bg-white">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Student List</h5>
                        <button class="btn btn-primary" id="assignSection">Assign Section</button>
                    </div>
                    <div class="card-body">
                        <table id="studentTable" class="display table table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Grade Level</th>
                                    <th>Track</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch students with name from sf1 and conditional track/grade_level from section
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
                                if ($student_result === false) {
                                    echo "<tr><td colspan='6'>Error fetching data: " . mysqli_error($conn) . "</td></tr>";
                                } else {
                                    while ($student = mysqli_fetch_assoc($student_result)) {
                                        echo "<tr>
                                            <td><input type='checkbox' class='rowCheckbox' value='{$student['LRN']}'></td>
                                            <td>{$student['LRN']}</td>
                                            <td>" . htmlspecialchars($student['name']) . "</td>
                                            <td>" . htmlspecialchars($student['grade_level']) . "</td>
                                            <td>" . htmlspecialchars($student['track']) . "</td>
                                            <td>" . htmlspecialchars($student['status']) . "</td>
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
                                columns: [1,2,3,4,5] // Exclude checkbox column
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            title: 'Student_Data',
                            exportOptions: {
                                columns: [1,2,3,4,5] // Exclude checkbox column
                            }
                        }
                    ],
                    pageLength: 20,
                    responsive: true,
                    search: {
                        smart: true
                    },
                    columnDefs: [
                        { orderable: false, targets: 0 } // Disable sorting on checkbox column
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
                                        Swal.fire('Success', 'Students assigned successfully', 'success');
                                        $('#assignModal').modal('hide');
                                        // Optionally reload the table or page
                                        location.reload();
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
            });
        </script>
    </body>
</html>