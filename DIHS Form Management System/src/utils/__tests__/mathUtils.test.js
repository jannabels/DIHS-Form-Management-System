const { add, subtract, multiply, divide } = require('../mathUtils');

describe('Math Utilities', () => {
  // Test add function
  describe('add()', () => {
    it('should add two numbers correctly', () => {
      expect(add(2, 3)).toBe(5);
      expect(add(-1, 5)).toBe(4);
      expect(add(0, 0)).toBe(0);
    });

    it('should handle decimal numbers', () => {
      expect(add(0.1, 0.2)).toBeCloseTo(0.3);
    });
  });

  // Test subtract function
  describe('subtract()', () => {
    it('should subtract two numbers correctly', () => {
      expect(subtract(5, 3)).toBe(2);
      expect(subtract(10, -5)).toBe(15);
    });
  });

  // Test multiply function
  describe('multiply()', () => {
    it('should multiply two numbers correctly', () => {
      expect(multiply(2, 3)).toBe(6);
      expect(multiply(-2, 3)).toBe(-6);
      expect(multiply(0, 5)).toBe(0);
    });
  });

  // Test divide function
  describe('divide()', () => {
    it('should divide two numbers correctly', () => {
      expect(divide(6, 3)).toBe(2);
      expect(divide(10, 2)).toBe(5);
    });

    it('should throw error when dividing by zero', () => {
      expect(() => divide(5, 0)).toThrow('Division by zero');
    });
  });
});
