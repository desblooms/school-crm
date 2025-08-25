<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Subject Assignment Debug</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>1. Checking teacher_subjects table structure</h2>";
    $stmt = $db->query("DESCRIBE teacher_subjects");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>2. Checking if assigned_date column exists</h2>";
    $hasAssignedDate = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'assigned_date') {
            $hasAssignedDate = true;
            break;
        }
    }
    
    if ($hasAssignedDate) {
        echo "✅ assigned_date column exists";
    } else {
        echo "❌ assigned_date column missing - this is likely causing the error";
        
        echo "<h3>Adding assigned_date column:</h3>";
        try {
            $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
            echo "✅ assigned_date column added successfully";
        } catch (Exception $e) {
            echo "❌ Failed to add column: " . $e->getMessage();
        }
    }
    
    echo "<h2>3. Current teacher_subjects data</h2>";
    $stmt = $db->query("SELECT COUNT(*) FROM teacher_subjects");
    $count = $stmt->fetchColumn();
    echo "<p>Current assignments: $count</p>";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM teacher_subjects LIMIT 5");
        $assignments = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach (array_keys($assignments[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        foreach ($assignments as $assignment) {
            echo "<tr>";
            foreach ($assignment as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>4. Available subjects</h2>";
    $stmt = $db->query("SELECT id, name, code FROM subjects");
    $subjects = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($subjects as $subject) {
        echo "<li>ID: {$subject['id']} - {$subject['name']} ({$subject['code']})</li>";
    }
    echo "</ul>";
    
    echo "<h2>5. Available classes</h2>";
    $stmt = $db->query("SELECT id, name, section FROM classes");
    $classes = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($classes as $class) {
        echo "<li>ID: {$class['id']} - {$class['name']} - {$class['section']}</li>";
    }
    echo "</ul>";
    
    echo "<h2>6. Available teachers</h2>";
    $stmt = $db->query("SELECT t.id, u.name, t.employee_id FROM teachers t JOIN users u ON t.user_id = u.id");
    $teachers = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($teachers as $teacher) {
        echo "<li>ID: {$teacher['id']} - {$teacher['name']} ({$teacher['employee_id']})</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>File: " . $e->getFile() . "</p>";
    echo "<p style='color: red;'>Line: " . $e->getLine() . "</p>";
}

echo "<p><a href='teachers/subjects.php?id=1'>Test Subject Assignment</a></p>";
?>