<?php
/**
 * Database Connection Pool Manager
 */
class ConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $max_connections;
    private $min_connections;
    private $timeout;
    
    private function __construct($max = 5, $min = 2, $timeout = 10) {
        $this->max_connections = $max;
        $this->min_connections = $min;
        $this->timeout = $timeout;
        
        // Pre-allocate minimum connections
        for ($i = 0; $i < $this->min_connections; $i++) {
            $this->connections[] = $this->createConnection();
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($max = 5, $min = 2, $timeout = 10) {
        if (self::$instance === null) {
            self::$instance = new self($max, $min, $timeout);
        }
        return self::$instance;
    }
    
    /**
     * Get connection from pool
     */
    public function getConnection() {
        // Return available connection if exists
        if (!empty($this->connections)) {
            $conn = array_pop($this->connections);
            if ($this->isConnectionValid($conn)) {
                return $conn;
            }
        }
        
        // Create new connection if under limit
        if (count($this->connections) < $this->max_connections) {
            return $this->createConnection();
        }
        
        // Wait for available connection
        sleep(1);
        return $this->getConnection();
    }
    
    /**
     * Return connection to pool
     */
    public function releaseConnection($conn) {
        if ($this->isConnectionValid($conn)) {
            $this->connections[] = $conn;
        }
    }
    
    /**
     * Check if connection is valid
     */
    private function isConnectionValid($conn) {
        try {
            $conn->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Create new database connection
     */
    private function createConnection() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                DB_HOST,
                DB_PORT,
                DB_NAME
            );
            
            return new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => $this->timeout
                ]
            );
        } catch (PDOException $e) {
            error_log("Connection Pool Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Close all connections
     */
    public function closeAll() {
        $this->connections = [];
    }
}

// Initialize pool
$pool = ConnectionPool::getInstance(DB_POOL_MAX, DB_POOL_MIN, DB_TIMEOUT);
?>
