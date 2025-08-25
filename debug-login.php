<?php
/**
 * Debug Login Issues - Find 500 error cause
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Debug</h1>";

// Test 1: Config loading
echo "<h2>1. Config Test</h2>";
try {
    require_once 'config/config.php';
    echo "✅ Config loaded<br>";
    echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 2: Session check
echo "<h2>2. Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅ Session started<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

// Test 3: Database connection
echo "<h2>3. Database Test</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected<br>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br>";
        
        // Check for admin user
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = 'admin@school.com'");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "Admin users found: " . $result['count'] . "<br>";
    } else {
        echo "❌ Users table missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Auth class loading
echo "<h2>4. Auth Class Test</h2>";
try {
    require_once 'includes/auth.php';
    if (class_exists('Auth')) {
        echo "✅ Auth class exists<br>";
        
        // Try to create Auth instance
        $auth = new Auth();
        echo "✅ Auth instance created<br>";
    } else {
        echo "❌ Auth class not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Auth error: " . $e->getMessage() . "<br>";
}

// Test 5: Security class loading
echo "<h2>5. Security Classes Test</h2>";
try {
    require_once 'includes/security.php';
    if (class_exists('SecurityManager')) {
        echo "✅ SecurityManager class exists<br>";
    } else {
        echo "❌ SecurityManager class not found<br>";
    }
    
    if (class_exists('Security')) {
        echo "✅ Security class exists<br>";
    } else {
        echo "❌ Security class not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Security error: " . $e->getMessage() . "<br>";
}

// Test 6: Simple login test
echo "<h2>6. Simple Login Test</h2>";
try {
    $test_email = 'admin@school.com';
    $test_password = 'admin123';
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found: " . $user['name'] . "<br>";
        if (password_verify($test_password, $user['password'])) {
            echo "✅ Password verification works<br>";
        } else {
            echo "❌ Password verification failed<br>";
            echo "Stored hash: " . substr($user['password'], 0, 20) . "...<br>";
        }
    } else {
        echo "❌ Admin user not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Login test error: " . $e->getMessage() . "<br>";
}

echo "<h2>Summary</h2>";
echo "<p><strong>Check the above tests for ❌ errors causing the 500 error.</strong></p>";
echo "<p><a href='simple-login.php'>Try Simple Login</a></p>";
echo "<p><a href='login.php'>Try Main Login</a></p>";
?>