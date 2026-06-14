const { getStudentStatus } = require('../../src/adviser/sf1.js');

// Mock MySQL connection
const mockConn = {
  query: jest.fn(),
  prepare: jest.fn(),
  close: jest.fn()
};

// Mock statement object
const mockStmt = {
  bind_param: jest.fn(),
  execute: jest.fn(),
  get_result: jest.fn(),
  close: jest.fn()
};

describe('getStudentStatus', () => {
  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();
    
    // Default mock implementation
    mockConn.query.mockImplementation((sql) => {
      if (sql.includes('information_schema.tables')) {
        return { fetch_assoc: () => ({ cnt: 1 }), free: jest.fn() };
      }
      return null;
    });
    
    mockConn.prepare.mockReturnValue(mockStmt);
    mockStmt.get_result.mockReturnValue({
      fetch_assoc: function* () {
        // Default to empty results
        return null;
      },
      free: jest.fn()
    });
  });

  it('should return Regular when grades table does not exist', () => {
    mockConn.query.mockImplementation((sql) => {
      if (sql.includes('information_schema.tables')) {
        return { fetch_assoc: () => ({ cnt: 0 }), free: jest.fn() };
      }
      return null;
    });

    const status = getStudentStatus(mockConn, '123456789012');
    expect(status).toBe('Regular');
  });

  it('should return Regular when all grades are above passing', () => {
    const mockResults = [
      { grade: '85' },
      { grade: '90' },
      { grade: '88' }
    ];
    
    mockStmt.get_result.mockReturnValue({
      fetch_assoc: function* () {
        for (const row of mockResults) {
          yield row;
        }
      },
      free: jest.fn()
    });

    const status = getStudentStatus(mockConn, '123456789012', 75);
    expect(status).toBe('Regular');
    expect(mockStmt.bind_param).toHaveBeenCalledWith('s', '123456789012');
  });

  it('should return Irregular when any grade is below passing', () => {
    const mockResults = [
      { grade: '85' },
      { grade: '70' },  // Below passing grade of 75
      { grade: '90' }
    ];

    // Reset mocks
    jest.clearAllMocks();
    
    // Mock the table check
    mockConn.query.mockImplementation((sql) => {
      if (sql.includes('information_schema.tables')) {
        return { fetch_assoc: () => ({ cnt: 1 }), free: jest.fn() };
      }
      return null;
    });

    // Mock the prepare and statement
    mockConn.prepare.mockImplementation((sql) => {
      return {
        bind_param: mockStmt.bind_param,
        execute: mockStmt.execute,
        get_result: () => ({
          fetch_assoc: function* () {
            for (const row of mockResults) {
              yield row;
            }
          },
          free: jest.fn()
        }),
        close: jest.fn()
      };
    });

    const status = getStudentStatus(mockConn, '123456789012', 75);
    expect(status).toBe('Irregular');
  });

  it('should handle empty result set by returning Regular', () => {
    mockStmt.get_result.mockReturnValue({
      fetch_assoc: function* () {
        return null; // No results
      },
      free: jest.fn()
    });

    const status = getStudentStatus(mockConn, '123456789012', 75);
    expect(status).toBe('Regular');
  });

  it('should handle database errors gracefully', () => {
    mockConn.query.mockImplementation(() => {
      throw new Error('Database error');
    });

    const status = getStudentStatus(mockConn, '123456789012', 75);
    expect(status).toBe('Regular'); // Default status on error
  });
});
