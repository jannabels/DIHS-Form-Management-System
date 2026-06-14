const { exampleHandler } = require('../../src/api/example');

describe('Example API Handler', () => {
  let req, res;

  beforeEach(() => {
    // Reset mocks before each test
    req = { query: {} };
    res = {
      status: jest.fn().mockReturnThis(),
      json: jest.fn(),
    };
  });

  test('should return 400 if name is not provided', async () => {
    await exampleHandler(req, res);

    expect(res.status).toHaveBeenCalledWith(400);
    expect(res.json).toHaveBeenCalledWith({
      success: false,
      error: 'Name is required',
    });
  });

  test('should return 200 with greeting message when name is provided', async () => {
    req.query.name = 'Test';
    await exampleHandler(req, res);

    expect(res.status).toHaveBeenCalledWith(200);
    expect(res.json).toHaveBeenCalledWith({
      success: true,
      message: 'Hello, Test!',
    });
  });

  test('should handle errors', async () => {
    // Mock a function to throw an error
    const originalConsoleError = console.error;
    console.error = jest.fn();
    
    // Force an error by making req.query undefined
    req = undefined;
    
    await exampleHandler(req, res);
    
    expect(res.status).toHaveBeenCalledWith(500);
    expect(res.json).toHaveBeenCalledWith({
      success: false,
      error: 'Internal server error',
    });
    
    // Restore console.error
    console.error = originalConsoleError;
  });
});
