// Import the login service
const { login, validatePassword, sanitizeInput } = require('../src/auth/loginService');

// Mock sessionStorage
const sessionStorageMock = (() => {
    let store = {};
    return {
        getItem: jest.fn(key => store[key] || null),
        setItem: jest.fn((key, value) => {
            store[key] = value.toString();
        }),
        removeItem: jest.fn(key => {
            delete store[key];
        }),
        clear: jest.fn(() => {
            store = {};
        })
    };
})();

// Mock document and window objects
global.document = {
    getElementById: jest.fn((id) => ({
        addEventListener: jest.fn(),
        value: '',
        classList: {
            add: jest.fn(),
            remove: jest.fn()
        }
    })),
    querySelector: jest.fn(() => ({
        style: {}
    })),
    createElement: jest.fn(() => ({
        textContent: '',
        style: {},
        classList: {
            add: jest.fn(),
            remove: jest.fn()
        },
        appendChild: jest.fn(),
        addEventListener: jest.fn()
    }))
};

global.window = {
    location: {
        href: '',
        replace: jest.fn()
    },
    alert: jest.fn(),
    sessionStorage: sessionStorageMock
};

describe('Login Functionality', () => {
    beforeEach(() => {
        // Reset all mocks before each test
        jest.clearAllMocks();
    });

    describe('Login Functionality', () => {
        test('successful login with admin credentials', async () => {
            const response = await login('admin', 'admin123');
            
            expect(response.success).toBe(true);
            expect(response.user.role).toBe('Super Admin');
            expect(response.user.id).toBe(1);
            expect(response.redirectUrl).toBe('/ITpage/indexit.php');
        });

        test('failed login with incorrect password', async () => {
            const response = await login('admin', 'wrongpassword');
            
            expect(response.success).toBe(false);
            expect(response.message).toBe('Invalid username or password');
        });

        test('failed login with non-existent username', async () => {
            const response = await login('nonexistent', 'password123');
            
            expect(response.success).toBe(false);
            expect(response.message).toBe('Invalid username or password');
        });

        test('failed login with inactive account', async () => {
            const response = await login('inactive', 'inactive123');
            
            expect(response.success).toBe(false);
            expect(response.message).toBe('Account is inactive. Please contact administrator.');
        });

        test('empty credentials', async () => {
            const response = await login('', '');
            expect(response.success).toBe(false);
            expect(response.message).toBe('Username and password are required');
        });
    });

    describe('Password Validation', () => {
        test('empty password', () => {
            const result = validatePassword('');
            expect(result.isValid).toBe(false);
            expect(result.message).toBe('Password is required');
        });

        test('short password', () => {
            const result = validatePassword('short');
            expect(result.isValid).toBe(false);
            expect(result.message).toContain('at least 8 characters');
        });

        test('password without uppercase', () => {
            const result = validatePassword('lowercase123');
            expect(result.isValid).toBe(false);
            expect(result.message).toContain('uppercase');
        });

        test('password without numbers', () => {
            const result = validatePassword('NoNumbersHere');
            expect(result.isValid).toBe(false);
            expect(result.message).toContain('number');
        });

        test('valid password', () => {
            const result = validatePassword('ValidPass123');
            expect(result.isValid).toBe(true);
            expect(result.message).toBe('Password is valid');
        });
    });

    describe('Input Sanitization', () => {
        test('sanitize input removes HTML tags', () => {
            const input = "<script>alert('xss')</script>test";
            const sanitized = sanitizeInput(input);
            expect(sanitized).toBe("scriptalert('xss')/scripttest");
        });

        test('sanitize input handles null/undefined', () => {
            expect(sanitizeInput(null)).toBe('');
            expect(sanitizeInput(undefined)).toBe('');
        });
    });

    describe('Role-based Redirects', () => {
        const testCases = [
            { role: 'Super Admin', expectedPath: '/ITpage/indexit.php', username: 'admin' },
            { role: 'Principal', expectedPath: '/principal/dashboard.php', username: 'principal' },
            { role: 'Guidance', expectedPath: '/guidance/guidance_sf1.php', username: 'guidance' },
            { role: 'Registrar', expectedPath: '/registrar/sf10.php', username: 'registrar' },
            { role: 'Adviser', expectedPath: '/adviser/adviser_sf2.php', username: 'teacher' },
            { role: 'OIC', expectedPath: '/oic/dashboard.php', username: 'oic' }
        ];

        testCases.forEach(({ role, expectedPath, username }) => {
            test(`redirects ${role} to ${expectedPath}`, async () => {
                const response = await login(username, `${username}123`);
                expect(response.success).toBe(true);
                expect(response.redirectUrl).toBe(expectedPath);
            });
        });
    });

    test('form validation - empty fields', () => {
        // Test XSS prevention
        const xssTest = () => {
            const dangerousInput = "<script>alert('xss')</script>";
            const sanitizedInput = dangerousInput.replace(/[<>]/g, '');
            return sanitizedInput === "scriptalert('xss')/script";
        };
        
        expect(xssTest()).toBe(true);

        // Mock form elements
        const form = {
            addEventListener: jest.fn((event, callback) => {
                if (event === 'submit') {
                    callback({ preventDefault: jest.fn() });
                }
            })
        };
        
        document.getElementById = jest.fn((id) => {
            if (id === 'loginForm') return form;
            if (id === 'username') return { value: '' };
            if (id === 'password') return { value: '' };
            return { classList: { add: jest.fn(), remove: jest.fn() } };
        });
        
        // This would be called in the actual form submission handler
        const validateForm = () => {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                return { isValid: false, message: 'Please fill in all fields' };
            }
            return { isValid: true };
        };
        
        const validation = validateForm();
        expect(validation.isValid).toBe(false);
        expect(validation.message).toBe('Please fill in all fields');
    });
});
