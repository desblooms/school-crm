<?php
// Immediate fix for subject assignment issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$results = [];
$errors = [];

function logResult($message) {
    global $results;
    $results[] = $message;
    echo $message . "<br>";
    flush();
}

function logError($message) {
    global $errors;
    $errors[] = $message;
    echo "<span style='color: red;'>" . $message . "</span><br>";
    flush();
}

echo "<h1>🔧 Immediate Subject Assignment Fix</h1>";
echo "<p>Applying fixes step by step...</p>";

try {
    $db = Database::getInstance()->getConnection();
    logResult("✅ Database connected successfully");
    
    // Fix 1: Add assigned_date column if missing
    echo "<h3>Step 1: Fix teacher_subjects table structure</h3>";
    
    $stmt = $db->query("SHOW COLUMNS FROM teacher_subjects LIKE 'assigned_date'");
    if (!$stmt->fetch()) {
        logResult("Adding assigned_date column...");
        $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
        logResult("✅ assigned_date column added successfully");
    } else {
        logResult("✅ assigned_date column already exists");
    }
    
    // Fix 2: Ensure proper table structure
    echo "<h3>Step 2: Verify table structure</h3>";
    
    $stmt = $db->query("DESCRIBE teacher_subjects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'teacher_id', 'subject_id', 'class_id', 'assigned_date'];
    $existingColumns = array_column($columns, 'Field');
    
    $missing = array_diff($requiredColumns, $existingColumns);
    if (!empty($missing)) {
        foreach ($missing as $column) {
            if ($column === 'assigned_date') {
                // Already handled above
                continue;
            }
            logError("❌ Missing required column: $column");
        }
    } else {
        logResult("✅ All required columns exist");
    }
    
    // Fix 3: Test basic functionality
    echo "<h3>Step 3: Test assignment functionality</h3>";
    
    // Get test data
    $stmt = $db->query("SELECT id FROM teachers LIMIT 1");
    $teacherId = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT id FROM subjects LIMIT 1");
    $subjectId = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT id FROM classes LIMIT 1");
    $classId = $stmt->fetchColumn();
    
    if ($teacherId && $subjectId && $classId) {
        logResult("Test data found: Teacher $teacherId, Subject $subjectId, Class $classId");
        
        // Clean up any existing test assignment
        $stmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
        $stmt->execute([$teacherId, $subjectId, $classId]);
        
        // Test the assignment
        $stmt = $db->prepare("
            INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) 
            VALUES (?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$teacherId, $subjectId, $classId])) {
            logResult("✅ Test assignment successful");
            
            // Verify it exists
            $stmt = $db->prepare("SELECT * FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
            $stmt->execute([$teacherId, $subjectId, $classId]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assignment) {
                logResult("✅ Assignment verified: ID " . $assignment['id'] . ", Date: " . $assignment['assigned_date']);
                
                // Clean up
                $stmt = $db->prepare("DELETE FROM teacher_subjects WHERE id = ?");
                $stmt->execute([$assignment['id']]);
                logResult("🧹 Test assignment cleaned up");
            } else {
                logError("❌ Assignment not found after insert");
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            logError("❌ Test assignment failed: " . $errorInfo[2]);
        }
    } else {
        logError("❌ No test data available (need at least 1 teacher, 1 subject, 1 class)");
    }
    
    // Fix 4: Test Teacher class method
    echo "<h3>Step 4: Test Teacher class</h3>";
    
    if (file_exists(__DIR__ . '/classes/Teacher.php')) {
        require_once __DIR__ . '/classes/Teacher.php';
        $teacher = new Teacher();
        
        if ($teacherId && $subjectId && $classId) {
            $result = $teacher->assignSubject($teacherId, $subjectId, $classId);
            
            if ($result['success']) {
                logResult("✅ Teacher class method successful");
                
                // Get the assignment and clean it up
                $assignments = $teacher->getTeacherSubjects($teacherId);
                foreach ($assignments as $assignment) {
                    if ($assignment['subject_id'] == $subjectId && $assignment['class_id'] == $classId) {
                        $teacher->removeSubject($assignment['id']);
                        logResult("🧹 Teacher class test cleaned up");
                        break;
                    }
                }
            } else {
                logError("❌ Teacher class method failed: " . $result['message']);
            }
        }
    } else {
        logError("❌ Teacher.php not found");
    }
    
    // Fix 5: Create sample data if needed
    echo "<h3>Step 5: Check sample data</h3>";
    
    $tables = ['teachers', 'subjects', 'classes'];
    $needsSampleData = false;
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $needsSampleData = true;
            logResult("⚠️ $table table is empty");
        } else {
            logResult("✅ $table: $count records");
        }
    }
    
    if ($needsSampleData) {
        logResult("⚠️ Some tables are empty. You may need sample data to test properly.");
        logResult("💡 Run the installation script or add some teachers, subjects, and classes manually.");
    }
    
    echo "<hr><h3>🎉 Fix Complete!</h3>";
    
    if (empty($errors)) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Subject assignment should now work properly.<br>";
        echo "The database structure has been fixed and tested.";
        echo "</div>";
        
        echo "<h4>Next Steps:</h4>";
        echo "<ul>";
        echo "<li><a href='teachers/subjects.php?id=1' style='color: blue;'>Test Subject Assignment Page</a></li>";
        echo "<li><a href='simple-debug.php' style='color: blue;'>Run Simple Debug Again</a></li>";
        echo "<li><a href='index.php' style='color: blue;'>Go to Dashboard</a></li>";
        echo "</ul>";
        
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0;'>";
        echo "<strong>❌ ISSUES FOUND:</strong><br>";
        foreach ($errors as $error) {
            echo "• " . strip_tags($error) . "<br>";
        }
        echo "</div>";
        
        echo "<h4>Recommended Actions:</h4>";
        echo "<ul>";
        echo "<li><a href='comprehensive-fix.php' style='color: blue;'>Run Comprehensive Fix</a></li>";
        echo "<li><a href='install.php' style='color: blue;'>Run Installation/Setup</a></li>";
        echo "<li><a href='check-setup.php' style='color: blue;'>Check Setup</a></li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    logError("❌ Critical Error: " . $e->getMessage());
    logError("File: " . $e->getFile() . " Line: " . $e->getLine());
}

?>

<div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;">
    <h4>Quick Links:</h4>
    <a href="simple-debug.php" style="display: inline-block; padding: 8px 12px; background: #2196F3; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Debug Again</a>
    <a href="teachers/subjects.php?id=1" style="display: inline-block; padding: 8px 12px; background: #4CAF50; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Test Assignment</a>
    <a href="comprehensive-fix.php" style="display: inline-block; padding: 8px 12px; background: #FF9800; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Full Fix</a>
    <a href="index.php" style="display: inline-block; padding: 8px 12px; background: #9E9E9E; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Dashboard</a>
</div>