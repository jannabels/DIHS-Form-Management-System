// Mock database of users
const testUsers = [
    { username: 'admin', password: 'admin123', role: 'Super Admin', status: 'active', id: 1 },
    { username: 'teacher', password: 'teacher123', role: 'Adviser', status: 'active', id: 2 },
    { username: 'inactive', password: 'inactive123', role: 'Adviser', status: 'inactive', id: 3 },
    { username: 'principal', password: 'principal123', role: 'Principal', status: 'active', id: 4 },
    { username: 'guidance', password: 'guidance123', role: 'Guidance', status: 'active', id: 5 },
    { username: 'registrar', password: 'registrar123', role: 'Registrar', status: 'active', id: 6 },
    { username: 'oic', password: 'oic123', role: 'OIC', status: 'active', id: 7 }
];

// Role-based redirect URLs
const redirectUrls = {
    'Super Admin': '/ITpage/indexit.php',
    'Principal': '/principal/dashboard.php',
    'Guidance': '/guidance/guidance_sf1.php',
    'Registrar': '/registrar/sf10.php',
    'Adviser': '/adviser/adviser_sf2.php',
    'OIC': '/oic/dashboard.php'
};

/**
 * Validates password strength
 * @param {string} password - The password to validate
 * @returns {{isValid: boolean, message: string}} Validation result
 */
const validatePassword = (password) => {
    if (!password) {
        return { isValid: false, message: 'Password is required' };
    }
    if (password.length < 8) {
        return { isValid: false, message: 'Password must be at least 8 characters long' };
    }
    if (!/[A-Z]/.test(password)) {
        return { isValid: false, message: 'Password must contain at least one uppercase letter' };
    }
    if (!/[0-9]/.test(password)) {
        return { isValid: false, message: 'Password must contain at least one number' };
    }
    return { isValid: true, message: 'Password is valid' };
};

/**
 * Sanitizes user input to prevent XSS
 * @param {string} input - The input to sanitize
 * @returns {string} Sanitized input
 */
const sanitizeInput = (input) => {
    if (!input) return '';
    return input.toString().replace(/[<>]/g, '');
};

/**
 * Authenticates a user
 * @param {string} username - The username
 * @param {string} password - The password
 * @returns {Promise<{success: boolean, user?: object, message?: string}>} Authentication result
 */
const login = async (username, password) => {
    try {
        // Input validation
        if (!username || !password) {
            return { success: false, message: 'Username and password are required' };
        }

        // Sanitize inputs
        const sanitizedUsername = sanitizeInput(username);
        const sanitizedPassword = sanitizeInput(password);

        // Find user
        const user = testUsers.find(u => 
            u.username === sanitizedUsername && 
            u.password === sanitizedPassword
        );

        if (!user) {
            return { success: false, message: 'Invalid username or password' };
        }

        // Check account status
        if (user.status.toLowerCase() !== 'active') {
            return { 
                success: false, 
                message: 'Account is inactive. Please contact administrator.' 
            };
        }

        // Return user data without password
        const { password: _, ...userData } = user;
        return { 
            success: true, 
            user: userData,
            redirectUrl: redirectUrls[user.role] || '/'
        };
    } catch (error) {
        console.error('Login error:', error);
        return { 
            success: false, 
            message: 'An error occurred during login. Please try again.' 
        };
    }
};

module.exports = {
    login,
    validatePassword,
    sanitizeInput
};
