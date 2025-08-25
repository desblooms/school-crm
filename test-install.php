<?php
/**
 * Installation Test Script
 * Test install.php functionality locally before deployment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Install.php Test Script</h1>";

// Test configuration loading
echo "<h2>1. Configuration Test</h2>";
try {
    require_once 'config/config.php';
    echo "✅ Config loaded successfully<br>";
    echo "Environment: " . ENVIRONMENT . "<br>";
    echo "Debug Mode: " . (DEBUG_MODE ? 'ON' : 'OFF') . "<br>";
    echo "DB Host: " . DB_HOST . "<br>";
    echo "DB Name: " . DB_NAME . "<br>";
    echo "DB User: " . DB_USER . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Database server connected<br>";
    
    // Test database creation
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database created/verified<br>";
    
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "✅ Database selected<br>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test SQL files
echo "<h2>3. SQL Files Test</h2>";
if (file_exists('database/tables.sql')) {
    echo "✅ tables.sql found<br>";
    $tables_sql = file_get_contents('database/tables.sql');
    echo "File size: " . strlen($tables_sql) . " bytes<br>";
    
    if (!empty($tables_sql)) {
        echo "✅ tables.sql not empty<br>";
        $statement_count = count(array_filter(explode(';', $tables_sql), 'trim'));
        echo "SQL statements: " . $statement_count . "<br>";
    } else {
        echo "❌ tables.sql is empty<br>";
    }
} else {
    echo "❌ tables.sql not found<br>";
}

if (file_exists('database/data.sql')) {
    echo "✅ data.sql found<br>";
    $data_sql = file_get_contents('database/data.sql');
    echo "File size: " . strlen($data_sql) . " bytes<br>";
} else {
    echo "❌ data.sql not found<br>";
}

// Test directory creation
echo "<h2>4. Directory Creation Test</h2>";
$dirs = ['uploads', 'invoices', 'logs', 'backups'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir<br>";
        } else {
            echo "❌ Failed to create: $dir<br>";
        }
    } else {
        echo "✅ Directory exists: $dir<br>";
    }
    
    if (is_writable($dir)) {
        echo "✅ Directory writable: $dir<br>";
    } else {
        echo "❌ Directory not writable: $dir<br>";
    }
}

// Test PHP extensions
echo "<h2>5. PHP Extensions Test</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'mbstring', 'openssl', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension missing<br>";
    }
}

// Test password hashing
echo "<h2>6. Password Hashing Test</h2>";
$test_password = 'admin123';
$hashed = password_hash($test_password, PASSWORD_DEFAULT);
if ($hashed && password_verify($test_password, $hashed)) {
    echo "✅ Password hashing works<br>";
    echo "Hash length: " . strlen($hashed) . "<br>";
} else {
    echo "❌ Password hashing failed<br>";
}

echo "<h2>Summary</h2>";
echo "<p><strong>If all tests show ✅, your install.php should work properly on Hostinger.</strong></p>";
echo "<p><strong>Fix any ❌ issues before uploading to production.</strong></p>";
echo "<p><a href='install.php'>Run Actual Installation</a></p>";
?>