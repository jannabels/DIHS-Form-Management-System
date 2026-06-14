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

// Handle promotion action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_promotion') {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $promoted = isset($_POST['promoted']) ? 1 : 0;
    $grade_level = mysqli_real_escape_string($conn, $_POST['grade_level']);
    $sy = mysqli_real_escape_string($conn, $_POST['sy']);
    
    // Update promotion status
    $update_query = "UPDATE sf9 SET 
                    promoted = ?, 
                    status = IF(? = 1, 'Promoted - Passed all subjects', 'Retained - Did not meet passing requirements'),
                    grade_level = ?,
                    sy = ?,
                    promotion_date = NOW()
                    WHERE LRN = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("issss", $promoted, $promoted, $grade_level, $sy, $lrn);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// Handle bulk promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_promote') {
    $promoted_lrns = isset($_POST['promoted_lrns']) ? $_POST['promoted_lrns'] : [];
    $sy = mysqli_real_escape_string($conn, $_POST['sy']);
    $success = true;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, mark all as retained
        $update_retained = "UPDATE sf9 SET 
                          promoted = 0, 
                          status = 'Retained - Did not meet passing requirements',
                          promotion_date = NOW()
                          WHERE grade_level = 'Grade 11' AND LRN IN (" . 
                          implode(',', array_fill(0, count($promoted_lrns), '?')) . ")";
        
        $stmt = $conn->prepare($update_retained);
        $types = str_repeat('s', count($promoted_lrns));
        $stmt->bind_param($types, ...$promoted_lrns);
        $stmt->execute();
        
        // Then update promoted students
        $update_promoted = "UPDATE sf9 SET 
                          promoted = 1, 
                          status = 'Promoted - Passed all subjects',
                          grade_level = 'Grade 12',
                          sy = ?,
                          promotion_date = NOW()
                          WHERE grade_level = 'Grade 11' AND LRN IN (" . 
                          implode(',', array_fill(0, count($promoted_lrns), '?')) . ")";
        
        $params = array_merge([$sy], $promoted_lrns);
        $stmt = $conn->prepare($update_promoted);
        $types = 's' . str_repeat('s', count($promoted_lrns));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Get current school year (you might want to get this from your settings)
$current_year = date('Y');
$next_year = $current_year + 1;
$current_sy = "$current_year-$next_year";

// Get all Grade 11 students with their subject grades and promotion status
$query = "
    SELECT 
        s.LRN,
        s.Name,
        s.section,
        'Grade 11' as grade_level,
        s.sy,
        IFNULL(sf9.status, 'New Student') as status,
        IFNULL(sf9.promoted, 0) as promoted,
        COALESCE(
            (SELECT 
                GROUP_CONCAT(
                    CONCAT(
                        sg.subject_code, ' (', 
                        COALESCE(sg.final_grade_12, sg.final_grade_34, 'N/A'),
                        IF(sg.final_grade_12 >= 75 OR sg.final_grade_34 >= 75, ' ✓', ' ✗'),
                        ')'
                    ) SEPARATOR '<br>'
                )
            FROM student_grades sg 
            WHERE sg.LRN = s.LRN
            GROUP BY sg.LRN),
            'No grades found'
        ) as subjects_grades,
        (SELECT COUNT(*) FROM student_grades WHERE LRN = s.LRN) as total_subjects,
        (SELECT COUNT(*) FROM student_grades 
         WHERE LRN = s.LRN AND (final_grade_12 >= 75 OR final_grade_34 >= 75)) as passed_subjects,
        (SELECT COUNT(*) = SUM(CASE WHEN final_grade_12 >= 75 OR final_grade_34 >= 75 THEN 1 ELSE 0 END) 
         FROM student_grades 
         WHERE LRN = s.LRN) as all_passed
    FROM 
        sf1 s
    LEFT JOIN 
        sf9 ON s.LRN = sf9.LRN AND sf9.grade_level = 'Grade 11'
    WHERE 
        s.section LIKE '%Grade 11%' OR s.section LIKE '%11%' OR 1=1
    GROUP BY 
        s.LRN, s.Name, s.section, s.sy
    ORDER BY 
        s.Name
";

$result = $conn->query($query);

// Check for query errors
if ($result === false) {
    die("Query failed: " . $conn->error);
}

$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} else {
    // Log or handle the case when no students are found
    error_log("No students found in Grade 11");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SF9 - Grade 11 to Grade 12 Promotion | DHS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.tailwind.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">SF9 - Grade 11 to Grade 12 Promotion</h1>
                <div class="flex space-x-2">
                    <button id="bulkPromoteBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user-graduate mr-1"></i> Promote Selected Students
                    </button>
                    <button id="printBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                </div>
            </div>

            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Promotion Rules:</strong> A student is promoted to Grade 12 if they pass all subjects (Final Grade ≥ 75).
                            The system automatically checks this, but you can manually override if needed.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="sy" class="block text-sm font-medium text-gray-700 mb-1">School Year:</label>
                <input type="text" id="sy" name="sy" value="<?php echo htmlspecialchars($current_sy); ?>" 
                       class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>

            <div class="overflow-x-auto">
                <table id="promotionTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="selectAll" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LRN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subjects & Grades</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($students as $student): ?>
                            <tr class="student-row" data-lrn="<?php echo htmlspecialchars($student['LRN']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="student-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" 
                                           data-lrn="<?php echo htmlspecialchars($student['LRN']); ?>"
                                           <?php echo ($student['all_passed'] ?? false) ? 'checked' : ''; ?>>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['LRN']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($student['Name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $student['subjects_grades'] ?? 'No grades found'; ?>
                                    <div class="mt-1 text-xs text-gray-500">
                                        <?php 
                                        $passed = $student['passed_subjects'] ?? 0;
                                        $total = $student['total_subjects'] ?? 0;
                                        echo "$passed of $total subjects passed"; 
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($student['all_passed'] ?? false) || ($student['promoted'] ?? false)): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Promoted - Passed all subjects
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Retained - Did not meet passing requirements
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-3 update-promotion" 
                                            data-lrn="<?php echo htmlspecialchars($student['LRN']); ?>"
                                            data-promoted="<?php echo ($student['all_passed'] || $student['promoted']) ? '1' : '0'; ?>">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#promotionTable').DataTable({
            paging: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            dom: 'Bfrtip',
            buttons: [
                'pageLength',
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel mr-1"></i> Export to Excel',
                    className: 'bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5] // LRN, Name, Section, Subjects, Status
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print mr-1"></i> Print',
                    className: 'bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5] // LRN, Name, Section, Subjects, Status
                    },
                    title: 'SF9 - Grade 11 to Grade 12 Promotion',
                    message: 'Generated on ' + new Date().toLocaleDateString()
                }
            ]
        });

        // Select all checkboxes
        $('#selectAll').change(function() {
            $('.student-checkbox').prop('checked', $(this).prop('checked'));
        });

        // Update individual promotion status
        $('.update-promotion').click(function() {
            const lrn = $(this).data('lrn');
            const currentPromoted = $(this).data('promoted') === '1';
            const newPromoted = !currentPromoted;
            
            updatePromotionStatus(lrn, newPromoted, $(this));
        });

        // Bulk promotion
        $('#bulkPromoteBtn').click(function() {
            const selectedLrns = [];
            $('.student-checkbox:checked').each(function() {
                selectedLrns.push($(this).data('lrn'));
            });

            if (selectedLrns.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Students Selected',
                    text: 'Please select at least one student to promote.'
                });
                return;
            }

            const sy = $('#sy').val();
            
            Swal.fire({
                title: 'Confirm Promotion',
                text: `Are you sure you want to promote ${selectedLrns.length} selected students to Grade 12 for SY ${sy}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, promote them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkPromoteStudents(selectedLrns, sy);
                }
            });
        });

        // Print button
        $('#printBtn').click(function() {
            window.print();
        });
    });

    function updatePromotionStatus(lrn, promoted, button) {
        const statusText = promoted ? 'Promoted - Passed all subjects' : 'Retained - Did not meet passing requirements';
        const statusClass = promoted ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        const statusTextDisplay = promoted ? 'Promoted - Passed all subjects' : 'Retained - Did not meet passing requirements';
        
        // Update UI immediately for better UX
        const row = button.closest('tr');
        row.find('.status-badge')
            .removeClass('bg-green-100 text-green-800 bg-red-100 text-red-800')
            .addClass(statusClass)
            .text(statusTextDisplay);
            
        // Update the checkbox state
        row.find(`.student-checkbox[data-lrn="${lrn}"]`).prop('checked', promoted);
        
        // Update the button data
        button.data('promoted', promoted ? '1' : '0');
        
        // Show loading state
        const originalHtml = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        // Send the update to the server
        $.ajax({
            url: 'sf9_promotion.php',
            type: 'POST',
            data: {
                action: 'update_promotion',
                lrn: lrn,
                promoted: promoted ? 1 : 0,
                grade_level: promoted ? 'Grade 12' : 'Grade 11',
                sy: $('#sy').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the status in the table
                    const statusCell = row.find('td:eq(5)');
                    statusCell.html(`
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                            ${statusTextDisplay}
                        </span>
                    `);
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: `Student ${promoted ? 'promoted' : 'retained'} successfully.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    // Revert UI on error
                    row.find('.status-badge')
                        .removeClass(statusClass)
                        .addClass(promoted ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800')
                        .text(promoted ? 'Retained - Did not meet passing requirements' : 'Promoted - Passed all subjects');
                    
                    row.find(`.student-checkbox[data-lrn="${lrn}"]`).prop('checked', !promoted);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Failed to update promotion status.'
                    });
                }
            },
            error: function() {
                // Revert UI on error
                row.find('.status-badge')
                    .removeClass(statusClass)
                    .addClass(promoted ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800')
                    .text(promoted ? 'Retained - Did not meet passing requirements' : 'Promoted - Passed all subjects');
                
                row.find(`.student-checkbox[data-lrn="${lrn}"]`).prop('checked', !promoted);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to connect to the server.'
                });
            },
            complete: function() {
                // Restore button
                button.html(originalHtml).prop('disabled', false);
            }
        });
    }

    function bulkPromoteStudents(lrns, sy) {
        // Show loading state
        const button = $('#bulkPromoteBtn');
        const originalHtml = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        // Send the bulk update to the server
        $.ajax({
            url: 'sf9_promotion.php',
            type: 'POST',
            data: {
                action: 'bulk_promote',
                promoted_lrns: lrns,
                sy: sy
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reload the page to reflect changes
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: `${lrns.length} students have been promoted to Grade 12 for SY ${sy}.`,
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Failed to process bulk promotion.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to connect to the server.'
                });
            },
            complete: function() {
                // Restore button
                button.html(originalHtml).prop('disabled', false);
            }
        });
    }
    </script>
</body>
</html>
