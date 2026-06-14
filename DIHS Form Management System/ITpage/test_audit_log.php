<?php
require_once '../db_connect.php';
require_once '../includes/AuditLog.php';

// Initialize AuditLog
$auditLog = new AuditLog($conn);

// Test data
$testActions = [
    ['login', 'users', '123'],
    ['create', 'students', '456'],
    ['update', 'grades', '789'],
    ['delete', 'attendance', '101'],
    ['failed_login', 'users', '123']
];

// Add test log entries
foreach ($testActions as $action) {
    $userId = 'test_user' . rand(1, 10);
    $result = $auditLog->log(
        $userId,
        $action[0],  // action
        $action[1],  // table_name
        $action[2],  // record_id
        ['test' => 'old value'],  // old_values
        ['test' => 'new value']   // new_values
    );
    
    if ($result) {
        echo "Successfully logged action: {$action[0]} on {$action[1]} (ID: {$action[2]})<br>";
    } else {
        echo "Failed to log action: {$action[0]}<br>";
    }
}

echo "<br>Test complete. <a href='audit_logs.php'>View Audit Logs</a>";
?>
