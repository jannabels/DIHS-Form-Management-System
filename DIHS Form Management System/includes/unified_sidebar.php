<?php
/**
 * Unified Sidebar Component
 * Fetches menu items based on user role from the database
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection - using relative path
require_once __DIR__ . '/../db_connect.php';

// Get user role from session
$userRole = $_SESSION['role'] ?? 'Guest';
$username = $_SESSION['username'] ?? '';

// Define menu items structure based on roles
$menuItems = [
    // OIC/Principal
    'Dashboard' => [
        'icon' => 'fas fa-tachometer-alt',
        'url' => 'dashboard.php',
        'roles' => ['OIC', 'Principal', 'Admin', 'IT']
    ],
    'School Days' => [
        'icon' => 'fas fa-calendar-alt',
        'url' => 'school_days_new.php',
        'roles' => ['OIC', 'Principal', 'Admin', 'IT']
    ],
    
    // Super Admin/IT
    'Audit Logs' => [
        'icon' => 'fas fa-clipboard-list',
        'url' => 'audit_logs.php',
        'roles' => ['Super Admin', 'Admin', 'IT']
    ],
    'Accounts' => [
        'icon' => 'fas fa-home',
        'url' => 'indexit.php',
        'roles' => ['Super Admin', 'Admin', 'IT']
    ],
   
    // Registrar
    'View Classes' => [
        'icon' => 'fas fa-list',
        'url' => '../registrar/class_sections.php',
        'roles' => ['Registrar']
    ],
    'SF1 - School Register' => [
        'icon' => 'fas fa-clipboard-check',
        'url' => '../registrar/guidance_sf1.php',
        'roles' => ['Registrar']
    ],
    'SF10 - Student Records' => [
        'icon' => 'fas fa-user-graduate',
        'url' => '../registrar/sf10.php',
        'roles' => ['Registrar']
    ],
    'Archived Students' => [
        'icon' => 'fas fa-archive',
        'url' => '../registrar/archived_students.php',
        'roles' => ['Registrar']
    ],
    
    // Guidance - SF1 School Register
    'SF1 - School Register (Guidance)' => [
        'icon' => 'fas fa-clipboard-check',
        'url' => '../guidance/guidance_sf1.php',
        'roles' => ['Guidance', 'Admin', 'IT']
    ],
    'View Class' => [
        'icon' => 'fas fa-users',
        'url' => '../guidance/class_sections.php',
        'roles' => ['Guidance', 'Admin', 'IT']
    ],
    'SF1 Statistics' => [
        'icon' => 'fas fa-chart-bar',
        'url' => '../guidance/sf1_statistics.php',
        'roles' => ['Guidance', 'Admin', 'IT']
    ],
    'Create Class' => [
        'icon' => 'fas fa-plus-circle',
        'url' => '../guidance/createclass.php',
        'roles' => ['Guidance', 'Admin', 'IT']
    ],
    'Archived Students' => [
        'icon' => 'fas fa-archive',
        'url' => '../adviser/archived_students.php',
        'roles' => ['Guidance', 'Admin', 'IT']
    ],
    
    // Adviser
    'SF1 - My Class' => [
        'icon' => 'fas fa-clipboard-list',
        'url' => 'adviser_sf1.php',
        'roles' => ['Adviser']
    ],
    'SF2 - Daily Attendance' => [
        'icon' => 'fas fa-calendar-day',
        'url' => 'adviser_sf2.php',
        'roles' => ['Adviser']
    ],
    'SF9 - Progress' => [
        'icon' => 'fas fa-chart-line',
        'url' => 'adviser_sf9.php',
        'roles' => ['Adviser']
    ]
];

// Function to check if menu item should be displayed for current user role
function shouldDisplayMenuItem($item, $userRole) {
    if (!isset($item['roles'])) {
        return true; // If no roles specified, show to all
    }
    
    // Normalize the user's role (trim and convert to lowercase)
    $userRole = strtolower(trim($userRole));
    
    // If the item has a roles array, check if the user's role is in it (case-insensitive)
    if (is_array($item['roles'])) {
        foreach ($item['roles'] as $role) {
            if (strtolower(trim($role)) === $userRole) {
                return true;
            }
        }
    }
    
    return false;
}

// Debug: Print session information for troubleshooting
echo '<!-- Debug: User Role: ' . htmlspecialchars($userRole) . ' -->';
$allRoles = [];
foreach ($menuItems as $item) {
    if (isset($item['roles'])) {
        $allRoles = array_merge($allRoles, (array)$item['roles']);
    }
}
echo '<!-- Debug: All Roles: ' . implode(', ', array_unique($allRoles)) . ' -->';

// Debug: Check if user has access to Archived Students
echo '<!-- Debug: Has access to Archived Students: ' . 
     (in_array($userRole, ['adviser', 'guidance', 'admin', 'it']) ? 'Yes' : 'No') . ' -->';

// Debug: Show all session variables for troubleshooting
echo '<!-- Debug: Session: ' . print_r($_SESSION, true) . ' -->';
?>

<!-- Sidebar -->
<div id="sidebar" class="fixed left-0 top-0 h-screen text-white shadow-lg z-10 transition-all duration-300 ease-in-out w-64 flex flex-col overflow-x-hidden" style="background-color: #047857; min-width: 16rem;">
<style>
    /* Ensure menu items are always visible */
    .nav-item, .nav-dropdown-toggle {
        display: flex !important;
        opacity: 1 !important;
        visibility: visible !important;
        height: auto !important;
        padding: 0.5rem 1rem !important;
        margin: 0.125rem 0.5rem !important;
        border-radius: 0.375rem !important;
        transition: all 0.2s ease-in-out !important;
    }
    
    /* Modal styles removed */
    .nav-item:hover, .nav-dropdown-toggle:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }
    .menu-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Ensure icons are properly aligned */
    .menu-icon {
        min-width: 1.5rem;
        text-align: center;
    }
</style>
    <!-- User Profile Section -->
    <div class="p-4 border-b" style="border-color: rgba(255, 255, 255, 0.1);">
        <div class="flex items-center space-x-3">
            <div class="relative">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-green-700 font-bold">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-green-700"></div>
            </div>
            <div class="user-info">
                <p class="font-medium text-sm"><?php echo htmlspecialchars($username); ?></p>
                <p class="text-xs text-green-200"><?php echo htmlspecialchars(ucfirst($userRole)); ?></p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-4" style="-webkit-overflow-scrolling: touch;">
        <div class="menu-header-container flex items-center justify-between px-4 mb-4">
            <h2 class="menu-title text-sm font-semibold text-green-200 uppercase tracking-wider">Menu</h2>
            <button id="toggleSidebar" class="text-green-200 hover:text-white focus:outline-none">
                <i class="fas fa-chevron-left menu-icon"></i>
                <i class="fas fa-chevron-right menu-icon hidden"></i>
            </button>
        </div>

        <nav class="space-y-1 px-2 w-full">
            <?php foreach ($menuItems as $title => $item): ?>
                <?php if (shouldDisplayMenuItem($item, $userRole)): ?>
                    <?php if (isset($item['submenu'])): ?>
                        <!-- Dropdown Menu -->
                        <div class="nav-dropdown">
                            <button class="nav-dropdown-toggle w-full flex items-center px-4 py-2 text-sm font-medium rounded-md text-green-100 hover:bg-green-800 focus:outline-none whitespace-nowrap overflow-hidden transition-colors duration-200">
                                <i class="<?php echo $item['icon']; ?> menu-icon w-6 text-center"></i>
                                <span class="menu-text ml-3"><?php echo $title; ?></span>
                                <i class="fas fa-chevron-down ml-auto text-xs"></i>
                            </button>
                            <div class="nav-dropdown-menu hidden pl-4 mt-1">
                                <?php foreach ($item['submenu'] as $subTitle => $subItem): ?>
                                    <?php if (shouldDisplayMenuItem($subItem, $userRole)): ?>
                                        <a href="<?php echo $subItem['url']; ?>" class="block px-4 py-2 text-sm text-green-100 hover:bg-green-800 rounded-md ml-6 whitespace-nowrap overflow-hidden text-ellipsis">
                                            <?php echo $subTitle; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Single Menu Item -->
                        <?php 
                            // Handle URL - can be a string or a function
                            $menuUrl = is_callable($item['url']) ? $item['url']($userRole) : $item['url'];
                        ?>
                        <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="nav-item flex items-center text-base font-medium text-white hover:bg-green-700 transition-colors duration-200">
                            <i class="<?php echo $item['icon']; ?> menu-icon w-6 text-center"></i>
                            <span class="menu-text ml-3"><?php echo $title; ?></span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Logout Button with Confirmation Modal -->
    <div class="p-4 border-t border-green-600">
        <button id="logoutBtn" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-green-100 hover:bg-green-800 rounded-md focus:outline-none transition-colors duration-200">
            <i class="fas fa-sign-out-alt"></i>
            <span class="menu-text ml-2">Logout</span>
        </button>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Logout Confirmation</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to log out?</p>
                <div class="flex justify-center space-x-4">
                    <button id="cancelLogout" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Cancel
                    </button>
                    <a href="/systemdihs/logout.php" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logout confirmation modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogout = document.getElementById('cancelLogout');

    if (logoutBtn && logoutModal && cancelLogout) {
        // Show modal when logout button is clicked
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        });

        // Hide modal when cancel button is clicked
        cancelLogout.addEventListener('click', function() {
            logoutModal.classList.add('hidden');
            document.body.style.overflow = ''; // Re-enable scrolling
        });

        // Hide modal when clicking outside the modal content
        logoutModal.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.classList.add('hidden');
                document.body.style.overflow = ''; // Re-enable scrolling
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !logoutModal.classList.contains('hidden')) {
                logoutModal.classList.add('hidden');
                document.body.style.overflow = ''; // Re-enable scrolling
            }
        });
    }
    // No modal functionality needed
    const sidebar = document.querySelector('.fixed.left-0');
    const toggleBtn = document.getElementById('toggleSidebar');
    const menuTexts = document.querySelectorAll('.menu-text');
    const menuIcons = document.querySelectorAll('.menu-icon');
    const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
    const mainContent = document.getElementById('mainContent');
    
    // Set initial sidebar and main content styles
    if (sidebar && mainContent) {
        sidebar.style.width = '16rem'; // w-64 equivalent
        mainContent.style.marginLeft = '16rem'; // ml-64 equivalent
        mainContent.style.transition = 'margin-left 0.3s ease-in-out';
    }
    
    // Toggle sidebar
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const isCollapsed = sidebar.classList.toggle('w-16');
            const mainContainer = document.getElementById('mainContainer');
            const menuTitle = document.querySelector('.menu-title');
            const dropdownArrows = document.querySelectorAll('.fa-chevron-down');
            
            // Toggle sidebar width and main content margin
            if (isCollapsed) {
                sidebar.style.width = '4rem'; // w-16 equivalent
                if (mainContent) {
                    mainContent.style.marginLeft = '4rem'; // ml-16 equivalent
                }
            } else {
                sidebar.style.width = '16rem'; // w-64 equivalent
                if (mainContent) {
                    mainContent.style.marginLeft = '16rem'; // ml-64 equivalent
                }
            }
            
            // Toggle menu text
            menuTexts.forEach(text => {
                text.classList.toggle('hidden', isCollapsed);
            });
            
            // Toggle menu title
            if (menuTitle) {
                menuTitle.classList.toggle('hidden', isCollapsed);
            }
            
            // Toggle chevron icons
            document.querySelectorAll('.fa-chevron-left, .fa-chevron-right').forEach(icon => {
                icon.classList.toggle('hidden');
            });
            
            // Toggle dropdown arrows
            dropdownArrows.forEach(arrow => {
                arrow.classList.toggle('hidden', isCollapsed);
            });
            
            // Close any open dropdown menus when toggling
            document.querySelectorAll('.nav-dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
            
            // Toggle icon centering
            const navItems = document.querySelectorAll('.nav-item, .nav-dropdown-toggle');
            navItems.forEach(item => {
                item.classList.toggle('justify-center', isCollapsed);
            });
            
            // Toggle main content padding
            if (mainContainer) {
                if (isCollapsed) {
                    mainContainer.style.paddingLeft = '1rem'; // pl-4 equivalent
                } else {
                    mainContainer.style.paddingLeft = '4rem'; // pl-16 equivalent
                }
            }
            
            // Don't save state to localStorage
        });
    }
    
    // Toggle dropdown menus
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            menu.classList.toggle('hidden');
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });
    
    // Always start with expanded sidebar
    const mainContainer = document.getElementById('mainContainer');
    const menuTitle = document.querySelector('.menu-title');
    
    // Ensure sidebar is expanded
    sidebar.classList.remove('w-16');
    
    // Show all menu texts
    menuTexts.forEach(text => text.classList.remove('hidden'));
    
    // Show menu title
    if (menuTitle) {
        menuTitle.classList.remove('hidden');
    }
    
    // Set chevron icons to left (collapsed state)
    document.querySelectorAll('.fa-chevron-left').forEach(icon => icon.classList.remove('hidden'));
    document.querySelectorAll('.fa-chevron-right').forEach(icon => icon.classList.add('hidden'));
    
    // Show dropdown arrows
    document.querySelectorAll('.fa-chevron-down').forEach(arrow => {
        arrow.classList.remove('hidden');
    });
    
    // Align items to start (left) when expanded
    const navItems = document.querySelectorAll('.nav-item, .nav-dropdown-toggle');
    navItems.forEach(item => {
        item.classList.remove('justify-center');
    });
    
    // Ensure main content has correct padding
    if (mainContainer) {
        mainContainer.classList.remove('pl-16');
        mainContainer.classList.add('pl-64');
    }
    
// Initialize sidebar functionality
const initSidebar = () => {
    // Existing sidebar toggle code here
};

// Initialize when DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
});
</script>
