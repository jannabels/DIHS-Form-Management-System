const {
  getAttendanceByDate,
  saveAttendance
} = require('../../services/attendanceService');

// Mock the database module
const db = require('../../db');

// Mock the db methods
jest.mock('../../db', () => ({
  query: jest.fn(),
  getConnection: jest.fn()
}));

describe('Attendance Service', () => {
  beforeEach(() => {
    // Clear all mocks before each test
    jest.clearAllMocks();
  });

  describe('getAttendanceByDate', () => {
    it('should fetch attendance data for a specific date', async () => {
      // Mock database response
      const mockAttendanceData = [
        { LRN: '123456789012', present: 1, absent: 0, tardy: 0 },
        { LRN: '987654321098', present: 0, absent: 1, tardy: 0 }
      ];
      
      db.query.mockResolvedValueOnce([mockAttendanceData]);
      
      // Call the function
      const date = '2023-11-23';
      const result = await getAttendanceByDate(date);
      
      // Assertions
      expect(db.query).toHaveBeenCalledWith(
        'SELECT LRN, present, absent, tardy FROM daily_attendance WHERE date = ?',
        [date]
      );
      
      expect(result).toEqual({
        success: true,
        data: {
          '123456789012': { present: true, absent: false, tardy: false },
          '987654321098': { present: false, absent: true, tardy: false }
        }
      });
    });

    it('should handle database errors', async () => {
      // Mock database error
      const error = new Error('Database connection failed');
      db.query.mockRejectedValueOnce(error);
      
      // Call the function
      const result = await getAttendanceByDate('2023-11-23');
      
      // Assertions
      expect(result).toEqual({
        success: false,
        error: 'Failed to fetch attendance data',
        details: 'Database connection failed'
      });
    });
  });

  describe('saveAttendance', () => {
    it('should save attendance data successfully', async () => {
      // Mock database connection
      const mockBeginTransaction = jest.fn();
      const mockCommit = jest.fn();
      const mockRollback = jest.fn();
      const mockQuery = jest.fn().mockResolvedValue([{}]);
      const mockRelease = jest.fn();
      
      db.getConnection.mockResolvedValueOnce({
        beginTransaction: mockBeginTransaction,
        commit: mockCommit,
        rollback: mockRollback,
        query: mockQuery,
        release: mockRelease
      });
      
      // Test data
      const date = '2023-11-23';
      const attendanceData = [
        { lrn: '123456789012', present: true, absent: false, tardy: false },
        { lrn: '987654321098', present: false, absent: true, tardy: false }
      ];
      
      // Call the function
      const result = await saveAttendance(date, attendanceData);
      
      // Assertions
      expect(mockBeginTransaction).toHaveBeenCalled();
      expect(mockQuery).toHaveBeenCalledWith(
        'DELETE FROM daily_attendance WHERE date = ?',
        [date]
      );
      
      // Check that insert was called for each record
      expect(mockQuery).toHaveBeenCalledWith(
        expect.stringContaining('INSERT INTO daily_attendance'),
        [date, '123456789012', true, false, false]
      );
      
      expect(mockQuery).toHaveBeenCalledWith(
        expect.stringContaining('INSERT INTO daily_attendance'),
        [date, '987654321098', false, true, false]
      );
      
      expect(mockCommit).toHaveBeenCalled();
      expect(mockRelease).toHaveBeenCalled();
      expect(result).toEqual({
        success: true,
        message: 'Attendance saved successfully'
      });
    });

    it('should handle database errors during save', async () => {
      // Mock database connection with error
      const error = new Error('Insert failed');
      const mockBeginTransaction = jest.fn();
      const mockRollback = jest.fn();
      const mockQuery = jest.fn().mockRejectedValueOnce(error);
      const mockRelease = jest.fn();
      
      db.getConnection.mockResolvedValueOnce({
        beginTransaction: mockBeginTransaction,
        commit: jest.fn(),
        rollback: mockRollback,
        query: mockQuery,
        release: mockRelease
      });
      
      // Call the function
      const result = await saveAttendance('2023-11-23', [
        { lrn: '123456789012', present: true }
      ]);
      
      // Assertions
      expect(mockBeginTransaction).toHaveBeenCalled();
      expect(mockRollback).toHaveBeenCalled();
      expect(mockRelease).toHaveBeenCalled();
      expect(result).toEqual({
        success: false,
        error: 'Failed to save attendance',
        details: 'Insert failed'
      });
    });
  });
});
