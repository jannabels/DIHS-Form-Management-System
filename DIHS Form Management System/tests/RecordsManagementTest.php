<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test class for Records Management Module
 */
class RecordsManagementTest extends TestCase
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
        
        // Create test tables
        self::createTestTables();
    }

    /**
     * Create test database tables
     */
    private static function createTestTables(): void
    {
        $sql = [
            "CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lrn VARCHAR(12) NOT NULL UNIQUE,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                grade_level INT NOT NULL,
                section VARCHAR(10) NOT NULL,
                school_year VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS student_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                record_type ENUM('sf1', 'sf9', 'sf10') NOT NULL,
                school_year VARCHAR(20) NOT NULL,
                data JSON NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];
        
        foreach ($sql as $query) {
            self::$pdo->exec($query);
        }
    }

    /**
     * Clean up the database after each test
     */
    protected function tearDown(): void
    {
        // Truncate test tables
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE student_records');
        self::$pdo->exec('TRUNCATE TABLE students');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Test student record creation
     */
    public function testStudentRecordCreation(): void
    {
        // Insert test student
        $stmt = self::$pdo->prepare("
            INSERT INTO students (lrn, first_name, last_name, grade_level, section, school_year)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $lrn = '123456789012';
        $firstName = 'Juan';
        $lastName = 'Dela Cruz';
        $gradeLevel = 10;
        $section = 'A';
        $schoolYear = '2023-2024';
        
        $result = $stmt->execute([$lrn, $firstName, $lastName, $gradeLevel, $section, $schoolYear]);
        $studentId = self::$pdo->lastInsertId();
        
        $this->assertTrue($result, 'Failed to insert student record');
        $this->assertGreaterThan(0, $studentId, 'Invalid student ID');
        
        // Verify the student was inserted
        $stmt = self::$pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($lrn, $student['lrn'], 'LRN does not match');
        $this->assertEquals($firstName, $student['first_name'], 'First name does not match');
        $this->assertEquals($lastName, $student['last_name'], 'Last name does not match');
    }

    /**
     * Test SF1 form data management
     */
    public function testSF1FormManagement(): void
    {
        // Insert test student
        $stmt = self::$pdo->prepare("
            INSERT INTO students (lrn, first_name, last_name, grade_level, section, school_year)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $lrn = '123456789012';
        $stmt->execute([$lrn, 'Maria', 'Clara', 10, 'A', '2023-2024']);
        $studentId = self::$pdo->lastInsertId();
        
        // Test SF1 form data
        $sf1Data = [
            'lrn' => $lrn,
            'school_year' => '2023-2024',
            'grade_level' => 10,
            'section' => 'A',
            'age' => 16,
            'birthdate' => '2007-01-01',
            'gender' => 'F',
            'address' => 'Sample Address',
            'parent_guardian' => 'Parent Name',
            'contact_number' => '09123456789'
        ];
        
        // Insert SF1 record
        $stmt = self::$pdo->prepare("
            INSERT INTO student_records (student_id, record_type, school_year, data)
            VALUES (?, 'sf1', ?, ?)
        ");
        $result = $stmt->execute([
            $studentId,
            $sf1Data['school_year'],
            json_encode($sf1Data)
        ]);
        
        $this->assertTrue($result, 'Failed to insert SF1 record');
        
        // Verify SF1 record
        $stmt = self::$pdo->prepare("
            SELECT data FROM student_records 
            WHERE student_id = ? AND record_type = 'sf1' AND school_year = ?
        ");
        $stmt->execute([$studentId, $sf1Data['school_year']]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($record, 'SF1 record not found');
        $retrievedData = json_decode($record['data'], true);
        $this->assertEquals($sf1Data['lrn'], $retrievedData['lrn'], 'LRN in SF1 does not match');
        $this->assertEquals($sf1Data['grade_level'], $retrievedData['grade_level'], 'Grade level does not match');
    }

    /**
     * Test student search functionality
     */
    public function testStudentSearch(): void
    {
        // Insert test students
        $students = [
            ['123456789012', 'Juan', 'Dela Cruz', 10, 'A', '2023-2024'],
            ['123456789013', 'Maria', 'Clara', 10, 'A', '2023-2024'],
            ['123456789014', 'Pedro', 'Penduko', 11, 'B', '2023-2024']
        ];
        
        $stmt = self::$pdo->prepare("
            INSERT INTO students (lrn, first_name, last_name, grade_level, section, school_year)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($students as $student) {
            $stmt->execute($student);
        }
        
        // Test search by LRN
        $searchTerm = '123456789012';
        $stmt = self::$pdo->prepare("
            SELECT * FROM students 
            WHERE lrn LIKE ? 
               OR first_name LIKE ? 
               OR last_name LIKE ?
        ");
        $searchPattern = "%$searchTerm%";
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $results, 'Should find exactly one student by LRN');
        $this->assertEquals($searchTerm, $results[0]['lrn'], 'Found wrong student by LRN');
        
        // Test search by name
        $searchTerm = 'Pedro';
        $searchPattern = "%$searchTerm%";
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $results, 'Should find exactly one student by name');
        $this->assertEquals($searchTerm, $results[0]['first_name'], 'Found wrong student by name');
    }
}
