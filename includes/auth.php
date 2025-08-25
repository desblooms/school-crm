<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($email, $password) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Rate limiting check
            if (!Security::checkRateLimit("login_$ip", MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_DURATION)) {
                SecurityManager::logSecurityEvent('Login rate limit exceeded', [
                    'email' => $email,
                    'ip' => $ip
                ]);
                return [
                    'success' => false, 
                    'message' => 'Too many failed login attempts. Please try again in ' . (LOGIN_LOCKOUT_DURATION / 60) . ' minutes.'
                ];
            }
            
            // Validate input
            $validation = SecurityManager::validateInput(['email' => $email, 'password' => $password], [
                'email' => ['required', 'email'],
                'password' => ['required', 'min_length' => 6]
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'message' => 'Invalid input data'];
            }
            
            $stmt = $this->db->prepare("
                SELECT id, name, email, password, role, status, failed_login_attempts, locked_until 
                FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                SecurityManager::logSecurityEvent('Login attempt with non-existent email', [
                    'email' => $email,
                    'ip' => $ip
                ]);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check if user is temporarily locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                SecurityManager::logSecurityEvent('Login attempt on locked account', [
                    'user_id' => $user['id'],
                    'email' => $email,
                    'ip' => $ip
                ]);
                return [
                    'success' => false, 
                    'message' => 'Account temporarily locked. Please try again later.'
                ];
            }
            
            if (password_verify($password, $user['password'])) {
                // Successful login - reset failed attempts
                $this->resetFailedAttempts($user['id']);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $this->logActivity($user['id'], 'login', 'User logged in successfully');
                
                Logger::info('User logged in', [
                    'user_id' => $user['id'],
                    'email' => $email,
                    'role' => $user['role'],
                    'ip' => $ip
                ]);
                
                return ['success' => true, 'message' => 'Login successful'];
            } else {
                // Failed login - increment attempts
                $this->handleFailedLogin($user['id']);
                
                SecurityManager::logSecurityEvent('Failed login attempt', [
                    'user_id' => $user['id'],
                    'email' => $email,
                    'ip' => $ip,
                    'attempts' => $user['failed_login_attempts'] + 1
                ]);
                
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
        } catch (Exception $e) {
            Logger::error('Login error', [
                'error' => $e->getMessage(),
                'email' => $email ?? 'unknown'
            ]);
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    private function resetFailedAttempts($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = 0, locked_until = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            Logger::error('Failed to reset login attempts', ['error' => $e->getMessage()]);
        }
    }
    
    private function handleFailedLogin($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1,
                    locked_until = CASE 
                        WHEN failed_login_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                        ELSE locked_until 
                    END
                WHERE id = ?
            ");
            $stmt->execute([MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_DURATION, $userId]);
        } catch (Exception $e) {
            Logger::error('Failed to handle failed login', ['error' => $e->getMessage()]);
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    public function hasPermission($permission) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        $permissions = [
            'admin' => ['*'],
            'accountant' => ['view_fees', 'manage_fees', 'view_reports', 'manage_invoices'],
            'teacher' => ['view_students', 'manage_attendance', 'view_classes'],
            'student' => ['view_profile', 'view_fees', 'view_attendance'],
            'parent' => ['view_profile', 'view_fees', 'view_attendance']
        ];
        
        $userRole = $_SESSION['user_role'];
        
        if (in_array('*', $permissions[$userRole] ?? [])) {
            return true;
        }
        
        return in_array($permission, $permissions[$userRole] ?? []);
    }
    
    private function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied');
        }
    }
    
    public function requirePermission($permission) {
        $this->requireLogin();
        if (!$this->hasPermission($permission)) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied');
        }
    }
}

function requireLogin() {
    $auth = new Auth();
    $auth->requireLogin();
}

function requireRole($role) {
    $auth = new Auth();
    $auth->requireRole($role);
}

function requirePermission($permission) {
    $auth = new Auth();
    $auth->requirePermission($permission);
}
?>