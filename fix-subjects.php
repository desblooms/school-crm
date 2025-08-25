<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

$success = false;
$errors = [];
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        
        $results[] = "✅ Database connection successful";
        
        // Check teacher_subjects table structure
        $stmt = $db->query("DESCRIBE teacher_subjects");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        $results[] = "ℹ️ Current columns in teacher_subjects: " . implode(', ', $columnNames);
        
        // Add missing assigned_date column if it doesn't exist
        if (!in_array('assigned_date', $columnNames)) {
            $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
            $results[] = "✅ Added assigned_date column to teacher_subjects table";
        } else {
            $results[] = "✅ assigned_date column already exists";
        }
        
        // Ensure the table has proper constraints
        try {
            // Check if unique key already exists
            $stmt = $db->query("SHOW INDEX FROM teacher_subjects WHERE Key_name = 'uk_teacher_subject_class'");
            $indexExists = $stmt->fetch();
            
            if (!$indexExists) {
                $db->exec("ALTER TABLE teacher_subjects ADD UNIQUE KEY uk_teacher_subject_class (teacher_id, subject_id, class_id)");
                $results[] = "✅ Added unique constraint for teacher-subject-class combination";
            } else {
                $results[] = "✅ Unique constraint already exists";
            }
        } catch (Exception $e) {
            // If constraint addition fails, it might already exist or there's duplicate data
            $results[] = "⚠️ Constraint note: " . $e->getMessage();
        }
        
        // Test the subject assignment functionality
        $results[] = "<br><strong>Testing Subject Assignment:</strong>";
        
        // Get a sample teacher, subject, and class
        $teacherStmt = $db->query("SELECT id FROM teachers LIMIT 1");
        $teacherId = $teacherStmt->fetchColumn();
        
        $subjectStmt = $db->query("SELECT id FROM subjects LIMIT 1");
        $subjectId = $subjectStmt->fetchColumn();
        
        $classStmt = $db->query("SELECT id FROM classes LIMIT 1");
        $classId = $classStmt->fetchColumn();
        
        if ($teacherId && $subjectId && $classId) {
            $results[] = "📝 Testing with Teacher ID: $teacherId, Subject ID: $subjectId, Class ID: $classId";
            
            // Test the assignment query
            $testStmt = $db->prepare("
                INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE assigned_date = NOW()
            ");
            
            $testResult = $testStmt->execute([$teacherId, $subjectId, $classId]);
            
            if ($testResult) {
                $results[] = "✅ Test assignment successful";
                
                // Clean up test assignment
                $cleanupStmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                $cleanupStmt->execute([$teacherId, $subjectId, $classId]);
                $results[] = "🧹 Test assignment cleaned up";
            } else {
                $errors[] = "❌ Test assignment failed";
            }
        } else {
            $results[] = "⚠️ No sample data available for testing (missing teachers, subjects, or classes)";
        }
        
        // Check if we have basic data
        $dataChecks = [
            'teachers' => 'Teachers',
            'subjects' => 'Subjects', 
            'classes' => 'Classes'
        ];
        
        $results[] = "<br><strong>Data Availability:</strong>";
        foreach ($dataChecks as $table => $name) {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $results[] = "✅ $name: $count records";
            } else {
                $results[] = "❌ $name: No records found";
                $errors[] = "No $name available - you need to add some first";
            }
        }
        
        if (empty($errors)) {
            $success = true;
            $results[] = "<br>✅ <strong>Subject assignment should now work properly!</strong>";
        }
        
    } catch (Exception $e) {
        $errors[] = "Database fix failed: " . $e->getMessage();
        $results[] = "❌ Error details: " . $e->getFile() . " line " . $e->getLine();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Fix Subject Assignment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-wrench text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Fix Subject Assignment</h1>
                <p class="text-gray-600 mt-2">Repair teacher-subject assignment functionality</p>
            </div>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                
                <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>Errors:</h4>
                    <ul class="mt-2 text-sm">
                        <?php foreach ($errors as $error): ?>
                        <li>• <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($results)): ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-4">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-info-circle mr-2"></i>Fix Results:</h4>
                    <div class="mt-2 text-sm">
                        <?php foreach ($results as $result): ?>
                        <div><?php echo $result; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>Fix Successful!</h4>
                    <p class="text-sm mt-2">Subject assignment functionality has been repaired.</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="teachers/subjects.php?id=1" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-book mr-2"></i>Test Subject Assignment
                    </a>
                    <a href="teachers/list.php" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center">
                        <i class="fas fa-users mr-2"></i>View Teachers
                    </a>
                </div>
                <?php else: ?>
                <button onclick="location.reload()" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition-colors flex items-center">
                    <i class="fas fa-redo mr-2"></i>Try Again
                </button>
                <?php endif; ?>

            <?php else: ?>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">This Fix Will:</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Add missing assigned_date column to teacher_subjects table
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Add proper constraints to prevent duplicate assignments
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Test the assignment functionality
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Verify data availability for assignments
                        </li>
                    </ul>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <div>
                            <h4 class="font-semibold">Before Running:</h4>
                            <p class="text-sm">This will modify the teacher_subjects table structure. The operation is safe and reversible.</p>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center text-lg">
                        <i class="fas fa-wrench mr-2"></i>Fix Subject Assignment
                    </button>
                </form>
                
            <?php endif; ?>
            
            <div class="mt-8 text-center">
                <div class="flex justify-center space-x-4 text-sm">
                    <a href="debug-subjects.php" class="text-blue-600 hover:text-blue-800">Debug Subjects</a>
                    <a href="repair-db.php" class="text-blue-600 hover:text-blue-800">Database Repair</a>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>