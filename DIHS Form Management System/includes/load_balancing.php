<?php
/**
 * Load Balancing and Stress Handling Configuration
 * This file contains functions to handle high traffic and distribute load
 */

// Enable output buffering with gzip compression
if (!ob_start("ob_gzhandler")) {
    ob_start();
}

// Set maximum execution time and memory limit
@ini_set('max_execution_time', 300); // 5 minutes
@ini_set('memory_limit', '512M');

// Configure session handling for better concurrency
@ini_set('session.use_strict_mode', 1);
@ini_set('session.cookie_httponly', 1);
@ini_set('session.cookie_secure', 1);
@ini_set('session.use_only_cookies', 1);
@ini_set('session.cookie_samesite', 'Lax');
@ini_set('session.gc_maxlifetime', 14400); // 4 hours

// Database connection pooling
class DBConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $connectionCount = 0;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        if (count($this->connections) > 0) {
            return array_pop($this->connections);
        }
        
        if ($this->connectionCount < $this->maxConnections) {
            $this->connectionCount++;
            $conn = new mysqli(
                'localhost',
                'root',
                '',
                'admindihs',
                null,
                '/tmp/mysql.sock' // Use socket for better performance
            );
            
            if ($conn->connect_error) {
                $this->connectionCount--;
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8mb4");
            $conn->query("SET SESSION sql_mode=''");
            
            return $conn;
        }
        
        // Wait for a connection to become available
        $start = microtime(true);
        $timeout = 5; // 5 seconds timeout
        
        while (count($this->connections) === 0 && (microtime(true) - $start) < $timeout) {
            usleep(100000); // Sleep for 100ms
        }
        
        if (count($this->connections) > 0) {
            return array_pop($this->connections);
        }
        
        throw new Exception("Database connection pool exhausted");
    }
    
    public function releaseConnection($conn) {
        if ($conn && $conn->ping()) {
            $this->connections[] = $conn;
        } else {
            $this->connectionCount--;
        }
    }
}

// Rate limiting to prevent abuse
class RateLimiter {
    private static $instance = null;
    private $rateLimit = 100; // Requests per minute
    private $ipLimit = 1000;  // Max requests per IP per minute
    private $cache = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function checkRateLimit() {
        $ip = $this->getClientIP();
        $now = time();
        $minute = (int)($now / 60);
        
        // Initialize cache for this minute if not exists
        if (!isset($this->cache[$minute])) {
            $this->cache = []; // Clear old data
            $this->cache[$minute] = [
                'total' => 0,
                'ips' => []
            ];
        }
        
        // Check global rate limit
        if ($this->cache[$minute]['total'] > $this->rateLimit) {
            $this->sendRateLimitHeaders($this->rateLimit, $this->cache[$minute]['total']);
            header('HTTP/1.1 429 Too Many Requests');
            exit('Rate limit exceeded. Please try again later.');
        }
        
        // Check IP rate limit
        if (isset($this->cache[$minute]['ips'][$ip]) && 
            $this->cache[$minute]['ips'][$ip] > $this->ipLimit) {
            $this->sendRateLimitHeaders($this->ipLimit, $this->cache[$minute]['ips'][$ip], true);
            header('HTTP/1.1 429 Too Many Requests');
            exit('Too many requests from your IP. Please try again later.');
        }
        
        // Increment counters
        $this->cache[$minute]['total']++;
        if (!isset($this->cache[$minute]['ips'][$ip])) {
            $this->cache[$minute]['ips'][$ip] = 0;
        }
        $this->cache[$minute]['ips'][$ip]++;
        
        // Set rate limit headers
        $this->sendRateLimitHeaders(
            $this->ipLimit,
            $this->cache[$minute]['ips'][$ip],
            true
        );
        
        return true;
    }
    
    private function sendRateLimitHeaders($limit, $used, $ipBased = false) {
        $remaining = max(0, $limit - $used);
        
        header("X-RateLimit-Limit: $limit");
        header("X-RateLimit-Remaining: $remaining");
        header("X-RateLimit-Reset: " . (time() + 60 - (time() % 60)));
        
        if ($ipBased) {
            header("X-RateLimit-IP: true");
        }
    }
    
    private function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        return $ip;
    }
}

// Initialize rate limiting
$rateLimiter = RateLimiter::getInstance();
$rateLimiter->checkRateLimit();

// Database query caching with TTL
function cachedQuery($query, $params = [], $ttl = 300) {
    $cacheKey = 'query_' . md5($query . serialize($params));
    
    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey, $success);
        if ($success) {
            return $cached;
        }
    }
    
    $db = DBConnectionPool::getInstance();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            
            array_unshift($bindParams, $types);
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $data, $ttl);
        }
        
        return $data;
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        $db->releaseConnection($conn);
    }
}

// Helper function for prepared statements
private function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Optimize database connection handling
function getDatabaseConnection() {
    return DBConnectionPool::getInstance()->getConnection();
}

// Release database connection
function releaseDatabaseConnection($conn) {
    DBConnectionPool::getInstance()->releaseConnection($conn);
}

// Session handling for high concurrency
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Use database sessions for better concurrency
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://127.0.0.1:6379');
        
        // Custom session ID generation
        ini_set('session.hash_function', 'sha256');
        ini_set('session.hash_bits_per_character', 5);
        
        // Start session with secure settings
        session_start([
            'cookie_lifetime' => 86400, // 24 hours
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'use_strict_mode' => true,
            'cookie_samesite' => 'Lax',
            'read_and_close' => false // Keep session open for write operations
        ]);
    }
}

// Initialize session handling
initSession();
?>
