<?php
// Production-Ready Configuration System
// ==================================

// Environment Detection
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'u345095192_school');
define('DB_USER', getenv('DB_USER') ?: 'u345095192_school');
define('DB_PASS', getenv('DB_PASS') ?: 'Datb@788');

// Application Configuration
define('APP_NAME', getenv('APP_NAME') ?: 'School CRM');
define('APP_VERSION', '1.2.0');
define('BASE_URL', rtrim(getenv('BASE_URL') ?: 'https://school.desblooms.com/', '/') . '/');

// Security Configuration
define('SECURITY_KEY', getenv('SECURITY_KEY') ?: 'school_crm_2024_secure_key_change_in_production');
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('SESSION_TIMEOUT', 86400); // 24 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 1800); // 30 minutes

// File Paths
define('ROOT_PATH', __DIR__ . '/../');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('INVOICE_PATH', ROOT_PATH . 'invoices/');
define('LOG_PATH', ROOT_PATH . 'logs/');
define('BACKUP_PATH', ROOT_PATH . 'backups/');

// Performance Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour
define('DB_QUERY_CACHE', true);

// Email Configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@school.desblooms.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: APP_NAME);

// File Upload Limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']);

// Timezone Configuration
date_default_timezone_set(getenv('TIMEZONE') ?: 'Asia/Kolkata');

// Error Reporting Configuration
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Custom Error Log Path
ini_set('error_log', LOG_PATH . 'php_errors.log');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', !DEBUG_MODE ? 1 : 0); // HTTPS only in production

// Start Session with Security
session_start([
    'cookie_lifetime' => SESSION_TIMEOUT,
    'cookie_secure' => !DEBUG_MODE,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Create Required Directories
$required_dirs = [UPLOAD_PATH, INVOICE_PATH, LOG_PATH, BACKUP_PATH];
foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Failed to create directory: $dir");
        }
    }
}

// Security Headers (for production)
if (!DEBUG_MODE) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com; img-src \'self\' data: https:; font-src \'self\' https://cdnjs.cloudflare.com;');
}

// Global Exception Handler
set_exception_handler('globalExceptionHandler');

// Global Error Handler  
set_error_handler('globalErrorHandler');

// Register Shutdown Function for Fatal Errors
register_shutdown_function('shutdownHandler');

/**
 * Global Exception Handler
 */
function globalExceptionHandler($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    if (DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>Uncaught Exception</h3>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre><strong>Stack Trace:</strong>\n" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        http_response_code(500);
        include ROOT_PATH . 'error-pages/500.html';
    }
    exit();
}

/**
 * Global Error Handler
 */
function globalErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error_types = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice'
    ];
    
    $error_type = $error_types[$errno] ?? 'Unknown Error';
    $log_message = "$error_type: $errstr in $errfile on line $errline";
    
    error_log($log_message);
    
    if (DEBUG_MODE && ($errno == E_ERROR || $errno == E_USER_ERROR)) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>$error_type</h3>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($errstr) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($errfile) . "</p>";
        echo "<p><strong>Line:</strong> $errline</p>";
        echo "</div>";
    }
    
    if ($errno == E_ERROR || $errno == E_USER_ERROR) {
        exit();
    }
    
    return true;
}

/**
 * Shutdown Handler for Fatal Errors
 */
function shutdownHandler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        
        if (!DEBUG_MODE) {
            if (!headers_sent()) {
                http_response_code(500);
                include ROOT_PATH . 'error-pages/500.html';
            }
        }
    }
}

/**
 * Application Logger
 */
class Logger {
    public static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $level: $message";
        
        if (!empty($context)) {
            $log_entry .= ' Context: ' . json_encode($context);
        }
        
        $log_entry .= PHP_EOL;
        
        $log_file = LOG_PATH . 'app.log';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    public static function debug($message, $context = []) {
        if (DEBUG_MODE) {
            self::log('DEBUG', $message, $context);
        }
    }
}

/**
 * Security Helper Functions
 */
class Security {
    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize Input
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate Email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Rate Limiting
     */
    public static function checkRateLimit($identifier, $max_attempts = 10, $window = 3600) {
        $cache_key = "rate_limit_$identifier";
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = ['count' => 0, 'window_start' => time()];
        }
        
        $data = $_SESSION[$cache_key];
        
        if (time() - $data['window_start'] > $window) {
            $_SESSION[$cache_key] = ['count' => 1, 'window_start' => time()];
            return true;
        }
        
        if ($data['count'] >= $max_attempts) {
            return false;
        }
        
        $_SESSION[$cache_key]['count']++;
        return true;
    }
}

/**
 * Utility Functions
 */
class Utils {
    /**
     * Format Currency
     */
    public static function formatCurrency($amount, $currency = '₹') {
        return $currency . number_format($amount, 2);
    }
    
    /**
     * Format Date
     */
    public static function formatDate($date, $format = 'M j, Y') {
        return date($format, strtotime($date));
    }
    
    /**
     * Generate UUID
     */
    public static function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Clean Phone Number
     */
    public static function cleanPhoneNumber($phone) {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}

// Initialize Application
Logger::info('Application initialized', [
    'environment' => ENVIRONMENT,
    'version' => APP_VERSION,
    'debug_mode' => DEBUG_MODE
]);

?>