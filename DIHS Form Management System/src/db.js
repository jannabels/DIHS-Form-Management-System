// Database connection and query handler
const mysql = require('mysql2/promise');

// Create a connection pool
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost', 
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'systemdihs',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Export a simple query function that uses the pool
const query = async (sql, params) => {
  const connection = await pool.getConnection();
  try {
    const [rows] = await connection.query(sql, params);
    return [rows];
  } finally {
    connection.release();
  }
};

// Export getConnection for transactions
const getConnection = async () => {
  const connection = await pool.getConnection();
  
  // Add transaction methods
  connection.beginTransaction = connection.beginTransaction || 
    (() => new Promise((resolve, reject) => {
      connection.query('START TRANSACTION', (err) => {
        if (err) reject(err);
        else resolve();
      });
    }));
    
  connection.commit = connection.commit || 
    (() => new Promise((resolve, reject) => {
      connection.query('COMMIT', (err) => {
        if (err) reject(err);
        else resolve();
      });
    }));
    
  connection.rollback = connection.rollback || 
    (() => new Promise((resolve, reject) => {
      connection.query('ROLLBACK', (err) => {
        if (err) reject(err);
        else resolve();
      });
    }));
    
  return connection;
};

module.exports = {
  query,
  getConnection,
  // Export pool for direct access if needed
  pool
};
