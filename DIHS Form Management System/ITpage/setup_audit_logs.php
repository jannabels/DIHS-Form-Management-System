<?php
require_once '../db_connect.php';

// Check if user is admin/IT
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'IT' && $_SESSION['role'] !== 'Super Admin')) {
    die('Access denied. Only IT administrators can perform this action.');
}

// Read the SQL file
$sql = file_get_contents('create_audit_logs_table.sql');

// Execute multi query
if ($conn->multi_query($sql)) {
    echo "Audit logs table created successfully. <a href='audit_logs.php'>Go to Audit Logs</a>";
    
    // Log this action
    $auditLog = new AuditLog($conn);
    $auditLog->log(
        $_SESSION['username'],
        'setup',
        'audit_logs',
        null,
        null,
        ['setup' => 'Audit logs table created or updated']
    );
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
