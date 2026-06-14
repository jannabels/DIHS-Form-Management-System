<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has OIC role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'OIC') {
    header('Location: /systemdihs/login/index.php');
    exit();
}

// Initialize variables
$message = '';
$current_year = date('Y');
$school_year = $_GET['school_year'] ?? $current_year . '-' . ($current_year + 1);
$months = [
    'June', 'July', 'August', 'September', 'October', 
    'November', 'December', 'January', 'February', 'March'
];

// Function to initialize school year
function initializeSchoolYear($conn, $school_year, $months) {
    // Delete existing entries for this school year
    $delete_sql = "DELETE FROM school_days WHERE school_year = ?";
    if (!($stmt = $conn->prepare($delete_sql))) {
        die("Error preparing delete: " . $conn->error);
    }
    $stmt->bind_param('s', $school_year);
    $stmt->execute();
    $stmt->close();

    // Insert default values
    $insert_sql = "INSERT INTO school_days (school_year, month_name, school_days) VALUES (?, ?, ?)";
    if (!($stmt = $conn->prepare($insert_sql))) {
        die("Error preparing insert: " . $conn->error);
    }

    // Set more accurate default school days per month (totaling 197 days)
    $default_days = [
        'June' => 22,
        'July' => 21,
        'August' => 23,
        'September' => 21,
        'October' => 22,
        'November' => 19,
        'December' => 15,
        'January' => 22,
        'February' => 20,
        'March' => 12
    ];
    
    foreach ($months as $month) {
        $days = $default_days[$month] ?? 0;
        $stmt->bind_param('ssi', $school_year, $month, $days);
        $stmt->execute();
    }
    $stmt->close();
}

// Handle form submission for bulk updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_all_days'])) {
    $school_year = $_POST['school_year'];
    $days = $_POST['days'] ?? [];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing entries for this school year
        $delete_sql = "DELETE FROM school_days WHERE school_year = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param('s', $school_year);
        $stmt->execute();
        
        // Insert new values
        $insert_sql = "INSERT INTO school_days (school_year, month_name, school_days) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        
        foreach ($days as $month => $days_count) {
            $days_count = (int)$days_count;
            if ($days_count >= 0 && $days_count <= 31) {
                $stmt->bind_param('ssi', $school_year, $month, $days_count);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $message = '<div class="p-3 mb-4 text-green-800 bg-green-100 rounded">School days updated successfully!</div>';
        
        // Refresh to show updated data
        header("Location: school_days_new.php?school_year=" . urlencode($school_year));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="p-3 mb-4 text-red-800 bg-red-100 rounded">Error updating school days: ' . $e->getMessage() . '</div>';
    }
}
// Initialize school year if needed
$check_sql = "SELECT COUNT(*) as count FROM school_days WHERE school_year = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param('s', $school_year);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row['count'] == 0) {
    initializeSchoolYear($conn, $school_year, $months);
}

// Get all school days for the current year
$school_days = [];
$sql = "SELECT month_name, school_days FROM school_days WHERE school_year = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $school_year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $school_days[$row['month_name']] = $row['school_days'];
}
$stmt->close();

// Generate year options
$year_options = [];
for ($y = $current_year - 1; $y <= $current_year + 1; $y++) {
    $year_options[] = $y . '-' . ($y + 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Days Management - OIC Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .month-row td {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        table {
            border-collapse: separate;
            border-spacing: 0;
        }
        table tr:not(:last-child) td {
            border-bottom: 1px solid #e5e5eb;
        }
        /* Sidebar styles */
        .sidebar-collapsed {
            width: 4rem !important;
        }
        .sidebar-collapsed + #mainContent {
            margin-left: 4rem !important;
        }
        /* Animate menu text when collapsing/expanding */
        .menu-text,
        .user-name,
        .user-role,
        .menu-item-text {
            transition: all 0.3s ease-in-out;
            white-space: nowrap;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }
        /* Hide text in menu items when collapsed */
        .sidebar-collapsed .menu-text,
        .sidebar-collapsed .user-name,
        .sidebar-collapsed .user-role,
        .sidebar-collapsed .menu-item-text {
            opacity: 0;
            width: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            transform: translateX(-10px);
        }
        /* Show text when expanded */
        .fixed.left-0:not(.sidebar-collapsed) .menu-text,
        .fixed.left-0:not(.sidebar-collapsed) .menu-item-text {
            opacity: 1;
            width: auto !important;
            margin-left: 0.75rem !important;
            transform: translateX(0);
        }
        /* Hide Menu heading when sidebar is collapsed */
        .sidebar-collapsed .menu-title {
            display: none !important;
        }
        /* Center icons when collapsed */
        .sidebar-collapsed .menu-icon {
            margin-right: 0;
            justify-content: center;
            width: 100%;
        }
        /* Adjust user profile section */
        .sidebar-collapsed .user-avatar {
            margin: 0 auto;
        }
        .sidebar-collapsed .user-info {
            display: none;
        }
        /* Adjust menu items */
        .sidebar-collapsed .nav-item {
            justify-content: center;
            padding: 0.75rem 0;
            position: relative;
        }
        /* Tooltip styles for collapsed sidebar */
        .nav-item {
            position: relative;
        }
        .nav-item::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #1f2937;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 50;
            margin-left: 1rem;
            pointer-events: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .nav-item::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: transparent transparent transparent #1f2937;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 51;
            margin-left: -6px;
            pointer-events: none;
        }
        .sidebar-collapsed .nav-item:hover::after,
        .sidebar-collapsed .nav-item:hover::before {
            opacity: 1;
            visibility: visible;
        }
        /* Add a subtle shadow to the sidebar */
        .fixed.left-0 {
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include Unified Sidebar -->
    <?php include '../includes/unified_sidebar.php'; ?>

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

    <!-- Main Content -->
    <div class="flex flex-col min-h-screen transition-all duration-300 ease-in-out bg-gray-50" id="mainContent">
        <main class="flex-1 w-full max-w-7xl mx-auto p-4 md:p-6 lg:p-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">School Days Management</h1>
                <div class="flex items-center space-x-4 mt-4 sm:mt-0">
                    <div>
                        <label for="year" class="text-sm font-medium text-gray-700 mr-2">School Year:</label>
                        <select id="year" class="border rounded px-3 py-1.5 text-sm" onchange="window.location.href='?school_year=' + this.value">
                            <?php foreach ($year_options as $year): ?>
                                <option value="<?= htmlspecialchars($year) ?>" <?= $year === $school_year ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button onclick="openEditModal()" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-edit mr-2"></i> Edit School Days
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <?= $message ?>
            <?php endif; ?>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School Days</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $total_days = 0;
                        foreach ($months as $month): 
                            $days = $school_days[$month] ?? 0;
                            $total_days += (int)$days;
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($month) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $days ?> days</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Total</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $total_days ?> days</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Edit Modal -->
<div id="editModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
            <form id="editForm" method="POST" action="">
                <input type="hidden" name="school_year" value="<?= htmlspecialchars($school_year) ?>">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-edit text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                                Edit School Days for <?= htmlspecialchars($school_year) ?>
                            </h3>
                            <div class="mt-4">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School Days</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($months as $month): 
                                                $days = $school_days[$month] ?? 0;
                                            ?>
                                                <tr>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($month) ?></td>
                                                    <td class="px-4 py-3">
                                                        <input type="number" 
                                                               name="days[<?= $month ?>]" 
                                                               value="<?= (int)$days ?>" 
                                                               min="0" 
                                                               max="31"
                                                               class="w-24 px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="update_all_days" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Add these new functions
    function openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // [Keep existing sidebar toggle and tooltip code]
</script>

    <script>
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.fixed.left-0');
            const toggleBtn = document.getElementById('toggleSidebar');
            const mainContent = document.getElementById('mainContent');
            
            // Check if sidebar state is saved in localStorage
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.remove('pl-64');
                mainContent.classList.add('pl-16');
            }
            
            // Toggle sidebar
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-collapsed');
                const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
                
                // Toggle main content padding
                if (isCollapsed) {
                    mainContent.classList.remove('pl-64');
                    mainContent.classList.add('pl-16');
                } else {
                    mainContent.classList.remove('pl-16');
                    mainContent.classList.add('pl-64');
                }
                
                // Update tooltips
                initSidebarTooltips();
            });
            
            // Logout button handler
            const logoutBtn = document.getElementById('logoutBtn');
            const logoutModal = document.getElementById('logoutModal');
            const cancelLogout = document.getElementById('cancelLogout');
            
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.classList.remove('hidden');
            });
            
            cancelLogout.addEventListener('click', function() {
                logoutModal.classList.add('hidden');
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    logoutModal.classList.add('hidden');
                }
            });
            
            // Initialize tooltips
            initSidebarTooltips();
        });
        
        // Initialize sidebar tooltips
        function initSidebarTooltips() {
            const navItems = document.querySelectorAll('.nav-item');
            const isCollapsed = document.querySelector('.fixed.left-0').classList.contains('sidebar-collapsed');
            
            navItems.forEach(item => {
                if (isCollapsed) {
                    const text = item.querySelector('.menu-text').textContent;
                    item.setAttribute('data-tooltip', text);
                } else {
                    item.removeAttribute('data-tooltip');
                }
            });
        }
        
        function editDays(index, month, days) {
            document.getElementById('editMonth').value = month;
            document.getElementById('editDays').value = days;
            document.getElementById('modalTitle').textContent = 'Edit School Days - ' + month;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editDays').focus();
        }
        
        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('logoutModal').classList.add('hidden');
            }
        });
    </script>
</body>
</html>
