const { capitalize, truncate, slugify } = require('../utils/stringUtils');

describe('String Utilities', () => {
  describe('capitalize', () => {
    test('should capitalize the first letter of a string', () => {
      expect(capitalize('hello')).toBe('Hello');
      expect(capitalize('HELLO')).toBe('Hello');
      expect(capitalize('hElLo')).toBe('Hello');
    });

    test('should handle empty string', () => {
      expect(capitalize('')).toBe('');
      expect(capitalize(null)).toBe('');
      expect(capitalize(undefined)).toBe('');
    });
  });

  describe('truncate', () => {
    test('should truncate string if longer than maxLength', () => {
      expect(truncate('Hello World', 5)).toBe('Hello...');
      expect(truncate('Hello', 5)).toBe('Hello');
    });

    test('should use custom ellipsis', () => {
      expect(truncate('Hello World', 5, '***')).toBe('Hello***');
    });

    test('should handle empty string', () => {
      expect(truncate('', 5)).toBe('');
      expect(truncate(null, 5)).toBe(undefined);
    });
  });

  describe('slugify', () => {
    test('should convert string to URL-friendly slug', () => {
      expect(slugify('Hello World!')).toBe('hello-world');
      expect(slugify('  Multiple   Spaces  ')).toBe('multiple-spaces');
      expect(slugify('Special@#!$%^&*()_+Characters')).toBe('special-characters');
    });

    test('should handle empty string', () => {
      expect(slugify('')).toBe('');
      expect(slugify(null)).toBe('');
    });
  });
});
