<?php
// Redirect to the new login page with absolute URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$redirectUrl = $protocol . $host . $basePath . '/login/';

// Ensure no output before header
if (!headers_sent()) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirectUrl);
    exit();
} else {
    // Fallback using meta refresh if headers already sent
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl) . '"></head><body>Redirecting to <a href="' . htmlspecialchars($redirectUrl) . '">login page</a>...</body></html>';
    exit();
}
