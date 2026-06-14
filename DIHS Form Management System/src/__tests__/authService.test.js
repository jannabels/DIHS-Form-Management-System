// Mock the database module
const mockDb = {
  getUserByUsername: jest.fn()
};

// Mock bcrypt
jest.mock('bcrypt', () => ({
  compare: jest.fn()
}));

// Import the service with the mock db
const authService = require('../services/authService')(mockDb);
const bcrypt = require('bcrypt');

describe('Authentication Service', () => {
  // Reset all mocks before each test
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('authenticate', () => {
    it('should authenticate user with valid credentials', async () => {
      // Mock user data
      const mockUser = {
        id: 1,
        username: 'testuser',
        password: 'hashedpassword',
        role: 'teacher'
      };

      // Setup mocks
      mockDb.getUserByUsername.mockResolvedValue(mockUser);
      bcrypt.compare.mockResolvedValue(true);

      // Call the function
      const result = await authService.authenticate('testuser', 'password123');

      // Assertions
      expect(mockDb.getUserByUsername).toHaveBeenCalledWith('testuser');
      expect(bcrypt.compare).toHaveBeenCalledWith('password123', 'hashedpassword');
      expect(result).toEqual({
        success: true,
        user: {
          id: 1,
          username: 'testuser',
          role: 'teacher'
        }
      });
    });

    it('should reject invalid username', async () => {
      // Setup mocks
      mockDb.getUserByUsername.mockResolvedValue(null);

      // Call the function
      const result = await authService.authenticate('nonexistent', 'password');

      // Assertions
      expect(result).toEqual({
        success: false,
        message: 'User not found'
      });
    });

    it('should reject invalid password', async () => {
      // Mock user data
      const mockUser = {
        id: 1,
        username: 'testuser',
        password: 'hashedpassword',
        role: 'teacher'
      };

      // Setup mocks
      mockDb.getUserByUsername.mockResolvedValue(mockUser);
      bcrypt.compare.mockResolvedValue(false);

      // Call the function
      const result = await authService.authenticate('testuser', 'wrongpassword');

      // Assertions
      expect(result).toEqual({
        success: false,
        message: 'Invalid credentials'
      });
    });
  });

  describe('generateToken', () => {
    it('should generate a token for a user', () => {
      const user = { id: 1, username: 'testuser' };
      const token = authService.generateToken(user);
      
      // The token should start with 'token-' followed by user id and a timestamp
      expect(token).toMatch(/^token-1-\d+$/);
    });
  });
});
