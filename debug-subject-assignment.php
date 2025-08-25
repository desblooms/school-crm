<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Teacher.php';

echo "<h1>Subject Assignment Debug</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<h2>✅ Database connection successful</h2>";
    
    // 1. Check teacher_subjects table structure
    echo "<h2>1. Teacher Subjects Table Structure:</h2>";
    $stmt = $db->query("DESCRIBE teacher_subjects");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra'] ?? ''}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if assigned_date column exists
    $hasAssignedDate = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'assigned_date') {
            $hasAssignedDate = true;
            break;
        }
    }
    
    if (!$hasAssignedDate) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "❌ <strong>ISSUE FOUND:</strong> assigned_date column is missing!<br>";
        echo "<button onclick=\"addAssignedDateColumn()\">Fix: Add assigned_date Column</button>";
        echo "</div>";
    } else {
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
        echo "✅ assigned_date column exists";
        echo "</div>";
    }
    
    // 2. Check available data
    echo "<h2>2. Available Test Data:</h2>";
    
    $teacherStmt = $db->query("SELECT t.id, u.name, t.employee_id FROM teachers t JOIN users u ON t.user_id = u.id LIMIT 3");
    $teachers = $teacherStmt->fetchAll();
    echo "<h3>Teachers (" . count($teachers) . " found):</h3>";
    foreach ($teachers as $teacher) {
        echo "• ID: {$teacher['id']} - {$teacher['name']} ({$teacher['employee_id']})<br>";
    }
    
    $subjectStmt = $db->query("SELECT id, name, code FROM subjects LIMIT 3");
    $subjects = $subjectStmt->fetchAll();
    echo "<h3>Subjects (" . count($subjects) . " found):</h3>";
    foreach ($subjects as $subject) {
        echo "• ID: {$subject['id']} - {$subject['name']} ({$subject['code']})<br>";
    }
    
    $classStmt = $db->query("SELECT id, name, section FROM classes LIMIT 3");
    $classes = $classStmt->fetchAll();
    echo "<h3>Classes (" . count($classes) . " found):</h3>";
    foreach ($classes as $class) {
        echo "• ID: {$class['id']} - {$class['name']} - {$class['section']}<br>";
    }
    
    // 3. Test manual assignment
    echo "<h2>3. Manual Assignment Test:</h2>";
    
    if (!empty($teachers) && !empty($subjects) && !empty($classes)) {
        $teacherId = $teachers[0]['id'];
        $subjectId = $subjects[0]['id'];
        $classId = $classes[0]['id'];
        
        echo "<p>Testing assignment: Teacher {$teachers[0]['name']} → Subject {$subjects[0]['name']} → Class {$classes[0]['name']}</p>";
        
        // First check if this assignment already exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
        $checkStmt->execute([$teacherId, $subjectId, $classId]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists > 0) {
            echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>";
            echo "⚠️ Assignment already exists - testing with removal first...";
            echo "</div>";
            
            $deleteStmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
            $deleteStmt->execute([$teacherId, $subjectId, $classId]);
            echo "✅ Existing assignment removed<br>";
        }
        
        // Try direct SQL insert
        echo "<h4>Direct SQL Insert Test:</h4>";
        try {
            if ($hasAssignedDate) {
                $insertStmt = $db->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) VALUES (?, ?, ?, NOW())");
            } else {
                $insertStmt = $db->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES (?, ?, ?)");
            }
            
            $result = $insertStmt->execute([$teacherId, $subjectId, $classId]);
            
            if ($result) {
                echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
                echo "✅ <strong>Direct SQL insert successful!</strong>";
                echo "</div>";
                
                // Verify the insert
                $verifyStmt = $db->prepare("SELECT * FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                $verifyStmt->execute([$teacherId, $subjectId, $classId]);
                $assignment = $verifyStmt->fetch();
                
                if ($assignment) {
                    echo "<h4>Inserted Record:</h4>";
                    echo "<pre>" . print_r($assignment, true) . "</pre>";
                }
                
                // Clean up
                $deleteStmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                $deleteStmt->execute([$teacherId, $subjectId, $classId]);
                echo "🧹 Test assignment cleaned up<br>";
                
            } else {
                echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
                echo "❌ <strong>Direct SQL insert failed!</strong>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
            echo "❌ <strong>SQL Error:</strong> " . $e->getMessage() . "<br>";
            echo "<strong>File:</strong> " . $e->getFile() . "<br>";
            echo "<strong>Line:</strong> " . $e->getLine();
            echo "</div>";
        }
        
        // Try using Teacher class method
        echo "<h4>Teacher Class Method Test:</h4>";
        try {
            $teacher = new Teacher();
            $result = $teacher->assignSubject($teacherId, $subjectId, $classId);
            
            if ($result['success']) {
                echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
                echo "✅ <strong>Teacher class method successful!</strong>";
                echo "</div>";
                
                // Clean up
                $subjects = $teacher->getTeacherSubjects($teacherId);
                if (!empty($subjects)) {
                    $teacher->removeSubject($subjects[0]['id']);
                    echo "🧹 Test assignment cleaned up via Teacher class<br>";
                }
            } else {
                echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
                echo "❌ <strong>Teacher class method failed:</strong> " . $result['message'];
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
            echo "❌ <strong>Teacher Class Error:</strong> " . $e->getMessage() . "<br>";
            echo "<strong>File:</strong> " . $e->getFile() . "<br>";
            echo "<strong>Line:</strong> " . $e->getLine();
            echo "</div>";
        }
        
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "❌ <strong>Cannot test:</strong> Missing required data (teachers, subjects, or classes)";
        echo "</div>";
    }
    
    // 4. Check existing assignments
    echo "<h2>4. Current Assignments:</h2>";
    $stmt = $db->query("SELECT COUNT(*) FROM teacher_subjects");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "<p>Total assignments: $count</p>";
        
        $stmt = $db->query("
            SELECT ts.*, s.name as subject_name, c.name as class_name, u.name as teacher_name
            FROM teacher_subjects ts
            JOIN subjects s ON ts.subject_id = s.id
            JOIN classes c ON ts.class_id = c.id
            JOIN teachers t ON ts.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            LIMIT 5
        ");
        $assignments = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Teacher</th><th>Subject</th><th>Class</th><th>Assigned Date</th></tr>";
        foreach ($assignments as $assignment) {
            echo "<tr>";
            echo "<td>{$assignment['id']}</td>";
            echo "<td>{$assignment['teacher_name']}</td>";
            echo "<td>{$assignment['subject_name']}</td>";
            echo "<td>{$assignment['class_name']}</td>";
            echo "<td>{$assignment['assigned_date'] ?? 'N/A'}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No assignments found.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 2px solid red; margin: 20px 0;'>";
    echo "<h3>❌ Critical Error:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>

<script>
function addAssignedDateColumn() {
    if (confirm('Add assigned_date column to teacher_subjects table?')) {
        fetch('debug-subject-assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=add_assigned_date_column'
        })
        .then(response => response.text())
        .then(data => {
            alert('Column added! Refreshing page...');
            location.reload();
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}
</script>

<div style="margin: 20px 0; padding: 10px; border: 1px solid #ccc;">
    <h3>Quick Actions:</h3>
    <a href="quick-fix.php" style="display: inline-block; padding: 10px 15px; background: #007cba; color: white; text-decoration: none; margin: 5px;">Quick Fix</a>
    <a href="comprehensive-fix.php" style="display: inline-block; padding: 10px 15px; background: #28a745; color: white; text-decoration: none; margin: 5px;">Comprehensive Fix</a>
    <a href="check-setup.php" style="display: inline-block; padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; margin: 5px;">Check Setup</a>
</div>

<?php
// Handle AJAX requests to add missing column
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_assigned_date_column') {
    try {
        $db = Database::getInstance()->getConnection();
        $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "SUCCESS: assigned_date column added";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage();
    }
    exit;
}
?>