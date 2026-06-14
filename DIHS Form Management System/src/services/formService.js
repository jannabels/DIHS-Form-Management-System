// Form Service Implementation
const ExcelJS = require('exceljs');
const fs = require('fs');
const path = require('path');
const { validateRequiredFields } = require('../utils/validation');

// Define form templates configuration
const FORM_TEMPLATES = {
  sf1: {
    templatePath: path.join(__dirname, '../../templates/SF1-SHS.xlsx'),
    requiredFields: ['lrn', 'schoolYear', 'gradeLevel', 'section'],
    outputDir: path.join(__dirname, '../../generated-forms/sf1')
  },
  sf9: {
    templatePath: path.join(__dirname, '../../templates/SF9-SHS.xlsx'),
    requiredFields: ['lrn', 'schoolYear', 'gradingPeriod'],
    outputDir: path.join(__dirname, '../../generated-forms/sf9')
  },
  sf10: {
    templatePath: path.join(__dirname, '../../templates/SF10-SHS.xlsx'),
    requiredFields: ['lrn', 'schoolYear', 'finalGrade'],
    outputDir: path.join(__dirname, '../../generated-forms/sf10')
  }
};

// Ensure output directories exist
Object.values(FORM_TEMPLATES).forEach(template => {
  if (!fs.existsSync(template.outputDir)) {
    fs.mkdirSync(template.outputDir, { recursive: true });
  }
});

module.exports = (db) => {
  return {
    /**
     * Generate a form
     * @param {string} formType - Type of form (sf1, sf9, sf10)
     * @param {Object} data - Form data
     * @returns {Promise<Object>} Result with success status and file path
     */
    generateForm: async (formType, data) => {
      try {
        const template = FORM_TEMPLATES[formType];
        if (!template) {
          return { success: false, error: 'Invalid form type' };
        }

        // Validate required fields
        const { isValid, missingFields } = validateRequiredFields(data, template.requiredFields);
        if (!isValid) {
          return { 
            success: false, 
            error: `Missing required fields: ${missingFields.join(', ')}` 
          };
        }

        // Load the template workbook
        const workbook = new ExcelJS.Workbook();
        await workbook.xlsx.readFile(template.templatePath);

        // Get the first worksheet
        const worksheet = workbook.worksheets[0];
        if (!worksheet) {
          return { success: false, error: 'Invalid template format' };
        }

        // Fill in the form data
        // This is a simplified example - in a real app, you would map data to specific cells
        // based on the template structure
        this.fillFormData(worksheet, data);

        // Generate output filename
        const timestamp = new Date().toISOString().replace(/[^0-9]/g, '').slice(0, 14);
        const outputFilename = `${formType.toUpperCase()}_${data.lrn}_${timestamp}.xlsx`;
        const outputPath = path.join(template.outputDir, outputFilename);

        // Save the workbook
        await workbook.xlsx.writeFile(outputPath);

        // Log the generation in the database
        await this.logFormGeneration(formType, data.lrn, outputPath);

        return { 
          success: true, 
          filePath: outputPath,
          filename: outputFilename,
          timestamp: new Date().toISOString()
        };
      } catch (error) {
        console.error(`Error generating ${formType} form:`, error);
        return { 
          success: false, 
          error: `Failed to generate ${formType} form: ${error.message}` 
        };
      }
    },

    /**
     * Fill form data into the worksheet
     * @private
     */
    fillFormData: (worksheet, data) => {
      // This is a simplified example. In a real app, you would:
      // 1. Map form fields to specific cells in the Excel template
      // 2. Handle different data types (dates, numbers, text)
      // 3. Apply formatting as needed
      
      // Example: worksheet.getCell('A1').value = data.someField;
      
      // For now, we'll just log that we would fill the data
      console.log('Filling form with data:', data);
    },

    /**
     * Log form generation in the database
     * @private
     */
    logFormGeneration: async (formType, lrn, filePath) => {
      try {
        // In a real app, you would save this to a database
        const logEntry = {
          formType,
          lrn,
          filePath,
          generatedAt: new Date().toISOString(),
          generatedBy: 'system' // In a real app, this would be the user ID
        };
        
        console.log('Form generation logged:', logEntry);
        return true;
      } catch (error) {
        console.error('Error logging form generation:', error);
        return false;
      }
    },

    /**
     * Get form template configuration
     * @param {string} formType - Type of form
     * @returns {Object|null} Template configuration or null if not found
     */
    getFormTemplate: (formType) => {
      const template = FORM_TEMPLATES[formType];
      if (!template) return null;
      
      // Return a copy without the internal paths
      return {
        type: formType,
        requiredFields: [...template.requiredFields],
        hasTemplate: fs.existsSync(template.templatePath)
      };
    },

    /**
     * Get all available form templates
     * @returns {Array} List of available form templates
     */
    getAllTemplates: () => {
      return Object.entries(FORM_TEMPLATES).map(([type, config]) => ({
        type,
        requiredFields: [...config.requiredFields],
        hasTemplate: fs.existsSync(config.templatePath)
      }));
    }
  };
};
