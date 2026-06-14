<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['username']) && isset($_SESSION['role']);
$userRole = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';
?>
<nav class="fixed top-0 left-0 right-0 bg-white shadow-md z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="#" class="flex-shrink-0 flex items-center">
                    <img class="h-8 w-auto" src="../images/deped-logo.png" alt="DepEd Logo">
                    <span class="ml-2 text-xl font-semibold text-gray-800">DIHS</span>
                </a>
            </div>
            <div class="flex items-center">
                <?php if ($isLoggedIn): ?>
                    <div class="ml-4 flex items-center md:ml-6">
                        <div class="relative ml-3">
                            <div class="flex items-center space-x-4">
                                <span class="text-gray-700"><?php echo htmlspecialchars($username); ?></span>
                                <div class="relative">
                                    <button type="button" class="flex items-center max-w-xs rounded-full bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                        <span class="sr-only">Open user menu</span>
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700">
                                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                                        </div>
                                    </button>
                                    <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-0">Your Profile</a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-1">Settings</a>
                                        <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-2">Sign out</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../login/index.php" class="ml-4 px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-500">Sign in</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Add padding to the top of the body to account for the fixed navbar -->
<div style="height: 4rem;"></div>

<script>
// Toggle dropdown menu
document.addEventListener('DOMContentLoaded', function() {
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.querySelector('[role="menu"]');
    
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', function() {
            const isExpanded = userMenuButton.getAttribute('aria-expanded') === 'true';
            userMenuButton.setAttribute('aria-expanded', !isExpanded);
            userMenu.classList.toggle('hidden');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenuButton.setAttribute('aria-expanded', 'false');
                userMenu.classList.add('hidden');
            }
        });
    }
});
</script>
