const mysql = require('mysql2/promise');
const { getStudentStatus } = require('../../adviser/sf1');

// Test database configuration
const dbConfig = {
  host: process.env.DB_HOST || 'localhost', 
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'test_dihs',
  port: process.env.DB_PORT || 3306
};

describe('SF1 Integration Tests', () => {
  let connection;
  let testLrn = 'TEST123456789';
  
  beforeAll(async () => {
    // Create a connection to the test database
    connection = await mysql.createConnection({
      ...dbConfig,
      multipleStatements: true
    });
    
    // Create test tables if they don't exist
    await connection.query(`
      CREATE TABLE IF NOT EXISTS student_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn VARCHAR(20) NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        quarter VARCHAR(20) NOT NULL,
        grade DECIMAL(5,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_grade (lrn, subject_code, quarter)
      );
    `);
    
    // Clear any existing test data
    await connection.query('DELETE FROM student_grades WHERE lrn = ?', [testLrn]);
    
    // Insert test data
    await connection.query(
      'INSERT INTO student_grades (lrn, subject_code, quarter, grade) VALUES ?',
      [
        [testLrn, 'MATH101', '1st Quarter', 90],
        [testLrn, 'MATH101', '2nd Quarter', 85],
        [testLrn, 'SCI101', '1st Quarter', 70],  // This should make the student irregular
        [testLrn, 'SCI101', '2nd Quarter', 92]
      ]
    );
  });
  
  afterAll(async () => {
    // Clean up test data
    await connection.query('DELETE FROM student_grades WHERE lrn = ?', [testLrn]);
    
    // Close the database connection
    await connection.end();
  });
  
  describe('getStudentStatus', () => {
    it('should return Irregular when student has grades below passing', async () => {
      const status = getStudentStatus(connection, testLrn, 75);
      expect(status).toBe('Irregular');
    });
    
    it('should return Regular when all grades are above passing', async () => {
      // Update the failing grade to passing
      await connection.query(
        'UPDATE student_grades SET grade = 80 WHERE lrn = ? AND subject_code = ? AND quarter = ?',
        [testLrn, 'SCI101', '1st Quarter']
      );
      
      const status = getStudentStatus(connection, testLrn, 75);
      expect(status).toBe('Regular');
    });
    
    it('should return Regular when no grades are found', async () => {
      const nonExistentLrn = 'NONEXISTENT123';
      const status = getStudentStatus(connection, nonExistentLrn, 75);
      expect(status).toBe('Regular');
    });
  });
});
