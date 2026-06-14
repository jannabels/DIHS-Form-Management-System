<?php
require_once __DIR__ . '/../db_connect.php';

class TestHelper {
    private static $testDbConfig;
    private static $testConn;

    public static function init() {
        // Load test database configuration
        self::$testDbConfig = require __DIR__ . '/config/test-db.php';
        
        // Set up test database connection
        self::setupTestDatabase();
        
        // Set up test data
        self::seedTestData();
    }

    private static function setupTestDatabase() {
        // Create test database if not exists
        $conn = new mysqli(
            self::$testDbConfig['host'],
            self::$testDbConfig['username'],
            self::$testDbConfig['password']
        );

        if ($conn->connect_error) {
            die("Test database connection failed: " . $conn->connect_error);
        }

        // Create test database
        $conn->query("CREATE DATABASE IF NOT EXISTS `" . self::$testDbConfig['database'] . "`");
        $conn->select_db(self::$testDbConfig['database']);
        
        // Store test connection
        self::$testConn = $conn;
    }

    private static function seedTestData() {
        // Import database schema
        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
            self::$testConn->multi_query($schema);
            
            // Clear any remaining results
            while (self::$testConn->next_result()) {
                if ($result = self::$testConn->store_result()) {
                    $result->free();
                }
            }
        }

        // Add test data
        // Example: self::$testConn->query("INSERT INTO users (username, password) VALUES ('testuser', 'hashedpassword')");
    }

    public static function cleanup() {
        // Drop test database after tests complete
        if (self::$testConn) {
            self::$testConn->query("DROP DATABASE IF EXISTS `" . self::$testDbConfig['database'] . "`");
            self::$testConn->close();
        }
    }

    public static function getTestDbConfig() {
        return self::$testDbConfig;
    }

    public static function getTestConnection() {
        return self::$testConn;
    }
}

// Initialize test environment when this file is included
TestHelper::init();

// Register shutdown function to clean up after tests
register_shutdown_function(['TestHelper', 'cleanup']);
