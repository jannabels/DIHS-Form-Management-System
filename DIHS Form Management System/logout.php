<?php
// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';
require_once 'includes/AuditLog.php';

// Log the logout action if user was logged in
if (isset($_SESSION['username'])) {
    $auditLog = new AuditLog($conn);
    $auditLog->log($_SESSION['username'], 'logout', 'accounts', $_SESSION['user_id'] ?? null);
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Redirect to login page with no-cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Location: login/index.php");

// Add JavaScript to prevent back button after redirect
echo '<script type="text/javascript">
    if (window.history && window.history.pushState) {
        window.history.pushState(null, null, "login/index.php");
        window.onpopstate = function() {
            window.history.pushState(null, null, "login/index.php");
        };
    }
</script>';

exit();
?>