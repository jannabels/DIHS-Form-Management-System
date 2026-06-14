// Authentication Service Implementation
const bcrypt = require('bcrypt');

module.exports = (db) => {
  return {
    /**
     * Authenticate a user with username and password
     * @param {string} username - The username
     * @param {string} password - The password
     * @returns {Object} Authentication result with success status and user data
     */
    authenticate: async (username, password) => {
      try {
        const user = await db.getUserByUsername(username);
        if (!user) {
          return { success: false, message: 'User not found' };
        }

        // In a real app, we would compare hashed passwords
        // const isMatch = await bcrypt.compare(password, user.password);
        const isMatch = password === user.password; // Simplified for testing

        if (!isMatch) {
          return { success: false, message: 'Invalid credentials' };
        }

        // Don't return password in the user object
        const { password: _, ...userWithoutPassword } = user;
        return { success: true, user: userWithoutPassword };
      } catch (error) {
        console.error('Authentication error:', error);
        return { success: false, message: 'Authentication failed' };
      }
    },

    /**
     * Generate a JWT token for the user
     * @param {Object} user - User object
     * @returns {string} JWT token
     */
    generateToken: (user) => {
      // In a real app, we would use jsonwebtoken
      return `token-${user.id}-${Date.now()}`;
    }
  };
};
