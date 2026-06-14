// Validation Utilities

/**
 * Validate LRN (Learner Reference Number) format
 * @param {string} lrn - The LRN to validate
 * @returns {boolean} Whether the LRN is valid
 */
const validateLRN = (lrn) => {
  // LRN must be 12 digits
  if (typeof lrn !== 'string' || lrn.length !== 12) {
    return false;
  }
  
  // Must contain only digits
  return /^\d+$/.test(lrn);
};

/**
 * Validate email format
 * @param {string} email - The email to validate
 * @returns {boolean} Whether the email is valid
 */
const validateEmail = (email) => {
  if (!email) return false;
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(String(email).toLowerCase());
};

/**
 * Validate required fields in an object
 * @param {Object} data - The object to validate
 * @param {string[]} requiredFields - Array of required field names
 * @returns {Object} Validation result with success status and missing fields
 */
const validateRequiredFields = (data, requiredFields) => {
  if (!data || typeof data !== 'object') {
    return {
      isValid: false,
      missingFields: requiredFields
    };
  }

  const missingFields = requiredFields.filter(field => {
    const value = data[field];
    return value === undefined || value === null || value === '';
  });

  return {
    isValid: missingFields.length === 0,
    missingFields
  };
};

/**
 * Validate form data structure
 * @param {Object} formData - The form data to validate
 * @param {Object} schema - Validation schema
 * @returns {Object} Validation result
 */
const validateFormData = (formData, schema) => {
  const errors = {};
  let isValid = true;

  for (const [field, rules] of Object.entries(schema)) {
    const value = formData[field];
    const fieldErrors = [];

    if (rules.required && (value === undefined || value === null || value === '')) {
      fieldErrors.push('This field is required');
      isValid = false;
    }

    if (value !== undefined && value !== null && value !== '') {
      if (rules.type && typeof value !== rules.type) {
        fieldErrors.push(`Expected ${rules.type}, got ${typeof value}`);
        isValid = false;
      }

      if (rules.minLength && value.length < rules.minLength) {
        fieldErrors.push(`Must be at least ${rules.minLength} characters`);
        isValid = false;
      }

      if (rules.maxLength && value.length > rules.maxLength) {
        fieldErrors.push(`Must be at most ${rules.maxLength} characters`);
        isValid = false;
      }

      if (rules.pattern && !rules.pattern.test(value)) {
        fieldErrors.push('Invalid format');
        isValid = false;
      }
    }

    if (fieldErrors.length > 0) {
      errors[field] = fieldErrors;
    }
  }

  return {
    isValid,
    errors: Object.keys(errors).length > 0 ? errors : null
  };
};

module.exports = {
  validateLRN,
  validateEmail,
  validateRequiredFields,
  validateFormData
};
