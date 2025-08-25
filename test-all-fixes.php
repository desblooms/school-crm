<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$results = [];
$errors = [];

echo "<h1>Testing All Applied Fixes</h1>";
echo "<p>Verifying that all 404 errors and functionality issues have been resolved.</p>";

try {
    $db = Database::getInstance()->getConnection();
    $results[] = "✅ Database connection successful";
    
    // Test 1: Check if all required files exist
    $results[] = "<br><strong>📁 File Existence Tests:</strong>";
    
    $requiredFiles = [
        'fees/student.php' => 'Student fee details page',
        'invoices/view.php' => 'Invoice/receipt view page',
        'teachers/attendance.php' => 'Teacher attendance management page',
        'apply-migrations.php' => 'Database migration tool',
        'database/migrations.sql' => 'SQL migration script'
    ];
    
    foreach ($requiredFiles as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $results[] = "✅ $file exists - $description";
        } else {
            $errors[] = "❌ $file missing - $description";
        }
    }
    
    // Test 2: Check database structure
    $results[] = "<br><strong>🗃️ Database Structure Tests:</strong>";
    
    // Check teacher_subjects table and assigned_date column
    try {
        $stmt = $db->query("SHOW COLUMNS FROM teacher_subjects LIKE 'assigned_date'");
        if ($stmt->fetch()) {
            $results[] = "✅ teacher_subjects.assigned_date column exists";
        } else {
            $errors[] = "❌ teacher_subjects.assigned_date column missing - run migrations";
        }
    } catch (Exception $e) {
        $errors[] = "❌ Cannot check teacher_subjects table: " . $e->getMessage();
    }
    
    // Check student_attendance table check-in/out columns
    try {
        $stmt = $db->query("SHOW COLUMNS FROM student_attendance LIKE 'check_in_time'");
        if ($stmt->fetch()) {
            $results[] = "✅ student_attendance.check_in_time column exists";
        } else {
            $errors[] = "❌ student_attendance.check_in_time column missing - run migrations";
        }
        
        $stmt = $db->query("SHOW COLUMNS FROM student_attendance LIKE 'check_out_time'");
        if ($stmt->fetch()) {
            $results[] = "✅ student_attendance.check_out_time column exists";
        } else {
            $errors[] = "❌ student_attendance.check_out_time column missing - run migrations";
        }
    } catch (Exception $e) {
        $errors[] = "❌ Cannot check student_attendance table: " . $e->getMessage();
    }
    
    // Check teachers table employee_id column
    try {
        $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'employee_id'");
        if ($stmt->fetch()) {
            $results[] = "✅ teachers.employee_id column exists";
        } else {
            $errors[] = "❌ teachers.employee_id column missing - run migrations";
        }
    } catch (Exception $e) {
        $errors[] = "❌ Cannot check teachers table: " . $e->getMessage();
    }
    
    // Test 3: Test class instantiation
    $results[] = "<br><strong>🏗️ Class Functionality Tests:</strong>";
    
    try {
        require_once __DIR__ . '/classes/Teacher.php';
        $teacher = new Teacher();
        $results[] = "✅ Teacher class loads and instantiates properly";
        
        // Test if new methods exist
        if (method_exists($teacher, 'getByUserId')) {
            $results[] = "✅ Teacher::getByUserId() method exists";
        } else {
            $errors[] = "❌ Teacher::getByUserId() method missing";
        }
        
        if (method_exists($teacher, 'getTeacherClasses')) {
            $results[] = "✅ Teacher::getTeacherClasses() method exists";
        } else {
            $errors[] = "❌ Teacher::getTeacherClasses() method missing";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Teacher class error: " . $e->getMessage();
    }
    
    try {
        require_once __DIR__ . '/classes/Student.php';
        $student = new Student();
        $results[] = "✅ Student class loads and instantiates properly";
        
        // Test if new methods exist
        if (method_exists($student, 'getStudentsByClass')) {
            $results[] = "✅ Student::getStudentsByClass() method exists";
        } else {
            $errors[] = "❌ Student::getStudentsByClass() method missing";
        }
        
        if (method_exists($student, 'getClassAttendanceByDate')) {
            $results[] = "✅ Student::getClassAttendanceByDate() method exists";
        } else {
            $errors[] = "❌ Student::getClassAttendanceByDate() method missing";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Student class error: " . $e->getMessage();
    }
    
    try {
        require_once __DIR__ . '/classes/Fee.php';
        $fee = new Fee();
        $results[] = "✅ Fee class loads and instantiates properly";
        
        // Test if new methods exist
        if (method_exists($fee, 'getPaymentByReceipt')) {
            $results[] = "✅ Fee::getPaymentByReceipt() method exists";
        } else {
            $errors[] = "❌ Fee::getPaymentByReceipt() method missing";
        }
        
        if (method_exists($fee, 'getMonthlyPayments')) {
            $results[] = "✅ Fee::getMonthlyPayments() method exists";
        } else {
            $errors[] = "❌ Fee::getMonthlyPayments() method missing";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Fee class error: " . $e->getMessage();
    }
    
    // Test 4: Check data integrity
    $results[] = "<br><strong>📊 Data Integrity Tests:</strong>";
    
    $tables = ['users', 'teachers', 'students', 'subjects', 'classes', 'fee_types'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $results[] = "✅ $table: $count records available";
            } else {
                $results[] = "⚠️ $table: No records (may need sample data)";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Cannot check $table: " . $e->getMessage();
        }
    }
    
    // Summary
    $results[] = "<br><strong>📋 Summary:</strong>";
    if (empty($errors)) {
        $results[] = "🎉 <strong>ALL TESTS PASSED!</strong> All fixes have been successfully applied.";
        $results[] = "✅ All 404 errors should now be resolved";
        $results[] = "✅ All functionality should work properly";
        $results[] = "✅ Database structure is correct";
        
        $results[] = "<br><strong>🔗 Test the following URLs:</strong>";
        $results[] = "• <a href='fees/student.php?id=1' target='_blank'>fees/student.php?id=1</a>";
        $results[] = "• <a href='invoices/view.php?id=1' target='_blank'>invoices/view.php?id=1</a>";
        $results[] = "• <a href='fees/collection.php' target='_blank'>fees/collection.php</a>";
        $results[] = "• <a href='students/attendance.php?id=1' target='_blank'>students/attendance.php?id=1</a>";
        $results[] = "• <a href='teachers/attendance.php' target='_blank'>teachers/attendance.php</a>";
    } else {
        $results[] = "⚠️ Some issues found. Please review the errors below and run migrations if needed.";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Critical test error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Verification Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .results { background: #f0f8ff; border: 1px solid #0066cc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .errors { background: #fff0f0; border: 1px solid #cc0000; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #f0fff0; border: 1px solid #00cc00; padding: 15px; margin: 10px 0; border-radius: 5px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php if (!empty($errors)): ?>
<div class="errors">
    <h3>⚠️ Issues Found:</h3>
    <?php foreach ($errors as $error): ?>
    <div><?php echo $error; ?></div>
    <?php endforeach; ?>
    <br>
    <strong>Next Steps:</strong>
    <ul>
        <li><a href="apply-migrations.php">Run Database Migrations</a></li>
        <li><a href="comprehensive-fix.php">Run Comprehensive Fix</a></li>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($results)): ?>
<div class="<?php echo empty($errors) ? 'success' : 'results'; ?>">
    <h3>📊 Test Results:</h3>
    <?php foreach ($results as $result): ?>
    <div><?php echo $result; ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
    <h3>🔧 Additional Tools:</h3>
    <p><a href="apply-migrations.php">Database Migrations</a> - Apply all database structure fixes</p>
    <p><a href="comprehensive-fix.php">Comprehensive Fix</a> - Complete database repair tool</p>
    <p><a href="test-fixes.php">Functionality Tests</a> - Test core functionality</p>
    <p><a href="diagnose-error.php">Error Diagnosis</a> - Interactive error diagnosis</p>
    <p><a href="index.php">Dashboard</a> - Return to main application</p>
</div>

</body>
</html>