// User Service Implementation
const bcrypt = require('bcrypt');

module.exports = (db) => {
  return {
    /**
     * Create a new user
     * @param {Object} userData - User data
     * @returns {Object} Created user data
     */
    createUser: async (userData) => {
      try {
        // Hash password before saving
        // const hashedPassword = await bcrypt.hash(userData.password, 10);
        const userToCreate = {
          ...userData,
          // password: hashedPassword,
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        };

        const createdUser = await db.createUser(userToCreate);
        const { password, ...userWithoutPassword } = createdUser;
        return userWithoutPassword;
      } catch (error) {
        console.error('Error creating user:', error);
        throw new Error('Failed to create user');
      }
    },

    /**
     * Update user role
     * @param {number} userId - User ID
     * @param {string} newRole - New role
     * @returns {Object} Updated user data
     */
    updateUserRole: async (userId, newRole) => {
      try {
        const updatedUser = await db.updateUserRole(userId, newRole);
        if (!updatedUser) {
          throw new Error('User not found');
        }
        
        const { password, ...userWithoutPassword } = updatedUser;
        return userWithoutPassword;
      } catch (error) {
        console.error('Error updating user role:', error);
        throw new Error('Failed to update user role');
      }
    },

    /**
     * Get user by ID
     * @param {number} userId - User ID
     * @returns {Object} User data
     */
    getUserById: async (userId) => {
      try {
        const user = await db.getUserById(userId);
        if (!user) {
          return null;
        }
        
        const { password, ...userWithoutPassword } = user;
        return userWithoutPassword;
      } catch (error) {
        console.error('Error fetching user:', error);
        throw new Error('Failed to fetch user');
      }
    }
  };
};
