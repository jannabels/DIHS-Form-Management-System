<!-- Logout Confirmation Modal -->
<div id="logoutModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
    <div class="bg-white rounded-lg shadow-xl w-96">
        <div class="p-6">
            <div class="flex items-center justify-center mb-4">
                <div class="rounded-full bg-red-100 p-3">
                    <i class="fas fa-sign-out-alt text-red-600 text-2xl"></i>
                </div>
            </div>
            <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Confirm Logout</h3>
            <p class="text-sm text-gray-500 text-center mb-6">Are you sure you want to log out of your account?</p>
            <div class="flex justify-center space-x-4">
                <button id="cancelLogout" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Cancel
                </button>
                <a href="/systemdihs/logout.php" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogout = document.getElementById('cancelLogout');
    
    if (!logoutBtn || !logoutModal) return;
    
    // Show modal when logout button is clicked
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        logoutModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
    
    // Hide modal when cancel is clicked
    if (cancelLogout) {
        cancelLogout.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoutModal.style.display = 'none';
            document.body.style.overflow = '';
        });
    }
    
    // Hide modal when clicking outside
    logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            logoutModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
    
    // Hide modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal.style.display === 'flex') {
            logoutModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
});
</script>
