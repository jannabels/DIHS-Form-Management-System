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

// Handle AJAX request to update subject status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_subject_status') {
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
    $is_completed = isset($_POST['is_completed']) ? 1 : 0;
    
    // Update the subject status in the database
    $update_query = "UPDATE student_grades SET is_completed = ? WHERE LRN = ? AND subject_code = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iss", $is_completed, $lrn, $subject_code);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// Get all SHS students
$students_query = "SELECT DISTINCT s.LRN, s.Name 
                  FROM sf1 s 
                  INNER JOIN student_grades sg ON s.LRN = sg.LRN 
                  WHERE sg.grade_level IN ('Grade 11', 'Grade 12')
                  ORDER BY s.Name";
$students_result = $conn->query($students_query);

// Get all SHS subjects
$subjects_query = "SELECT DISTINCT subject_code, subject_name, grade_level, semester 
                  FROM curriculum 
                  WHERE grade_level IN ('Grade 11', 'Grade 12')
                  ORDER BY grade_level, semester, subject_name";
$subjects_result = $conn->query($subjects_query);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SHS Subjects | DHS</title>
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
            <h1 class="text-2xl font-bold text-gray-800 mb-6">SHS Subjects Management</h1>
            
            <div class="mb-6">
                <label for="studentSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Student:</label>
                <select id="studentSelect" class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">-- Select a student --</option>
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($student['LRN']); ?>">
                            <?php echo htmlspecialchars($student['Name'] . ' (' . $student['LRN'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="subjectList" class="hidden">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Subject Completion Status</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Name</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade Level</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody id="subjectTableBody" class="divide-y divide-gray-200">
                            <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                                <tr class="subject-row" data-subject-code="<?php echo htmlspecialchars($subject['subject_code']); ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($subject['subject_code']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($subject['grade_level']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($subject['semester']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <input type="checkbox" class="subject-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" 
                                                data-subject-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                                onchange="updateSubjectStatus(this)">
                                            <span class="ml-2 text-sm text-gray-700">Completed</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('table').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            responsive: true
        });

        // When student is selected, load their subject status
        $('#studentSelect').change(function() {
            const lrn = $(this).val();
            if (lrn) {
                loadStudentSubjects(lrn);
                $('#subjectList').removeClass('hidden');
            } else {
                $('#subjectList').addClass('hidden');
            }
        });
    });

    function loadStudentSubjects(lrn) {
        // Show loading state
        $('.subject-row').each(function() {
            const checkbox = $(this).find('.subject-checkbox');
            checkbox.prop('disabled', true);
        });

        // Fetch student's subject status
        $.ajax({
            url: 'get_student_subjects.php',
            type: 'GET',
            data: { lrn: lrn },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reset all checkboxes
                    $('.subject-checkbox').prop('checked', false);
                    
                    // Update checkboxes based on response
                    response.subjects.forEach(function(subject) {
                        $(`.subject-checkbox[data-subject-code="${subject.subject_code}"]`)
                            .prop('checked', subject.is_completed === '1' || subject.final_grade_12 >= 75 || subject.final_grade_34 >= 75);
                    });

                    // Enable all checkboxes
                    $('.subject-checkbox').prop('disabled', false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Failed to load student subjects.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to connect to the server.'
                });
            }
        });
    }

    function updateSubjectStatus(checkbox) {
        const lrn = $('#studentSelect').val();
        const subjectCode = $(checkbox).data('subject-code');
        const isCompleted = $(checkbox).is(':checked');

        // Disable checkbox while updating
        $(checkbox).prop('disabled', true);

        // Send update request
        $.ajax({
            url: 'update_subject_status.php',
            type: 'POST',
            data: {
                lrn: lrn,
                subject_code: subjectCode,
                is_completed: isCompleted ? 1 : 0,
                action: 'update_subject_status'
            },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    // Revert checkbox if update failed
                    $(checkbox).prop('checked', !isChecked);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Failed to update subject status.'
                    });
                }
                // Re-enable checkbox
                $(checkbox).prop('disabled', false);
            },
            error: function() {
                // Revert checkbox on error
                $(checkbox).prop('checked', !isChecked);
                $(checkbox).prop('disabled', false);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to connect to the server.'
                });
            }
        });
    }
    </script>
</body>
</html>
