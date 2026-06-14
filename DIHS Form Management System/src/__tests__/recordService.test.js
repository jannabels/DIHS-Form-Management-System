// Mock the database module
const mockDb = {
  getStudentByLRN: jest.fn(),
  searchStudents: jest.fn(),
  updateStudentRecord: jest.fn()
};

// Mock the validation module
jest.mock('../../src/utils/validation', () => ({
  validateLRN: jest.fn()
}));

// Import the service with the mock db
const recordService = require('../services/recordService')(mockDb);
const { validateLRN } = require('../../src/utils/validation');

describe('Record Service', () => {
  // Reset all mocks before each test
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('findStudentByLRN', () => {
    it('should find a student by valid LRN', async () => {
      // Mock data
      const mockStudent = {
        lrn: '123456789012',
        firstName: 'John',
        lastName: 'Doe',
        gradeLevel: 10
      };

      // Setup mocks
      validateLRN.mockReturnValue(true);
      mockDb.getStudentByLRN.mockResolvedValue(mockStudent);

      // Call the function
      const result = await recordService.findStudentByLRN('123456789012');

      // Assertions
      expect(validateLRN).toHaveBeenCalledWith('123456789012');
      expect(mockDb.getStudentByLRN).toHaveBeenCalledWith('123456789012');
      expect(result).toEqual(mockStudent);
    });

    it('should throw error for invalid LRN', async () => {
      // Setup mocks
      validateLRN.mockReturnValue(false);

      // Call the function and expect it to throw
      await expect(recordService.findStudentByLRN('invalid')).rejects.toThrow('Invalid LRN format');
    });
  });

  describe('searchStudents', () => {
    it('should search for students by query', async () => {
      // Mock data
      const mockStudents = [
        { lrn: '123456789012', firstName: 'John', lastName: 'Doe' },
        { lrn: '123456789013', firstName: 'Jane', lastName: 'Doe' }
      ];

      // Setup mocks
      mockDb.searchStudents.mockResolvedValue(mockStudents);

      // Call the function
      const query = 'Doe';
      const result = await recordService.searchStudents(query);

      // Assertions
      expect(mockDb.searchStudents).toHaveBeenCalledWith(query);
      expect(result).toEqual(mockStudents);
    });

    it('should throw error for empty query', async () => {
      // Call the function and expect it to throw
      await expect(recordService.searchStudents('')).rejects.toThrow('Search query is required');
    });
  });

  describe('updateStudentRecord', () => {
    it('should update student record with valid data', async () => {
      // Mock data
      const lrn = '123456789012';
      const recordType = 'sf1';
      const updateData = {
        schoolYear: '2023-2024',
        gradeLevel: 11,
        section: 'A'
      };
      const updatedRecord = { ...updateData, lrn, updatedAt: new Date().toISOString() };

      // Setup mocks
      validateLRN.mockReturnValue(true);
      mockDb.updateStudentRecord.mockResolvedValue(updatedRecord);

      // Call the function
      const result = await recordService.updateStudentRecord(lrn, recordType, updateData);

      // Assertions
      expect(validateLRN).toHaveBeenCalledWith(lrn);
      expect(mockDb.updateStudentRecord).toHaveBeenCalledWith(lrn, recordType, updateData);
      expect(result).toEqual(updatedRecord);
    });

    it('should throw error for invalid record type', async () => {
      // Setup mocks
      validateLRN.mockReturnValue(true);

      // Call the function and expect it to throw
      await expect(
        recordService.updateStudentRecord('123456789012', 'invalidType', {})
      ).rejects.toThrow('Invalid record type');
    });
  });
});
