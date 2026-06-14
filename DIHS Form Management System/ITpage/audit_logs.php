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

// Initialize AuditLog
$auditLog = new AuditLog($conn);

// Get current page and set items per page
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

// Filter to show only login and logout actions
$filters = [
    'action' => 'login,logout'  // Only show login and logout actions
];

// Get logs from database
try {
    $logsData = $auditLog->getLogs($filters, $currentPage, $perPage);
    $logs = $logsData['data'];
    $totalPages = $logsData['total_pages'];
    $totalItems = $logsData['total'];
} catch (Exception $e) {
    $error = "Error loading audit logs: " . $e->getMessage();
    $logs = [];
    $totalPages = 1;
    $totalItems = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs Dashboard</title>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        
        .log-row:hover {
            background-color: #f0fdf4;
        }
        
        .status-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-error {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .filter-active {
            background-color: #16a34a;
            color: white;
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
    </style>
    <style>@view-transition { navigation: auto; }</style>
    <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
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

    <div class="flex flex-col min-h-screen transition-all duration-300 ease-in-out bg-gray-50" id="mainContent">
        <main class="flex-1 w-full max-w-7xl mx-auto p-4 md:p-6 lg:p-8"><!-- Logs Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                    <p class="mt-1 text-sm text-gray-500">A list of all audit log entries in your system</p>
                </div>
                <?php if (empty($logs)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No audit logs found</h3>
                        <p class="mt-1 text-sm text-gray-500">No audit logs match your current filters.</p>
                        <div class="mt-6">
                            <a href="?" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                </svg>
                                Reset filters
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="px-4 py-2 bg-gray-50 text-xs text-gray-500">
                        Showing <?php echo (($currentPage - 1) * $perPage) + 1; ?> to <?php echo min($currentPage * $perPage, $totalItems); ?> of <?php echo $totalItems; ?> results
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 group" onclick="sortTable('created_at')">
                                        <div class="flex items-center">
                                            Timestamp
                                            <svg class="ml-1 w-4 h-4 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 group" onclick="sortTable('user_role')">
                                        <div class="flex items-center">
                                            Role
                                            <svg class="ml-1 w-4 h-4 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('action')">
                                        <div class="flex items-center">
                                            Action
                                            <svg class="ml-1 w-4 h-4 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('table_name')">
                                        <div class="flex items-center">
                                            Table
                                            <svg class="ml-1 w-4 h-4 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('ip_address')">
                                        <div class="flex items-center">
                                            IP Address
                                            <svg class="ml-1 w-4 h-4 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($logs as $log): 
                                    $userName = '';
                                    if (!empty($log['first_name']) || !empty($log['last_name'])) {
                                        $userName = trim($log['first_name'] . ' ' . $log['last_name']);
                                    }
                                    if (empty($userName)) {
                                        $userName = $log['user_id'];
                                    }
                                    
                                    $actionClass = 'bg-gray-100 text-gray-800';
                                    $actionText = ucfirst($log['action']);
                                    
                                    switch (strtolower($log['action'])) {
                                        case 'create':
                                            $actionClass = 'bg-green-100 text-green-800';
                                            break;
                                        case 'update':
                                            $actionClass = 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'delete':
                                            $actionClass = 'bg-red-100 text-red-800';
                                            break;
                                        case 'login':
                                            $actionClass = 'bg-purple-100 text-purple-800';
                                            $actionText = 'Login';
                                            break;
                                        case 'logout':
                                            $actionClass = 'bg-yellow-100 text-yellow-800';
                                            $actionText = 'Logout';
                                            break;
                                    }
                                ?>
                                <tr class="log-row hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                            $role = '';
                                            $userRole = isset($log['user_role']) ? strtolower($log['user_role']) : '';
                                            if (strpos($userRole, 'super admin') !== false) {
                                                $role = 'Super Admin';
                                            } elseif (strpos($userRole, 'guidance') !== false) {
                                                $role = 'Guidance';
                                            } elseif (strpos($userRole, 'registrar') !== false) {
                                                $role = 'Registrar';
                                            } elseif (strpos($userRole, 'oic') !== false) {
                                                $role = 'OIC';
                                            } elseif (strpos($userRole, 'adviser') !== false) {
                                                $role = 'Adviser';
                                            } else {
                                                $role = $log['user_id'];
                                            }
                                        ?>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($role); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($log['user_id']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $actionClass; ?>">
                                            <?php echo $actionText; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($log['table_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($currentPage > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php else: ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                                    Previous
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php else: ?>
                                <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                                    Next
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo (($currentPage - 1) * $perPage) + 1; ?></span>
                                    to <span class="font-medium"><?php echo min($currentPage * $perPage, $totalItems); ?></span>
                                    of <span class="font-medium"><?php echo $totalItems; ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if ($startPage > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo $i == $currentPage ? 'bg-green-50 text-green-600 border-green-500 z-10' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor;
                                    
                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                                    }
                                    ?>

                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Next</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
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
        // Current sort state
        let currentSort = {
            field: 'created_at',
            direction: 'desc'
        };
        
        // Function to handle table sorting
        function sortTable(field) {
            const url = new URL(window.location.href);
            
            // Toggle direction if clicking the same field
            if (currentSort.field === field) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.field = field;
                currentSort.direction = 'asc';
            }
            
            // Update URL parameters
            url.searchParams.set('sort', currentSort.field);
            url.searchParams.set('order', currentSort.direction);
            
            // Reload the page with new sort parameters
            window.location.href = url.toString();
        }
        
        // Initialize sort state from URL
        function initSort() {
            const urlParams = new URLSearchParams(window.location.search);
            const sortField = urlParams.get('sort');
            const sortOrder = urlParams.get('order');
            
            if (sortField) {
                currentSort.field = sortField;
                currentSort.direction = sortOrder === 'asc' ? 'asc' : 'desc';
                
                // Update UI to show current sort
                document.querySelectorAll('th').forEach(header => {
                    const field = header.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
                    if (field === currentSort.field) {
                        const icon = header.querySelector('svg');
                        if (icon) {
                            icon.classList.remove('opacity-0');
                            icon.style.transform = currentSort.direction === 'asc' ? 'rotate(0deg)' : 'rotate(180deg)';
                        }
                    }
                });
            }
        }
        
        // Call initSort when the page loads
        document.addEventListener('DOMContentLoaded', initSort);
        
        // Default configuration
        const defaultConfig = {
            page_title: "Audit Logs Dashboard",
            search_placeholder: "Search audit logs...",
            export_button_text: "Export Logs",
            background_color: "#f9fafb",
            surface_color: "#ffffff",
            text_color: "#111827",
            primary_action_color: "#16a34a",
            secondary_action_color: "#059669"
        };

        // Convert PHP logs to JavaScript format
        const logs = <?php echo json_encode($logs); ?>;
        
        // Add status based on action type for styling
        logs.forEach(log => {
            log.user_name = log.first_name && log.last_name ? 
                `${log.first_name} ${log.last_name}` : 
                log.user_id || 'System';
                
            // Set status based on action type for styling
            if (log.action === 'login' || log.action === 'create' || log.action === 'update') {
                log.status = 'success';
            } else if (log.action === 'delete' || log.action === 'failed_login') {
                log.status = 'error';
            } else {
                log.status = 'warning';
            }
        });
        
        // Filtered logs based on search and filters
        let filteredLogs = [...logs];
        
        // Store pagination info
        const totalPages = <?php echo $totalPages; ?>;
        const currentPage = <?php echo $currentPage; ?>;
        
        // Initialize the page
        function initializePage() {
            renderLogs();
            setupEventListeners();
            
            // Set initial filter
            document.querySelector('[data-filter="all"]').classList.add('filter-active');
        }

        // Render logs table
        function renderLogs() {
            const tbody = document.getElementById('logs-tbody');
            const logsToShow = filteredLogs.slice((currentPage - 1) * 10, currentPage * 10);
            
            tbody.innerHTML = logsToShow.map(log => `
                <tr class="log-row">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.created_at}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.user_id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.action}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.table_name}${log.record_id ? ' (ID: ' + log.record_id + ')' : ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full status-${log.status}">
                            ${log.status.charAt(0).toUpperCase() + log.status.slice(1)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.ip_address}</td>
                </tr>
            `).join('');
            
            updatePagination();
        }

        // Update pagination information
        function updatePagination() {
            const pagination = document.getElementById('pagination');
            if (!pagination) return;
            
            // Calculate pagination range
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
            
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            // Build pagination HTML
            let paginationHTML = `
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button onclick="changePage(${currentPage > 1 ? currentPage - 1 : 1})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" ${currentPage <= 1 ? 'disabled' : ''}>
                            Previous
                        </button>
                        <button onclick="changePage(${currentPage < totalPages ? currentPage + 1 : totalPages})" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" ${currentPage >= totalPages ? 'disabled' : ''}>
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">${(currentPage - 1) * 10 + 1}</span> to <span class="font-medium">${Math.min(currentPage * 10, filteredLogs.length)}</span> of <span class="font-medium">${filteredLogs.length}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button onclick="changePage(${currentPage > 1 ? currentPage - 1 : 1})" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" ${currentPage <= 1 ? 'disabled' : ''}>
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>`;
            
            // Add page numbers
            for (let i = startPage; i <= endPage; i++) {
                const isCurrent = i === currentPage;
                paginationHTML += `
                    <button onclick="changePage(${i})" class="${isCurrent ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        ${i}
                    </button>`;
            }
            
            paginationHTML += `
                                <button onclick="changePage(${currentPage < totalPages ? currentPage + 1 : totalPages})" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" ${currentPage >= totalPages ? 'disabled' : ''}>
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>`;
            
            pagination.innerHTML = paginationHTML;
        }
        
        // Function to change page
        function changePage(page) {
            if (page < 1 || page > totalPages || page === currentPage) return;
            
            // Get current URL and parameters
            const url = new URL(window.location.href);
            
            // Update page parameter
            url.searchParams.set('page', page);
            
            // Navigate to the new URL
            window.location.href = url.toString();
        }

        // Filter logs by status
        function filterLogs(status) {
            const filtered = logs.filter(log => log.status === status);
            filteredLogs = filtered;
            currentPage = 1;
            renderLogs();
            
            // Update filter button states
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('filter-active');
            });
            document.querySelector(`[data-filter="${status}"]`).classList.add('filter-active');
        }

        // Search logs
        function searchLogs(query) {
            const searchTerm = query.toLowerCase();
            
            if (searchTerm === '') {
                filteredLogs = [...logs];
            } else {
                filteredLogs = logs.filter(log => 
                    log.user_name.toLowerCase().includes(searchTerm) ||
                    log.action.toLowerCase().includes(searchTerm) ||
                    log.resource.toLowerCase().includes(searchTerm) ||
                    log.ip_address.includes(searchTerm)
                );
            }
            
            currentPage = 1;
            renderLogs();
        }

        // Setup event listeners
        function setupEventListeners() {
            // Filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    filterLogs(btn.dataset.filter);
                });
            });

            // Search input
            document.getElementById('search-input').addEventListener('input', (e) => {
                searchLogs(e.target.value);
            });

            // Refresh button
            document.getElementById('refresh-btn').addEventListener('click', () => {
                // Simulate refresh
                const btn = document.getElementById('refresh-btn');
                btn.disabled = true;
                btn.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Refreshing...';
                
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Refresh';
                    renderLogs();
                }, 1000);
            });

            // Export button
            document.getElementById('export-btn').addEventListener('click', () => {
                // Simulate export
                const btn = document.getElementById('export-btn');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span id="export-button-text">Exporting...</span>';
                
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    
                    // Show success message
                    const message = document.createElement('div');
                    message.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
                    message.textContent = 'Audit logs exported successfully!';
                    document.body.appendChild(message);
                    
                    setTimeout(() => {
                        message.remove();
                    }, 3000);
                }, 2000);
            });

            // Pagination buttons
            document.getElementById('prev-desktop').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderLogs();
                }
            });

            document.getElementById('next-desktop').addEventListener('click', () => {
                const maxPages = Math.ceil(filteredLogs.length / 10);
                if (currentPage < maxPages) {
                    currentPage++;
                    renderLogs();
                }
            });

            document.getElementById('prev-mobile').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderLogs();
                }
            });

            document.getElementById('next-mobile').addEventListener('click', () => {
                const maxPages = Math.ceil(filteredLogs.length / 10);
                if (currentPage < maxPages) {
                    currentPage++;
                    renderLogs();
                }
            });

            // Page number buttons
            document.querySelectorAll('.page-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentPage = parseInt(btn.dataset.page);
                    renderLogs();
                    
                    // Update active page button
                    document.querySelectorAll('.page-btn').forEach(b => {
                        b.classList.remove('bg-green-50', 'text-green-600');
                        b.classList.add('bg-white', 'text-gray-700');
                    });
                    btn.classList.remove('bg-white', 'text-gray-700');
                    btn.classList.add('bg-green-50', 'text-green-600');
                });
            });
        }

        // Element SDK configuration
        async function onConfigChange(config) {
            // Update page title
            const titleElement = document.getElementById('page-title');
            if (titleElement) {
                titleElement.textContent = config.page_title || defaultConfig.page_title;
            }

            // Update search placeholder
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.placeholder = config.search_placeholder || defaultConfig.search_placeholder;
            }

            // Update export button text
            const exportButtonText = document.getElementById('export-button-text');
            if (exportButtonText) {
                exportButtonText.textContent = config.export_button_text || defaultConfig.export_button_text;
            }

            // Update colors
            const backgroundColor = config.background_color || defaultConfig.background_color;
            const surfaceColor = config.surface_color || defaultConfig.surface_color;
            const textColor = config.text_color || defaultConfig.text_color;
            const primaryActionColor = config.primary_action_color || defaultConfig.primary_action_color;
            const secondaryActionColor = config.secondary_action_color || defaultConfig.secondary_action_color;

            document.body.style.backgroundColor = backgroundColor;
            
            // Update surface colors
            document.querySelectorAll('.bg-white').forEach(el => {
                el.style.backgroundColor = surfaceColor;
            });

            // Update text colors
            document.querySelectorAll('.text-gray-900').forEach(el => {
                el.style.color = textColor;
            });

            // Update primary action colors
            document.querySelectorAll('.bg-green-600').forEach(el => {
                el.style.backgroundColor = primaryActionColor;
            });

            // Update secondary action colors
            document.querySelectorAll('.text-green-700').forEach(el => {
                el.style.color = secondaryActionColor;
            });
        }

        // Initialize Element SDK
        if (window.elementSdk) {
            window.elementSdk.init({
                defaultConfig: defaultConfig,
                onConfigChange: onConfigChange,
                mapToCapabilities: (config) => ({
                    recolorables: [
                        {
                            get: () => config.background_color || defaultConfig.background_color,
                            set: (value) => {
                                config.background_color = value;
                                window.elementSdk.setConfig({ background_color: value });
                            }
                        },
                        {
                            get: () => config.surface_color || defaultConfig.surface_color,
                            set: (value) => {
                                config.surface_color = value;
                                window.elementSdk.setConfig({ surface_color: value });
                            }
                        },
                        {
                            get: () => config.text_color || defaultConfig.text_color,
                            set: (value) => {
                                config.text_color = value;
                                window.elementSdk.setConfig({ text_color: value });
                            }
                        },
                        {
                            get: () => config.primary_action_color || defaultConfig.primary_action_color,
                            set: (value) => {
                                config.primary_action_color = value;
                                window.elementSdk.setConfig({ primary_action_color: value });
                            }
                        },
                        {
                            get: () => config.secondary_action_color || defaultConfig.secondary_action_color,
                            set: (value) => {
                                config.secondary_action_color = value;
                                window.elementSdk.setConfig({ secondary_action_color: value });
                            }
                        }
                    ],
                    borderables: [],
                    fontEditable: undefined,
                    fontSizeable: undefined
                }),
                mapToEditPanelValues: (config) => new Map([
                    ["page_title", config.page_title || defaultConfig.page_title],
                    ["search_placeholder", config.search_placeholder || defaultConfig.search_placeholder],
                    ["export_button_text", config.export_button_text || defaultConfig.export_button_text]
                ])
            });
        }

        // Initialize the page when DOM is loaded
        document.addEventListener('DOMContentLoaded', initializePage);
    </script>
    <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'99f38702f1e69890',t:'MTc2MzI1OTgxNy4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script>
</body>
</html>