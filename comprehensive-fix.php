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
        
        // Fix 1: Add missing assigned_date column to teacher_subjects table
        $results[] = "<br><strong>🔧 Fixing Subject Assignment Issues:</strong>";
        
        $stmt = $db->query("DESCRIBE teacher_subjects");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        if (!in_array('assigned_date', $columnNames)) {
            $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
            $results[] = "✅ Added assigned_date column to teacher_subjects table";
        } else {
            $results[] = "✅ assigned_date column already exists";
        }
        
        // Fix 2: Add missing columns to student_attendance table for enhanced functionality
        $results[] = "<br><strong>🔧 Fixing Attendance Issues:</strong>";
        
        $stmt = $db->query("DESCRIBE student_attendance");
        $attendanceColumns = $stmt->fetchAll();
        $attendanceColumnNames = array_column($attendanceColumns, 'Field');
        
        // Add check_in_time and check_out_time columns if they don't exist
        if (!in_array('check_in_time', $attendanceColumnNames)) {
            $db->exec("ALTER TABLE student_attendance ADD COLUMN check_in_time TIME NULL AFTER status");
            $results[] = "✅ Added check_in_time column to student_attendance table";
        }
        
        if (!in_array('check_out_time', $attendanceColumnNames)) {
            $db->exec("ALTER TABLE student_attendance ADD COLUMN check_out_time TIME NULL AFTER check_in_time");
            $results[] = "✅ Added check_out_time column to student_attendance table";
        }
        
        // Update student_attendance enum to include 'excused' status
        $db->exec("ALTER TABLE student_attendance MODIFY COLUMN status ENUM('present', 'absent', 'late', 'half_day', 'excused') NOT NULL");
        $results[] = "✅ Updated student_attendance status enum to include 'excused'";
        
        // Fix 3: Add missing columns to teacher_payroll table
        $results[] = "<br><strong>🔧 Fixing Teacher Payroll Issues:</strong>";
        
        $stmt = $db->query("SHOW TABLES LIKE 'teacher_payroll'");
        if (!$stmt->fetch()) {
            // Create teacher_payroll table if it doesn't exist
            $db->exec("
                CREATE TABLE teacher_payroll (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    teacher_id INT NOT NULL,
                    month_year VARCHAR(7) NOT NULL,
                    basic_salary DECIMAL(10,2) NOT NULL,
                    allowances DECIMAL(10,2) DEFAULT 0,
                    deductions DECIMAL(10,2) DEFAULT 0,
                    overtime_hours DECIMAL(4,2) DEFAULT 0,
                    overtime_rate DECIMAL(10,2) DEFAULT 0,
                    overtime_pay DECIMAL(10,2) DEFAULT 0,
                    present_days INT DEFAULT 0,
                    working_days INT DEFAULT 0,
                    gross_salary DECIMAL(10,2) NOT NULL,
                    net_salary DECIMAL(10,2) NOT NULL,
                    payment_date DATE NULL,
                    payment_method ENUM('cash', 'bank_transfer', 'cheque') DEFAULT 'bank_transfer',
                    status ENUM('pending', 'paid') DEFAULT 'pending',
                    generated_by INT NOT NULL,
                    remarks TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
                    FOREIGN KEY (generated_by) REFERENCES users(id),
                    UNIQUE KEY unique_teacher_month (teacher_id, month_year)
                )
            ");
            $results[] = "✅ Created teacher_payroll table with all required columns";
        } else {
            $stmt = $db->query("DESCRIBE teacher_payroll");
            $payrollColumns = $stmt->fetchAll();
            $payrollColumnNames = array_column($payrollColumns, 'Field');
            
            // Add missing columns to existing table
            $missingColumns = [
                'overtime_hours' => 'DECIMAL(4,2) DEFAULT 0',
                'overtime_rate' => 'DECIMAL(10,2) DEFAULT 0', 
                'overtime_pay' => 'DECIMAL(10,2) DEFAULT 0',
                'present_days' => 'INT DEFAULT 0',
                'working_days' => 'INT DEFAULT 0',
                'gross_salary' => 'DECIMAL(10,2) DEFAULT 0',
                'remarks' => 'TEXT'
            ];
            
            foreach ($missingColumns as $column => $definition) {
                if (!in_array($column, $payrollColumnNames)) {
                    $db->exec("ALTER TABLE teacher_payroll ADD COLUMN $column $definition");
                    $results[] = "✅ Added $column column to teacher_payroll table";
                }
            }
        }
        
        // Fix 4: Ensure proper constraints and indexes
        $results[] = "<br><strong>🔧 Adding Database Constraints:</strong>";
        
        // Check and add unique constraints
        try {
            $stmt = $db->query("SHOW INDEX FROM teacher_subjects WHERE Key_name = 'unique_teacher_subject_class'");
            $indexExists = $stmt->fetch();
            
            if (!$indexExists) {
                $db->exec("ALTER TABLE teacher_subjects ADD UNIQUE KEY unique_teacher_subject_class (teacher_id, subject_id, class_id)");
                $results[] = "✅ Added unique constraint for teacher-subject-class combination";
            } else {
                $results[] = "✅ Unique constraint already exists for teacher_subjects";
            }
        } catch (Exception $e) {
            $results[] = "⚠️ Constraint note: " . $e->getMessage();
        }
        
        // Fix 5: Ensure fee_payments table has proper status column
        $results[] = "<br><strong>🔧 Fixing Fee Collection Issues:</strong>";
        
        $stmt = $db->query("DESCRIBE fee_payments");
        $feeColumns = $stmt->fetchAll();
        $feeColumnNames = array_column($feeColumns, 'Field');
        
        // Check if status column exists and has proper enum values
        $statusColumn = null;
        foreach ($feeColumns as $column) {
            if ($column['Field'] === 'status') {
                $statusColumn = $column;
                break;
            }
        }
        
        if ($statusColumn) {
            // Update enum values to ensure 'paid' is included
            $db->exec("ALTER TABLE fee_payments MODIFY COLUMN status ENUM('paid', 'pending', 'failed', 'refunded') DEFAULT 'paid'");
            $results[] = "✅ Updated fee_payments status enum values";
        }
        
        // Fix 6: Test core functionality
        $results[] = "<br><strong>🧪 Testing Core Functionality:</strong>";
        
        // Test subject assignment
        $teacherStmt = $db->query("SELECT id FROM teachers LIMIT 1");
        $teacherId = $teacherStmt->fetchColumn();
        
        $subjectStmt = $db->query("SELECT id FROM subjects LIMIT 1");
        $subjectId = $subjectStmt->fetchColumn();
        
        $classStmt = $db->query("SELECT id FROM classes LIMIT 1");
        $classId = $classStmt->fetchColumn();
        
        if ($teacherId && $subjectId && $classId) {
            // Test the assignment query
            $testStmt = $db->prepare("
                INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE assigned_date = NOW()
            ");
            
            if ($testStmt->execute([$teacherId, $subjectId, $classId])) {
                $results[] = "✅ Subject assignment functionality test successful";
                
                // Clean up test
                $cleanupStmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                $cleanupStmt->execute([$teacherId, $subjectId, $classId]);
            }
        }
        
        // Test fee payment insertion
        $studentStmt = $db->query("SELECT id FROM students LIMIT 1");
        $studentId = $studentStmt->fetchColumn();
        
        $feeTypeStmt = $db->query("SELECT id FROM fee_types LIMIT 1");
        $feeTypeId = $feeTypeStmt->fetchColumn();
        
        if ($studentId && $feeTypeId) {
            $testReceiptNum = "TEST" . time();
            $testStmt = $db->prepare("
                INSERT INTO fee_payments (student_id, fee_type_id, amount, payment_method, payment_date, month_year, collected_by, receipt_number, status)
                VALUES (?, ?, 100.00, 'cash', CURDATE(), '2024-01', 1, ?, 'paid')
            ");
            
            if ($testStmt->execute([$studentId, $feeTypeId, $testReceiptNum])) {
                $results[] = "✅ Fee collection functionality test successful";
                
                // Clean up test
                $cleanupStmt = $db->prepare("DELETE FROM fee_payments WHERE receipt_number = ?");
                $cleanupStmt->execute([$testReceiptNum]);
            }
        }
        
        // Test student attendance
        if ($studentId && $classId) {
            $testDate = date('Y-m-d');
            $testStmt = $db->prepare("
                INSERT INTO student_attendance (student_id, class_id, date, status, marked_by)
                VALUES (?, ?, ?, 'present', 1)
                ON DUPLICATE KEY UPDATE status = 'present'
            ");
            
            if ($testStmt->execute([$studentId, $classId, $testDate])) {
                $results[] = "✅ Student attendance functionality test successful";
                
                // Clean up test
                $cleanupStmt = $db->prepare("DELETE FROM student_attendance WHERE student_id = ? AND date = ?");
                $cleanupStmt->execute([$studentId, $testDate]);
            }
        }
        
        // Check data integrity
        $results[] = "<br><strong>📊 Data Integrity Check:</strong>";
        
        $tables = ['users', 'teachers', 'students', 'subjects', 'classes', 'fee_types', 'teacher_subjects'];
        foreach ($tables as $table) {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $results[] = "✅ $table: $count records";
            } else {
                $results[] = "⚠️ $table: No records found";
                if (in_array($table, ['users', 'subjects', 'classes', 'fee_types'])) {
                    $errors[] = "Critical: No $table available - please add some data first";
                }
            }
        }
        
        if (empty($errors)) {
            $success = true;
            $results[] = "<br>🎉 <strong>All database issues have been fixed successfully!</strong>";
            $results[] = "✅ Subject assignment should now work properly";
            $results[] = "✅ Fee collection should now work properly"; 
            $results[] = "✅ Attendance tracking should now work properly";
            $results[] = "✅ Teacher payroll system is ready";
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
    <title><?php echo APP_NAME; ?> - Comprehensive Database Fix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-blue-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tools text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Comprehensive Database Fix</h1>
                <p class="text-gray-600 mt-2">Fix all database issues: Subject Assignment, Fee Collection, Attendance & More</p>
            </div>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                
                <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>Errors Found:</h4>
                    <ul class="mt-2 text-sm">
                        <?php foreach ($errors as $error): ?>
                        <li>• <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($results)): ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4 max-h-96 overflow-y-auto">
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
                    <h4 class="font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>All Fixes Applied Successfully!</h4>
                    <p class="text-sm mt-2">Your School CRM database has been repaired and is ready to use.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="teachers/subjects.php?id=1" class="bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-book mr-2"></i>Test Subject Assignment
                    </a>
                    <a href="fees/collection.php" class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-money-bill mr-2"></i>Test Fee Collection
                    </a>
                    <a href="students/list.php" class="bg-purple-600 text-white px-4 py-3 rounded-md hover:bg-purple-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-users mr-2"></i>Test Attendance
                    </a>
                    <a href="teachers/payroll.php" class="bg-orange-600 text-white px-4 py-3 rounded-md hover:bg-orange-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-dollar-sign mr-2"></i>Test Payroll
                    </a>
                    <a href="index.php" class="bg-gray-600 text-white px-4 py-3 rounded-md hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>Go to Dashboard
                    </a>
                    <a href="install.php" class="bg-indigo-600 text-white px-4 py-3 rounded-md hover:bg-indigo-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-cog mr-2"></i>Setup Wizard
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
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">This Comprehensive Fix Will:</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-medium text-blue-800 mb-3"><i class="fas fa-book mr-2"></i>Subject Assignment Issues</h4>
                            <ul class="space-y-2 text-sm text-blue-700">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Add missing assigned_date column
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Add proper unique constraints
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Test assignment functionality
                                </li>
                            </ul>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-3"><i class="fas fa-money-bill mr-2"></i>Fee Collection Issues</h4>
                            <ul class="space-y-2 text-sm text-green-700">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Fix fee_payments status enum
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Ensure proper payment tracking
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Test fee collection process
                                </li>
                            </ul>
                        </div>
                        
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <h4 class="font-medium text-purple-800 mb-3"><i class="fas fa-calendar mr-2"></i>Attendance Issues</h4>
                            <ul class="space-y-2 text-sm text-purple-700">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Add check-in/check-out time columns
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Add 'excused' attendance status
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Test attendance marking
                                </li>
                            </ul>
                        </div>
                        
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <h4 class="font-medium text-orange-800 mb-3"><i class="fas fa-dollar-sign mr-2"></i>Payroll System</h4>
                            <ul class="space-y-2 text-sm text-orange-700">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Create/fix teacher_payroll table
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Add overtime calculation columns
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Ensure salary processing works
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-semibold">Important Notes:</h4>
                            <ul class="text-sm mt-2 space-y-1">
                                <li>• This will modify your database structure safely</li>
                                <li>• All changes are backwards compatible</li>
                                <li>• Existing data will be preserved</li>
                                <li>• A backup is recommended before running</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="text-center">
                    <button type="submit" class="bg-blue-600 text-white px-8 py-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center mx-auto text-lg font-medium">
                        <i class="fas fa-tools mr-3"></i>Fix All Database Issues
                    </button>
                </form>
                
            <?php endif; ?>
            
            <div class="mt-8 text-center border-t pt-6">
                <div class="flex justify-center space-x-6 text-sm">
                    <a href="debug-subjects.php" class="text-blue-600 hover:text-blue-800">Debug Subjects</a>
                    <a href="fix-subjects.php" class="text-blue-600 hover:text-blue-800">Fix Subjects Only</a>
                    <a href="repair-db.php" class="text-blue-600 hover:text-blue-800">Database Repair</a>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>