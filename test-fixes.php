<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Teacher.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/classes/Fee.php';
require_once __DIR__ . '/classes/Subject.php';

$results = [];
$errors = [];

try {
    $db = Database::getInstance()->getConnection();
    $results[] = "✅ Database connection successful";
    
    // Test Teacher Subject Assignment
    $results[] = "<br><strong>🧪 Testing Subject Assignment:</strong>";
    $teacher = new Teacher();
    $subject = new Subject();
    
    $teacherStmt = $db->query("SELECT id FROM teachers LIMIT 1");
    $teacherId = $teacherStmt->fetchColumn();
    
    $subjectStmt = $db->query("SELECT id FROM subjects LIMIT 1");
    $subjectId = $subjectStmt->fetchColumn();
    
    $classStmt = $db->query("SELECT id FROM classes LIMIT 1");
    $classId = $classStmt->fetchColumn();
    
    if ($teacherId && $subjectId && $classId) {
        $result = $teacher->assignSubject($teacherId, $subjectId, $classId);
        if ($result['success']) {
            $results[] = "✅ Subject assignment test successful";
            
            // Test getting teacher subjects
            $subjects = $teacher->getTeacherSubjects($teacherId);
            $results[] = "✅ Retrieved " . count($subjects) . " assigned subjects";
            
            // Clean up test assignment
            if (!empty($subjects)) {
                $teacher->removeSubject($subjects[0]['id']);
                $results[] = "✅ Test assignment cleaned up";
            }
        } else {
            $errors[] = "❌ Subject assignment failed: " . $result['message'];
        }
    } else {
        $errors[] = "⚠️ No test data available (missing teachers, subjects, or classes)";
    }
    
    // Test Fee Collection
    $results[] = "<br><strong>🧪 Testing Fee Collection:</strong>";
    $fee = new Fee();
    
    $studentStmt = $db->query("SELECT id FROM students LIMIT 1");
    $studentId = $studentStmt->fetchColumn();
    
    $feeTypeStmt = $db->query("SELECT id FROM fee_types LIMIT 1");
    $feeTypeId = $feeTypeStmt->fetchColumn();
    
    if ($studentId && $feeTypeId) {
        $result = $fee->collectFee(
            $studentId,
            $feeTypeId,
            100.00,
            'cash',
            1, // admin user id
            date('Y-m'),
            null,
            'Test payment'
        );
        
        if ($result['success']) {
            $results[] = "✅ Fee collection test successful - Receipt: " . $result['receipt_number'];
            
            // Test getting student fee status
            $feeStatus = $fee->getStudentFeeStatus($studentId);
            $results[] = "✅ Retrieved fee status for student: " . count($feeStatus) . " fee types";
            
            // Clean up test payment
            $cleanupStmt = $db->prepare("DELETE FROM fee_payments WHERE receipt_number = ?");
            $cleanupStmt->execute([$result['receipt_number']]);
            $results[] = "✅ Test payment cleaned up";
        } else {
            $errors[] = "❌ Fee collection failed: " . $result['message'];
        }
    } else {
        $errors[] = "⚠️ No test data available (missing students or fee types)";
    }
    
    // Test Student Attendance
    $results[] = "<br><strong>🧪 Testing Student Attendance:</strong>";
    $student = new Student();
    
    if ($studentId && $classId) {
        $result = $student->markAttendance(
            $studentId,
            $classId,
            'present',
            1, // admin user id
            date('Y-m-d'),
            '08:30:00', // check-in time
            '15:30:00', // check-out time
            'Test attendance'
        );
        
        if ($result['success']) {
            $results[] = "✅ Attendance marking test successful";
            
            // Test getting attendance by month
            $attendance = $student->getAttendanceByMonth($studentId, date('Y-m'));
            $results[] = "✅ Retrieved " . count($attendance) . " attendance records";
            
            // Test attendance summary
            $summary = $student->getAttendanceSummary($studentId);
            $results[] = "✅ Attendance summary: {$summary['present']} present, {$summary['absent']} absent";
            
            // Clean up test attendance
            $cleanupStmt = $db->prepare("DELETE FROM student_attendance WHERE student_id = ? AND date = ?");
            $cleanupStmt->execute([$studentId, date('Y-m-d')]);
            $results[] = "✅ Test attendance cleaned up";
        } else {
            $errors[] = "❌ Attendance marking failed: " . $result['message'];
        }
    } else {
        $errors[] = "⚠️ No test data available (missing students or classes)";
    }
    
    // Test Database Structure
    $results[] = "<br><strong>📊 Database Structure Check:</strong>";
    
    // Check teacher_subjects table
    $stmt = $db->query("DESCRIBE teacher_subjects");
    $columns = array_column($stmt->fetchAll(), 'Field');
    if (in_array('assigned_date', $columns)) {
        $results[] = "✅ teacher_subjects.assigned_date column exists";
    } else {
        $errors[] = "❌ teacher_subjects.assigned_date column missing";
    }
    
    // Check student_attendance table
    $stmt = $db->query("DESCRIBE student_attendance");
    $columns = array_column($stmt->fetchAll(), 'Field');
    $hasCheckIn = in_array('check_in_time', $columns);
    $hasCheckOut = in_array('check_out_time', $columns);
    
    if ($hasCheckIn && $hasCheckOut) {
        $results[] = "✅ student_attendance check-in/check-out columns exist";
    } else {
        $results[] = "⚠️ student_attendance check-in/check-out columns missing (will be added by comprehensive fix)";
    }
    
    // Check fee_payments table
    $stmt = $db->query("SHOW CREATE TABLE fee_payments");
    $createTable = $stmt->fetchColumn(1);
    if (strpos($createTable, "'paid'") !== false) {
        $results[] = "✅ fee_payments.status includes 'paid' option";
    } else {
        $errors[] = "❌ fee_payments.status missing 'paid' option";
    }
    
    if (empty($errors)) {
        $results[] = "<br>🎉 <strong>All tests passed successfully!</strong>";
        $results[] = "✅ Subject assignment functionality works";
        $results[] = "✅ Fee collection functionality works";
        $results[] = "✅ Attendance tracking functionality works";
        $results[] = "✅ Database structure is correct";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Critical error: " . $e->getMessage();
    $results[] = "Error in file: " . $e->getFile() . " line " . $e->getLine();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Test Fixes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-green-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-vial text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Test All Fixes</h1>
                <p class="text-gray-600 mt-2">Automated testing of subject assignment, fee collection, and attendance</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <h4 class="font-semibold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>Test Failures:</h4>
                <ul class="mt-2 text-sm">
                    <?php foreach ($errors as $error): ?>
                    <li>• <?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4 max-h-96 overflow-y-auto">
                <h4 class="font-semibold flex items-center"><i class="fas fa-flask mr-2"></i>Test Results:</h4>
                <div class="mt-2 text-sm space-y-1">
                    <?php foreach ($results as $result): ?>
                    <div><?php echo $result; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($errors)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <h4 class="font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>All Tests Passed!</h4>
                <p class="text-sm mt-2">Your School CRM is working properly. All core functionality has been tested successfully.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="teachers/subjects.php?id=1" class="bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-book mr-2"></i>Subject Assignment
                </a>
                <a href="fees/collection.php" class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-money-bill mr-2"></i>Fee Collection
                </a>
                <a href="students/list.php" class="bg-purple-600 text-white px-4 py-3 rounded-md hover:bg-purple-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-users mr-2"></i>Student Management
                </a>
                <a href="index.php" class="bg-gray-600 text-white px-4 py-3 rounded-md hover:bg-gray-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
            </div>
            <?php else: ?>
            <div class="text-center">
                <p class="text-gray-600 mb-4">Some tests failed. Please run the comprehensive fix first:</p>
                <a href="comprehensive-fix.php" class="bg-red-600 text-white px-6 py-3 rounded-md hover:bg-red-700 transition-colors flex items-center mx-auto w-max">
                    <i class="fas fa-tools mr-2"></i>Run Comprehensive Fix
                </a>
            </div>
            <?php endif; ?>

            <div class="mt-8 text-center border-t pt-6">
                <div class="flex justify-center space-x-6 text-sm">
                    <a href="comprehensive-fix.php" class="text-blue-600 hover:text-blue-800">Comprehensive Fix</a>
                    <a href="debug-subjects.php" class="text-blue-600 hover:text-blue-800">Debug Subjects</a>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                    <button onclick="location.reload()" class="text-green-600 hover:text-green-800">
                        <i class="fas fa-redo mr-1"></i>Run Tests Again
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>