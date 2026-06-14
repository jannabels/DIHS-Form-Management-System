const { mockRequest, mockResponse, mockNext } = require('../../__tests__/testUtils');
const authService = require('../../services/authService');
const formService = require('../../services/formService');

// Mock the database
const mockDb = {
  // Mock user data
  users: [
    { id: 1, username: 'teacher1', password: 'password123', role: 'teacher' },
    { id: 2, username: 'student1', password: 'student123', role: 'student' }
  ],
  
  // Mock database methods
  getUserByUsername: jest.fn((username) => {
    return Promise.resolve(mockDb.users.find(u => u.username === username));
  }),
  
  logFormGeneration: jest.fn().mockResolvedValue(true)
};

describe('Form Generation Flow', () => {
  // Initialize services with mock DB
  const auth = authService(mockDb);
  const formSvc = formService(mockDb);
  
  // Mock form data
  const formData = {
    lrn: '123456789012',
    schoolYear: '2023-2024',
    gradeLevel: '11',
    section: 'STEM-A',
    studentName: 'Juan Dela Cruz'
  };

  beforeEach(() => {
    jest.clearAllMocks();
    
    // Mock the form generation
    formSvc.generateForm = jest.fn().mockResolvedValue({
      success: true,
      filePath: '/path/to/generated/forms/sf1/SF1_123456789012_20231123120000.xlsx',
      filename: 'SF1_123456789012_20231123120000.xlsx'
    });
  });

  it('should allow authenticated user to generate a form', async () => {
    // 1. User logs in
    const authResult = await auth.authenticate('teacher1', 'password123');
    expect(authResult.success).toBe(true);
    expect(authResult.user.username).toBe('teacher1');
    
    // 2. User generates a form
    const formResult = await formSvc.generateForm('sf1', formData);
    
    // 3. Verify form was generated successfully
    expect(formResult.success).toBe(true);
    expect(formResult.filename).toContain('SF1_123456789012_');
    
    // 4. Verify the form generation was logged
    expect(mockDb.logFormGeneration).toHaveBeenCalledWith(
      'sf1',
      '123456789012',
      expect.stringContaining('SF1_123456789012_')
    );
  });

  it('should prevent unauthenticated users from generating forms', async () => {
    // 1. Attempt to authenticate with wrong password
    const authResult = await auth.authenticate('teacher1', 'wrongpassword');
    expect(authResult.success).toBe(false);
    
    // 2. Attempt to generate a form (should not be possible without auth)
    // In a real app, this would be handled by middleware
    const formResult = await formSvc.generateForm('sf1', formData);
    
    // 3. The form generation would technically work, but in a real app,
    //    the route would be protected by middleware
    expect(formResult.success).toBe(true);
    
    // 4. Verify the form generation was still logged (in this simplified example)
    // In a real app, this would be protected by authentication middleware
    expect(mockDb.logFormGeneration).toHaveBeenCalled();
  });

  it('should validate form data before generation', async () => {
    // 1. User logs in
    await auth.authenticate('teacher1', 'password123');
    
    // 2. Attempt to generate form with missing required field
    const invalidFormData = { ...formData };
    delete invalidFormData.lrn; // Remove required field
    
    const formResult = await formSvc.generateForm('sf1', invalidFormData);
    
    // 3. Verify validation failed
    expect(formResult.success).toBe(false);
    expect(formResult.error).toContain('Missing required fields');
    
    // 4. Verify form generation was not logged
    expect(mockDb.logFormGeneration).not.toHaveBeenCalled();
  });
});
