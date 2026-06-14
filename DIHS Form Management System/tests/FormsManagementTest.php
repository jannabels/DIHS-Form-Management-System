<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Test class for Forms Management Module
 */
class FormsManagementTest extends TestCase
{
    /** @var string Test output directory */
    private const TEST_OUTPUT_DIR = __DIR__ . '/../generated-forms/test';
    
    /** @var PDO $pdo PDO connection */
    private static $pdo;

    /**
     * Set up before all tests
     */
    public static function setUpBeforeClass(): void
    {
        // Create test database connection
        self::$pdo = new PDO('mysql:host=localhost;dbname=test_systemdihs', 'root', '');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test output directory
        if (!file_exists(self::TEST_OUTPUT_DIR)) {
            mkdir(self::TEST_OUTPUT_DIR, 0777, true);
        }
        
        // Create test tables
        self::createTestTables();
        
        // Create test template directory
        $templateDir = __DIR__ . '/../templates';
        if (!file_exists($templateDir)) {
            mkdir($templateDir, 0777, true);
        }
        
        // Create sample template files
        self::createSampleTemplates($templateDir);
    }

    /**
     * Create test database tables
     */
    private static function createTestTables(): void
    {
        $sql = [
            "CREATE TABLE IF NOT EXISTS form_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                form_type VARCHAR(10) NOT NULL,
                template_path VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_form_type (form_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS generated_forms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                form_type VARCHAR(10) NOT NULL,
                student_lrn VARCHAR(12) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                school_year VARCHAR(20) NOT NULL,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                generated_by INT,
                FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];
        
        foreach ($sql as $query) {
            self::$pdo->exec($query);
        }
    }
    
    /**
     * Create sample template files
     */
    private static function createSampleTemplates(string $templateDir): void
    {
        $templates = [
            'SF1-SHS.xlsx' => [
                'A1' => 'Learner Reference Number (LRN)',
                'B1' => 'Last Name',
                'C1' => 'First Name',
                'D1' => 'Middle Name',
                'E1' => 'Grade Level',
                'F1' => 'Section',
                'G1' => 'School Year'
            ],
            'SF9-SHS.xlsx' => [
                'A1' => 'LEARNER\'S NAME',
                'B1' => 'LRN',
                'C1' => 'GRADE LEVEL',
                'D1' => 'SCHOOL YEAR',
                'E1' => 'GRADING PERIOD'
            ],
            'SF10-SHS.xlsx' => [
                'A1' => 'LEARNER\'S NAME',
                'B1' => 'LRN',
                'C1' => 'GRADE LEVEL',
                'D1' => 'SCHOOL YEAR',
                'E1' => 'FINAL GRADE'
            ]
        ];
        
        foreach ($templates as $filename => $data) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            foreach ($data as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            $writer = new Xlsx($spreadsheet);
            $writer->save("$templateDir/$filename");
        }
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Truncate test tables
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE generated_forms');
        self::$pdo->exec('TRUNCATE TABLE form_templates');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        // Clear test output directory
        $files = glob(self::TEST_OUTPUT_DIR . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Test form template management
     */
    public function testFormTemplateManagement(): void
    {
        // Insert a form template
        $formType = 'sf1';
        $templatePath = __DIR__ . "/../templates/SF1-SHS.xlsx";
        
        $stmt = self::$pdo->prepare("
            INSERT INTO form_templates (form_type, template_path)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE template_path = VALUES(template_path)
        ");
        
        $result = $stmt->execute([$formType, $templatePath]);
        $this->assertTrue($result, 'Failed to insert form template');
        
        // Retrieve the template
        $stmt = self::$pdo->prepare("SELECT * FROM form_templates WHERE form_type = ?");
        $stmt->execute([$formType]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($template, 'Template not found');
        $this->assertEquals($formType, $template['form_type'], 'Form type does not match');
        $this->assertEquals($templatePath, $template['template_path'], 'Template path does not match');
    }

    /**
     * Test SF1 form generation
     */
    public function testSF1FormGeneration(): void
    {
        // Prepare test data
        $formType = 'sf1';
        $studentData = [
            'lrn' => '123456789012',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'M',
            'grade_level' => 10,
            'section' => 'A',
            'school_year' => '2023-2024',
            'birthdate' => '2007-01-01',
            'gender' => 'M',
            'address' => 'Sample Address',
            'parent_guardian' => 'Parent Name',
            'contact_number' => '09123456789'
        ];
        
        // Generate the form
        $outputFile = $this->generateForm($formType, $studentData);
        
        // Verify the file was created
        $this->assertFileExists($outputFile, 'Form file was not created');
        
        // Verify the file content
        $spreadsheet = IOFactory::load($outputFile);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Check if the template headers are present
        $this->assertEquals(
            'Learner Reference Number (LRN)',
            $sheet->getCell('A1')->getValue(),
            'Template header mismatch'
        );
        
        // Log the form generation
        $this->logFormGeneration($formType, $studentData['lrn'], $outputFile, $studentData['school_year']);
        
        // Verify the log was created
        $stmt = self::$pdo->prepare("
            SELECT * FROM generated_forms 
            WHERE form_type = ? AND student_lrn = ? AND school_year = ?
        ");
        $stmt->execute([$formType, $studentData['lrn'], $studentData['school_year']]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($log, 'Form generation was not logged');
        $this->assertEquals($outputFile, $log['file_path'], 'File path in log does not match');
    }
    
    /**
     * Generate a form with the given data
     */
    private function generateForm(string $formType, array $data): string
    {
        $templateFile = __DIR__ . "/../templates/" . strtoupper($formType) . "-SHS.xlsx";
        $outputFile = self::TEST_OUTPUT_DIR . "/{$formType}_{$data['lrn']}_" . date('YmdHis') . ".xlsx";
        
        // Load the template
        $spreadsheet = IOFactory::load($templateFile);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Map data to cells (simplified example)
        $mapping = [
            'sf1' => [
                'lrn' => 'A2',
                'last_name' => 'B2',
                'first_name' => 'C2',
                'middle_name' => 'D2',
                'grade_level' => 'E2',
                'section' => 'F2',
                'school_year' => 'G2'
            ],
            'sf9' => [
                'last_name' => 'A2',
                'first_name' => 'A2', // Combined with last name
                'lrn' => 'B2',
                'grade_level' => 'C2',
                'school_year' => 'D2'
            ],
            'sf10' => [
                'last_name' => 'A2',
                'first_name' => 'A2', // Combined with last name
                'lrn' => 'B2',
                'grade_level' => 'C2',
                'school_year' => 'D2',
                'final_grade' => 'E2'
            ]
        ];
        
        // Apply the mapping
        foreach ($mapping[$formType] as $field => $cell) {
            if (isset($data[$field])) {
                // Special handling for combined fields
                if ($field === 'last_name' && isset($data['first_name'])) {
                    $sheet->setCellValue($cell, "{$data['last_name']}, {$data['first_name']}");
                } else {
                    $sheet->setCellValue($cell, $data[$field]);
                }
            }
        }
        
        // Save the file
        $writer = new Xlsx($spreadsheet);
        $writer->save($outputFile);
        
        return $outputFile;
    }
    
    /**
     * Log form generation in the database
     */
    private function logFormGeneration(string $formType, string $studentLrn, string $filePath, string $schoolYear): bool
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO generated_forms 
            (form_type, student_lrn, file_path, school_year, generated_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        // In a real application, you would get the current user ID from the session
        $generatedBy = 1; // Assuming user ID 1 is the system or admin
        
        return $stmt->execute([$formType, $studentLrn, $filePath, $schoolYear, $generatedBy]);
    }
    
    /**
     * Test form validation
     */
    public function testFormValidation(): void
    {
        // Test valid form data
        $validData = [
            'lrn' => '123456789012',
            'school_year' => '2023-2024',
            'grade_level' => 10,
            'section' => 'A'
        ];
        
        $this->assertTrue($this->validateFormData('sf1', $validData), 'Valid form data should pass validation');
        
        // Test missing required field
        $invalidData = [
            'lrn' => '123456789012',
            'school_year' => '2023-2024'
            // Missing grade_level and section
        ];
        
        $this->assertFalse($this->validateFormData('sf1', $invalidData), 'Invalid form data should fail validation');
    }
    
    /**
     * Validate form data against required fields
     */
    private function validateFormData(string $formType, array $data): bool
    {
        $requiredFields = [
            'sf1' => ['lrn', 'school_year', 'grade_level', 'section'],
            'sf9' => ['lrn', 'school_year', 'grading_period'],
            'sf10' => ['lrn', 'school_year', 'final_grade']
        ];
        
        if (!isset($requiredFields[$formType])) {
            return false;
        }
        
        foreach ($requiredFields[$formType] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return false;
            }
        }
        
        return true;
    }
}
