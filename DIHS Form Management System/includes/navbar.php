<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug output - remove after testing
require_once __DIR__ . '/../db_connect.php';
$db_role = '';
if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT role FROM accounts WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $db_role = $row['role'];
    }
    $stmt->close();
}

// Debug output removed

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: /systemdihs/login/index.php");
    exit();
}

// Normalize the role for consistent comparison
$current_role = strtolower(str_replace(' ', '_', trim($_SESSION['role'])));
$username = htmlspecialchars($_SESSION['username']);

// For backward compatibility with existing role checks
$role_key = strtolower(str_replace(' ', '_', trim($_SESSION['role'])));
?>
<nav class="text-white shadow-md fixed top-0 left-0 right-0 w-full z-50" style="background-color: #16A34A;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Left side - User indicator and Logo -->
            <div class="flex items-center space-x-4">
                <!-- User indicator -->
                <div class="flex items-center text-white">
                    <div class="h-8 w-8 rounded-full bg-[#15803D] flex items-center justify-center text-white font-semibold">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <span class="ml-2 text-sm hidden md:inline">
                        <?php echo $username; ?>
                    </span>
                </div>
                
                <!-- Logo/Brand - Text removed -->
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-1 ml-6">
                <?php if (in_array($current_role, ['oic', 'principal'])): ?>
                    <!-- OIC/Principal Menu -->
                    <a href="/systemdihs/oic/dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="/systemdihs/oic/school_days_new.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'school_days_new.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="far fa-calendar-alt mr-1"></i> School Days
                    </a>
                <?php endif; ?>

                <?php if (in_array($current_role, ['super_admin', 'it'])): ?>
                    <!-- Super Admin/IT Menu -->
                    <a href="/systemdihs/ITpage/audit_logs.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'audit_logs.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-clipboard-list mr-1"></i> Audit Logs
                    </a>
                    <a href="/systemdihs/ITpage/indexit.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'indexit.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-tools mr-1"></i> Indexit
                    </a>
                <?php endif; ?>

                <?php if ($current_role === 'registrar'): ?>
                    <!-- Registrar Menu - Only SF10 -->
                    <a href="/systemdihs/registrar/sf10.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'sf10.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-file-export mr-1"></i> SF10
                    </a>
                <?php endif; ?>

                <?php if ($current_role === 'guidance'): ?>
                    <!-- Guidance Menu -->
                    <a href="/systemdihs/guidance/guidance_sf1.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'sf1.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-file-alt mr-1"></i> SF1
                    </a>
                    <a href="/systemdihs/guidance/createclass.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'createclass.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-plus-circle mr-1"></i> Create Class
                    </a>
                    <a href="/systemdihs/guidance/class_sections.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'class_sections.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-list-ul mr-1"></i> View Class
                    </a>
                <?php endif; ?>

                <?php if ($current_role === 'adviser'): ?>
                    <!-- Adviser Menu -->
                    <a href="/systemdihs/adviser/adviser_sf1.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'adviser_sf1.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-file-alt mr-1"></i> SF1
                    </a>
                    <a href="/systemdihs/adviser/adviser_sf2.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'adviser_sf2.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-file-invoice mr-1"></i> SF2
                    </a>
                    <a href="/systemdihs/adviser/adviser_sf9.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-[#15803D] <?= basename($_SERVER['PHP_SELF']) === 'adviser_sf9.php' ? 'bg-[#15803D]' : '' ?>">
                        <i class="fas fa-file-pdf mr-1"></i> SF9
                    </a>
                <?php endif; ?>
            </div>

            <!-- Right side - Sign Out Button -->
            <div class="flex items-center">
                <!-- Sign Out button -->
                <button type="button" class="p-2 rounded-full text-white hover:bg-[#047857] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#16A34A] focus:ring-white" title="Sign out" onclick="showLogoutConfirmation()">
                    <span class="sr-only">Sign out</span>
                    <i class="h-5 w-5 fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center p-4">
    <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-sign-out-alt text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-3">Confirm Log Out</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Are you sure you want to Log out?</p>
            </div>
            <div class="flex items-center justify-between px-4 py-3 space-x-3">
                <button id="cancelLogout" class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
                <a href="/systemdihs/logout.php" class="flex-1 px-4 py-2 text-center bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Yes, Log Out
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const logoutModal = document.getElementById('logoutModal');
    const logoutBtn = document.querySelector('[onclick="showLogoutConfirmation()"]');
    const cancelLogout = document.getElementById('cancelLogout');

    // Show modal function
    function showLogoutConfirmation(e) {
        if (e) e.preventDefault();
        logoutModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Hide modal function
    function hideLogoutConfirmation() {
        logoutModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Add event listeners
    if (logoutBtn) {
        logoutBtn.addEventListener('click', showLogoutConfirmation);
    }
    
    if (cancelLogout) {
        cancelLogout.addEventListener('click', hideLogoutConfirmation);
    }

    // Close modal when clicking outside of it
    logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            hideLogoutConfirmation();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !logoutModal.classList.contains('hidden')) {
            hideLogoutConfirmation();
        }
    });
});
</script>

<!-- Add some padding to the top of the page to account for the fixed navbar -->
<div class="h-16"></div>

<script>
// User dropdown functionality (kept for desktop)
document.getElementById('user-menu')?.addEventListener('click', function() {
    const dropdown = document.getElementById('user-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('user-dropdown');
    const userMenu = document.getElementById('user-menu');
    
    if (userMenu && dropdown && !userMenu.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Handle logout with confirmation
function confirmLogout(event) {
    event.preventDefault();
    const logoutUrl = event.currentTarget.getAttribute('href');
    
    Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to sign out of the system.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#dc2626',
        confirmButtonText: 'Yes, sign out',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = logoutUrl;
        }
    });
}

// Add event listeners to all logout links
document.addEventListener('DOMContentLoaded', function() {
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', confirmLogout);
    });
});
</script>
