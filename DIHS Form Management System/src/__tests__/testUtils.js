/**
 * Test utilities for setting up test environment
 */

/**
 * Creates a mock request object for testing Express routes
 */
export const mockRequest = (body = {}, params = {}, query = {}, headers = {}) => ({
  body,
  params,
  query,
  headers,
  session: {},
  user: {},
  get(header) {
    return this.headers[header];
  },
  // Add other Express request methods as needed
});

/**
 * Creates a mock response object for testing Express routes
 */
export const mockResponse = () => {
  const res = {};
  res.status = jest.fn().mockReturnValue(res);
  res.json = jest.fn().mockReturnValue(res);
  res.send = jest.fn().mockReturnValue(res);
  res.redirect = jest.fn().mockReturnValue(res);
  return res;
};

/**
 * Creates a mock next function for testing middleware
 */
export const mockNext = () => jest.fn();

/**
 * Resets all mocks between tests
 */
export const resetMocks = () => {
  jest.clearAllMocks();
};

/**
 * Sets up the test environment before each test
 */
export const setupTestEnvironment = () => {
  // Mock console methods to keep test output clean
  global.console = {
    ...console,
    log: jest.fn(),
    error: jest.fn(),
    warn: jest.fn(),
    info: jest.fn(),
    debug: jest.fn(),
  };

  // Add any global test setup here
  process.env.NODE_ENV = 'test';
};

/**
 * Cleans up the test environment after each test
 */
export const cleanupTestEnvironment = () => {
  // Clean up any test-specific environment variables
  jest.resetModules();
  jest.restoreAllMocks();
};
