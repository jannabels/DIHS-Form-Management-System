<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and sidebar
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/unified_sidebar.php';

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Registrar') {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Fetch all class sections
$sections = [];
$query = "SELECT 
            s.section_id, 
            s.class_name, 
            s.grade_level, 
            s.track, 
            s.adviser as adviser_id,
            s.semester,
            a.`First Name` as adviser_first_name,
            a.`Last Name` as adviser_last_name
          FROM section s 
          LEFT JOIN accounts a ON s.adviser = a.id
          ORDER BY s.grade_level, s.class_name";

// Debug: Log the query
error_log("Sections Query: " . $query);

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
}

// Debug: Log the query and result count
error_log("Class sections query: " . $query);
error_log("Found " . count($sections) . " sections");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Sections - Registrar</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Ensure content doesn't hide behind fixed sidebar */
        .main-content {
            margin-left: 16rem; /* Match sidebar width */
            padding: 1.5rem;
            min-height: 100vh;
            transition: margin 0.3s ease;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Main Content Wrapper -->
    <div id="mainContainer" class="main-content">
        <!-- Main Content -->
        <main class="w-full">
        <div class="max-w-7xl w-full mx-auto">
        <!-- Single Card Container -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <!-- Card Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <h1 class="text-2xl font-bold text-gray-800">Class Sections</h1>
                    <a href="../guidance/createclass.php" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2"></i> Create New Section
                    </a>
                </div>
            </div>
            
            <!-- Card Body -->
            <div class="p-6">
                <!-- Search and Filter Controls -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search Bar -->
                    <div class="md:col-span-2">
                        <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-1">Search Sections</label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search by section name or code..." 
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grade Level Filter -->
                    <div>
                        <label for="gradeFilter" class="block text-sm font-medium text-gray-700 mb-1">Grade Level</label>
                        <div class="flex gap-2">
                            <select id="gradeFilter" 
                                class="flex-1 pl-3 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white">
                                <option value="">All Grades</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                            <button id="clearFilters" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white/90 shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <?php if (count($sections) > 0): ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade Level</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Strand</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adviser</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sectionsContainer" class="bg-white divide-y divide-gray-200">
                            <?php foreach ($sections as $section): 
                                $adviser_name = '';
                                if (!empty($section['adviser_first_name']) || !empty($section['adviser_last_name'])) {
                                    $adviser_name = trim($section['adviser_first_name'] . ' ' . $section['adviser_last_name']);
                                } else {
                                    $adviser_name = !empty($section['adviser']) ? $section['adviser'] : 'Unassigned';
                                }
                            ?>
                            <tr class="section-row hover:bg-gray-50" data-grade="<?php echo htmlspecialchars($section['grade_level']); ?>" data-section="<?php echo htmlspecialchars($section['class_name']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    $grade_display = trim($section['grade_level'] ?? '');
                                    // If grade_level already contains "Grade", use it as-is, otherwise add "Grade" prefix
                                    if (stripos($grade_display, 'Grade') === 0) {
                                        echo htmlspecialchars($grade_display);
                                    } else {
                                        // Extract just the number if it exists
                                        if (preg_match('/(\d+)/', $grade_display, $matches)) {
                                            echo 'Grade ' . htmlspecialchars($matches[1]);
                                        } else {
                                            echo !empty($grade_display) ? 'Grade ' . htmlspecialchars($grade_display) : '';
                                        }
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($section['class_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo !empty($section['track']) ? htmlspecialchars($section['track']) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                        $adviser_name = '';
                                        if (!empty($section['adviser_first_name']) && !empty($section['adviser_last_name'])) {
                                            $adviser_name = trim($section['adviser_first_name'] . ' ' . $section['adviser_last_name']);
                                        } elseif (!empty($section['adviser']) && is_string($section['adviser'])) {
                                            $adviser_name = $section['adviser'];
                                        } else {
                                            $adviser_name = 'Unassigned';
                                        }
                                        echo htmlspecialchars($adviser_name); 
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="guidance_sf1.php?section_id=<?php echo (int)$section['section_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900">
                                        View SF1
                                    </a>
                                </td>
                            </tr> 
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-8 bg-white">
                        <p class="text-gray-500">No class sections found. 
                            <a href="../guidance/createclass.php" class="text-blue-600 hover:underline font-medium">Create one now</a>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
        </main>
    </div>

    <!-- Sidebar Toggle Script -->
    <script>
        // Sidebar toggle functionality is handled by unified_sidebar.php
        document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const gradeFilter = document.getElementById('gradeFilter');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const sectionRows = document.querySelectorAll('#sectionsContainer tr.section-row');

        function filterSections() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedGrade = gradeFilter.value;

            sectionRows.forEach(row => {
                const sectionName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const gradeLevel = row.querySelector('td:first-child').textContent.toLowerCase();
                const rowText = row.textContent.toLowerCase();
                
                const matchesSearch = sectionName.includes(searchTerm) || 
                                    rowText.includes(searchTerm);
                const matchesGrade = !selectedGrade || 
                                   gradeLevel.includes(`grade ${selectedGrade}`) || 
                                   gradeLevel.includes(selectedGrade);

                if (matchesSearch && matchesGrade) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Event listeners
        searchInput.addEventListener('input', filterSections);
        gradeFilter.addEventListener('change', filterSections);
        
        clearFiltersBtn.addEventListener('click', function() {
            searchInput.value = '';
            gradeFilter.value = '';
            filterSections();
        });
    });
    </script>
</body>
</html>

