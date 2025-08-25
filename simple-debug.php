<?php
// Simple debug script with extensive error handling
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Simple Subject Assignment Debug</h1>";
echo "<p>Starting diagnosis...</p>";

// Step 1: Check if config files exist
echo "<h2>Step 1: Check Files</h2>";
$configPath = __DIR__ . '/config/config.php';
if (file_exists($configPath)) {
    echo "✅ config.php exists<br>";
    try {
        require_once $configPath;
        echo "✅ config.php loaded<br>";
    } catch (Exception $e) {
        echo "❌ config.php error: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "❌ config.php not found at: $configPath<br>";
    exit;
}

$dbPath = __DIR__ . '/config/database.php';
if (file_exists($dbPath)) {
    echo "✅ database.php exists<br>";
    try {
        require_once $dbPath;
        echo "✅ database.php loaded<br>";
    } catch (Exception $e) {
        echo "❌ database.php error: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "❌ database.php not found at: $dbPath<br>";
    exit;
}

// Step 2: Test database connection
echo "<h2>Step 2: Database Connection</h2>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Check teacher_subjects table
echo "<h2>Step 3: Check teacher_subjects Table</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'teacher_subjects'");
    if ($stmt->fetch()) {
        echo "✅ teacher_subjects table exists<br>";
        
        // Check columns
        $stmt = $db->query("DESCRIBE teacher_subjects");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Columns:</h3>";
        $hasAssignedDate = false;
        foreach ($columns as $column) {
            echo "• " . $column['Field'] . " (" . $column['Type'] . ")<br>";
            if ($column['Field'] === 'assigned_date') {
                $hasAssignedDate = true;
            }
        }
        
        if (!$hasAssignedDate) {
            echo "<div style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;'>";
            echo "<strong>❌ ISSUE FOUND:</strong> assigned_date column is missing!<br>";
            echo "<a href='#' onclick='addColumn()' style='color: blue; text-decoration: underline;'>Click here to add it</a>";
            echo "</div>";
        } else {
            echo "<div style='background: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
            echo "✅ assigned_date column exists";
            echo "</div>";
        }
        
    } else {
        echo "❌ teacher_subjects table does not exist<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// Step 4: Check for test data
echo "<h2>Step 4: Check Test Data</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM teachers");
    $teacherCount = $stmt->fetchColumn();
    echo "Teachers: $teacherCount<br>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM subjects");
    $subjectCount = $stmt->fetchColumn();
    echo "Subjects: $subjectCount<br>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM classes");
    $classCount = $stmt->fetchColumn();
    echo "Classes: $classCount<br>";
    
    if ($teacherCount > 0 && $subjectCount > 0 && $classCount > 0) {
        echo "<div style='background: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
        echo "✅ Test data available";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3e0; padding: 10px; border-left: 4px solid #ff9800; margin: 10px 0;'>";
        echo "⚠️ Missing test data - need at least one teacher, subject, and class";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "❌ Error checking test data: " . $e->getMessage() . "<br>";
}

// Step 5: Simple assignment test
echo "<h2>Step 5: Simple Assignment Test</h2>";
try {
    // Get one record from each table
    $stmt = $db->query("SELECT id FROM teachers LIMIT 1");
    $teacherId = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT id FROM subjects LIMIT 1");
    $subjectId = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT id FROM classes LIMIT 1");
    $classId = $stmt->fetchColumn();
    
    if ($teacherId && $subjectId && $classId) {
        echo "Using: Teacher ID $teacherId, Subject ID $subjectId, Class ID $classId<br>";
        
        // Remove any existing assignment
        $stmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
        $stmt->execute([$teacherId, $subjectId, $classId]);
        echo "Cleaned up any existing test assignment<br>";
        
        // Try simple insert
        if ($hasAssignedDate) {
            $sql = "INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) VALUES (?, ?, ?, NOW())";
        } else {
            $sql = "INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES (?, ?, ?)";
        }
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$teacherId, $subjectId, $classId]);
        
        if ($result) {
            echo "<div style='background: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
            echo "✅ <strong>SUCCESS!</strong> Assignment insert worked!";
            echo "</div>";
            
            // Clean up
            $stmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
            $stmt->execute([$teacherId, $subjectId, $classId]);
            echo "🧹 Test cleaned up<br>";
            
        } else {
            $errorInfo = $stmt->errorInfo();
            echo "<div style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;'>";
            echo "❌ <strong>FAILED!</strong> Insert error: " . $errorInfo[2];
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #fff3e0; padding: 10px; border-left: 4px solid #ff9800; margin: 10px 0;'>";
        echo "⚠️ Cannot test - missing required data";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;'>";
    echo "❌ <strong>EXCEPTION!</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine();
    echo "</div>";
}

// Step 6: Load Teacher class test
echo "<h2>Step 6: Teacher Class Test</h2>";
$teacherPath = __DIR__ . '/classes/Teacher.php';
if (file_exists($teacherPath)) {
    try {
        require_once $teacherPath;
        echo "✅ Teacher class loaded<br>";
        
        $teacher = new Teacher();
        echo "✅ Teacher object created<br>";
        
        if ($teacherId && $subjectId && $classId) {
            $result = $teacher->assignSubject($teacherId, $subjectId, $classId);
            
            if ($result['success']) {
                echo "<div style='background: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
                echo "✅ <strong>Teacher class method worked!</strong>";
                echo "</div>";
                
                // Clean up
                $subjects = $teacher->getTeacherSubjects($teacherId);
                if (!empty($subjects)) {
                    $teacher->removeSubject($subjects[0]['id']);
                    echo "🧹 Teacher class test cleaned up<br>";
                }
            } else {
                echo "<div style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;'>";
                echo "❌ <strong>Teacher class method failed:</strong> " . htmlspecialchars($result['message']);
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Teacher class error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Teacher.php not found<br>";
}

echo "<hr>";
echo "<h2>Summary & Next Steps</h2>";
if (isset($hasAssignedDate) && !$hasAssignedDate) {
    echo "<div style='background: #fff3e0; padding: 15px; border: 2px solid #ff9800; margin: 10px 0;'>";
    echo "<strong>🔧 ACTION NEEDED:</strong> The assigned_date column is missing from teacher_subjects table.<br>";
    echo "This is likely the cause of your subject assignment errors.<br><br>";
    echo "<strong>Solutions:</strong><br>";
    echo "1. <a href='quick-fix.php' style='color: blue;'>Run Quick Fix</a> - safest option<br>";
    echo "2. <a href='comprehensive-fix.php' style='color: blue;'>Run Comprehensive Fix</a> - complete solution<br>";
    echo "3. Or click the link above to add the column manually";
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; padding: 15px; border: 2px solid #4caf50; margin: 10px 0;'>";
    echo "<strong>✅ GOOD NEWS:</strong> The database structure appears to be correct!<br>";
    echo "If you're still having issues, they may be in the application logic or data validation.";
    echo "</div>";
}

?>

<script>
function addColumn() {
    if (confirm('Add assigned_date column to teacher_subjects table?')) {
        fetch('simple-debug.php?action=add_column', {
            method: 'GET'
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('SUCCESS')) {
                alert('Column added successfully! Refreshing page...');
                location.reload();
            } else {
                alert('Error: ' + data);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}
</script>

<?php
// Handle the add column action
if (isset($_GET['action']) && $_GET['action'] === 'add_column') {
    try {
        $db = Database::getInstance()->getConnection();
        $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "SUCCESS: assigned_date column added to teacher_subjects table";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage();
    }
    exit;
}
?>

<div style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; background: #f9f9f9;">
    <h3>Quick Actions:</h3>
    <a href="quick-fix.php" style="display: inline-block; padding: 8px 12px; background: #2196F3; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Quick Fix</a>
    <a href="comprehensive-fix.php" style="display: inline-block; padding: 8px 12px; background: #4CAF50; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Full Fix</a>
    <a href="check-setup.php" style="display: inline-block; padding: 8px 12px; background: #9E9E9E; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Check Setup</a>
    <a href="index.php" style="display: inline-block; padding: 8px 12px; background: #607D8B; color: white; text-decoration: none; margin: 3px; border-radius: 4px;">Dashboard</a>
</div>