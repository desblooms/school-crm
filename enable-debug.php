<?php
// Temporary script to enable debug mode and see errors
putenv('APP_ENV=development');

// Force debug mode
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', true);

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Debug Mode Enabled - Testing Main Pages</h1>";

echo "<h2>1. Testing Config</h2>";
try {
    require_once 'config/config.php';
    echo "<p>✅ Config loaded - Environment: " . ENVIRONMENT . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Config error: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Testing Database</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Testing Auth</h2>";
try {
    require_once 'includes/auth.php';
    echo "<p>✅ Auth loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Auth error: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Testing Security</h2>";
try {
    require_once 'includes/security.php';
    echo "<p>✅ Security loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Security error: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Testing Main Index Page</h2>";
echo "<p><a href='index.php?debug=1' target='_blank'>Test Index Page</a> (opens in new tab)</p>";

echo "<h2>6. Testing Login Page</h2>";
echo "<p><a href='login.php?debug=1' target='_blank'>Test Login Page</a> (opens in new tab)</p>";

echo "<hr>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>If you see errors above, fix them first</li>";
echo "<li>Click the test links to see what specific errors occur</li>";
echo "<li>Check the server error logs for more details</li>";
echo "</ol>";

// Also check if there are any database tables
echo "<h2>7. Database Tables Check</h2>";
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Found " . count($tables) . " tables: " . implode(', ', $tables) . "</p>";
    
    if (count($tables) == 0) {
        echo "<p style='color: red;'>❌ No database tables found! You need to run the installer.</p>";
        echo "<p><a href='install.php'>Run Database Installation</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Cannot check tables: " . $e->getMessage() . "</p>";
}
?>