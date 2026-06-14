<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../db_connect.php';

// Check if user is logged in as Adviser
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Adviser') {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Get current adviser's ID
$adviser_id = $_SESSION['user_id'];

// Fetch class sections assigned to this adviser
$sections = [];
$query = "SELECT 
            s.section_id, 
            s.class_name, 
            s.grade_level, 
            s.track, 
            s.adviser as adviser_id,
            s.semester,
            a.`First Name` as adviser_first_name,
            a.`Last Name` as adviser_last_name,
            (SELECT COUNT(*) FROM student_section WHERE section_id = s.section_id) as student_count
          FROM section s 
          LEFT JOIN accounts a ON s.adviser = a.id
          WHERE s.adviser = ?
          ORDER BY s.grade_level, s.class_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Class Sections - Adviser</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Include the unified sidebar -->
    <?php include '../includes/unified_sidebar.php'; ?>
    
    <!-- Main Content Wrapper -->
    <div id="mainContainer" class="flex-1 transition-all duration-200 pl-64">
        <!-- Main Content -->
        <main class="container mx-auto px-4 py-6">
            <div class="max-w-7xl w-full mx-auto">
                <!-- Single Card Container -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <!-- Card Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <h1 class="text-2xl font-bold text-gray-800">My Class Sections</h1>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-6">
                        <!-- Search Bar -->
                        <div class="mb-6">
                            <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-1">Search My Sections</label>
                            <div class="relative max-w-md">
                                <input type="text" id="searchInput" placeholder="Search by section name or grade level..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Sections Table -->
                        <div class="bg-white/90 shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <?php if (count($sections) > 0): ?>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade Level</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Strand</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($sections as $section): ?>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            Grade <?php echo htmlspecialchars($section['grade_level']); ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($section['class_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900">
                                                            <?php echo htmlspecialchars($section['track'] ?? 'N/A'); ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            <?php echo $section['semester'] === '1st' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                            <?php echo htmlspecialchars($section['semester'] ?? 'N/A'); ?> Semester
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo (int)$section['student_count']; ?> students
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <a href="class_students.php?section_id=<?php echo $section['section_id']; ?>" 
                                                           class="text-blue-600 hover:text-blue-900 mr-4">
                                                            <i class="fas fa-users mr-1"></i> View Students
                                                        </a>
                                                        <a href="adviser_sf9.php?section_id=<?php echo $section['section_id']; ?>" 
                                                           class="text-green-600 hover:text-green-900">
                                                            <i class="fas fa-file-alt mr-1"></i> View SF9
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="text-center py-8 bg-white">
                                        <p class="text-gray-500">You are not assigned to any class sections yet.</p>
                                        <p class="text-gray-400 text-sm mt-2">Please contact the registrar if this is incorrect.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const rows = document.querySelectorAll('tbody tr');

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });
    </script>
</body>
</html>
