<?php
class AuditLog {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function log($userId, $action, $tableName, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
            
            $oldValues = $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
            $newValues = $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;
            
            $stmt = $this->conn->prepare("
                INSERT INTO audit_logs 
                (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                error_log("Failed to prepare statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param(
                "ssssssss",
                $userId,
                $action,
                $tableName,
                $recordId,
                $oldValues,
                $newValues,
                $ipAddress,
                $userAgent
            );
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Failed to execute statement: " . $stmt->error);
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in AuditLog::log: " . $e->getMessage());
            return false;
        }
    }

    public function getLogs($filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        $types = '';
        
        // Build WHERE conditions
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 's';
        }
        
        if (!empty($filters['action'])) {
            $actions = is_array($filters['action']) ? $filters['action'] : explode(',', $filters['action']);
            $placeholders = implode(',', array_fill(0, count($actions), '?'));
            $where[] = "al.action IN ($placeholders)";
            $params = array_merge($params, $actions);
            $types .= str_repeat('s', count($actions));
        }
        
        if (!empty($filters['table_name'])) {
            $where[] = "al.table_name = ?";
            $params[] = $filters['table_name'];
            $types .= 's';
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "al.created_at >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
            $types .= 's';
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "al.created_at <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
            $types .= 's';
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // First, get the total count
        $countQuery = "SELECT COUNT(*) as total FROM audit_logs al $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        
        if ($countStmt === false) {
            error_log("Error in count query: " . $this->conn->error);
            error_log("Query: " . $countQuery);
            return ['data' => [], 'total' => 0, 'total_pages' => 1];
        }
        
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        
        $countStmt->execute();
        $result = $countStmt->get_result();
        $total = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Now get the paginated results
        $query = "
            SELECT al.*, a.`First Name` as first_name, a.`Last Name` as last_name, a.`Role` as user_role
            FROM audit_logs al
            LEFT JOIN accounts a ON al.user_id = a.Username
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt === false) {
            error_log("Error in query preparation: " . $this->conn->error);
            error_log("Query: " . $query);
            return ['data' => [], 'total' => $total, 'total_pages' => ceil($total / $perPage)];
        }
        
        // Add pagination parameters
        $paginationTypes = $types . 'ii';
        $paginationParams = $params;
        $paginationParams[] = (int)$perPage;
        $paginationParams[] = (int)$offset;
        
        if (!empty($paginationParams)) {
            $stmt->bind_param($paginationTypes, ...$paginationParams);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return [
            'data' => $result->fetch_all(MYSQLI_ASSOC),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
}