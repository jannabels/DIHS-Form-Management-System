<?php
header('Content-Type: text/plain');

// Simple metrics collection
$startTime = microtime(true);

// Database metrics
try {
    $db = new PDO('mysql:host=localhost;dbname=admindihs', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Count users
    $stmt = $db->query('SELECT COUNT(*) as count FROM accounts');
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count students
    $stmt = $db->query('SELECT COUNT(*) as count FROM student_grades');
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count active sessions (example)
    $sessionCount = rand(1, 100); // Replace with actual session count
    
    // Output metrics in Prometheus format
    echo "# HELP system_users_total Total number of system users\n";
    echo "# TYPE system_users_total gauge\n";
    echo "system_users_total $userCount\n\n";
    
    echo "# HELP system_students_total Total number of students\n";
    echo "# TYPE system_students_total gauge\n";
    echo "system_students_total $studentCount\n\n";
    
    echo "# HELP system_active_sessions Number of active sessions\n";
    echo "# TYPE system_active_sessions gauge\n";
    echo "system_active_sessions $sessionCount\n\n";
    
} catch (PDOException $e) {
    // Log error but don't expose details
    error_log('Metrics error: ' . $e->getMessage());
}

// System metrics
$load = sys_getloadavg();
$memory = memory_get_usage(true);

// Output system metrics
echo "# HELP php_memory_usage_bytes Memory usage in bytes\n";
echo "# TYPE php_memory_usage_bytes gauge\n";
echo "php_memory_usage_bytes $memory\n\n";

echo "# HELP php_request_duration_seconds Request duration in seconds\n";
echo "# TYPE php_request_duration_seconds gauge\n";
$duration = microtime(true) - $startTime;
echo "php_request_duration_seconds $duration\n";
?>
