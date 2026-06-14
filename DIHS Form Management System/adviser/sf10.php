<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header('Location: ../login/index.php');
    exit();
}

// Include database connection
include '../db_connect.php';
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
        <?php include '../includes/unified_sidebar.php'; ?>
        <div id="main" class=" p-4">
            <div class="container mt-2">
                <div class="card bg-white">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">SF10 - Student Progress</h5>
                    </div>
                   <!-- Replace the empty <div class="card-body text-center py-5"> with this -->

<div class="card-body">
    <table id="studentTable" class="display table table-bordered" style="width:100%">
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
            // Fetch students with name from sf1 and details from sf9 and section
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

<!-- Add this script to the existing <script> block or create a new one -->

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

        // Handle download button click
        $(document).on('click', '.downloadBtn', function() {
            var lrn = $(this).data('lrn');
            window.location.href = 'export_sf10.php?lrn=' + lrn;
        });
    });
</script>
    </body>
</html>