// src/utils/mathUtils.js

/**
 * Adds two numbers
 * @param {number} a - First number
 * @param {number} b - Second number
 * @returns {number} Sum of a and b
 */
const add = (a, b) => a + b;

/**
 * Subtracts b from a
 * @param {number} a - First number
 * @param {number} b - Number to subtract
 * @returns {number} Result of a - b
 */
const subtract = (a, b) => a - b;

/**
 * Multiplies two numbers
 * @param {number} a - First number
 * @param {number} b - Second number
 * @returns {number} Product of a and b
 */
const multiply = (a, b) => a * b;

/**
 * Divides a by b
 * @param {number} a - Dividend
 * @param {number} b - Divisor
 * @returns {number} Result of a / b
 * @throws {Error} If b is zero
 */
const divide = (a, b) => {
  if (b === 0) {
    throw new Error('Division by zero');
  }
  return a / b;
};

module.exports = {
  add,
  subtract,
  multiply,
  divide
};
