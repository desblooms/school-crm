<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>Teacher Table Column Debug</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful<br><br>";
    
    // Check if teachers table exists
    $stmt = $db->query("SHOW TABLES LIKE 'teachers'");
    if ($stmt->fetch()) {
        echo "✅ Teachers table exists<br><br>";
        
        // Show all columns in teachers table
        echo "<h2>Teachers Table Structure:</h2>";
        $stmt = $db->query("DESCRIBE teachers");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Check if employee_id column exists
        $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'employee_id'");
        if ($stmt->fetch()) {
            echo "✅ employee_id column exists in teachers table<br><br>";
        } else {
            echo "❌ employee_id column MISSING from teachers table<br><br>";
            echo "<strong>This is the problem!</strong> The employee_id column needs to be added to the teachers table.<br>";
        }
        
        // Test simple query
        echo "<h2>Testing Simple Query:</h2>";
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM teachers");
            $count = $stmt->fetchColumn();
            echo "✅ Simple count query works: $count teachers<br>";
        } catch (Exception $e) {
            echo "❌ Simple query failed: " . $e->getMessage() . "<br>";
        }
        
        // Test query with employee_id
        echo "<h2>Testing Query with employee_id:</h2>";
        try {
            $stmt = $db->query("SELECT id, employee_id FROM teachers LIMIT 1");
            $result = $stmt->fetch();
            if ($result) {
                echo "✅ Query with employee_id works<br>";
                echo "Sample data: ID=" . $result['id'] . ", employee_id=" . $result['employee_id'] . "<br>";
            } else {
                echo "⚠️ No data in teachers table<br>";
            }
        } catch (Exception $e) {
            echo "❌ Query with employee_id failed: " . $e->getMessage() . "<br>";
            echo "<strong>This confirms the column is missing!</strong><br>";
        }
        
        // Test the problematic Teacher class query
        echo "<h2>Testing Teacher Class Query:</h2>";
        try {
            require_once __DIR__ . '/classes/Teacher.php';
            $teacher = new Teacher();
            $teachers = $teacher->getAll(5, 0, '');
            echo "✅ Teacher::getAll() works - returned " . count($teachers) . " teachers<br>";
        } catch (Exception $e) {
            echo "❌ Teacher::getAll() failed: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ Teachers table does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><h2>Solution:</h2>";
echo "If employee_id column is missing, you need to run the database migrations:<br>";
echo "<a href='apply-migrations.php' style='background: blue; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Apply Migrations</a><br>";
echo "Or add the column manually with SQL: <code>ALTER TABLE teachers ADD COLUMN employee_id VARCHAR(20) UNIQUE;</code>";
?>