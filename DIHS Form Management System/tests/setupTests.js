// Global test setup
import { jest } from '@jest/globals';

// Global test timeout (can be overridden in individual tests)
jest.setTimeout(10000);

// Mock console methods to keep test output clean
const originalConsole = { ...console };

global.beforeEach(() => {
  // Clear all mocks before each test
  jest.clearAllMocks();
  
  // Mock console methods
  global.console = {
    ...originalConsole,
    log: jest.fn(),
    error: jest.fn(),
    warn: jest.fn(),
    info: jest.fn(),
    debug: jest.fn(),
  };
});

// Restore original console after all tests
afterAll(() => {
  global.console = originalConsole;
});

// Add custom matchers
expect.extend({
  /**
   * Check if a string is a valid date string
   */
  toBeValidDate(received) {
    const pass = !isNaN(Date.parse(received));
    return {
      message: () => `expected ${received} ${pass ? 'not ' : ''}to be a valid date`,
      pass,
    };
  },
  
  /**
   * Check if an object has all the specified keys
   */
  toHaveKeys(received, keys) {
    const missingKeys = keys.filter(key => !(key in received));
    const pass = missingKeys.length === 0;
    return {
      message: () => 
        `Expected object to have all of the following keys: ${keys.join(', ')}. ` +
        `Missing: ${missingKeys.join(', ')}`,
      pass,
    };
  },
});

// Helper functions
global.testHelpers = {
  /**
   * Wait for a specified number of milliseconds
   * @param {number} ms - Milliseconds to wait
   * @returns {Promise<void>}
   */
  wait: (ms) => new Promise(resolve => setTimeout(resolve, ms)),
  
  /**
   * Create a mock response object for Express
   */
  mockResponse: () => {
    const res = {};
    res.status = jest.fn().mockReturnValue(res);
    res.json = jest.fn().mockReturnValue(res);
    res.send = jest.fn().mockReturnValue(res);
    res.redirect = jest.fn().mockReturnValue(res);
    return res;
  },
  
  /**
   * Create a mock request object for Express
   */
  mockRequest: (data = {}) => ({
    ...data,
    params: data.params || {},
    query: data.query || {},
    body: data.body || {},
    session: data.session || {},
    user: data.user || null,
  }),
};
