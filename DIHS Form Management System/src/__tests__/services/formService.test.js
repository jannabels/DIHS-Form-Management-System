const fs = require('fs');
const path = require('path');
const ExcelJS = require('exceljs');
const formService = require('../../services/formService');

// Mock the filesystem module
jest.mock('fs');

// Mock ExcelJS
jest.mock('exceljs', () => {
  const mockWorkbook = {
    xlsx: {
      readFile: jest.fn(),
      writeFile: jest.fn()
    },
    worksheets: [{
      getCell: jest.fn().mockReturnValue({ value: null }),
      getRow: jest.fn().mockReturnThis(),
      getCell: jest.fn().mockReturnThis(),
      value: null
    }]
  };
  
  return {
    Workbook: jest.fn(() => mockWorkbook)
  };
});

describe('Form Service', () => {
  // Mock database
  const mockDb = {
    logFormGeneration: jest.fn().mockResolvedValue(true)
  };

  // Create form service with mock DB
  const formSvc = formService(mockDb);

  // Reset mocks before each test
  beforeEach(() => {
    jest.clearAllMocks();
    
    // Mock fs.existsSync and fs.mkdirSync
    fs.existsSync.mockReturnValue(false);
    fs.mkdirSync.mockImplementation(() => {});
    
    // Mock the fillFormData method
    formSvc.fillFormData = jest.fn();
  });

  describe('generateForm', () => {
    const formData = {
      lrn: '123456789012',
      schoolYear: '2023-2024',
      gradeLevel: '11',
      section: 'STEM-A',
      studentName: 'Juan Dela Cruz'
    };

    it('should generate SF1 form successfully', async () => {
      // Act
      const result = await formSvc.generateForm('sf1', formData);
      
      // Assert
      expect(result.success).toBe(true);
      expect(result.filePath).toContain('SF1_123456789012_');
      expect(fs.mkdirSync).toHaveBeenCalled();
      expect(ExcelJS.Workbook).toHaveBeenCalled();
      expect(formSvc.fillFormData).toHaveBeenCalled();
      expect(mockDb.logFormGeneration).toHaveBeenCalledWith(
        'sf1', 
        '123456789012', 
        expect.stringContaining('SF1_123456789012_')
      );
    });

    it('should return error for invalid form type', async () => {
      // Act
      const result = await formSvc.generateForm('invalid_form', formData);
      
      // Assert
      expect(result).toEqual({
        success: false,
        error: 'Invalid form type'
      });
    });

    it('should return error for missing required fields', async () => {
      // Arrange - Missing required 'section' field
      const invalidData = { ...formData };
      delete invalidData.section;
      
      // Act
      const result = await formSvc.generateForm('sf1', invalidData);
      
      // Assert
      expect(result).toEqual({
        success: false,
        error: 'Missing required fields: section'
      });
    });

    it('should handle file system errors', async () => {
      // Arrange
      const error = new Error('File system error');
      fs.existsSync.mockImplementation(() => { throw error; });
      
      // Act
      const result = await formSvc.generateForm('sf1', formData);
      
      // Assert
      expect(result).toEqual({
        success: false,
        error: expect.stringContaining('Failed to generate sf1 form:')
      });
    });
  });

  describe('fillFormData', () => {
    it('should fill form data correctly', () => {
      // Arrange
      const mockWorksheet = {
        getCell: jest.fn().mockReturnThis(),
        getRow: jest.fn().mockReturnThis(),
        value: null
      };
      
      const formData = {
        lrn: '123456789012',
        studentName: 'Juan Dela Cruz',
        gradeLevel: '11',
        section: 'STEM-A'
      };
      
      // Act
      formSvc.fillFormData(mockWorksheet, formData, 'sf1');
      
      // Assert
      // Verify that getCell was called with the correct coordinates
      // This is a simplified example - in a real test, you would check all the expected cell updates
      expect(mockWorksheet.getCell).toHaveBeenCalled();
      expect(mockWorksheet.getRow).toHaveBeenCalled();
    });
  });
});
