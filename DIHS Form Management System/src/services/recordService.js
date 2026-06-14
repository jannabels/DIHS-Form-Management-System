// Record Service Implementation
const { validateLRN } = require('../utils/validation');

module.exports = (db) => {
  return {
    /**
     * Find student by LRN
     * @param {string} lrn - Learner Reference Number
     * @returns {Object|null} Student data or null if not found
     */
    findStudentByLRN: async (lrn) => {
      try {
        if (!validateLRN(lrn)) {
          throw new Error('Invalid LRN format');
        }
        return await db.getStudentByLRN(lrn);
      } catch (error) {
        console.error('Error finding student by LRN:', error);
        throw new Error('Failed to find student');
      }
    },

    /**
     * Search for students by query
     * @param {string} query - Search query (LRN, last name, or first name)
     * @returns {Array} Array of matching students
     */
    searchStudents: async (query) => {
      try {
        if (!query || typeof query !== 'string' || query.trim() === '') {
          throw new Error('Search query is required');
        }
        return await db.searchStudents(query.trim());
      } catch (error) {
        console.error('Error searching students:', error);
        throw new Error('Failed to search students');
      }
    },

    /**
     * Update student record
     * @param {string} lrn - Learner Reference Number
     * @param {string} recordType - Type of record (sf1, sf9, sf10)
     * @param {Object} data - Record data to update
     * @returns {Object} Updated student record
     */
    updateStudentRecord: async (lrn, recordType, data) => {
      try {
        if (!validateLRN(lrn)) {
          throw new Error('Invalid LRN format');
        }

        const validRecordTypes = ['sf1', 'sf9', 'sf10'];
        if (!validRecordTypes.includes(recordType)) {
          throw new Error('Invalid record type');
        }

        // Validate record data structure based on type
        if (!this.validateRecordData(recordType, data)) {
          throw new Error(`Invalid data structure for ${recordType}`);
        }

        return await db.updateStudentRecord(lrn, recordType, data);
      } catch (error) {
        console.error('Error updating student record:', error);
        throw new Error(`Failed to update ${recordType} record: ${error.message}`);
      }
    },

    /**
     * Validate record data structure based on type
     * @private
     */
    validateRecordData: (recordType, data) => {
      // Basic validation - in a real app, this would be more comprehensive
      if (!data || typeof data !== 'object') {
        return false;
      }

      const requiredFields = {
        sf1: ['schoolYear', 'gradeLevel', 'section'],
        sf9: ['schoolYear', 'gradingPeriod'],
        sf10: ['schoolYear', 'finalGrade']
      };

      const fields = requiredFields[recordType] || [];
      return fields.every(field => data[field] !== undefined);
    },

    /**
     * Get all records for a student
     * @param {string} lrn - Learner Reference Number
     * @returns {Object} All records for the student
     */
    getAllStudentRecords: async (lrn) => {
      try {
        if (!validateLRN(lrn)) {
          throw new Error('Invalid LRN format');
        }
        
        const student = await db.getStudentByLRN(lrn);
        if (!student) {
          throw new Error('Student not found');
        }
        
        return student.records || {};
      } catch (error) {
        console.error('Error getting student records:', error);
        throw new Error('Failed to get student records');
      }
    }
  };
};
