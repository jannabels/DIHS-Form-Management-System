// Mock database module for testing
const mockDb = {
  query: jest.fn(),
  getConnection: jest.fn()
};

module.exports = mockDb;
