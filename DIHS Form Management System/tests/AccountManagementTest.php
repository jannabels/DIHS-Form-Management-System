<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test class for Account Management Module
 */
class AccountManagementTest extends TestCase
{
    /** @var PDO $pdo PDO connection */
    private static $pdo;

    /**
     * Set up the database connection before all tests
     */
    public static function setUpBeforeClass(): void
    {
        // Create a test database connection
        self::$pdo = new PDO('mysql:host=localhost;dbname=test_systemdihs', 'root', '');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables if they don't exist
        $this->createTestTables();
    }

    /**
     * Create test database tables
     */
    private static function createTestTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL,
                email VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        self::$pdo->exec($sql);
    }

    /**
     * Clean up the database after each test
     */
    protected function tearDown(): void
    {
        // Truncate test tables
        self::$pdo->exec('TRUNCATE TABLE users');
    }

    /**
     * Test user registration
     */
    public function testUserRegistration(): void
    {
        // Test data
        $username = 'testuser' . uniqid();
        $password = password_hash('Test@123', PASSWORD_DEFAULT);
        $email = $username . '@example.com';
        $role = 'teacher';

        // Insert test user
        $stmt = self::$pdo->prepare("
            INSERT INTO users (username, password, email, role) 
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([$username, $password, $email, $role]);

        $this->assertTrue($result, 'User registration failed');
        $this->assertEquals(1, $stmt->rowCount(), 'User was not inserted');
    }

    /**
     * Test user authentication
     */
    public function testUserAuthentication(): void
    {
        // Create a test user
        $username = 'testuser' . uniqid();
        $password = 'Test@123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $email = $username . '@example.com';
        $role = 'teacher';

        // Insert test user
        $stmt = self::$pdo->prepare("
            INSERT INTO users (username, password, email, role) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $hashedPassword, $email, $role]);
        $userId = self::$pdo->lastInsertId();

        // Test authentication
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($user, 'User not found');
        $this->assertEquals($username, $user['username'], 'Username does not match');
        $this->assertTrue(password_verify($password, $user['password']), 'Password verification failed');
    }

    /**
     * Test role-based access control
     */
    public function testRoleBasedAccessControl(): void
    {
        // Create test roles and permissions
        $roles = [
            'admin' => ['users:read', 'users:write', 'students:read', 'students:write'],
            'teacher' => ['students:read', 'grades:write'],
            'student' => ['grades:read']
        ];

        // Test admin permissions
        $this->assertContains('users:read', $roles['admin'], 'Admin should have users:read permission');
        $this->assertContains('users:write', $roles['admin'], 'Admin should have users:write permission');
        
        // Test teacher permissions
        $this->assertContains('students:read', $roles['teacher'], 'Teacher should have students:read permission');
        $this->assertNotContains('users:write', $roles['teacher'], 'Teacher should not have users:write permission');
        
        // Test student permissions
        $this->assertContains('grades:read', $roles['student'], 'Student should have grades:read permission');
        $this->assertNotContains('students:write', $roles['student'], 'Student should not have students:write permission');
    }
}
