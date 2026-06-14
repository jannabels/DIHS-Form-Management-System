function getStudentStatus(conn, lrn, passing_grade = 75) {
    let status = 'Regular';

    try {
        // Check if student_grades table exists
        const table_check = conn.query(
            "SELECT COUNT(*) as cnt FROM information_schema.tables " +
            "WHERE table_schema = DATABASE() AND table_name = 'student_grades'"
        );
        
        if (!table_check) return status;
        
        const table_row = table_check.fetch_assoc();
        table_check.free();
        
        if (!table_row || table_row.cnt == 0) {
            return status;
        }

        // Query the student_grades table
        const sql = "SELECT grade FROM student_grades WHERE lrn = ? AND grade IS NOT NULL AND grade != ''";
        const stmt = conn.prepare(sql);
        if (!stmt) return status;
        
        stmt.bind_param("s", lrn);
        stmt.execute();
        const result = stmt.get_result();
        
        while (row = result.fetch_assoc()) {
            if (parseFloat(row.grade) < passing_grade) {
                status = 'Irregular';
                break;
            }
        }
        
        result.free();
        stmt.close();
        
    } catch (e) {
        console.error("Error in getStudentStatus:", e);
    }
    
    return status;
}

module.exports = { getStudentStatus };
