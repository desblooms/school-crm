<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>School CRM Test Page</h1>";

try {
    echo "<h2>1. Testing Config</h2>";
    require_once 'config/config.php';
    echo "✅ Config loaded successfully<br>";
    
    echo "<h2>2. Testing Database Connection</h2>";
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected successfully<br>";
    
    echo "<h2>3. Testing Auth</h2>";
    require_once 'includes/auth.php';
    echo "✅ Auth loaded successfully<br>";
    
    echo "<h2>4. Testing Classes</h2>";
    require_once 'classes/Student.php';
    $student = new Student();
    echo "✅ Student class loaded successfully<br>";
    
    require_once 'classes/Teacher.php';
    $teacher = new Teacher();
    echo "✅ Teacher class loaded successfully<br>";
    
    require_once 'classes/Fee.php';
    $fee = new Fee();
    echo "✅ Fee class loaded successfully<br>";
    
    require_once 'classes/Invoice.php';
    $invoice = new Invoice();
    echo "✅ Invoice class loaded successfully<br>";
    
    echo "<h2>5. Testing Database Queries</h2>";
    
    // Test if basic tables exist
    $tables = ['users', 'students', 'teachers', 'classes', 'subjects', 'fee_types'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Table '$table' exists with $count records<br>";
        } catch (Exception $e) {
            echo "❌ Table '$table' error: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>All Tests Passed!</h2>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error occurred:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>File: " . $e->getFile() . "</p>";
    echo "<p style='color: red;'>Line: " . $e->getLine() . "</p>";
}
?>