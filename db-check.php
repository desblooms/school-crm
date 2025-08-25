<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Schema Checker</h1>";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check which tables exist
    $stmt = $db->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Existing Tables (" . count($existingTables) . "):</h2>";
    echo "<ul>";
    foreach ($existingTables as $table) {
        echo "<li>✅ $table</li>";
    }
    echo "</ul>";
    
    // Required tables for the system
    $requiredTables = [
        'users' => 'Core user accounts',
        'students' => 'Student records',
        'teachers' => 'Teacher records', 
        'classes' => 'Class/Section definitions',
        'subjects' => 'Subject definitions',
        'fee_types' => 'Fee type definitions',
        'fee_structure' => 'Fee structure per class',
        'fee_payments' => 'Fee payment records',
        'invoices' => 'Invoice records',
        'invoice_items' => 'Invoice line items',
        'student_attendance' => 'Student attendance records',
        'teacher_attendance' => 'Teacher attendance records',
        'teacher_subjects' => 'Teacher-Subject assignments',
        'activity_logs' => 'System activity logs',
        'settings' => 'System settings'
    ];
    
    echo "<h2>Missing Tables:</h2>";
    $missingTables = [];
    foreach ($requiredTables as $table => $description) {
        if (!in_array($table, $existingTables)) {
            echo "<p style='color: red;'>❌ Missing: <strong>$table</strong> - $description</p>";
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "<p style='color: green;'>✅ All required tables exist!</p>";
    } else {
        echo "<h3>Found " . count($missingTables) . " missing tables</h3>";
    }
    
    // Test some basic queries on existing tables
    echo "<h2>Table Data Check:</h2>";
    foreach ($existingTables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "<p>✅ $table: $count records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error querying $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test specific queries that might be failing
    echo "<h2>Testing Specific Queries:</h2>";
    
    $testQueries = [
        "SELECT COUNT(*) FROM users WHERE role = 'admin'" => "Admin users",
        "SELECT COUNT(*) FROM students" => "Student count", 
        "SELECT COUNT(*) FROM teachers" => "Teacher count",
        "SELECT COUNT(*) FROM classes" => "Class count"
    ];
    
    foreach ($testQueries as $query => $description) {
        try {
            $stmt = $db->query($query);
            $result = $stmt->fetchColumn();
            echo "<p>✅ $description: $result</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error in '$description': " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Error:</h2>";
    echo "<p style='color: red;'>Message: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>File: " . $e->getFile() . "</p>";
    echo "<p style='color: red;'>Line: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If tables are missing, run the database installation script</li>";
echo "<li>If queries fail, check table structure matches expected schema</li>";
echo "<li>If all looks good, the main application should work</li>";
echo "</ol>";
echo "<p><a href='install.php'>Run Database Installation</a> | <a href='index.php'>Try Main Application</a></p>";
?>