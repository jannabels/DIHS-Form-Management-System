import { setupTestEnvironment } from './testUtils';

// Set up test environment before all tests
beforeAll(() => {
  setupTestEnvironment();
});

// Reset all mocks after each test
afterEach(() => {
  jest.clearAllMocks();
});

// Clean up after all tests are done
afterAll(async () => {
  // Close any open connections or clean up resources
  // For example: await database.close()
});

// Global test timeout (10 seconds)
jest.setTimeout(10000);
