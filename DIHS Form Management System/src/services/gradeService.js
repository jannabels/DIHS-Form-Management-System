const db = require('../db');

/**
 * Fetches grades for a specific student
 * @param {string} lrn - Student's LRN
 * @param {string} [semester='1st Semester'] - Semester (e.g., '1st Semester', '2nd Semester')
 * @returns {Promise<Object>} Grades data
 */
const fetchStudentGrades = async (lrn, semester = '1st Semester') => {
  try {
    // Get student's current grade level and track
    const [gradeResults] = await db.query(
      `SELECT sec.grade_level, sec.track 
       FROM sf9 sf
       JOIN section sec ON sf.section = sec.class_name
       WHERE sf.LRN = ?
       LIMIT 1`,
      [lrn]
    );

    if (gradeResults.length === 0) {
      throw new Error("Student's grade level not found");
    }

    const { grade_level: gradeLevel, track } = gradeResults[0];
    const semesterNum = semester.includes('1') ? '1' : '2';

    // Get subjects for the student's grade level and track
    const [subjects] = await db.query(
      `SELECT subject_code, subject_name 
       FROM curriculum 
       WHERE grade_level = ? 
       AND track = ? 
       AND LOWER(semester) LIKE ?`,
      [gradeLevel, track, `%${semesterNum}%`]
    );

    // Get existing grades for the student
    const [grades] = await db.query(
      `SELECT * FROM student_grades 
       WHERE lrn = ? AND semester = ?`,
      [lrn, semester]
    );

    // Format the response
    const formattedGrades = {
      studentInfo: { lrn, gradeLevel, track, semester },
      subjects: {},
      finalGrades: {}
    };

    // Organize grades by subject
    subjects.forEach(subject => {
      const subjectKey = subject.subject_code.toLowerCase().replace(/[ -]/g, '_');
      const subjectGrades = grades.filter(g => g.subject_code === subject.subject_code);
      
      formattedGrades.subjects[subjectKey] = {
        code: subject.subject_code,
        name: subject.subject_name,
        grades: {}
      };

      // Add grades for each quarter
      ['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'].forEach(quarter => {
        const grade = subjectGrades.find(g => g.quarter === quarter);
        formattedGrades.subjects[subjectKey].grades[quarter] = grade ? grade.grade : null;
      });

      // Calculate final grade if all quarters are present
      const allGrades = Object.values(formattedGrades.subjects[subjectKey].grades);
      if (allGrades.every(g => g !== null)) {
        const average = allGrades.reduce((sum, g) => sum + parseFloat(g), 0) / allGrades.length;
        formattedGrades.finalGrades[subjectKey] = average.toFixed(2);
      }
    });

    return { success: true, data: formattedGrades };
  } catch (error) {
    console.error('Error fetching grades:', error);
    return { 
      success: false, 
      error: error.message || 'Failed to fetch grades',
      details: process.env.NODE_ENV === 'development' ? error.stack : undefined
    };
  }
};

/**
 * Saves or updates student grades
 * @param {string} lrn - Student's LRN
 * @param {string} subjectCode - Subject code
 * @param {string} quarter - Quarter (e.g., '1st Quarter')
 * @param {number} grade - Numeric grade
 * @param {string} semester - Semester (e.g., '1st Semester')
 * @returns {Promise<Object>} Save operation result
 */
const saveGrade = async (lrn, subjectCode, quarter, grade, semester) => {
  const connection = await db.getConnection();
  
  try {
    await connection.beginTransaction();

    // Check if grade already exists
    const [existing] = await connection.query(
      `SELECT id FROM student_grades 
       WHERE lrn = ? AND subject_code = ? AND quarter = ? AND semester = ?`,
      [lrn, subjectCode, quarter, semester]
    );

    if (existing.length > 0) {
      // Update existing grade
      await connection.query(
        `UPDATE student_grades 
         SET grade = ?, updated_at = NOW() 
         WHERE id = ?`,
        [grade, existing[0].id]
      );
    } else {
      // Insert new grade
      await connection.query(
        `INSERT INTO student_grades 
         (lrn, subject_code, quarter, semester, grade, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())`,
        [lrn, subjectCode, quarter, semester, grade]
      );
    }

    await connection.commit();
    return { success: true, message: 'Grade saved successfully' };
  } catch (error) {
    await connection.rollback();
    console.error('Error saving grade:', error);
    return { 
      success: false, 
      error: 'Failed to save grade',
      details: error.message 
    };
  } finally {
    connection.release();
  }
};

module.exports = {
  fetchStudentGrades,
  saveGrade
};