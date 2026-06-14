<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db_connect.php';
require_once '../includes/AuditLog.php';

// Check if user is logged in and has the required role
$allowed_roles = ['IT', 'Super Admin', 'Admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header('Location: /systemdihs/login/index.php');
    exit();
}

$auditLog = new AuditLog($conn);
$message = '';
$messageType = '';

// Handle form submission for adding/editing curriculum
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $grade_level = trim($_POST['grade_level']);
        $track = trim($_POST['track']);
        $semester = trim($_POST['semester']);
        $subject_code = trim($_POST['subject_code']);
        $subject_name = trim($_POST['subject_name']);
        $subject_type = trim($_POST['subject_type']);

        if (empty($grade_level) || empty($track) || empty($semester) || empty($subject_code) || empty($subject_name)) {
            throw new Exception("All fields are required");
        }

        if ($id > 0) {
            // Update existing curriculum
            $stmt = $conn->prepare("UPDATE curriculum SET grade_level = ?, track = ?, semester = ?, subject_code = ?, subject_name = ?, subject_type = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $grade_level, $track, $semester, $subject_code, $subject_name, $subject_type, $id);
            $action = 'updated';
        } else {
            // Insert new curriculum
            $stmt = $conn->prepare("INSERT INTO curriculum (grade_level, track, semester, subject_code, subject_name, subject_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $grade_level, $track, $semester, $subject_code, $subject_name, $subject_type);
            $action = 'added';
        }

        if ($stmt->execute()) {
            $message = "Curriculum successfully $action";
            $messageType = 'success';
            
            // Log the action
            $auditLog->log($_SESSION['user_id'] ?? 'system', "Curriculum $action", 'curriculum', $id, null, [
                'grade_level' => $grade_level,
                'track' => $track,
                'semester' => $semester,
                'subject_code' => $subject_code,
                'subject_name' => $subject_name,
                'subject_type' => $subject_type
            ]);
            
            // Redirect to avoid form resubmission
            header("Location: manage_curriculum.php?success=" . urlencode($message));
            exit();
        } else {
            throw new Exception("Error saving curriculum: " . $conn->error);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // Get curriculum info before deleting for audit log
        $stmt = $conn->prepare("SELECT * FROM curriculum WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $curriculum = $result->fetch_assoc();
        
        // Delete the curriculum
        $stmt = $conn->prepare("DELETE FROM curriculum WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Log the deletion
            if ($curriculum) {
                $auditLog->log(
                    $_SESSION['user_id'] ?? 'system',
                    'Curriculum deleted',
                    'curriculum',
                    $id,
                    [
                        'grade_level' => $curriculum['grade_level'],
                        'track' => $curriculum['track'],
                        'semester' => $curriculum['semester'],
                        'subject_code' => $curriculum['subject_code'],
                        'subject_name' => $curriculum['subject_name'],
                        'subject_type' => $curriculum['subject_type']
                    ],
                    null
                );
            }
            
            header("Location: manage_curriculum.php?success=" . urlencode("Curriculum deleted successfully"));
            exit();
        } else {
            throw new Exception("Error deleting curriculum: " . $conn->error);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get all curriculum entries
$curriculum = [];
$tracks = [];
$grade_levels = [];
$semesters = [];

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

try {
    // Get total number of records
    $total_result = $conn->query("SELECT COUNT(*) as total FROM curriculum");
    $total_rows = $total_result ? $total_result->fetch_assoc()['total'] : 0;
    $total_pages = ceil($total_rows / $items_per_page);
    
    // Validate page number
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    // Get paginated curriculum entries
    $query = "SELECT * FROM curriculum ORDER BY grade_level, track, semester, subject_code LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $curriculum = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Get unique values for filters
    $result = $conn->query("SELECT DISTINCT track FROM curriculum WHERE track != '' ORDER BY track");
    $tracks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    $result = $conn->query("SELECT DISTINCT grade_level FROM curriculum WHERE grade_level != '' ORDER BY grade_level");
    $grade_levels = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    $result = $conn->query("SELECT DISTINCT semester FROM curriculum WHERE semester != '' ORDER BY semester");
    $semesters = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
} catch (Exception $e) {
    $message = "Error loading curriculum: " . $e->getMessage();
    $messageType = 'error';
}

// Check for success message in URL
if (isset($_GET['success'])) {
    $message = urldecode($_GET['success']);
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Curriculum - IT Admin</title>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Function to show the curriculum modal
        function showCurriculumModal(id = '', grade_level = '', track = '', semester = '', subject_code = '', subject_name = '', subject_type = 'Core') {
            const modal = document.getElementById('curriculumModal');
            const modalTitle = document.getElementById('modalTitle');
            
            if (id) {
                modalTitle.textContent = 'Edit Curriculum';
                document.getElementById('id').value = id;
                document.getElementById('grade_level').value = grade_level;
                document.getElementById('track').value = track;
                document.getElementById('semester').value = semester;
                document.getElementById('subject_code').value = subject_code;
                document.getElementById('subject_name').value = subject_name;
                document.getElementById('subject_type').value = subject_type;
            } else {
                modalTitle.textContent = 'Add New Curriculum';
                document.getElementById('curriculumForm').reset();
                document.getElementById('id').value = '';
            }
            
            // Show the modal
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            
            // Focus on the first input field
            setTimeout(() => {
                const firstInput = modal.querySelector('input, select');
                if (firstInput) firstInput.focus();
            }, 100);
        }
        
        // Function to hide the curriculum modal
        function hideCurriculumModal() {
            const modal = document.getElementById('curriculumModal');
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        
        // Close modal when clicking outside the modal content
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('curriculumModal');
            const modalContent = document.querySelector('#curriculumModal > div');
            
            if (event.target === document.getElementById('modalBackdrop') || 
                (event.target === modal && !modalContent.contains(event.target))) {
                hideCurriculumModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById('curriculumModal');
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                hideCurriculumModal();
            }
        });
        
        // Function to confirm before deleting
        function confirmDelete(id, subject) {
            if (confirm(`Are you sure you want to delete "${subject}"?`)) {
                window.location.href = `manage_curriculum.php?delete=${id}`;
            }
        }
        
        // Function to populate edit form in modal
        function editCurriculum(id, grade_level, track, semester, subject_code, subject_name, subject_type) {
            showCurriculumModal(id, grade_level, track, semester, subject_code, subject_name, subject_type);
        }
        
        // Reset form for new entry
        function newCurriculum() {
            showCurriculumModal();
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Include Unified Sidebar -->
    <?php include '../includes/unified_sidebar.php'; ?>
        <!-- Logout Button - Fixed at bottom -->
        <div class="border-t border-green-600 p-4 flex-shrink-0">
            <a href="#" id="logoutBtn" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-white rounded-lg hover:bg-red-600 group">
                <span class="menu-icon flex items-center">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </span>
                <span class="menu-text ml-3">Logout</span>
            </a>
        </div>
    </div>

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

    <div class="flex flex-col h-screen pl-0 md:pl-16 lg:pl-64 transition-all duration-300 ease-in-out" id="mainContent">
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page header -->
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Manage Curriculum
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <button type="button" onclick="showCurriculumModal()" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-plus mr-2"></i> Add New
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="mb-6 rounded-md bg-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle h-5 w-5 text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-400" aria-hidden="true"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-800">
                                <?php echo htmlspecialchars($message); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Curriculum Modal -->
            <div id="curriculumModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-full p-4 text-center">
                    <!-- Background overlay -->
                    <div id="modalBackdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="hideCurriculumModal()"></div>

                    <!-- Modal panel -->
                    <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-4xl">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                                        Add New Curriculum
                                    </h3>
                                    <div class="mt-5">
                                        <form id="curriculumForm" method="POST" action="manage_curriculum.php" class="space-y-4">
                                            <input type="hidden" name="id" id="id" value="">
                                            
                                            <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6">
                                                <div class="sm:col-span-2">
                                                    <label for="grade_level" class="block text-sm font-medium text-gray-700">Grade Level</label>
                                                    <div class="mt-1">
                                                        <select id="grade_level" name="grade_level" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                                                            <option value="">Select Grade Level</option>
                                                            <option value="Grade 11">Grade 11</option>
                                                            <option value="Grade 12">Grade 12</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="sm:col-span-2">
                                                    <label for="track" class="block text-sm font-medium text-gray-700">Track</label>
                                                    <div class="mt-1">
                                                        <select id="track" name="track" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                                                            <option value="">Select Track</option>
                                                            <option value="STEM">STEM</option>
                                                            <option value="HUMSS">HUMSS</option>
                                                            <option value="ABM">ABM</option>
                                                            <option value="GAS">GAS</option>
                                                            <option value="TVL-ICT">TVL-ICT</option>
                                                            <option value="TVL-HE">TVL-HE</option>
                                                            <option value="TVL-IA">TVL-IA</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="sm:col-span-2">
                                                    <label for="semester" class="block text-sm font-medium text-gray-700">Semester</label>
                                                    <div class="mt-1">
                                                        <select id="semester" name="semester" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                                                            <option value="">Select Semester</option>
                                                            <option value="1st Semester">1st Semester</option>
                                                            <option value="2nd Semester">2nd Semester</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="sm:col-span-2">
                                                    <label for="subject_code" class="block text-sm font-medium text-gray-700">Subject Code</label>
                                                    <div class="mt-1">
                                                        <input type="text" name="subject_code" id="subject_code" required class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                </div>
                                                
                                                <div class="sm:col-span-3">
                                                    <label for="subject_name" class="block text-sm font-medium text-gray-700">Subject Name</label>
                                                    <div class="mt-1">
                                                        <input type="text" name="subject_name" id="subject_name" required class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                </div>
                                                
                                                <div class="sm:col-span-1">
                                                    <label for="subject_type" class="block text-sm font-medium text-gray-700">Type</label>
                                                    <div class="mt-1">
                                                        <select id="subject_type" name="subject_type" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                                                            <option value="Core">Core</option>
                                                            <option value="Applied">Applied</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" form="curriculumForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save
                            </button>
                            <button type="button" onclick="hideCurriculumModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Curriculum List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-6 py-2 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Curriculum List
                    </h3>
                    <div class="w-64">
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="searchInput" class="focus:ring-green-500 focus:border-green-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" placeholder="Search...">
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table id="curriculumTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                    Grade Level
                                    <i class="fas fa-sort ml-1"></i>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                                    Track
                                    <i class="fas fa-sort ml-1"></i>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                                    Semester
                                    <i class="fas fa-sort ml-1"></i>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(3)">
                                    Code
                                    <i class="fas fa-sort ml-1"></i>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
                                    Subject Name
                                    <i class="fas fa-sort ml-1"></i>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type / Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($curriculum)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No curriculum entries found. Click "Add New" to create one.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($curriculum as $item): ?>
                                    <tr class="hover:bg-gray-50">
                                        <!-- Removed actions cell from here -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($item['grade_level']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($item['track']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($item['semester']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($item['subject_code']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="font-medium"><?php echo htmlspecialchars($item['subject_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $item['subject_type'] === 'Core' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                    <?php echo htmlspecialchars($item['subject_type']); ?>
                                                </span>
                                                <button onclick="editCurriculum('<?php echo $item['id']; ?>', '<?php echo addslashes($item['grade_level']); ?>', '<?php echo addslashes($item['track']); ?>', '<?php echo addslashes($item['semester']); ?>', '<?php echo addslashes($item['subject_code']); ?>', '<?php echo addslashes($item['subject_name']); ?>', '<?php echo $item['subject_type']; ?>')" class="text-green-600 hover:text-green-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                                    <span class="font-medium"><?php echo min($offset + $items_per_page, $total_rows); ?></span> of 
                                    <span class="font-medium"><?php echo $total_rows; ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left h-5 w-5"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start = max(1, $page - 2);
                                    $end = min($start + 4, $total_pages);
                                    $start = max(1, $end - 4);
                                    
                                    if ($start > 1) {
                                        echo '<a href="?page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if ($start > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $start; $i <= $end; $i++): 
                                    ?>
                                        <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo $i == $page ? 'bg-green-50 text-green-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end < $total_pages): ?>
                                        <?php if ($end < $total_pages - 1): ?>
                                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                        <?php endif; ?>
                                        <a href="?page=<?php echo $total_pages; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right h-5 w-5"></i>
                                    </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        /* Sidebar styles */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .nav-item {
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            margin: 0.25rem 0;
        }
        
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-text {
            transition: all 0.3s ease;
        }
        
        .sidebar-collapsed .menu-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            margin-left: 0;
        }
        
        .tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 1rem;
            background-color: #1f2937;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            white-space: nowrap;
            z-index: 50;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }
        
        .tooltip::after {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border-width: 0.5rem;
            border-style: solid;
            border-color: transparent #1f2937 transparent transparent;
        }
        /* Add smooth transitions for the modal */
        #curriculumModal {
            transition: opacity 0.15s ease-in-out;
        }
        
        #curriculumModal:not(.hidden) {
            opacity: 1;
        }
        
        #curriculumModal.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        /* Modal animation */
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        #curriculumModal > div {
            animation: modalFadeIn 0.15s ease-out;
        }
    </style>
    
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.fixed.left-0');
            const toggleBtn = document.getElementById('toggleSidebar');
            const leftArrows = document.querySelectorAll('.fa-chevron-left');
            const rightArrows = document.querySelectorAll('.fa-chevron-right');
            const mainContent = document.getElementById('mainContent');
            const menuTexts = document.querySelectorAll('.menu-text');
            const menuIcons = document.querySelectorAll('.menu-icon');
            const menuHeaders = document.querySelectorAll('.menu-header-container');
            const userInfo = document.querySelector('.user-info');
            const navItems = document.querySelectorAll('.nav-item');
            const logoutModal = document.getElementById('logoutModal');
            const cancelLogout = document.getElementById('cancelLogout');
            const logoutBtn = document.getElementById('logoutBtn');

            // Check for saved state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            // Apply saved state
            if (isCollapsed) {
                toggleSidebar(true);
            }

            // Toggle sidebar
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isCollapsedNow = !sidebar.classList.contains('w-16');
                    toggleSidebar(isCollapsedNow);
                });
            }

            function toggleSidebar(collapse) {
                if (collapse) {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-16');
                    mainContent.classList.remove('pl-64');
                    mainContent.classList.add('pl-16');
                    menuTexts.forEach(text => text.classList.add('opacity-0', 'invisible'));
                    menuIcons.forEach(icon => icon.classList.add('mx-auto'));
                    menuHeaders.forEach(header => {
                        header.classList.add('justify-center');
                        const menuTitle = header.querySelector('.menu-title');
                        if (menuTitle) menuTitle.classList.add('hidden');
                    });
                    if (userInfo) userInfo.classList.add('opacity-0', 'invisible');
                    leftArrows.forEach(arrow => arrow.classList.add('hidden'));
                    rightArrows.forEach(arrow => arrow.classList.remove('hidden'));
                } else {
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    mainContent.classList.remove('pl-16');
                    mainContent.classList.add('pl-64');
                    menuTexts.forEach(text => text.classList.remove('opacity-0', 'invisible'));
                    menuIcons.forEach(icon => icon.classList.remove('mx-auto'));
                    menuHeaders.forEach(header => {
                        header.classList.remove('justify-center');
                        const menuTitle = header.querySelector('.menu-title');
                        if (menuTitle) menuTitle.classList.remove('hidden');
                    });
                    if (userInfo) userInfo.classList.remove('opacity-0', 'invisible');
                    leftArrows.forEach(arrow => arrow.classList.remove('hidden'));
                    rightArrows.forEach(arrow => arrow.classList.add('hidden'));
                }
                
                // Save state
                localStorage.setItem('sidebarCollapsed', collapse);
                
                // Initialize or destroy tooltips based on state
                if (collapse) {
                    initSidebarTooltips();
                } else {
                    // Clean up tooltips when expanding
                    document.querySelectorAll('.tooltip').forEach(tooltip => tooltip.remove());
                    navItems.forEach(item => {
                        item.removeAttribute('data-tooltip');
                        item.removeAttribute('data-tooltip-init');
                        item.style.removeProperty('position');
                    });
                }
            }

            // Initialize tooltips for sidebar items
            function initSidebarTooltips() {
                const menuItems = document.querySelectorAll('.nav-item');
                
                menuItems.forEach(item => {
                    if (sidebar.classList.contains('w-16')) {
                        const text = item.querySelector('.menu-text')?.textContent || '';
                        item.setAttribute('data-tooltip', text);
                        
                        // Add tooltip styles
                        if (!item.hasAttribute('data-tooltip-init')) {
                            item.style.position = 'relative';
                            item.setAttribute('data-tooltip-init', 'true');
                            
                            item.addEventListener('mouseenter', function(e) {
                                const tooltip = document.createElement('div');
                                tooltip.className = 'tooltip';
                                tooltip.textContent = this.getAttribute('data-tooltip');
                                
                                // Position the tooltip
                                const rect = this.getBoundingClientRect();
                                tooltip.style.position = 'fixed';
                                tooltip.style.left = `${rect.right + 10}px`;
                                tooltip.style.top = `${rect.top + window.scrollY}px`;
                                tooltip.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                                tooltip.style.color = 'white';
                                tooltip.style.padding = '4px 8px';
                                tooltip.style.borderRadius = '4px';
                                tooltip.style.fontSize = '12px';
                                tooltip.style.whiteSpace = 'nowrap';
                                tooltip.style.zIndex = '9999';
                                
                                document.body.appendChild(tooltip);
                                this._tooltip = tooltip;
                                
                                // Show tooltip with a slight delay
                                setTimeout(() => {
                                    if (this._tooltip) {
                                        this._tooltip.style.opacity = '1';
                                    }
                                }, 100);
                            });
                            
                            item.addEventListener('mouseleave', function() {
                                if (this._tooltip) {
                                    document.body.removeChild(this._tooltip);
                                    this._tooltip = null;
                                }
                            });
                        }
                    } else {
                        item.removeAttribute('data-tooltip');
                        item.removeAttribute('data-tooltip-init');
                        item.style.removeProperty('position');
                        
                        // Clean up event listeners
                        const newItem = item.cloneNode(true);
                        item.parentNode.replaceChild(newItem, item);
                    }
                });
            }

            // Logout modal
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    logoutModal.classList.remove('hidden');
                });
            }

            if (cancelLogout) {
                cancelLogout.addEventListener('click', function() {
                    logoutModal.classList.add('hidden');
                });
            }

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    logoutModal.classList.add('hidden');
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !logoutModal.classList.contains('hidden')) {
                    logoutModal.classList.add('hidden');
                }
            });
        });
        // Handle form submission success
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('curriculumForm');
            if (form) {
                form.addEventListener('submit', function() {
                    // Hide the modal after form submission
                    setTimeout(hideCurriculumModal, 100);
                });
            }
            
            // Close modal when clicking the Cancel button
            const cancelButton = document.querySelector('[onclick="hideCurriculumModal()"]');
            if (cancelButton) {
                cancelButton.addEventListener('click', hideCurriculumModal);
            }
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#curriculumTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Sort functionality
        function sortTable(columnIndex) {
            const table = document.getElementById('curriculumTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.rows);
            const header = table.rows[0].cells[columnIndex];
            const isNumeric = columnIndex === 0; // Grade Level is numeric
            
            // Toggle sort direction
            const isAscending = !header.classList.contains('asc');
            
            // Reset sort indicators
            document.querySelectorAll('th i').forEach(icon => {
                icon.className = 'fas fa-sort ml-1';
            });
            
            // Set sort indicator
            const sortIcon = header.querySelector('i');
            sortIcon.className = isAscending ? 'fas fa-sort-up ml-1' : 'fas fa-sort-down ml-1';
            
            // Sort rows
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();
                
                if (isNumeric) {
                    const aNum = parseInt(aText) || 0;
                    const bNum = parseInt(bText) || 0;
                    return isAscending ? aNum - bNum : bNum - aNum;
                } else {
                    return isAscending 
                        ? aText.localeCompare(bText)
                        : bText.localeCompare(aText);
                }
            });
            
            // Re-append rows in new order
            rows.forEach(row => tbody.appendChild(row));
            
            // Toggle sort direction class
            if (isAscending) {
                header.classList.add('asc');
                header.classList.remove('desc');
            } else {
                header.classList.add('desc');
                header.classList.remove('asc');
            }
        }
    </script>
</body>
</html>
