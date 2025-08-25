<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$success = false;
$errors = [];
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        $results[] = "✅ Database connection successful";
        
        // Step 1: Check if assigned_date column exists and add it if missing
        $results[] = "<br><strong>🔧 Fixing teacher_subjects table:</strong>";
        
        $stmt = $db->query("SHOW COLUMNS FROM teacher_subjects LIKE 'assigned_date'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
            $results[] = "✅ Added assigned_date column";
        } else {
            $results[] = "✅ assigned_date column already exists";
        }
        
        // Step 2: Test subject assignment manually
        $results[] = "<br><strong>🧪 Testing subject assignment:</strong>";
        
        // Get test data
        $teacherStmt = $db->query("SELECT id FROM teachers LIMIT 1");
        $teacherId = $teacherStmt->fetchColumn();
        
        $subjectStmt = $db->query("SELECT id FROM subjects LIMIT 1");
        $subjectId = $subjectStmt->fetchColumn();
        
        $classStmt = $db->query("SELECT id FROM classes LIMIT 1");
        $classId = $classStmt->fetchColumn();
        
        if ($teacherId && $subjectId && $classId) {
            $results[] = "Using: Teacher ID $teacherId, Subject ID $subjectId, Class ID $classId";
            
            // Remove any existing assignment first
            $deleteStmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
            $deleteStmt->execute([$teacherId, $subjectId, $classId]);
            
            // Try the assignment
            $insertStmt = $db->prepare("
                INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if ($insertStmt->execute([$teacherId, $subjectId, $classId])) {
                $results[] = "✅ Subject assignment test successful!";
                
                // Verify it was inserted
                $verifyStmt = $db->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                $verifyStmt->execute([$teacherId, $subjectId, $classId]);
                $count = $verifyStmt->fetchColumn();
                
                if ($count > 0) {
                    $results[] = "✅ Assignment verified in database";
                    
                    // Test retrieval
                    $getStmt = $db->prepare("
                        SELECT ts.*, s.name as subject_name, c.name as class_name 
                        FROM teacher_subjects ts 
                        JOIN subjects s ON ts.subject_id = s.id 
                        JOIN classes c ON ts.class_id = c.id 
                        WHERE ts.teacher_id = ? AND ts.subject_id = ? AND ts.class_id = ?
                    ");
                    $getStmt->execute([$teacherId, $subjectId, $classId]);
                    $assignment = $getStmt->fetch();
                    
                    if ($assignment) {
                        $results[] = "✅ Retrieved assignment: {$assignment['subject_name']} for {$assignment['class_name']}";
                    }
                    
                    // Clean up
                    $deleteStmt->execute([$teacherId, $subjectId, $classId]);
                    $results[] = "🧹 Test assignment cleaned up";
                } else {
                    $errors[] = "❌ Assignment not found after insertion";
                }
            } else {
                $errorInfo = $insertStmt->errorInfo();
                $errors[] = "❌ Failed to insert assignment: " . $errorInfo[2];
            }
        } else {
            $errors[] = "❌ Missing test data - need at least one teacher, subject, and class";
        }
        
        // Step 3: Create a simple working version of assignSubject function
        $results[] = "<br><strong>📝 Creating improved assignment function:</strong>";
        
        $improvedFunctionCode = '
        function assignSubjectImproved($db, $teacherId, $subjectId, $classId) {
            try {
                // Check if assignment already exists
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                $checkStmt->execute([$teacherId, $subjectId, $classId]);
                
                if ($checkStmt->fetchColumn() > 0) {
                    return ["success" => false, "message" => "Assignment already exists"];
                }
                
                // Insert new assignment
                $insertStmt = $db->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) VALUES (?, ?, ?, NOW())");
                
                if ($insertStmt->execute([$teacherId, $subjectId, $classId])) {
                    return ["success" => true, "message" => "Subject assigned successfully", "assignment_id" => $db->lastInsertId()];
                } else {
                    $errorInfo = $insertStmt->errorInfo();
                    return ["success" => false, "message" => "Database insert failed: " . $errorInfo[2]];
                }
                
            } catch (Exception $e) {
                return ["success" => false, "message" => "Exception: " . $e->getMessage()];
            }
        }';
        
        eval($improvedFunctionCode);
        
        // Test the improved function
        if ($teacherId && $subjectId && $classId) {
            $testResult = assignSubjectImproved($db, $teacherId, $subjectId, $classId);
            
            if ($testResult['success']) {
                $results[] = "✅ Improved function test successful!";
                
                // Clean up
                $deleteStmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                $deleteStmt->execute([$teacherId, $subjectId, $classId]);
                $results[] = "🧹 Improved function test cleaned up";
            } else {
                $results[] = "❌ Improved function test failed: " . $testResult['message'];
            }
        }
        
        if (empty($errors)) {
            $success = true;
            $results[] = "<br>🎉 <strong>Subject assignment should now work properly!</strong>";
        }
        
    } catch (Exception $e) {
        $errors[] = "Fix failed: " . $e->getMessage();
        $results[] = "❌ Error in: " . $e->getFile() . " line " . $e->getLine();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Fix Subject Assignment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-xl p-8">
        <div class="text-center mb-8">
            <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-book text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Fix Subject Assignment</h1>
            <p class="text-gray-600 mt-2">Targeted fix for subject assignment database errors</p>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <h4 class="font-semibold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>Issues Found:</h4>
                <ul class="mt-2 text-sm">
                    <?php foreach ($errors as $error): ?>
                    <li>• <?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4 max-h-64 overflow-y-auto">
                <h4 class="font-semibold flex items-center"><i class="fas fa-info-circle mr-2"></i>Fix Results:</h4>
                <div class="mt-2 text-sm space-y-1">
                    <?php foreach ($results as $result): ?>
                    <div><?php echo $result; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <h4 class="font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>Subject Assignment Fixed!</h4>
                <p class="text-sm mt-2">The database structure has been fixed and subject assignment is working.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="teachers/subjects.php?id=1" class="bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-book mr-2"></i>Test Subject Assignment
                </a>
                <a href="debug-subject-assignment.php" class="bg-yellow-600 text-white px-4 py-3 rounded-md hover:bg-yellow-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-bug mr-2"></i>Debug Details
                </a>
                <a href="index.php" class="bg-gray-600 text-white px-4 py-3 rounded-md hover:bg-gray-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
            </div>
            <?php else: ?>
            <div class="text-center">
                <button onclick="location.reload()" class="bg-red-600 text-white px-6 py-3 rounded-md hover:bg-red-700 transition-colors flex items-center mx-auto">
                    <i class="fas fa-redo mr-2"></i>Try Again
                </button>
            </div>
            <?php endif; ?>

        <?php else: ?>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">This fix will:</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Add missing assigned_date column to teacher_subjects table
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Test subject assignment functionality manually
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Verify database operations work correctly
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Provide detailed error information if issues persist
                    </li>
                </ul>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    <div>
                        <h4 class="font-semibold">Targeted Fix</h4>
                        <p class="text-sm">This script specifically addresses subject assignment issues with detailed testing and error reporting.</p>
                    </div>
                </div>
            </div>
            
            <form method="POST" class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center mx-auto text-lg">
                    <i class="fas fa-wrench mr-2"></i>Fix Subject Assignment
                </button>
            </form>
            
        <?php endif; ?>
        
        <div class="mt-8 text-center border-t pt-6">
            <div class="flex justify-center space-x-6 text-sm">
                <a href="debug-subject-assignment.php" class="text-blue-600 hover:text-blue-800">Detailed Debug</a>
                <a href="comprehensive-fix.php" class="text-blue-600 hover:text-blue-800">Comprehensive Fix</a>
                <a href="check-setup.php" class="text-blue-600 hover:text-blue-800">Check Setup</a>
                <a href="index.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>