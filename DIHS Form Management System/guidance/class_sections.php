<?php
// Last updated: 2025-11-20 03:33:45
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Guidance') {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Pagination settings
$items_per_page = 5; // Number of items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $items_per_page;

// Get total number of sections
$count_query = "SELECT COUNT(*) as total FROM section";
$count_result = $conn->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Adjust page if it's out of range
$page = min($page, $total_pages);

// Fetch paginated class sections
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
          ORDER BY s.grade_level, s.class_name
          LIMIT $items_per_page OFFSET $offset";

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
    <title>Class Sections - Guidance</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Class Sections</h1>
                    <a href="createclass.php" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
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
                                    <?php 
                                    // Build the section name for the URL
                                    $section_name_for_url = '';
                                    if (!empty($section['grade_level']) && !empty($section['class_name'])) {
                                        $section_name_for_url = trim($section['grade_level'] . ' - ' . $section['class_name']);
                                        if (!empty($section['track'])) {
                                            $section_name_for_url = $section['track'] . ' ' . $section_name_for_url;
                                        }
                                    } elseif (!empty($section['section_name'])) {
                                        $section_name_for_url = $section['section_name'];
                                    } else {
                                        $section_name_for_url = 'Section ' . $section['section_id'];
                                    }
                                    ?>
                                    <a href="guidance_sf1.php?section_id=<?php echo (int)$section['section_id']; ?>&section_name=<?php echo urlencode($section_name_for_url); ?>" 
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                       data-section-name="<?php echo htmlspecialchars($section_name_for_url); ?>">
                                        <i class="fas fa-file-alt mr-1"></i> View SF1
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-8 bg-white">
                        <p class="text-gray-500">No class sections found. 
                            <a href="createclass.php" class="text-blue-600 hover:underline font-medium">Create one now</a>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Page <span class="font-medium"><?php echo $page; ?></span> of 
                    <span class="font-medium"><?php echo $total_pages; ?></span> | 
                    Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to 
                    <span class="font-medium"><?php echo min($offset + $items_per_page, $total_items); ?></span> of 
                    <span class="font-medium"><?php echo $total_items; ?></span> sections
                </div>
                <div class="flex items-center space-x-2">
                    <!-- First Page -->
                    <a href="?page=1" class="px-3 py-1 border rounded <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <!-- Previous Page -->
                    <a href="?page=<?php echo max(1, $page - 1); ?>" class="px-3 py-1 border rounded <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 1);
                    $end_page = min($total_pages, $page + 1);
                    
                    // Adjust if we're near the start
                    if ($start_page > 1) {
                        echo '<span class="px-3 py-1">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                        $active = $i == $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-100';
                    ?>
                        <a href="?page=<?php echo $i; ?>" class="px-3 py-1 border rounded <?php echo $active; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Add ellipsis if needed -->
                    <?php if ($end_page < $total_pages): ?>
                        <span class="px-3 py-1">...</span>
                    <?php endif; ?>
                    
                    <!-- Next Page -->
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>" class="px-3 py-1 border rounded <?php echo $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <!-- Last Page -->
                    <a href="?page=<?php echo $total_pages; ?>" class="px-3 py-1 border rounded <?php echo $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const gradeFilter = document.getElementById('gradeFilter');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const sectionRows = document.querySelectorAll('#sectionsContainer tr.section-row');
        const urlParams = new URLSearchParams(window.location.search);
        
        // Preserve search parameters in pagination links
        function updatePaginationLinks() {
            const paginationLinks = document.querySelectorAll('.pagination a');
            paginationLinks.forEach(link => {
                const url = new URL(link.href);
                if (searchInput.value) {
                    url.searchParams.set('search', searchInput.value);
                }
                if (gradeFilter.value) {
                    url.searchParams.set('grade', gradeFilter.value);
                }
                link.href = url.toString();
            });
        }

        function filterSections() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedGrade = gradeFilter.value;
            let visibleCount = 0;

            sectionRows.forEach(row => {
                const sectionName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const gradeLevel = row.querySelector('td:first-child').textContent.toLowerCase();
                const rowText = row.textContent.toLowerCase();
                
                const matchesSearch = searchTerm === '' || 
                                    sectionName.includes(searchTerm) || 
                                    rowText.includes(searchTerm);
                const matchesGrade = !selectedGrade || 
                                   gradeLevel.includes(`grade ${selectedGrade}`) || 
                                   gradeLevel.includes(selectedGrade);

                if (matchesSearch && matchesGrade) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update the pagination info
            const paginationInfo = document.querySelector('.pagination-info');
            if (paginationInfo) {
                paginationInfo.textContent = `Showing ${visibleCount} of ${sectionRows.length} sections`;
            }

            updatePaginationLinks();
        }

        // Initialize with URL parameters
        if (urlParams.has('search')) {
            searchInput.value = urlParams.get('search');
        }
        if (urlParams.has('grade')) {
            gradeFilter.value = urlParams.get('grade');
        }

        // Initial filter
        filterSections();

        // Event listeners
        searchInput.addEventListener('input', filterSections);
        gradeFilter.addEventListener('change', filterSections);
        
        clearFiltersBtn.addEventListener('click', function() {
            searchInput.value = '';
            gradeFilter.value = '';
            filterSections();
            // Remove search parameters from URL
            const url = new URL(window.location);
            url.searchParams.delete('search');
            url.searchParams.delete('grade');
            window.history.pushState({}, '', url);
        });
    });
    </script>
</body>
</html>
