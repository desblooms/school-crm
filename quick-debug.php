<?php
// Quick Debug Script - Check what's causing the 500 errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>School CRM Quick Debug</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Basic PHP
echo "<h2>1. PHP Status</h2>";
echo "<p>✅ PHP Version: " . PHP_VERSION . "</p>";

// Test 2: Config file
echo "<h2>2. Configuration</h2>";
try {
    require_once 'config/config.php';
    echo "<p>✅ Config file loaded successfully</p>";
    echo "<p>Environment: " . ENVIRONMENT . "</p>";
    echo "<p>Debug Mode: " . (DEBUG_MODE ? 'ON' : 'OFF') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Config error: " . $e->getMessage() . "</p>";
    exit();
}

// Test 3: Database connection
echo "<h2>3. Database Connection</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test basic query
    $stmt = $db->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "<p>MySQL Version: " . $result['version'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 4: Required directories
echo "<h2>4. Directory Permissions</h2>";
$dirs = ['uploads/', 'invoices/', 'logs/', 'backups/'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p>✅ $dir exists</p>";
        if (is_writable($dir)) {
            echo "<p>✅ $dir is writable</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $dir is not writable</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $dir does not exist</p>";
    }
}

// Test 5: Auth system
echo "<h2>5. Authentication System</h2>";
try {
    require_once 'includes/auth.php';
    echo "<p>✅ Auth system loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Auth system error: " . $e->getMessage() . "</p>";
}

// Test 6: Session
echo "<h2>6. Session Status</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>✅ Session is active</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Session not active</p>";
}

echo "<hr>";
echo "<p><strong>If you see any red ❌ errors above, those need to be fixed first.</strong></p>";
echo "<p><a href='index.php'>Test Main Page</a> | <a href='login.php'>Test Login Page</a></p>";
?>