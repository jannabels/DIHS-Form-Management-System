const { fetchStudentGrades, saveGrade } = require('../../services/gradeService');

// Mock the database module
const db = require('../../db');

// Mock the db methods
jest.mock('../../db', () => ({
  query: jest.fn().mockImplementation((sql, params) => {
    // Handle different queries based on SQL content
    if (sql.includes('FROM sf9')) {
      return Promise.resolve([[{ grade_level: '11', track: 'STEM' }]]);
    } else if (sql.includes('FROM curriculum')) {
      return Promise.resolve([[
        { subject_code: 'MATH101', subject_name: 'Mathematics' },
        { subject_code: 'SCI101', subject_name: 'Science' }
      ]]);
    } else if (sql.includes('FROM student_grades')) {
      return Promise.resolve([[
        { subject_code: 'MATH101', quarter: '1st Quarter', grade: '90' },
        { subject_code: 'MATH101', quarter: '2nd Quarter', grade: '85' },
        { subject_code: 'SCI101', quarter: '1st Quarter', grade: '92' }
      ]]);
    }
    return Promise.resolve([[]]);
  }),
  getConnection: jest.fn()
}));

describe('Grade Service', () => {
  beforeEach(() => {
    // Clear all mocks before each test
    jest.clearAllMocks();
  });

  describe('fetchStudentGrades', () => {
    it('should fetch and format student grades', async () => {
      const result = await fetchStudentGrades('123456789012');
      expect(result.success).toBe(true);
      expect(result.data.studentInfo.lrn).toBe('123456789012');
      expect(result.data.studentInfo.gradeLevel).toBe('11');
      expect(result.data.subjects.math101.grades['1st Quarter']).toBe('90');
    });

    it('should handle student not found', async () => {
      // Override the mock for this specific test
      db.query.mockImplementationOnce(() => Promise.resolve([[]]));
      
      const result = await fetchStudentGrades('nonexistent');
      expect(result.success).toBe(false);
      expect(result.error).toBe("Student's grade level not found");
    });
  });

  describe('saveGrade', () => {
    it('should save a new grade', async () => {
      // Mock database connection
      const mockBeginTransaction = jest.fn();
      const mockCommit = jest.fn();
      const mockQuery = jest.fn()
        .mockResolvedValueOnce([[]]) // No existing grade
        .mockResolvedValueOnce([{ insertId: 1 }]); // Insert result
      
      db.getConnection.mockResolvedValueOnce({
        beginTransaction: mockBeginTransaction,
        commit: mockCommit,
        rollback: jest.fn(),
        query: mockQuery,
        release: jest.fn()
      });

      const result = await saveGrade(
        '123456789012', // lrn
        'MATH101',      // subjectCode
        '1st Quarter',  // quarter
        90,             // grade
        '1st Semester'  // semester
      );

      expect(result.success).toBe(true);
      expect(mockQuery).toHaveBeenCalledWith(
        expect.stringContaining('INSERT INTO student_grades'),
        ['123456789012', 'MATH101', '1st Quarter', '1st Semester', 90]
      );
    });

    it('should update an existing grade', async () => {
      // Mock database connection
      const mockBeginTransaction = jest.fn();
      const mockCommit = jest.fn();
      const mockQuery = jest.fn()
        .mockResolvedValueOnce([[{ id: 42 }]]) // Existing grade
        .mockResolvedValueOnce([{ affectedRows: 1 }]); // Update result
      
      db.getConnection.mockResolvedValueOnce({
        beginTransaction: mockBeginTransaction,
        commit: mockCommit,
        rollback: jest.fn(),
        query: mockQuery,
        release: jest.fn()
      });

      const result = await saveGrade(
        '123456789012', // lrn
        'MATH101',      // subjectCode
        '1st Quarter',  // quarter
        95,             // grade (updated)
        '1st Semester'  // semester
      );

      expect(result.success).toBe(true);
      expect(mockQuery).toHaveBeenCalledWith(
        expect.stringContaining('UPDATE student_grades'),
        [95, 42]
      );
    });

    it('should handle database errors', async () => {
      const error = new Error('Database error');
      db.getConnection.mockImplementationOnce(() => Promise.reject(error));

      await expect(
        saveGrade(
          '123456789012', // lrn
          'MATH101',      // subjectCode
          '1st Quarter',  // quarter
          90,             // grade
          '1st Semester'  // semester
        )
      ).rejects.toThrow('Database error');
    });
  });
});