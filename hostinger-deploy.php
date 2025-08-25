<?php
/**
 * Hostinger Deployment Helper
 * Run this file after uploading to check deployment status
 */

// Enable error reporting for this diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Hostinger Deployment Check</title>";
echo "<style>body{font-family:Arial;margin:40px;} .success{color:green;} .error{color:red;} .warning{color:orange;} .info{color:blue;}</style></head><body>";
echo "<h1>School CRM Deployment Check</h1>";

// Check PHP version
echo "<h2>PHP Environment</h2>";
echo "<div class='info'>PHP Version: " . phpversion() . "</div>";

if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo "<div class='success'>✅ PHP version is compatible</div>";
} else {
    echo "<div class='error'>❌ PHP version too old. Requires PHP 7.4+</div>";
}

// Check required extensions
echo "<h2>PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'curl', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ $ext extension loaded</div>";
    } else {
        echo "<div class='error'>❌ $ext extension missing</div>";
    }
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$check_dirs = ['uploads', 'invoices', 'logs', 'backups'];
foreach ($check_dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<div class='success'>✅ Created directory: $dir</div>";
        } else {
            echo "<div class='error'>❌ Failed to create directory: $dir</div>";
        }
    } else {
        echo "<div class='success'>✅ Directory exists: $dir</div>";
    }
    
    if (is_writable($dir)) {
        echo "<div class='success'>✅ Directory writable: $dir</div>";
    } else {
        echo "<div class='error'>❌ Directory not writable: $dir (chmod 755 needed)</div>";
    }
}

// Test configuration loading
echo "<h2>Configuration Test</h2>";
try {
    require_once 'config/config.php';
    echo "<div class='success'>✅ Config loaded successfully</div>";
    echo "<div class='info'>Environment: " . ENVIRONMENT . "</div>";
    echo "<div class='info'>Debug Mode: " . (DEBUG_MODE ? 'ON' : 'OFF') . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Config error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Test database connection
echo "<h2>Database Connection</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<div class='success'>✅ Database connected</div>";
    echo "<div class='info'>Host: " . DB_HOST . "</div>";
    echo "<div class='info'>Database: " . DB_NAME . "</div>";
    
    // Test a simple query
    $stmt = $db->query("SELECT 1 as test");
    if ($stmt && $stmt->fetch()) {
        echo "<div class='success'>✅ Database query test passed</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='warning'>⚠️ Check your database credentials in config/config.php</div>";
}

// Check .htaccess
echo "<h2>.htaccess Configuration</h2>";
if (file_exists('.htaccess')) {
    echo "<div class='success'>✅ .htaccess file exists</div>";
} else {
    echo "<div class='warning'>⚠️ .htaccess file missing - may cause issues</div>";
}

// Check mod_rewrite
if (function_exists('apache_get_modules')) {
    if (in_array('mod_rewrite', apache_get_modules())) {
        echo "<div class='success'>✅ mod_rewrite enabled</div>";
    } else {
        echo "<div class='warning'>⚠️ mod_rewrite not detected</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Cannot detect mod_rewrite status</div>";
}

// Environment variables check
echo "<h2>Environment Variables</h2>";
$env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value !== false && !empty($value)) {
        echo "<div class='success'>✅ $var is set</div>";
    } else {
        echo "<div class='warning'>⚠️ $var not set (using default from config.php)</div>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<div class='info'>";
echo "1. If all checks pass, delete this file (hostinger-deploy.php) for security<br>";
echo "2. Update database credentials in config/config.php if needed<br>";
echo "3. Run the installation by visiting install.php<br>";
echo "4. Check error logs in logs/ directory if issues persist<br>";
echo "</div>";

echo "</body></html>";
?>