<?php
// Test logging functionality
$logFile = __DIR__ . '/import_debug.log';
$testMessage = "Test log entry at " . date('Y-m-d H:i:s') . "\n";

// Try to write to the log file
if (file_put_contents($logFile, $testMessage, FILE_APPEND) === false) {
    echo "Failed to write to log file. Check file permissions.\n";
    
    // Check if directory is writable
    $dir = dirname($logFile);
    if (!is_writable($dir)) {
        echo "Directory $dir is not writable.\n";
        echo "Current permissions: " . decoct(fileperms($dir) & 0777) . "\n";
    } else {
        echo "Directory is writable, but file creation failed.\n";
    }
    
    // Try creating the file with explicit permissions
    if (touch($logFile) && chmod($logFile, 0666)) {
        echo "Successfully created log file with 666 permissions.\n";
        if (file_put_contents($logFile, $testMessage, FILE_APPEND) !== false) {
            echo "Successfully wrote to log file after creation.\n";
        } else {
            echo "Still cannot write to log file after creation.\n";
        }
    } else {
        echo "Failed to create log file.\n";
    }
} else {
    echo "Successfully wrote to log file.\n";
    echo "Log file location: $logFile\n";
    echo "Current permissions: " . decoct(fileperms($logFile) & 0777) . "\n";
}

// Show current PHP user and group
echo "PHP running as user: " . get_current_user() . "\n";
if (function_exists('posix_getpwuid')) {
    $processUser = posix_getpwuid(posix_geteuid());
    echo "Process running as user: " . $processUser['name'] . "\n";
}

// Show directory listing
$files = scandir(__DIR__);
echo "\nDirectory contents (first 10 files):\n";
$count = 0;
foreach ($files as $file) {
    if ($count++ >= 10) break;
    $path = __DIR__ . '/' . $file;
    $perms = fileperms($path);
    $perms = decoct($perms & 0777);
    echo sprintf("%-30s %s\n", $file, $perms);
}
?>
