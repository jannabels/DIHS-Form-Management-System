
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db_connect.php';

$adviser_id = $_SESSION['user_id'] ?? null;
$role       = $_SESSION['role'] ?? null;

// Default values
$grade_level = null;
$track       = null;
$semester    = null;
$class_name  = null;

if ($role === 'Adviser' && $adviser_id) {
    // Fetch adviser's section first (only one)
    $sec_sql = "
        SELECT grade_level, track, semester, class_name
        FROM section
        WHERE adviser = ?
        LIMIT 1
    ";
    $sec_stmt = $conn->prepare($sec_sql);
    $sec_stmt->bind_param("i", $adviser_id);
    $sec_stmt->execute();
    $sec_result = $sec_stmt->get_result();
    $section    = $sec_result->fetch_assoc();
    $sec_stmt->close();

    if ($section) {
        // Save section values in variables for later use
        $grade_level = $section['grade_level'];
        $track       = $section['track'];
        $semester    = $section['semester'];
        $class_name  = $section['class_name'];

            // Save in session for export_sf2.php
    $_SESSION['sf2_grade_level'] = $grade_level;
    $_SESSION['sf2_track']       = $track;
    $_SESSION['sf2_semester']    = $semester;
    $_SESSION['sf2_section']     = $class_name;
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>SF2 - Daily Attendance</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
        <!-- Flatpickr CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body {
                background: #f8f9fa !important;
                margin: 0;
                padding: 0;
                font-family: 'Roboto', sans-serif;
                font-size: 14px;
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
                        <h5 class="card-title mb-0">SF2 - Daily Attendance</h5>
                    </div>
                    <!-- Legend -->
                    <div class="text-center py-2 border-bottom">
                        <small class="text-muted">
                            <strong>Legend:</strong> 
                            <span class="badge bg-success">P - Present</span>
                            <span class="badge bg-danger">A - Absent</span>
                            <span class="badge bg-warning text-dark">L - Late</span>
                        </small>
                    </div>
                    <div class="card-body text-center py-5">
                        <!-- Controls -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="datepicker" class="form-label">Select Date of Attendance:</label>
                                <input id="datepicker" class="form-control" placeholder="Select a date">
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal">Attendance</button>
                                <button id="saveAttendance" class="btn btn-success">Save Attendance</button>
                                <button id="exportExcel" class="btn btn-info">Export Excel</button>
                            </div>
                        </div>


                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table id="attendanceTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>LRN</th>
                                        <th>Name</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
   <?php
// Logged-in account details - using variables already defined at the top of the file

if ($role === 'Adviser' && $adviser_id) {
    // fetch students only in the adviser's section(s)
    $sql = "
        SELECT sf1.LRN, sf1.Name
        FROM sf1
        INNER JOIN sf9 ON sf1.LRN = sf9.LRN
        INNER JOIN section sec ON sf9.section = sec.class_name
        WHERE sec.adviser = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $adviser_id); // adviser id is an integer
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($student = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td><input type="checkbox" class="selectRow" data-lrn="' . htmlspecialchars($student['LRN']) . '"></td>';
            echo '<td>' . htmlspecialchars($student['LRN']) . '</td>';
            echo '<td>' . htmlspecialchars($student['Name']) . '</td>';
            echo '<td>';
            echo '<button class="btn btn-sm btn-outline-success remark-btn me-1 fw-bold" data-type="present">P</button>';
            echo '<button class="btn btn-sm btn-outline-danger remark-btn me-1 fw-bold" data-type="absent">A</button>';
            echo '<button class="btn btn-sm btn-outline-warning remark-btn fw-bold" data-type="tardy">L</button>';
            echo '</td>';
            echo '</tr>';
        }
    } else { 
        echo '<tr><td colspan="4" class="text-center">No students found for this adviser.</td></tr>';
    }

    $stmt->close();
} else {
    echo '<tr><td colspan="4" class="text-center">Unauthorized access.</td></tr>';
}


mysqli_close($conn);
?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Attendance Modal -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="attendanceModalLabel">Assign Attendance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <button id="bulk-present" class="btn btn-success me-2 fw-bold">P - Present</button>
                        <button id="bulk-absent" class="btn btn-danger me-2 fw-bold">A - Absent</button>
                        <button id="bulk-tardy" class="btn btn-warning fw-bold">L - Late</button>
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
        <!-- Flatpickr JS -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script>
            // Initialize Flatpickr
            flatpickr("#datepicker", {
                dateFormat: "F j, Y",
                disable: [
                    function(date) {
                        return (date.getDay() === 0); // Disable Sundays
                    }
                ],
                minDate: new Date(new Date().getFullYear(), 0, 1),
                maxDate: new Date(new Date().getFullYear(), 11, 31),
                onChange: function(selectedDates, dateStr, instance) {
                    loadAttendance(dateStr);
                }
            });

            // Initialize DataTable without export button
            var table = $('#attendanceTable').DataTable({
                dom: 'frtip' // No 'B' to hide built-in buttons
            });

            // Select All Checkbox
            $('#selectAll').on('click', function() {
                $('.selectRow').prop('checked', this.checked);
            });

            // Remark Buttons (per row)
            $(document).on('click', '.remark-btn', function() {
                var row = $(this).closest('tr');
                var isActive = $(this).hasClass('active');
                row.find('.remark-btn').removeClass('active');
                if (!isActive) {
                    $(this).addClass('active');
                }
            });

            // Bulk Assignment
            $('#bulk-present').on('click', function() {
                $('.selectRow:checked').each(function() {
                    $(this).closest('tr').find('.remark-btn[data-type="present"]').click();
                });
                $('#attendanceModal').modal('hide');
            });

            $('#bulk-absent').on('click', function() {
                $('.selectRow:checked').each(function() {
                    $(this).closest('tr').find('.remark-btn[data-type="absent"]').click();
                });
                $('#attendanceModal').modal('hide');
            });

            $('#bulk-tardy').on('click', function() {
                $('.selectRow:checked').each(function() {
                    $(this).closest('tr').find('.remark-btn[data-type="tardy"]').click();
                });
                $('#attendanceModal').modal('hide');
            });

            // Save Attendance
            $('#saveAttendance').on('click', function() {
                var fullDate = $('#datepicker').val();
                if (!fullDate) {
                    Swal.fire('Error', 'Please select a date.', 'error');
                    return;
                }

                var dateObj = new Date(fullDate);
                var month = dateObj.toLocaleString('en-US', { month: 'long' });

                var attendances = [];
                $('#attendanceTable tbody tr').each(function() {
                    var row = $(this);
                    var lrn = row.find('.selectRow').data('lrn');
                    var activeBtn = row.find('.remark-btn.active');
                    if (activeBtn.length) {
                        var remark = activeBtn.data('type');
                        var data = {
                            lrn: lrn,
                            month: month,
                            date: fullDate,
                            present: (remark === 'present') ? '1' : '0',
                            absent: (remark === 'absent') ? '1' : '0',
                            tardy: (remark === 'tardy') ? '1' : '0'
                        };
                        attendances.push(data);
                    }
                });

                if (attendances.length === 0) {
                    Swal.fire('Error', 'No attendance remarks selected.', 'error');
                    return;
                }

                $.ajax({
                    url: 'save_attendance.php',
                    type: 'POST',
                    data: { attendances: JSON.stringify(attendances) },
                    success: function(response) {
                        Swal.fire('Success', 'Attendance saved successfully.', 'success');
                        loadAttendance(fullDate); // Reload to reflect any changes
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to save attendance.', 'error');
                    }
                });
            });

            // Export Excel to template
            $('#exportExcel').on('click', function() {
                var fullDate = $('#datepicker').val();
                if (!fullDate) {
                    Swal.fire('Error', 'Please select a date to determine the month.', 'error');
                    return;
                }

                var dateObj = new Date(fullDate);
                var month = dateObj.toLocaleString('en-US', { month: 'long' });

                window.location.href = 'export_sf2.php?month=' + encodeURIComponent(month);
            });

            // Load Attendance Function
            function loadAttendance(fullDate) {
                if (!fullDate) return;
                $.ajax({
                    url: 'get_attendance.php',
                    type: 'GET',
                    data: { date: fullDate },
                    success: function(response) {
                        var data = JSON.parse(response);
                        $('#attendanceTable tbody tr').each(function() {
                            var row = $(this);
                            var lrn = row.find('.selectRow').data('lrn');
                            row.find('.remark-btn').removeClass('active');
                            if (data[lrn]) {
                                var att = data[lrn];
                                if (att.present === '1') {
                                    row.find('.remark-btn[data-type="present"]').addClass('active');
                                } else if (att.absent === '1') {
                                    row.find('.remark-btn[data-type="absent"]').addClass('active');
                                } else if (att.tardy === '1') {
                                    row.find('.remark-btn[data-type="tardy"]').addClass('active');
                                }
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load attendance.', 'error');
                    }
                });
            }

        </script>
    </body>
</html>