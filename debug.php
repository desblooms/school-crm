<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Information</h1>";

echo "<h2>PHP Version</h2>";
echo phpversion();

echo "<h2>Testing Config</h2>";
try {
    require_once 'config/config.php';
    echo "✅ Config loaded successfully<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "DB_USER: " . DB_USER . "<br>";
    echo "APP_NAME: " . APP_NAME . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

echo "<h2>Testing Database Connection</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected successfully<br>";
    
    // Test a simple query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Database query test passed: " . $result['test'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Testing Auth</h2>";
try {
    require_once 'includes/auth.php';
    echo "✅ Auth loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Auth error: " . $e->getMessage() . "<br>";
}

echo "<h2>Testing File Permissions</h2>";
$paths = [
    'config/',
    'includes/',
    'uploads/',
    'invoices/',
    'assets/',
    'classes/'
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "✅ {$path} exists<br>";
        if (is_readable($path)) {
            echo "✅ {$path} is readable<br>";
        } else {
            echo "❌ {$path} is not readable<br>";
        }
    } else {
        echo "❌ {$path} does not exist<br>";
    }
}

echo "<h2>Session Information</h2>";
echo "Session status: " . session_status() . "<br>";
if (isset($_SESSION)) {
    echo "Session variables: " . print_r($_SESSION, true) . "<br>";
} else {
    echo "No session variables<br>";
}

echo "<h2>Server Information</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<h2>Error Logs</h2>";
echo "Error log location: " . ini_get('error_log') . "<br>";
echo "Log errors: " . (ini_get('log_errors') ? 'Yes' : 'No') . "<br>";
?>