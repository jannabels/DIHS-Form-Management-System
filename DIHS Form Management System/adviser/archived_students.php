<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$allowed_roles = ['Guidance', 'Registrar', 'Adviser', 'Admin', 'IT'];

// Redirect if not logged in with appropriate role
if (!$user_id || !in_array($role, $allowed_roles)) {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Determine archive type from URL or default to 'other'
$archive_type = isset($_GET['type']) ? $_GET['type'] : 'other';

// Base query for archived students
$query = "SELECT s.LRN, s.Name, s.Sex, s.Birthdate, s.Age, s.Religious_Affiliation, 
                 s.House_Street_Sitio_Purok, s.Barangay, s.Municipality_City, s.Province, 
                 s.Fathers_Name, s.Mothers_Maiden_Name, s.`Name(Guardian)`, 
                 s.Relationship, s.Contact_Number, s.Remarks, s.archived_at, s.section";

// Add section fields if the tables exist
$tables_exist = false;
$tables_check = $conn->query("SHOW TABLES LIKE 'class_sections'");
if ($tables_check->num_rows > 0) {
    $query .= ", c.section_name, c.grade_level, c.track";
    $tables_exist = true;
}

$query .= " FROM sf1 s";

// Add JOIN if tables exist
if ($tables_exist) {
    $query .= " LEFT JOIN class_sections c ON s.section = c.section_name";
}

$query .= " WHERE (s.status = 'dropped' OR s.status = 'transferred' OR s.status = 'graduated' OR LOWER(s.Remarks) LIKE '%kicked out%')";

// Filter by archive type
if ($archive_type === 'graduated') {
    $query .= " AND (s.status = 'graduated' OR LOWER(s.Remarks) LIKE '%graduated%' OR LOWER(s.Remarks) LIKE '%completed%')";
    $pageTitle = "Graduated Students";
} else {
    $query .= " AND (s.status = 'dropped' OR s.status = 'transferred' OR LOWER(s.Remarks) LIKE '%kicked out%' OR LOWER(s.Remarks) LIKE '%dropped out%' OR LOWER(s.Remarks) LIKE '%transferred out%')";
    $pageTitle = "Archived Students";
}

// Add role-based filtering if tables exist
if ($role === 'Adviser' && $tables_exist) {
    // Check if adviser_sections table exists
    $adviser_sections_check = $conn->query("SHOW TABLES LIKE 'adviser_sections'");
    if ($adviser_sections_check->num_rows > 0) {
        $query .= " AND s.section IN (
            SELECT section_name FROM class_sections WHERE id IN (
                SELECT section_id FROM adviser_sections WHERE user_id = $user_id
            )
        )";
    }
}

$query .= " ORDER BY s.archived_at DESC";

// Get total number of records for pagination
$count_query = str_ireplace('SELECT s.LRN, s.Name, s.Sex, s.Birthdate, s.Age, s.Religious_Affiliation, 
                 s.House_Street_Sitio_Purok, s.Barangay, s.Municipality_City, s.Province, 
                 s.Fathers_Name, s.Mothers_Maiden_Name, s.`Name(Guardian)`, 
                 s.Relationship, s.Contact_Number, s.Remarks, s.archived_at, s.section', 'SELECT COUNT(*) as total', $query);
$total_records = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to the query
$query .= " LIMIT $records_per_page OFFSET $offset";

try {
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?php echo $pageTitle; ?> - DHS System</title>
        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            
            ::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb {
                background: #cbd5e0;
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: #a0aec0;
            }
            
            /* Animations */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .animate-fade-in {
                animation: fadeIn 0.3s ease-out forwards;
            }
            
            /* Card hover effect */
            .student-card {
                transition: all 0.2s ease-in-out;
            }
            
            .student-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }
            
            /* For the rotate chevron in accordion */
            .transform-rotate-180 {
                transform: rotate(180deg);
            }
        </style>
    </head>
    <body class="bg-gray-50">
        <div class="flex h-screen overflow-hidden">
            <!-- Include Unified Sidebar -->
            <?php include '../includes/unified_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="flex-1 overflow-auto ml-0 md:ml-16 lg:ml-64 transition-all duration-300">
                <div class="p-4 md:p-6 lg:p-8">
                    <!-- Header Section -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
                            <p class="text-gray-600 mt-1">
                                <?php 
                                    if ($role === 'Adviser') {
                                        echo 'View archived students from your assigned sections';
                                    } else {
                                        echo 'View and manage archived student records';
                                    }
                                ?>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0 flex space-x-3">
                            <div class="relative">
                                <button id="filterDropdown" class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-filter mr-2"></i> Filter
                                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                                </button>
                                <!-- Dropdown menu -->
                                <div id="filterMenu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                                    <div class="p-3">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Filter by Status</p>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">All Students</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">Kicked Out</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">Dropped Out</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                                <span class="ml-2 text-sm text-gray-700">Transferred Out</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Get counts for each status
                    $kicked_out = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE (status = 'dropped' OR status = 'transferred') AND LOWER(Remarks) LIKE '%kicked out%'")->fetch_assoc()['count'];
                    $dropped_out = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE status = 'dropped' AND (LOWER(Remarks) LIKE '%dropped out%' OR LOWER(Remarks) NOT LIKE '%kicked out%' AND LOWER(Remarks) NOT LIKE '%transferred out%')")->fetch_assoc()['count'];
                    $transferred_out = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE status = 'transferred' AND LOWER(Remarks) LIKE '%transferred out%'")->fetch_assoc()['count'];
                    $graduated = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE status = 'graduated' OR LOWER(Remarks) LIKE '%graduated%' OR LOWER(Remarks) LIKE '%completed%'")->fetch_assoc()['count'];
                    $total_archived = $kicked_out + $dropped_out + $transferred_out + $graduated;
                    ?>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Kicked Out Card -->
                        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500 hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-600">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Kicked Out</p>
                                    <p class="text-2xl font-semibold text-gray-800"><?php echo $kicked_out; ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $total_archived > 0 ? round(($kicked_out / $total_archived) * 100, 1) : 0; ?>% of total</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dropped Out Card -->
                        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500 hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Dropped Out</p>
                                    <p class="text-2xl font-semibold text-gray-800"><?php echo $dropped_out; ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $total_archived > 0 ? round(($dropped_out / $total_archived) * 100, 1) : 0; ?>% of total</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transferred Out Card -->
                        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500 hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Transferred Out</p>
                                    <p class="text-2xl font-semibold text-gray-800"><?php echo $transferred_out; ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $total_archived > 0 ? round(($transferred_out / $total_archived) * 100, 1) : 0; ?>% of total</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Graduated</p>
                                    <p class="text-2xl font-semibold text-gray-800">
                                        <?php 
                                            $graduated = $conn->query("SELECT COUNT(*) as count FROM sf1 WHERE status = 'archived' AND (LOWER(Remarks) LIKE '%graduated%' OR LOWER(Remarks) LIKE '%completed%')")->fetch_assoc()['count'];
                                            echo $graduated;
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table View -->
                    <div id="tableView" class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LRN</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archived Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ($result && $result->num_rows > 0): 
                                        $result->data_seek(0); // Reset result pointer
                                        while ($student = $result->fetch_assoc()): 
                                            $archived_date = !empty($student['archived_at']) ? date('M d, Y', strtotime($student['archived_at'])) : 'N/A';
                                            $remarks = !empty($student['Remarks']) ? $student['Remarks'] : 'No remarks';
                                            
                                            // Determine status badge
                                            $status_badge = '';
                                            $badge_class = 'bg-gray-500';
                                            
                                            if (stripos($remarks, 'kicked out') !== false) {
                                                $status_badge = 'Kicked Out';
                                                $badge_class = 'bg-red-500';
                                            } elseif (stripos($remarks, 'dropped out') !== false) {
                                                $status_badge = 'Dropped Out';
                                                $badge_class = 'bg-yellow-500';
                                            } elseif (stripos($remarks, 'transferred out') !== false) {
                                                $status_badge = 'Transferred Out';
                                                $badge_class = 'bg-blue-500';
                                            } elseif (stripos($remarks, 'graduated') !== false || stripos($remarks, 'completed') !== false) {
                                                $status_badge = 'Graduated';
                                                $badge_class = 'bg-green-500';
                                            } else {
                                                $status_badge = 'Archived';
                                            }
                                    ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($student['LRN']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                                        <i class="fas fa-user-graduate"></i>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['Name']); ?></div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php 
                                                                $age = !empty($student['Age']) ? $student['Age'] . ' years' : 'N/A';
                                                                echo $age . ' • ' . (!empty($student['Sex']) ? htmlspecialchars($student['Sex']) : 'N/A');
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full text-white <?php echo $badge_class; ?>">
                                                    <?php echo $status_badge; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo !empty($student['section']) ? htmlspecialchars($student['section']) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $archived_date; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex space-x-2 justify-end">
                                                    <a href="../registrar/export_sf9.php?lrn=<?php echo urlencode($student['LRN']); ?>" class="inline-flex items-center px-3 py-1 border border-blue-600 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                        <i class="fas fa-file-pdf mr-1"></i> SF9
                                                    </a>
                                                    <a href="../registrar/export_sf10.php?lrn=<?php echo urlencode($student['LRN']); ?>" class="inline-flex items-center px-3 py-1 border border-green-600 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                        <i class="fas fa-file-alt mr-1"></i> SF10
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center">
                                                <div class="inline-flex flex-col items-center">
                                                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                                                        <i class="fas fa-archive text-2xl text-blue-600"></i>
                                                    </div>
                                                    <h3 class="text-lg font-medium text-gray-900 mb-1">No archived students</h3>
                                                    <p class="text-gray-500">There are currently no students in the archive.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                                <?php endif; ?>
                                <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                                        <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> of 
                                        <span class="font-medium"><?php echo $total_records; ?></span> results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <i class="fas fa-chevron-left h-5 w-5"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $start_page + 4);
                                        if ($end_page - $start_page < 4) {
                                            $start_page = max(1, $end_page - 4);
                                        }
                                        
                                        if ($start_page > 1): ?>
                                            <a href="?page=1<?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                                            <?php if ($start_page > 2): ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <a href="?page=<?php echo $i; ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border <?php echo $i == $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($end_page < $total_pages): ?>
                                            <?php if ($end_page < $total_pages - 1): ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                            <?php endif; ?>
                                            <a href="?page=<?php echo $total_pages; ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $total_pages; ?></a>
                                        <?php endif; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Table view is now the only view, no need for toggle logic

            // Toggle filter dropdown
            document.getElementById('filterDropdown').addEventListener('click', function() {
                document.getElementById('filterMenu').classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('filterDropdown');
                const menu = document.getElementById('filterMenu');
                if (dropdown && menu && !dropdown.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });

            // Search functionality with AJAX
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const searchTerm = e.target.value.trim();
                
                if (searchTerm.length === 0) {
                    // If search is empty, reload the page without search
                    const url = new URL(window.location.href);
                    url.searchParams.delete('search');
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                    return;
                }
                
                // Debounce the search to avoid too many requests
                searchTimeout = setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', searchTerm);
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                }, 500);
            });
            
            // Handle search on page load if search parameter exists
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const searchTerm = urlParams.get('search');
                if (searchTerm) {
                    searchInput.value = searchTerm;
                }
            });
            
            // Show restore confirmation modal
            function showRestoreModal(lrn, name) {
                document.getElementById('restoreLrn').value = lrn;
                document.getElementById('studentName').textContent = name;
                document.getElementById('restoreModal').classList.remove('hidden');
            }

            // Handle form submission with fetch
            document.getElementById('restoreForm')?.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        alert('Student restored successfully');
                        // Reload the page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'Failed to restore student'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request');
                }
            });
                .finally(() => {
                    hideRestoreModal();
                });
            }
        </script>
    </body>
</html>
<?php $conn->close(); ?>