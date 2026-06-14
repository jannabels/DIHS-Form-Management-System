const { login, validatePassword, sanitizeInput } = require('../../auth/loginService');

describe('Login Service', () => {
  describe('login', () => {
    it('should successfully login with valid admin credentials', async () => {
      const result = await login('admin', 'admin123');
      expect(result.success).toBe(true);
      expect(result.user).toBeDefined();
      expect(result.user.username).toBe('admin');
      expect(result.user.role).toBe('Super Admin');
      expect(result.redirectUrl).toBe('/ITpage/indexit.php');
      expect(result.user.password).toBeUndefined(); // Password should be removed
    });

    it('should fail with incorrect password', async () => {
      const result = await login('admin', 'wrongpassword');
      expect(result.success).toBe(false);
      expect(result.message).toBe('Invalid username or password');
    });

    it('should fail with non-existent username', async () => {
      const result = await login('nonexistent', 'password');
      expect(result.success).toBe(false);
      expect(result.message).toBe('Invalid username or password');
    });

    it('should fail with empty username or password', async () => {
      const result1 = await login('', 'password');
      expect(result1.success).toBe(false);
      expect(result1.message).toBe('Username and password are required');

      const result2 = await login('admin', '');
      expect(result2.success).toBe(false);
      expect(result2.message).toBe('Username and password are required');
    });

    it('should fail for inactive accounts', async () => {
      const result = await login('inactive', 'inactive123');
      expect(result.success).toBe(false);
      expect(result.message).toBe('Account is inactive. Please contact administrator.');
    });

    it('should sanitize input to prevent XSS', async () => {
      const maliciousInput = '<script>alert("xss")</script>';
      const result = await login(maliciousInput, maliciousInput);
      expect(result.success).toBe(false);
      // The sanitized input should not contain any HTML tags
      expect(result.message).not.toContain('<script>');
    });
  });

  describe('validatePassword', () => {
    it('should validate password strength correctly', () => {
      // Test too short password
      expect(validatePassword('short')).toEqual({
        isValid: false,
        message: 'Password must be at least 8 characters long'
      });

      // Test missing uppercase
      expect(validatePassword('lowercase123')).toEqual({
        isValid: false,
        message: 'Password must contain at least one uppercase letter'
      });

      // Test missing number
      expect(validatePassword('Uppercase')).toEqual({
        isValid: false,
        message: 'Password must contain at least one number'
      });

      // Test valid password
      expect(validatePassword('ValidPass123')).toEqual({
        isValid: true,
        message: 'Password is valid'
      });
    });
  });

  describe('sanitizeInput', () => {
    it('should remove HTML tags from input', () => {
      expect(sanitizeInput('<script>alert("xss")</script>')).toBe('scriptalert("xss")/script');
      expect(sanitizeInput('<div>Hello</div>')).toBe('divHello/div');
      expect(sanitizeInput('normal text')).toBe('normal text');
      expect(sanitizeInput(undefined)).toBe('');
      expect(sanitizeInput(null)).toBe('');
    });
  });
});
