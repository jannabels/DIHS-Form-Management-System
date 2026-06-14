<?php
/**
 * Database Performance Optimizations
 * This file contains functions to optimize database queries and performance
 */

/**
 * Optimize database tables
 * @param mysqli $conn Database connection
 * @return array Results of optimization
 */
function optimizeDatabaseTables($conn) {
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    $optimized = [];
    
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    foreach ($tables as $table) {
        $conn->query("OPTIMIZE TABLE `$table`");
        $optimized[] = "Optimized table: $table";
    }
    
    return $optimized;
}

/**
 * Add necessary indexes to improve query performance
 * @param mysqli $conn Database connection
 */
function addPerformanceIndexes($conn) {
    $indexes = [
        // School days table
        "ALTER TABLE `school_days` ADD INDEX `idx_school_year` (`school_year`)",
        
        // Student records
        "ALTER TABLE `student_records` ADD INDEX `idx_lrn` (`lrn`)",
        "ALTER TABLE `student_records` ADD INDEX `idx_section` (`section`)",
        "ALTER TABLE `student_records` ADD INDEX `idx_grade_level` (`grade_level`)",
        
        // Grades table
        "ALTER TABLE `grades` ADD INDEX `idx_lrn_subject` (`lrn`, `subject_code`)",
        "ALTER TABLE `grades` ADD INDEX `idx_section_subject` (`section`, `subject_code`)",
        
        // Attendance
        "ALTER TABLE `attendance` ADD INDEX `idx_lrn_date` (`lrn`, `date`)",
        "ALTER TABLE `attendance` ADD INDEX `idx_section_date` (`section`, `date`)",
    ];
    
    $results = [];
    
    foreach ($indexes as $sql) {
        try {
            $conn->query($sql);
            $results[] = "Added index: " . substr($sql, 0, 50) . "...";
        } catch (mysqli_sql_exception $e) {
            // Skip if index already exists
            if ($e->getCode() != 1061) { // 1061 is the error code for duplicate key
                $results[] = "Error: " . $e->getMessage();
            }
        }
    }
    
    return $results;
}

/**
 * Enable query caching
 * @param mysqli $conn Database connection
 * @param int $cacheSize Size in MB for query cache
 */
function enableQueryCache($conn, $cacheSize = 64) {
    $results = [];
    
    // Set query cache size
    $conn->query("SET GLOBAL query_cache_size = " . ($cacheSize * 1024 * 1024));
    $results[] = "Query cache size set to {$cacheSize}MB";
    
    // Enable query cache
    $conn->query("SET GLOBAL query_cache_type = 1");
    $results[] = "Query cache enabled";
    
    return $results;
}

/**
 * Check if APCu is available
 * @return bool True if APCu is available
 */
function is_apcu_available() {
    return function_exists('apcu_enabled') && apcu_enabled();
}

/**
 * Get value from APCu cache with fallback
 * @param string $key Cache key
 * @param mixed $default Default value if key doesn't exist
 * @return mixed Cached value or default
 */
function cache_get($key, $default = false) {
    if (is_apcu_available()) {
        $value = apcu_fetch($key, $success);
        return $success ? $value : $default;
    }
    return $default;
}

/**
 * Store value in APCu cache with fallback
 * @param string $key Cache key
 * @param mixed $value Value to store
 * @param int $ttl Time to live in seconds
 * @return bool True on success
 */
function cache_set($key, $value, $ttl = 3600) {
    if (is_apcu_available()) {
        return apcu_store($key, $value, $ttl);
    }
    return false;
}

/**
 * Optimize the school days query
 * @param mysqli $conn Database connection
 * @param string $schoolYear School year
 * @return array School days data
 */
function getOptimizedSchoolDays($conn, $schoolYear) {
    $cacheKey = 'school_days_' . md5($schoolYear);
    $cachedData = cache_get($cacheKey);
    
    if ($cachedData !== false) {
        return $cachedData;
    }
    
    $school_days = [];
    $query = "SELECT month_name, school_days 
              FROM school_days 
              WHERE school_year = ? 
              ORDER BY FIELD(month_name, 'June', 'July', 'August', 'September', 'October', 
                           'November', 'December', 'January', 'February', 'March', 'April', 'May')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $schoolYear);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $school_days[$row['month_name']] = $row['school_days'];
    }
    
    // Cache for 1 hour
    cache_set($cacheKey, $school_days, 3600);
    
    return $school_days;
}
?>
