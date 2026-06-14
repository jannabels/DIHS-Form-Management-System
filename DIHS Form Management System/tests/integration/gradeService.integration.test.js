const { fetchStudentGrades, saveGrade } = require('../../src/services/gradeService');
const TestHelper = require('../TestHelper');

// Use a longer timeout for integration tests
jest.setTimeout(30000);

describe('Grade Service Integration Tests', () => {
  let testDb;
  
  beforeAll(async () => {
    // Get test database connection
    testDb = TestHelper.getTestConnection();
    
    // Create necessary tables if they don't exist
    await testDb.query(`
      CREATE TABLE IF NOT EXISTS sf9 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        LRN VARCHAR(20) NOT NULL,
        section VARCHAR(50)
      );
      
      CREATE TABLE IF NOT EXISTS section (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_name VARCHAR(50) NOT NULL,
        grade_level VARCHAR(10) NOT NULL,
        track VARCHAR(50)
      );
      
      CREATE TABLE IF NOT EXISTS curriculum (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_code VARCHAR(20) NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        grade_level VARCHAR(10) NOT NULL,
        track VARCHAR(50)
      );
      
      CREATE TABLE IF NOT EXISTS student_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn VARCHAR(20) NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        quarter VARCHAR(20) NOT NULL,
        grade DECIMAL(5,2) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_grade (lrn, subject_code, quarter, semester)
      );
    `);
    
    // Insert test data
    await testDb.query(`
      INSERT INTO section (class_name, grade_level, track) VALUES 
      ('11-STEM', '11', 'STEM');
      
      INSERT INTO sf9 (LRN, section) VALUES 
      ('123456789012', '11-STEM');
      
      INSERT INTO curriculum (subject_code, subject_name, grade_level, track) VALUES 
      ('MATH101', 'Mathematics', '11', 'STEM'),
      ('SCI101', 'Science', '11', 'STEM');
    `);
  });
  
  afterAll(async () => {
    // Clean up test data
    await testDb.query('DROP TABLE IF EXISTS student_grades');
    await testDb.query('DROP TABLE IF EXISTS sf9');
    await testDb.query('DROP TABLE IF EXISTS section');
    await testDb.query('DROP TABLE IF EXISTS curriculum');
  });
  
  describe('fetchStudentGrades', () => {
    it('should fetch grades for a student', async () => {
      // Insert test grade
      await testDb.query(
        'INSERT INTO student_grades (lrn, subject_code, quarter, grade, semester) VALUES (?, ?, ?, ?, ?)',
        ['123456789012', 'MATH101', '1st Quarter', 90, '1st Semester']
      );
      
      const result = await fetchStudentGrades('123456789012');
      
      expect(result).toBeDefined();
      expect(result.grades).toBeInstanceOf(Array);
      expect(result.grades.length).toBeGreaterThan(0);
      expect(result.grades[0]).toHaveProperty('subject_code');
      expect(result.grades[0]).toHaveProperty('quarter');
      expect(result.grades[0]).toHaveProperty('grade');
    });
    
    it('should return empty array if no grades found', async () => {
      const result = await fetchStudentGrades('999999999999');
      expect(result.grades).toHaveLength(0);
    });
  });
  
  describe('saveGrade', () => {
    it('should save a new grade', async () => {
      const result = await saveGrade(
        '123456789012', 
        'SCI101', 
        '1st Quarter', 
        92, 
        '1st Semester'
      );
      
      expect(result).toHaveProperty('success', true);
      
      // Verify the grade was saved
      const [savedGrade] = await testDb.query(
        'SELECT * FROM student_grades WHERE lrn = ? AND subject_code = ? AND quarter = ?',
        ['123456789012', 'SCI101', '1st Quarter']
      );
      
      expect(savedGrade).toHaveLength(1);
      expect(parseInt(savedGrade[0].grade)).toBe(92);
    });
    
    it('should update an existing grade', async () => {
      // First insert a grade
      await saveGrade('123456789012', 'MATH101', '2nd Quarter', 85, '1st Semester');
      
      // Then update it
      const result = await saveGrade(
        '123456789012', 
        'MATH101', 
        '2nd Quarter', 
        88, 
        '1st Semester'
      );
      
      expect(result).toHaveProperty('success', true);
      
      // Verify the grade was updated
      const [updatedGrade] = await testDb.query(
        'SELECT * FROM student_grades WHERE lrn = ? AND subject_code = ? AND quarter = ?',
        ['123456789012', 'MATH101', '2nd Quarter']
      );
      
      expect(updatedGrade).toHaveLength(1);
      expect(parseInt(updatedGrade[0].grade)).toBe(88);
    });
  });
});
