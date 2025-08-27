<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>School CRM Setup</h1>";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Database connected</p>";
    
    // Check if users table exists
    echo "<h2>1. Database Tables Check</h2>";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        $userTable = $stmt->fetch();
        
        if (!$userTable) {
            echo "<p style='color: red;'>❌ Users table missing</p>";
            echo "<p><strong>Run the installer first:</strong> <a href='install.php'>Install Database</a></p>";
            exit();
        }
        echo "<p>✅ Users table exists</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
        exit();
    }
    
    // Check for admin user
    echo "<h2>2. Admin User Setup</h2>";
    $stmt = $db->prepare("SELECT id, name, email, role, status FROM users WHERE email = ?");
    $stmt->execute(['admin@school.com']);
    $adminUser = $stmt->fetch();
    
    if (!$adminUser) {
        echo "<p style='color: orange;'>⚠️ Admin user doesn't exist. Creating...</p>";
        
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute(['Administrator', 'admin@school.com', $adminPassword, 'admin', 'active']);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create admin user</p>";
        }
    } else {
        echo "<p>✅ Admin user exists: " . htmlspecialchars($adminUser['name']) . "</p>";
        echo "<p>Status: " . htmlspecialchars($adminUser['status']) . "</p>";
        
        if ($adminUser['status'] !== 'active') {
            echo "<p style='color: orange;'>⚠️ Admin user is not active. Activating...</p>";
            $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE email = ?");
            $stmt->execute(['admin@school.com']);
            echo "<p style='color: green;'>✅ Admin user activated</p>";
        }
    }
    
    // Reset admin password to ensure it works
    echo "<h2>3. Password Reset</h2>";
    echo "<p>Resetting admin password to ensure it works...</p>";
    $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$newPassword, 'admin@school.com']);
    echo "<p style='color: green;'>✅ Admin password reset successfully</p>";
    
    // Test the password
    $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute(['admin@school.com']);
    $user = $stmt->fetch();
    
    if ($user && password_verify('admin123', $user['password'])) {
        echo "<p style='color: green;'>✅ Password verification test passed</p>";
    } else {
        echo "<p style='color: red;'>❌ Password verification test failed</p>";
    }
    
    echo "<hr>";
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p><strong>You can now login with:</strong></p>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Email:</strong> admin@school.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "</div>";
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>