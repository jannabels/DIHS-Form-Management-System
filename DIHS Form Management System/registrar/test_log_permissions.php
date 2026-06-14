<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Test file paths
$logFiles = [
    'import_debug.log',
    'import_errors.log',
    'test_write.log'
];

$results = [];

foreach ($logFiles as $logFile) {
    $fullPath = __DIR__ . '/' . $logFile;
    $testMessage = "Test message at " . date('Y-m-d H:i:s') . "\n";
    
    // Check if file exists
    $exists = file_exists($fullPath);
    $writable = is_writable($fullPath) || (!file_exists($fullPath) && is_writable(dirname($fullPath)));
    
    // Try to write to the file
    $writeResult = @file_put_contents($fullPath, $testMessage, FILE_APPEND);
    
    // Get file permissions in Windows-compatible way
    $filePerms = 'N/A';
    if (file_exists($fullPath)) {
        $perms = fileperms($fullPath);
        $filePerms = '';
        $filePerms .= (($perms & 0x0100) ? 'r' : '-');
        $filePerms .= (($perms & 0x0080) ? 'w' : '-');
        $filePerms .= (($perms & 0x0040) ? 'x' : '-');
    }
    
    $results[$logFile] = [
        'exists' => $exists ? 'Yes' : 'No',
        'writable' => $writable ? 'Yes' : 'No',
        'write_result' => $writeResult === false ? 'Failed' : 'Success',
        'file_perms' => $filePerms,
        'directory' => __DIR__,
        'directory_writable' => is_writable(__DIR__) ? 'Yes' : 'No'
    ];
}

// Also check PHP error log path
$phpErrorLog = ini_get('error_log');
$results['php_error_log'] = [
    'path' => $phpErrorLog,
    'exists' => file_exists($phpErrorLog) ? 'Yes' : 'No',
    'writable' => is_writable($phpErrorLog) ? 'Yes' : 'No'
];

// Output results as JSON
header('Content-Type: application/json');
echo json_encode([
    'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A',
    'php_version' => phpversion(),
    'current_user' => get_current_user(),
    'file_permissions' => $results,
    'directory' => __DIR__,
    'directory_writable' => is_writable(__DIR__) ? 'Yes' : 'No'
], JSON_PRETTY_PRINT);
?>
