-- Create the audit_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL COMMENT 'ID of the user who performed the action',
  `action` varchar(50) NOT NULL COMMENT 'Action performed (e.g., login, create, update, delete)',
  `table_name` varchar(50) DEFAULT NULL COMMENT 'Name of the affected database table',
  `record_id` varchar(50) DEFAULT NULL COMMENT 'ID of the affected record',
  `old_values` text DEFAULT NULL COMMENT 'JSON string of old values',
  `new_values` text DEFAULT NULL COMMENT 'JSON string of new values',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of the user',
  `user_agent` text DEFAULT NULL COMMENT 'User agent string',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the action was performed',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores audit trail of all significant actions in the system';

-- Insert some test data
INSERT INTO `audit_logs` (`user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`) VALUES
('admin', 'login', 'users', '1', NULL, '{"last_login": "2023-11-16 10:30:00"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'),
('admin', 'create', 'students', '1001', NULL, '{"first_name": "John", "last_name": "Doe", "grade": "10"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'),
('teacher1', 'update', 'grades', '501', '{"grade": "B"}', '{"grade": "A"}', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'),
('admin', 'delete', 'users', '42', '{"username": "olduser", "email": "old@example.com"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'),
('teacher2', 'failed_login', 'users', NULL, NULL, '{"attempted_username": "teacher2"}', '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36');

-- Add a foreign key to the accounts table if it exists
-- This is optional and depends on your database structure
-- ALTER TABLE `audit_logs`
-- ADD CONSTRAINT `fk_audit_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`Username`) ON DELETE SET NULL ON UPDATE CASCADE;
