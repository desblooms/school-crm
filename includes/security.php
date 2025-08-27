<?php
/**
 * Comprehensive Security Layer for School CRM
 * Includes CSRF protection, input validation, and security utilities
 */

class SecurityManager {
    
    /**
     * Validate CSRF token from form submission
     */
    public static function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Security::verifyCSRFToken($token)) {
                Logger::warning('CSRF token validation failed', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
                
                http_response_code(403);
                die(json_encode(['error' => 'Invalid request. Please refresh and try again.']));
            }
        }
    }
    
    /**
     * Generate CSRF token input field
     */
    public static function csrfField() {
        $token = Security::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate and sanitize user input
     */
    public static function validateInput($data, $rules = []) {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            // Required field check
            if (in_array('required', $fieldRules) && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            if (!empty($value)) {
                // Email validation
                if (in_array('email', $fieldRules) && !Security::validateEmail($value)) {
                    $errors[$field] = 'Invalid email format';
                }
                
                // Minimum length
                if (isset($fieldRules['min_length']) && strlen($value) < $fieldRules['min_length']) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $fieldRules['min_length'] . ' characters';
                }
                
                // Maximum length
                if (isset($fieldRules['max_length']) && strlen($value) > $fieldRules['max_length']) {
                    $errors[$field] = ucfirst($field) . ' must not exceed ' . $fieldRules['max_length'] . ' characters';
                }
                
                // Numeric validation
                if (in_array('numeric', $fieldRules) && !is_numeric($value)) {
                    $errors[$field] = ucfirst($field) . ' must be a number';
                }
                
                // Phone validation
                if (in_array('phone', $fieldRules) && !preg_match('/^[+]?[0-9]{10,15}$/', Utils::cleanPhoneNumber($value))) {
                    $errors[$field] = 'Invalid phone number format';
                }
                
                // Date validation
                if (in_array('date', $fieldRules) && !self::validateDate($value)) {
                    $errors[$field] = 'Invalid date format';
                }
                
                // Amount validation
                if (in_array('amount', $fieldRules) && (!is_numeric($value) || $value < 0)) {
                    $errors[$field] = 'Invalid amount';
                }
            }
            
            // Sanitize value
            $sanitized[$field] = Security::sanitizeInput($value);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    /**
     * Validate date format
     */
    private static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Check user permissions
     */
    public static function checkPermission($permission) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        $permissions = self::getRolePermissions($_SESSION['user_role']);
        return in_array($permission, $permissions);
    }
    
    /**
     * Get permissions for user role
     */
    private static function getRolePermissions($role) {
        $permissions = [
            'admin' => [
                'view_students', 'add_students', 'edit_students', 'delete_students',
                'view_teachers', 'add_teachers', 'edit_teachers', 'delete_teachers',
                'view_accounts', 'manage_fees', 'view_reports', 'system_settings',
                'view_invoices', 'create_invoices', 'manage_expenses',
                'manage_payroll', 'backup_data', 'system_health'
            ],
            'teacher' => [
                'view_students', 'add_students', 'edit_students',
                'view_reports', 'manage_attendance', 'view_invoices'
            ],
            'accountant' => [
                'view_students', 'view_accounts', 'manage_fees',
                'view_invoices', 'create_invoices', 'manage_expenses',
                'view_reports'
            ],
            'student' => [
                'view_profile', 'view_invoices', 'make_payments'
            ]
        ];
        
        return $permissions[$role] ?? [];
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $context = []) {
        $securityContext = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        Logger::warning("Security Event: $event", array_merge($securityContext, $context));
    }
    
    /**
     * Check for suspicious activity
     */
    public static function detectSuspiciousActivity() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Check for common attack patterns
        $suspicious = false;
        $reasons = [];
        
        // SQL injection patterns
        $sqlPatterns = ['/union.*select/i', '/drop.*table/i', '/insert.*into/i', '/delete.*from/i'];
        $requestData = json_encode($_REQUEST);
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $requestData)) {
                $suspicious = true;
                $reasons[] = 'SQL injection attempt detected';
                break;
            }
        }
        
        // XSS patterns
        $xssPatterns = ['/<script/i', '/javascript:/i', '/onload=/i', '/onerror=/i'];
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $requestData)) {
                $suspicious = true;
                $reasons[] = 'XSS attempt detected';
                break;
            }
        }
        
        // Path traversal
        if (strpos($requestData, '../') !== false || strpos($requestData, '..\\') !== false) {
            $suspicious = true;
            $reasons[] = 'Path traversal attempt detected';
        }
        
        // Suspicious user agents
        $maliciousUA = ['bot', 'crawler', 'scanner', 'exploit', 'hack'];
        foreach ($maliciousUA as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $suspicious = true;
                $reasons[] = 'Suspicious user agent detected';
                break;
            }
        }
        
        if ($suspicious) {
            self::logSecurityEvent('Suspicious activity detected', [
                'reasons' => $reasons,
                'request_data' => $_REQUEST
            ]);
            
            // Implement countermeasures
            self::handleSuspiciousActivity($reasons);
        }
    }
    
    /**
     * Handle suspicious activity
     */
    private static function handleSuspiciousActivity($reasons) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Rate limiting
        if (!Security::checkRateLimit($ip, 5, 300)) { // Max 5 requests per 5 minutes
            http_response_code(429);
            die(json_encode(['error' => 'Too many requests. Please try again later.']));
        }
        
        // Log to security log
        $securityLog = LOG_PATH . 'security.log';
        $logEntry = date('Y-m-d H:i:s') . " - Suspicious activity from IP: $ip - Reasons: " . implode(', ', $reasons) . PHP_EOL;
        file_put_contents($securityLog, $logEntry, FILE_APPEND | LOCK_EX);
        
        // In production, you might want to:
        // - Block the IP temporarily
        // - Send alert emails to administrators
        // - Increase logging for this IP
    }
    
    /**
     * Secure file upload validation
     */
    public static function validateFileUpload($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file uploaded';
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File is too large';
                break;
            default:
                $errors[] = 'Unknown upload error';
                break;
        }
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds limit (' . formatBytes(MAX_FILE_SIZE) . ')';
        }
        
        // Check file type
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);
        
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check file extension
        $pathInfo = pathinfo($file['name']);
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        if (!in_array($extension, ALLOWED_FILE_TYPES)) {
            $errors[] = 'File extension not allowed';
        }
        
        // Scan for malicious content (basic check)
        $content = file_get_contents($file['tmp_name']);
        $maliciousPatterns = ['<?php', '<script', 'javascript:', 'vbscript:'];
        foreach ($maliciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $errors[] = 'File contains potentially malicious content';
                break;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName) {
        $pathInfo = pathinfo($originalName);
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        // Generate unique filename
        $filename = date('Y-m-d_') . 
                   uniqid() . '_' . 
                   preg_replace('/[^a-zA-Z0-9._-]/', '', $pathInfo['filename']);
        
        return $filename . '.' . $extension;
    }
    
    /**
     * Encrypt sensitive data
     */
    public static function encrypt($data) {
        $key = SECURITY_KEY;
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public static function decrypt($encryptedData) {
        $key = SECURITY_KEY;
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 1024,
            'time_cost' => 2,
            'threads' => 2
        ]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate strong password
     */
    public static function generatePassword($length = 12) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
    }
    
    /**
     * Initialize security middleware
     */
    public static function init() {
        // Check for suspicious activity
        self::detectSuspiciousActivity();
        
        // Session security
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
        } else if (time() - $_SESSION['CREATED'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['CREATED'] = time();
        }
    }
}

// Initialize security on every page load
SecurityManager::init();

/**
 * Helper function to require permission (renamed to avoid conflict with auth.php)
 */
function requireSecurityPermission($permission) {
    if (!SecurityManager::checkPermission($permission)) {
        SecurityManager::logSecurityEvent('Permission denied', [
            'required_permission' => $permission,
            'user_role' => $_SESSION['user_role'] ?? 'guest'
        ]);
        
        http_response_code(403);
        if (DEBUG_MODE) {
            die('<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;">
                <h3>Access Denied</h3>
                <p>You do not have permission to access this page.</p>
                <p>Required permission: ' . htmlspecialchars($permission) . '</p>
                </div>');
        } else {
            header('Location: ../403.php');
            exit();
        }
    }
}

/**
 * Helper function to format bytes
 */
function formatBytes($size, $precision = 2) {
    if ($size > 0) {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    } else {
        return '0 B';
    }
}

/**
 * Helper function to clean and validate phone number
 */
function validatePhoneNumber($phone) {
    $cleaned = Utils::cleanPhoneNumber($phone);
    return preg_match('/^[+]?[0-9]{10,15}$/', $cleaned) ? $cleaned : false;
}
?>