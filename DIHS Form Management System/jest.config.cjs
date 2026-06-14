// jest.config.cjs
module.exports = {
  // Test environment
  testEnvironment: 'node',
  
  // Where to find tests
  roots: ['<rootDir>/tests', '<rootDir>/src/__tests__'],
  
  // File patterns for test files
  testMatch: [
    '**/__tests__/**/*.test.js',
    '**/?(*.)+(spec|test).[jt]s?(x)'
  ],
  
  // Transform settings
  transform: {
    '^.+\\.js$': 'babel-jest',
  },
  
  // Module file extensions
  moduleFileExtensions: ['js', 'json', 'node'],
  
  // Coverage settings
  collectCoverage: true,
  collectCoverageFrom: [
    'src/**/*.js',
    '!**/node_modules/**',
    '!**/vendor/**',
  ],
  coverageDirectory: 'coverage',
  coverageReporters: ['text', 'lcov', 'clover', 'json', 'html'],
  
  // Coverage thresholds
  coverageThreshold: {
    global: {
      branches: 50,
      functions: 50,
      lines: 50,
      statements: 50
    }
  },
  
  // Test timeout
  testTimeout: 10000,
  
  // Show test results
  verbose: true,
  
  // Force exit to prevent hanging
  forceExit: true,
  
  // Clear mocks between tests
  clearMocks: true,
  
  // Setup files
  setupFilesAfterEnv: ['<rootDir>/tests/setupTests.js'],
  
  // Module name mapper (useful for mocking)
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },
};