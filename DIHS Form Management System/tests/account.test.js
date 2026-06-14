// Account Management Module Tests
describe('Account Management Module', () => {
  // Mock user data
  const mockUsers = [
    { id: 1, username: 'admin', role: 'admin', password: 'hashed_password' },
    { id: 2, username: 'teacher1', role: 'teacher', password: 'teacher_pass' },
    { id: 3, username: 'guidance1', role: 'guidance', password: 'guidance_pass' }
  ];

  // Mock database functions
  const db = {
    getUserByUsername: jest.fn((username) => 
      mockUsers.find(user => user.username === username) || null
    ),
    createUser: jest.fn((user) => {
      const newUser = { ...user, id: mockUsers.length + 1 };
      mockUsers.push(newUser);
      return newUser;
    }),
    updateUserRole: jest.fn((userId, newRole) => {
      const user = mockUsers.find(u => u.id === userId);
      if (user) {
        user.role = newRole;
        return user;
      }
      return null;
    })
  };

  // Authentication Service Tests
  describe('Authentication Service', () => {
    test('should authenticate user with valid credentials', () => {
      const authService = require('../../src/services/authService')(db);
      const result = authService.authenticate('admin', 'hashed_password');
      expect(result.success).toBe(true);
      expect(result.user).toHaveProperty('username', 'admin');
    });

    test('should reject invalid credentials', () => {
      const authService = require('../../src/services/authService')(db);
      const result = authService.authenticate('admin', 'wrong_password');
      expect(result.success).toBe(false);
    });
  });

  // User Management Tests
  describe('User Management', () => {
    test('should create new user', () => {
      const userService = require('../../src/services/userService')(db);
      const newUser = {
        username: 'newuser',
        password: 'newpass',
        role: 'teacher',
        email: 'new@example.com'
      };
      
      const createdUser = userService.createUser(newUser);
      expect(createdUser).toHaveProperty('id');
      expect(createdUser.username).toBe('newuser');
    });

    test('should update user role', () => {
      const userService = require('../../src/services/userService')(db);
      const updatedUser = userService.updateUserRole(2, 'senior_teacher');
      expect(updatedUser.role).toBe('senior_teacher');
    });
  });

  // Authorization Tests
  describe('Authorization', () => {
    test('should allow admin to access all resources', () => {
      const authzService = require('../../src/services/authzService')(db);
      const hasAccess = authzService.checkAccess('admin', 'all_users', 'read');
      expect(hasAccess).toBe(true);
    });

    test('should restrict teacher from admin functions', () => {
      const authzService = require('../../src/services/authzService')(db);
      const hasAccess = authzService.checkAccess('teacher', 'all_users', 'read');
      expect(hasAccess).toBe(false);
    });
  });
});
