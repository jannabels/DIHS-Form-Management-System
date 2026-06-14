// Authorization Service Implementation

// Define role-based access control (RBAC) permissions
const PERMISSIONS = {
  admin: {
    all_users: ['read', 'create', 'update', 'delete'],
    student_records: ['read', 'create', 'update', 'delete'],
    forms: ['generate', 'view', 'export']
  },
  teacher: {
    student_records: ['read', 'update'],
    forms: ['generate', 'view']
  },
  guidance: {
    student_records: ['read', 'create', 'update'],
    forms: ['generate', 'view', 'export']
  }
};

module.exports = (db) => {
  return {
    /**
     * Check if a role has permission to perform an action on a resource
     * @param {string} role - User role
     * @param {string} resource - Resource being accessed
     * @param {string} action - Action being performed
     * @returns {boolean} Whether the action is allowed
     */
    checkAccess: (role, resource, action) => {
      try {
        // If user has no role, deny access
        if (!role) return false;
        
        // If role doesn't exist in permissions, deny access
        if (!PERMISSIONS[role]) return false;
        
        // If resource doesn't exist for role, deny access
        if (!PERMISSIONS[role][resource]) return false;
        
        // Check if action is allowed for the resource
        return PERMISSIONS[role][resource].includes(action);
      } catch (error) {
        console.error('Authorization error:', error);
        return false;
      }
    },

    /**
     * Get all permissions for a role
     * @param {string} role - User role
     * @returns {Object} All permissions for the role
     */
    getRolePermissions: (role) => {
      return PERMISSIONS[role] || {};
    },

    /**
     * Middleware to check if user is authenticated and authorized
     * @param {string} resource - Resource being accessed
     * @param {string} action - Action being performed
     * @returns {Function} Express middleware function
     */
    authorize: (resource, action) => {
      return (req, res, next) => {
        try {
          // Get user from request (assuming user is attached to request by auth middleware)
          const user = req.user;
          
          if (!user || !user.role) {
            return res.status(401).json({ error: 'Unauthorized' });
          }
          
          if (this.checkAccess(user.role, resource, action)) {
            return next();
          }
          
          return res.status(403).json({ error: 'Forbidden' });
        } catch (error) {
          console.error('Authorization middleware error:', error);
          return res.status(500).json({ error: 'Internal server error' });
        }
      };
    }
  };
};
