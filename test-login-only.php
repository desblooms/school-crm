<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Page Test</h1>";

try {
    require_once 'config/config.php';
    echo "<p>✅ Config loaded</p>";
    
    require_once 'includes/auth.php';
    echo "<p>✅ Auth loaded</p>";
    
    // Test just the login logic WITHOUT including header/sidebar
    echo "<p>✅ Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
    echo "<p>✅ User logged in: " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . "</p>";
    
    if (!isset($_SESSION['user_id'])) {
        echo "<p>✅ User should be redirected to login - this is correct</p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
    } else {
        echo "<p>User is logged in as: " . htmlspecialchars($_SESSION['user_name'] ?? 'Unknown') . "</p>";
        
        // Test the problematic includes
        echo "<h2>Testing Header Include</h2>";
        try {
            ob_start();
            include 'includes/header.php';
            $header = ob_get_contents();
            ob_end_clean();
            echo "<p>✅ Header included successfully</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Header error: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Testing Sidebar Include</h2>";
        try {
            ob_start();
            include 'includes/sidebar.php';
            $sidebar = ob_get_contents();
            ob_end_clean();
            echo "<p>✅ Sidebar included successfully</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Sidebar error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fatal Error: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>File: " . $e->getFile() . "</p>";
    echo "<p style='color: red;'>Line: " . $e->getLine() . "</p>";
}
?>