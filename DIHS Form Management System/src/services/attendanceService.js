// For testing purposes, we'll use a relative path
const db = require('../db');

/**
 * Fetches attendance data for a specific date
 * @param {string} date - Date in YYYY-MM-DD format
 * @returns {Promise<Object>} Attendance data grouped by LRN
 */
const getAttendanceByDate = async (date) => {
  try {
    const [rows] = await db.query(
      'SELECT LRN, present, absent, tardy FROM daily_attendance WHERE date = ?',
      [date]
    );

    const attendanceData = {};
    rows.forEach(row => {
      attendanceData[row.LRN] = {
        present: Boolean(row.present),
        absent: Boolean(row.absent),
        tardy: Boolean(row.tardy)
      };
    });

    return { success: true, data: attendanceData };
  } catch (error) {
    console.error('Error fetching attendance:', error);
    return { 
      success: false, 
      error: 'Failed to fetch attendance data',
      details: error.message 
    };
  }
};

/**
 * Saves attendance data for multiple students
 * @param {string} date - Date in YYYY-MM-DD format
 * @param {Array} attendanceData - Array of attendance records
 * @returns {Promise<Object>} Save operation result
 */
const saveAttendance = async (date, attendanceData) => {
  const connection = await db.getConnection();
  
  try {
    await connection.beginTransaction();
    
    // Delete existing records for the date
    await connection.query('DELETE FROM daily_attendance WHERE date = ?', [date]);
    
    // Insert new records
    for (const record of attendanceData) {
      await connection.query(
        `INSERT INTO daily_attendance 
         (date, LRN, present, absent, tardy, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())`,
        [
          date,
          record.lrn,
          record.present || false,
          record.absent || false,
          record.tardy || false
        ]
      );
    }
    
    await connection.commit();
    return { success: true, message: 'Attendance saved successfully' };
  } catch (error) {
    await connection.rollback();
    console.error('Error saving attendance:', error);
    return { 
      success: false, 
      error: 'Failed to save attendance',
      details: error.message 
    };
  } finally {
    connection.release();
  }
};

module.exports = {
  getAttendanceByDate,
  saveAttendance
};
