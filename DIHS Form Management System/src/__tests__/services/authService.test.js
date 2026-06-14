const { mockRequest, mockResponse, mockNext, resetMocks } = require('../testUtils');
const authService = require('../../services/authService');

describe('Auth Service', () => {
  // Mock database
  const mockDb = {
    getUserByUsername: jest.fn(),
  };

  // Create auth service with mock DB
  const auth = authService(mockDb);

  // Reset mocks before each test
  beforeEach(() => {
    resetMocks();
  });

  describe('authenticate', () => {
    it('should return success with user data when credentials are valid', async () => {
      // Arrange
      const mockUser = {
        id: 1,
        username: 'testuser',
        password: 'password123',
        role: 'admin'
      };
      
      mockDb.getUserByUsername.mockResolvedValue(mockUser);
      
      // Act
      const result = await auth.authenticate('testuser', 'password123');
      
      // Assert
      expect(result).toEqual({
        success: true,
        user: {
          id: 1,
          username: 'testuser',
          role: 'admin'
        }
      });
      expect(mockDb.getUserByUsername).toHaveBeenCalledWith('testuser');
    });

    it('should return error when user is not found', async () => {
      // Arrange
      mockDb.getUserByUsername.mockResolvedValue(null);
      
      // Act
      const result = await auth.authenticate('nonexistent', 'password');
      
      // Assert
      expect(result).toEqual({
        success: false,
        message: 'User not found'
      });
    });

    it('should return error when password is incorrect', async () => {
      // Arrange
      const mockUser = {
        id: 1,
        username: 'testuser',
        password: 'correctpassword',
        role: 'admin'
      };
      
      mockDb.getUserByUsername.mockResolvedValue(mockUser);
      
      // Act
      const result = await auth.authenticate('testuser', 'wrongpassword');
      
      // Assert
      expect(result).toEqual({
        success: false,
        message: 'Invalid credentials'
      });
    });

    it('should handle database errors', async () => {
      // Arrange
      mockDb.getUserByUsername.mockRejectedValue(new Error('Database error'));
      
      // Act
      const result = await auth.authenticate('testuser', 'password');
      
      // Assert
      expect(result).toEqual({
        success: false,
        message: 'Authentication failed'
      });
    });
  });

  describe('generateToken', () => {
    it('should generate a token for the user', () => {
      // Arrange
      const user = { id: 1, username: 'testuser' };
      
      // Act
      const token = auth.generateToken(user);
      
      // Assert
      expect(token).toMatch(/^token-1-\d+$/);
    });
  });
});
