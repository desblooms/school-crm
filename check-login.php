<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login System Diagnosis</h1>";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Database connected</p>";
    
    // Check if users table exists
    echo "<h2>1. Check Users Table</h2>";
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        echo "<p>✅ Users table exists with columns:</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Users table missing: " . $e->getMessage() . "</p>";
        echo "<p><strong>Solution: Run the database installer</strong></p>";
        echo "<p><a href='install.php'>Install Database Tables</a></p>";
        exit();
    }
    
    // Check existing users
    echo "<h2>2. Check Existing Users</h2>";
    $stmt = $db->query("SELECT id, name, email, role, status FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p style='color: red;'>❌ No users found in database</p>";
        echo "<h3>Creating Admin User...</h3>";
        
        // Create admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['Administrator', 'admin@school.com', $adminPassword, 'admin', 'active']);
        
        echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        echo "<p><strong>Login credentials:</strong></p>";
        echo "<ul>";
        echo "<li>Email: admin@school.com</li>";
        echo "<li>Password: admin123</li>";
        echo "</ul>";
    } else {
        echo "<p>✅ Found " . count($users) . " users:</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if admin user exists
        $adminExists = false;
        foreach ($users as $user) {
            if ($user['email'] === 'admin@school.com' && $user['status'] === 'active') {
                $adminExists = true;
                break;
            }
        }
        
        if (!$adminExists) {
            echo "<p style='color: orange;'>⚠️ No active admin@school.com user found</p>";
            echo "<h3>Creating Admin User...</h3>";
            
            // Create admin user
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute(['Administrator', 'admin@school.com', $adminPassword, 'admin', 'active']);
            
            echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        }
    }
    
    // Test login credentials
    echo "<h2>3. Test Login Credentials</h2>";
    $stmt = $db->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute(['admin@school.com']);
    $adminUser = $stmt->fetch();
    
    if ($adminUser) {
        echo "<p>✅ Admin user found: " . htmlspecialchars($adminUser['name']) . "</p>";
        
        // Test password
        if (password_verify('admin123', $adminUser['password'])) {
            echo "<p style='color: green;'>✅ Password verification successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed</p>";
            echo "<p>Updating admin password...</p>";
            
            $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$newPassword, 'admin@school.com']);
            
            echo "<p style='color: green;'>✅ Password updated successfully</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Admin user not found</p>";
    }
    
    echo "<hr>";
    echo "<h2>Ready to Test</h2>";
    echo "<p><strong>Login credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@school.com</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>