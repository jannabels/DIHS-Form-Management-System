<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../login/');
    exit();
}

// Only allow registrar and admin roles
if ($_SESSION['role'] !== 'registrar' && $_SESSION['role'] !== 'admin') {
    header('Location: ../unauthorized.php');
    exit();
}

require_once '../db_connect.php';
$page_title = "Graduated Students Archive";

// Include navbar after all session and role checks
require_once '../includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - DIHS Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .archived-card {
            border-left: 4px solid #28a745;
        }
        .archived-badge {
            position: absolute;
            top: 10px;
            right: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/unified_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Graduated Students Archive</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addGraduatedModal">
                            <i class="bi bi-person-plus"></i> Mark as Graduated
                        </button>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-archive me-1"></i>
                        Archived Student Records
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="graduatedStudentsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>LRN</th>
                                        <th>Name</th>
                                        <th>Graduation Date</th>
                                        <th>School Year</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM vw_archived_students ORDER BY graduation_date DESC";
                                    $result = $conn->query($query);
                                    
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['LRN']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
                                            echo "<td>" . date('F d, Y', strtotime($row['graduation_date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['graduation_year']) . "</td>";
                                            echo "<td>
                                                    <button class='btn btn-sm btn-info view-btn' data-lrn='" . $row['LRN'] . "' title='View Records'>
                                                        <i class='bi bi-eye'></i> View
                                                    </button>
                                                    <button class='btn btn-sm btn-warning unarchive-btn' data-lrn='" . $row['LRN'] . "' title='Unarchive Student'>
                                                        <i class='bi bi-arrow-counterclockwise'></i> Unarchive
                                                    </button>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>No graduated students found in the archive.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Graduated Student Modal -->
    <div class="modal fade" id="addGraduatedModal" tabindex="-1" aria-labelledby="addGraduatedModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGraduatedModalLabel">Mark Student as Graduated</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="graduateStudentForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="lrn" class="form-label">Student LRN</label>
                            <input type="text" class="form-control" id="lrn" name="lrn" required 
                                   pattern="[0-9]{12}" title="LRN must be 12 digits">
                            <div class="form-text">Enter the 12-digit LRN of the student</div>
                        </div>
                        <div class="mb-3">
                            <label for="graduation_date" class="form-label">Graduation Date</label>
                            <input type="date" class="form-control" id="graduation_date" name="graduation_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="school_year" class="form-label">School Year</label>
                            <input type="text" class="form-control" id="school_year" name="school_year" 
                                   value="<?php echo (date('Y')-1) . '-' . date('y'); ?>" required>
                        </div>
                        <div id="studentInfo" class="alert alert-info d-none">
                            <strong id="studentName"></strong><br>
                            <span id="studentDetails"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Mark as Graduated</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Records Modal -->
    <div class="modal fade" id="viewRecordsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Student Records - <span id="modalStudentName"></span> (LRN: <span id="modalLRN"></span>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="recordsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="sf1-tab" data-bs-toggle="tab" data-bs-target="#sf1" type="button" role="tab">SF1</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sf9-tab" data-bs-toggle="tab" data-bs-target="#sf9" type="button" role="tab">SF9</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sf10-tab" data-bs-toggle="tab" data-bs-target="#sf10" type="button" role="tab">SF10</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 border border-top-0" id="recordsTabContent">
                        <div class="tab-pane fade show active" id="sf1" role="tabpanel" aria-labelledby="sf1-tab">
                            <div id="sf1Content" class="table-responsive">
                                <!-- SF1 content will be loaded here via AJAX -->
                                <div class="text-center my-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p>Loading SF1 data...</p>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="sf9" role="tabpanel" aria-labelledby="sf9-tab">
                            <div id="sf9Content" class="table-responsive">
                                <!-- SF9 content will be loaded here via AJAX -->
                                <div class="text-center my-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p>Loading SF9 data...</p>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="sf10" role="tabpanel" aria-labelledby="sf10-tab">
                            <div id="sf10Content" class="table-responsive">
                                <!-- SF10 content will be loaded here via AJAX -->
                                <div class="text-center my-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p>Loading SF10 data...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printRecordsBtn">
                        <i class="bi bi-printer"></i> Print Records
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#graduatedStudentsTable').DataTable({
                pageLength: 25,
                order: [[2, 'desc']] // Sort by graduation date by default
            });

            // Check student LRN
            $('#lrn').on('blur', function() {
                var lrn = $(this).val();
                if (lrn.length === 12) {
                    $.ajax({
                        url: 'check_student.php',
                        method: 'POST',
                        data: { lrn: lrn, action: 'check' },
                        dataType: 'json',
                        success: function(response) {
                            if (response.exists) {
                                $('#studentName').text(response.name);
                                $('#studentDetails').html(`
                                    Grade Level: ${response.grade_level || 'N/A'}<br>
                                    Section: ${response.section || 'N/A'}<br>
                                    Status: <span class="badge bg-${response.status === 'graduated' ? 'success' : 'primary'}">${response.status || 'active'}</span>
                                `);
                                $('#studentInfo').removeClass('d-none alert-danger').addClass('alert-info');
                            } else {
                                $('#studentInfo').removeClass('d-none alert-info').addClass('alert-danger');
                                $('#studentName').text('Student not found');
                                $('#studentDetails').text('No student found with the provided LRN');
                            }
                        }
                    });
                }
            });

            // Handle form submission
            $('#graduateStudentForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'process_graduation.php',
                    method: 'POST',
                    data: $(this).serialize() + '&action=graduate',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });

            // Handle view button click
            $('#graduatedStudentsTable').on('click', '.view-btn', function() {
                var lrn = $(this).data('lrn');
                var name = $(this).closest('tr').find('td:eq(1)').text();
                
                $('#modalLRN').text(lrn);
                $('#modalStudentName').text(name);
                
                // Load SF1 data
                loadRecord('sf1', lrn);
                
                // Show the modal
                var modal = new bootstrap.Modal(document.getElementById('viewRecordsModal'));
                modal.show();
                
                // Load other tabs when clicked
                $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                    var target = $(e.target).attr('href').substring(1); // Get tab id without #
                    loadRecord(target, lrn);
                });
            });

            // Function to load record data
            function loadRecord(type, lrn) {
                var $content = $('#' + type + 'Content');
                
                // Only load if content is empty or loading
                if ($content.find('.spinner-border').length === 0) return;
                
                $.ajax({
                    url: 'get_student_record.php',
                    method: 'POST',
                    data: { lrn: lrn, type: type },
                    success: function(response) {
                        $content.html(response);
                    },
                    error: function() {
                        $content.html('<div class="alert alert-danger">Error loading ' + type + ' data</div>');
                    }
                });
            }

            // Handle unarchive button click
            $('.unarchive-btn').on('click', function() {
                if (confirm('Are you sure you want to unarchive this student? This will make their records editable again.')) {
                    var lrn = $(this).data('lrn');
                    var $row = $(this).closest('tr');
                    
                    $.ajax({
                        url: 'process_graduation.php',
                        method: 'POST',
                        data: { lrn: lrn, action: 'unarchive' },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                table.row($row).remove().draw();
                                alert(response.message);
                            } else {
                                alert('Error: ' + response.message);
                            }
                        }
                    });
                }
            });

            // Print records
            $('#printRecordsBtn').on('click', function() {
                window.print();
            });
        });
    </script>
</body>
</html>