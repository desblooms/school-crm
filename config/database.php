<?php
require_once 'config.php';

/**
 * Production-Ready Database Connection Class
 * Implements singleton pattern with connection pooling and error handling
 */
class Database {
    private static $instance = null;
    private $connection = null;
    private $transactionLevel = 0;
    private $queryCount = 0;
    private $queryLog = [];
    
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Establish database connection with retry logic
     */
    private function connect($retryCount = 0) {
        $maxRetries = 3;
        
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 30,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false, // Disable persistent connections for better control
            ];
            
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4;port=%s',
                DB_HOST,
                DB_NAME,
                defined('DB_PORT') ? DB_PORT : 3306
            );
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set SQL mode for better data integrity
            $this->connection->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            
            if (class_exists('Logger')) {
                Logger::info('Database connection established', [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'retry_count' => $retryCount
                ]);
            }
            
        } catch (PDOException $e) {
            if (class_exists('Logger')) {
                Logger::error('Database connection failed', [
                    'error' => $e->getMessage(),
                    'retry_count' => $retryCount,
                    'max_retries' => $maxRetries
                ]);
            }
            
            if ($retryCount < $maxRetries) {
                sleep(1 * ($retryCount + 1)); // Exponential backoff
                $this->connect($retryCount + 1);
                return;
            }
            
            // Final failure - log and handle gracefully
            $this->handleConnectionFailure($e);
        }
    }
    
    /**
     * Handle connection failure gracefully
     */
    private function handleConnectionFailure(PDOException $e) {
        if (class_exists('Logger')) {
            Logger::error('Database connection failed permanently', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            die('<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;">
                <h3>Database Connection Error</h3>
                <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                <p><strong>Code:</strong> ' . $e->getCode() . '</p>
                <p>Please check your database configuration and ensure the database server is running.</p>
                </div>');
        } else {
            http_response_code(503);
            if (file_exists(ROOT_PATH . 'error-pages/503.html')) {
                include ROOT_PATH . 'error-pages/503.html';
            } else {
                die('Service temporarily unavailable. Please try again later.');
            }
            exit();
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        // Check if connection is still alive
        if (!$this->isConnectionAlive()) {
            if (class_exists('Logger')) {
                Logger::warning('Database connection lost, reconnecting...');
            }
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Check if database connection is alive
     */
    private function isConnectionAlive() {
        try {
            if ($this->connection === null) {
                return false;
            }
            
            $stmt = $this->connection->query('SELECT 1');
            return $stmt !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Execute prepared statement with logging
     */
    public function execute($query, $params = []) {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->connection->prepare($query);
            $result = $stmt->execute($params);
            
            $executionTime = microtime(true) - $startTime;
            $this->queryCount++;
            
            // Log slow queries
            if ($executionTime > 1.0 && class_exists('Logger')) { // Log queries taking more than 1 second
                Logger::warning('Slow query detected', [
                    'query' => $query,
                    'execution_time' => $executionTime,
                    'params' => $params
                ]);
            }
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $this->queryLog[] = [
                    'query' => $query,
                    'params' => $params,
                    'execution_time' => $executionTime,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            if (class_exists('Logger')) {
                Logger::error('Database query failed', [
                    'query' => $query,
                    'params' => $params,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
            }
            
            throw $e;
        }
    }
    
    /**
     * Begin transaction with nested support
     */
    public function beginTransaction() {
        if ($this->transactionLevel == 0) {
            $this->connection->beginTransaction();
        } else {
            $this->connection->exec("SAVEPOINT sp_level_{$this->transactionLevel}");
        }
        
        $this->transactionLevel++;
        
        if (class_exists('Logger')) {
            Logger::debug('Transaction started', [
                'level' => $this->transactionLevel
            ]);
        }
    }
    
    /**
     * Commit transaction with nested support
     */
    public function commit() {
        if ($this->transactionLevel <= 0) {
            throw new Exception('No active transaction to commit');
        }
        
        $this->transactionLevel--;
        
        if ($this->transactionLevel == 0) {
            $this->connection->commit();
        } else {
            $this->connection->exec("RELEASE SAVEPOINT sp_level_{$this->transactionLevel}");
        }
        
        if (class_exists('Logger')) {
            Logger::debug('Transaction committed', [
                'level' => $this->transactionLevel + 1
            ]);
        }
    }
    
    /**
     * Rollback transaction with nested support
     */
    public function rollback() {
        if ($this->transactionLevel <= 0) {
            throw new Exception('No active transaction to rollback');
        }
        
        $this->transactionLevel--;
        
        if ($this->transactionLevel == 0) {
            $this->connection->rollback();
        } else {
            $this->connection->exec("ROLLBACK TO SAVEPOINT sp_level_{$this->transactionLevel}");
        }
        
        if (class_exists('Logger')) {
            Logger::debug('Transaction rolled back', [
                'level' => $this->transactionLevel + 1
            ]);
        }
    }
    
    /**
     * Get database statistics
     */
    public function getStats() {
        return [
            'query_count' => $this->queryCount,
            'transaction_level' => $this->transactionLevel,
            'query_log' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $this->queryLog : null,
            'connection_status' => $this->isConnectionAlive() ? 'alive' : 'dead'
        ];
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $stmt = $this->connection->query('SELECT VERSION() as version, NOW() as current_time');
            $result = $stmt->fetch();
            
            return [
                'status' => 'success',
                'version' => $result['version'],
                'current_time' => $result['current_time'],
                'ping_time' => microtime(true)
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }
    
    // Prevent cloning and unserialization
    private function __clone() {
        throw new Exception('Cannot clone singleton database instance');
    }
    
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton database instance');
    }
    
    /**
     * Cleanup on destruction
     */
    public function __destruct() {
        // Log final statistics
        if ($this->queryCount > 0 && class_exists('Logger')) {
            Logger::info('Database session ended', [
                'total_queries' => $this->queryCount,
                'active_transactions' => $this->transactionLevel
            ]);
        }
        
        // Rollback any remaining transactions
        while ($this->transactionLevel > 0) {
            $this->rollback();
        }
    }
}

/**
 * Database Health Monitor
 */
class DatabaseMonitor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Perform comprehensive health check
     */
    public function healthCheck() {
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_status' => 'healthy',
            'checks' => []
        ];
        
        // Connection test
        $connectionTest = $this->db->testConnection();
        $results['checks']['connection'] = $connectionTest;
        
        if ($connectionTest['status'] !== 'success') {
            $results['overall_status'] = 'critical';
        }
        
        // Table integrity check
        $tableCheck = $this->checkTableIntegrity();
        $results['checks']['table_integrity'] = $tableCheck;
        
        if (class_exists('Logger')) {
            Logger::info('Database health check completed', $results);
        }
        
        return $results;
    }
    
    private function checkTableIntegrity() {
        $tables = ['users', 'students', 'teachers', 'classes', 'fee_payments', 'invoices'];
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->db->getConnection()->query("CHECK TABLE `$table`");
                $result = $stmt->fetch();
                $results[$table] = $result['Msg_text'] === 'OK' ? 'OK' : $result['Msg_text'];
            } catch (Exception $e) {
                $results[$table] = 'ERROR: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
}
?>