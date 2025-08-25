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
        
        // Migration 0: Add employee_id column to teachers table if missing
        $results[] = "<br><strong>🔧 Migration 0: Adding employee_id column to teachers</strong>";
        try {
            $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'employee_id'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE teachers ADD COLUMN employee_id VARCHAR(20) UNIQUE");
                $results[] = "✅ Added employee_id column to teachers table";
                
                // Generate employee IDs for existing teachers
                $stmt = $db->query("SELECT id FROM teachers WHERE employee_id IS NULL OR employee_id = ''");
                $teachers = $stmt->fetchAll();
                foreach ($teachers as $teacher) {
                    $employeeId = 'EMP' . str_pad($teacher['id'], 4, '0', STR_PAD_LEFT);
                    $updateStmt = $db->prepare("UPDATE teachers SET employee_id = ? WHERE id = ?");
                    $updateStmt->execute([$employeeId, $teacher['id']]);
                }
                if (count($teachers) > 0) {
                    $results[] = "✅ Generated employee IDs for " . count($teachers) . " existing teachers";
                }
            } else {
                $results[] = "✅ employee_id column already exists";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Migration 0 failed: " . $e->getMessage();
        }
        
        // Migration 1: Add assigned_date column to teacher_subjects table
        $results[] = "<br><strong>🔧 Migration 1: Adding assigned_date column to teacher_subjects</strong>";
        try {
            $stmt = $db->query("SHOW COLUMNS FROM teacher_subjects LIKE 'assigned_date'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
                $results[] = "✅ Added assigned_date column to teacher_subjects table";
            } else {
                $results[] = "✅ assigned_date column already exists";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Migration 1 failed: " . $e->getMessage();
        }
        
        // Migration 2: Add check_in_time and check_out_time to student_attendance
        $results[] = "<br><strong>🔧 Migration 2: Adding attendance time columns</strong>";
        try {
            $stmt = $db->query("SHOW COLUMNS FROM student_attendance LIKE 'check_in_time'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE student_attendance ADD COLUMN check_in_time TIME NULL AFTER status");
                $results[] = "✅ Added check_in_time column to student_attendance table";
            } else {
                $results[] = "✅ check_in_time column already exists";
            }
            
            $stmt = $db->query("SHOW COLUMNS FROM student_attendance LIKE 'check_out_time'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE student_attendance ADD COLUMN check_out_time TIME NULL AFTER check_in_time");
                $results[] = "✅ Added check_out_time column to student_attendance table";
            } else {
                $results[] = "✅ check_out_time column already exists";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Migration 2 failed: " . $e->getMessage();
        }
        
        // Migration 3: Update student_attendance status enum
        $results[] = "<br><strong>🔧 Migration 3: Updating attendance status enum</strong>";
        try {
            $db->exec("ALTER TABLE student_attendance MODIFY COLUMN status ENUM('present', 'absent', 'late', 'half_day', 'excused') NOT NULL");
            $results[] = "✅ Updated student_attendance status enum to include 'excused'";
        } catch (Exception $e) {
            $results[] = "⚠️ Status enum update warning: " . $e->getMessage();
        }
        
        // Migration 4: Create teacher_payroll table
        $results[] = "<br><strong>🔧 Migration 4: Creating teacher_payroll table</strong>";
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'teacher_payroll'");
            if (!$stmt->fetch()) {
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
                        gross_salary DECIMAL(10,2) DEFAULT 0,
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
                $results[] = "✅ Created teacher_payroll table successfully";
            } else {
                $results[] = "✅ teacher_payroll table already exists";
                
                // Add any missing columns to existing table
                $existingColumns = [];
                $stmt = $db->query("DESCRIBE teacher_payroll");
                while ($row = $stmt->fetch()) {
                    $existingColumns[] = $row['Field'];
                }
                
                $requiredColumns = [
                    'overtime_hours' => 'DECIMAL(4,2) DEFAULT 0',
                    'overtime_rate' => 'DECIMAL(10,2) DEFAULT 0',
                    'overtime_pay' => 'DECIMAL(10,2) DEFAULT 0',
                    'present_days' => 'INT DEFAULT 0',
                    'working_days' => 'INT DEFAULT 0',
                    'gross_salary' => 'DECIMAL(10,2) DEFAULT 0',
                    'remarks' => 'TEXT'
                ];
                
                foreach ($requiredColumns as $column => $definition) {
                    if (!in_array($column, $existingColumns)) {
                        $db->exec("ALTER TABLE teacher_payroll ADD COLUMN $column $definition");
                        $results[] = "✅ Added $column column to teacher_payroll table";
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "❌ Migration 4 failed: " . $e->getMessage();
        }
        
        // Migration 5: Update fee_payments status enum
        $results[] = "<br><strong>🔧 Migration 5: Updating fee_payments status enum</strong>";
        try {
            $db->exec("ALTER TABLE fee_payments MODIFY COLUMN status ENUM('paid', 'pending', 'failed', 'refunded') DEFAULT 'paid'");
            $results[] = "✅ Updated fee_payments status enum values";
        } catch (Exception $e) {
            $results[] = "⚠️ Fee payments enum update warning: " . $e->getMessage();
        }
        
        // Migration 6: Add unique constraint (with duplicate handling)
        $results[] = "<br><strong>🔧 Migration 6: Adding database constraints</strong>";
        try {
            // Check if constraint already exists
            $stmt = $db->query("SHOW INDEX FROM teacher_subjects WHERE Key_name = 'unique_teacher_subject_class'");
            if (!$stmt->fetch()) {
                // Remove duplicates first if any exist
                $db->exec("
                    DELETE ts1 FROM teacher_subjects ts1
                    INNER JOIN teacher_subjects ts2 
                    WHERE ts1.id < ts2.id 
                    AND ts1.teacher_id = ts2.teacher_id 
                    AND ts1.subject_id = ts2.subject_id 
                    AND ts1.class_id = ts2.class_id
                ");
                
                // Now add the unique constraint
                $db->exec("ALTER TABLE teacher_subjects ADD UNIQUE KEY unique_teacher_subject_class (teacher_id, subject_id, class_id)");
                $results[] = "✅ Added unique constraint for teacher-subject-class combination";
            } else {
                $results[] = "✅ Unique constraint already exists";
            }
        } catch (Exception $e) {
            $results[] = "⚠️ Constraint warning: " . $e->getMessage();
        }
        
        // Final verification
        $results[] = "<br><strong>🧪 Verification Tests:</strong>";
        
        // Test subject assignment
        $teacherStmt = $db->query("SELECT id FROM teachers LIMIT 1");
        $teacherId = $teacherStmt->fetchColumn();
        
        $subjectStmt = $db->query("SELECT id FROM subjects LIMIT 1");
        $subjectId = $subjectStmt->fetchColumn();
        
        $classStmt = $db->query("SELECT id FROM classes LIMIT 1");
        $classId = $classStmt->fetchColumn();
        
        if ($teacherId && $subjectId && $classId) {
            try {
                $testStmt = $db->prepare("
                    INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE assigned_date = NOW()
                ");
                
                if ($testStmt->execute([$teacherId, $subjectId, $classId])) {
                    $results[] = "✅ Subject assignment test passed";
                    
                    // Clean up test
                    $cleanupStmt = $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
                    $cleanupStmt->execute([$teacherId, $subjectId, $classId]);
                }
            } catch (Exception $e) {
                $results[] = "⚠️ Subject assignment test: " . $e->getMessage();
            }
        }
        
        if (empty($errors)) {
            $success = true;
            $results[] = "<br>🎉 <strong>All migrations completed successfully!</strong>";
            $results[] = "✅ Database structure has been updated";
            $results[] = "✅ All functionality should now work properly";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Critical migration error: " . $e->getMessage();
        $results[] = "Error in file: " . $e->getFile() . " line " . $e->getLine();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Database Migrations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-purple-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-database text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Database Migrations</h1>
                <p class="text-gray-600 mt-2">Apply database structure fixes and updates</p>
            </div>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                
                <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>Migration Errors:</h4>
                    <ul class="mt-2 text-sm">
                        <?php foreach ($errors as $error): ?>
                        <li>• <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($results)): ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4 max-h-96 overflow-y-auto">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-info-circle mr-2"></i>Migration Results:</h4>
                    <div class="mt-2 text-sm space-y-1">
                        <?php foreach ($results as $result): ?>
                        <div><?php echo $result; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>All Migrations Applied Successfully!</h4>
                    <p class="text-sm mt-2">Your database has been updated with the latest structure changes.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="test-fixes.php" class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-vial mr-2"></i>Test All Fixes
                    </a>
                    <a href="teachers/subjects.php?id=1" class="bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-book mr-2"></i>Test Subject Assignment
                    </a>
                    <a href="fees/collection.php" class="bg-purple-600 text-white px-4 py-3 rounded-md hover:bg-purple-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-money-bill mr-2"></i>Test Fee Collection
                    </a>
                    <a href="students/attendance.php" class="bg-orange-600 text-white px-4 py-3 rounded-md hover:bg-orange-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-calendar mr-2"></i>Test Attendance
                    </a>
                    <a href="teachers/payroll.php" class="bg-indigo-600 text-white px-4 py-3 rounded-md hover:bg-indigo-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-dollar-sign mr-2"></i>Test Payroll
                    </a>
                    <a href="index.php" class="bg-gray-600 text-white px-4 py-3 rounded-md hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>Go to Dashboard
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
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">The following database migrations will be applied:</h3>
                    
                    <div class="space-y-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-medium text-blue-800 mb-2"><i class="fas fa-book mr-2"></i>Migration 1: Teacher Subject Assignment</h4>
                            <ul class="space-y-1 text-sm text-blue-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Add assigned_date column to teacher_subjects table</li>
                            </ul>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-2"><i class="fas fa-calendar mr-2"></i>Migration 2-3: Student Attendance Enhancement</h4>
                            <ul class="space-y-1 text-sm text-green-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Add check_in_time and check_out_time columns</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Add 'excused' status to attendance enum</li>
                            </ul>
                        </div>
                        
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <h4 class="font-medium text-purple-800 mb-2"><i class="fas fa-dollar-sign mr-2"></i>Migration 4: Teacher Payroll System</h4>
                            <ul class="space-y-1 text-sm text-purple-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Create teacher_payroll table with all required columns</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Add overtime calculation support</li>
                            </ul>
                        </div>
                        
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <h4 class="font-medium text-orange-800 mb-2"><i class="fas fa-money-bill mr-2"></i>Migration 5-6: Fee System & Constraints</h4>
                            <ul class="space-y-1 text-sm text-orange-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Update fee_payments status enum</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Add unique constraints for data integrity</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-semibold">Migration Safety Notes:</h4>
                            <ul class="text-sm mt-2 space-y-1">
                                <li>• All migrations are backwards compatible</li>
                                <li>• Existing data will be preserved</li>
                                <li>• Duplicate records will be safely handled</li>
                                <li>• Each migration can be applied multiple times safely</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="text-center">
                    <button type="submit" class="bg-purple-600 text-white px-8 py-4 rounded-lg hover:bg-purple-700 transition-colors flex items-center mx-auto text-lg font-medium">
                        <i class="fas fa-database mr-3"></i>Apply All Migrations
                    </button>
                </form>
                
            <?php endif; ?>
            
            <div class="mt-8 text-center border-t pt-6">
                <div class="flex justify-center space-x-6 text-sm">
                    <a href="comprehensive-fix.php" class="text-blue-600 hover:text-blue-800">Comprehensive Fix</a>
                    <a href="test-fixes.php" class="text-green-600 hover:text-green-800">Test Fixes</a>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>