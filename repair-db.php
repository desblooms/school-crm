<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

$success = false;
$errors = [];
$warnings = [];
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $results[] = "✅ Database connection successful";
        
        // Check existing tables
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $results[] = "ℹ️ Found " . count($existingTables) . " existing tables: " . implode(', ', $existingTables);
        
        // Required tables
        $requiredTables = [
            'users', 'students', 'teachers', 'classes', 'subjects', 
            'fee_types', 'fee_structure', 'fee_payments', 'invoices', 
            'invoice_items', 'student_attendance', 'teacher_attendance',
            'teacher_subjects', 'activity_logs', 'settings'
        ];
        
        $missingTables = array_diff($requiredTables, $existingTables);
        
        if (!empty($missingTables)) {
            $results[] = "⚠️ Missing tables: " . implode(', ', $missingTables);
            
            // Execute table creation
            if (file_exists('database/tables.sql')) {
                $tables_sql = file_get_contents('database/tables.sql');
                $pdo->exec($tables_sql);
                $results[] = "✅ Tables created successfully";
            } else {
                throw new Exception("tables.sql file not found");
            }
        } else {
            $results[] = "✅ All required tables exist";
        }
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            $results[] = "⚠️ No admin users found, creating default admin";
            
            if (file_exists('database/data.sql')) {
                $data_sql = file_get_contents('database/data.sql');
                $pdo->exec($data_sql);
                $results[] = "✅ Default data inserted";
            }
            
            // Hash the default password
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@school.com'");
            $stmt->execute([$hashedPassword]);
            $results[] = "✅ Admin password updated";
        } else {
            $results[] = "✅ Admin user exists ($adminCount found)";
        }
        
        // Check table structure for common issues
        $columnChecks = [
            'students' => ['father_name', 'mother_name', 'guardian_phone'],
            'teachers' => ['status'],
            'users' => ['user_type', 'user_id']
        ];
        
        foreach ($columnChecks as $table => $columns) {
            if (in_array($table, $existingTables)) {
                $stmt = $pdo->query("DESCRIBE `$table`");
                $tableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($columns as $column) {
                    if (!in_array($column, $tableColumns)) {
                        $warnings[] = "Column `$column` missing from table `$table`";
                    }
                }
            }
        }
        
        // Add missing columns if needed
        if (in_array('students', $existingTables)) {
            $addColumns = [
                "ALTER TABLE students ADD COLUMN IF NOT EXISTS father_name VARCHAR(100)",
                "ALTER TABLE students ADD COLUMN IF NOT EXISTS mother_name VARCHAR(100)"
            ];
            
            foreach ($addColumns as $sql) {
                try {
                    $pdo->exec($sql);
                    $results[] = "✅ Added missing column to students table";
                } catch (Exception $e) {
                    if (!str_contains($e->getMessage(), 'Duplicate column name')) {
                        $warnings[] = "Column addition issue: " . $e->getMessage();
                    }
                }
            }
        }
        
        // Add users table columns for login system
        if (in_array('users', $existingTables)) {
            $addUserColumns = [
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS user_type VARCHAR(20)",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS user_id INT"
            ];
            
            foreach ($addUserColumns as $sql) {
                try {
                    $pdo->exec($sql);
                    $results[] = "✅ Added login system columns to users table";
                } catch (Exception $e) {
                    if (!str_contains($e->getMessage(), 'Duplicate column name')) {
                        $warnings[] = "User column addition issue: " . $e->getMessage();
                    }
                }
            }
        }
        
        // Test basic queries
        $testQueries = [
            "SELECT COUNT(*) FROM users" => "Users count",
            "SELECT COUNT(*) FROM students" => "Students count", 
            "SELECT COUNT(*) FROM teachers" => "Teachers count",
            "SELECT COUNT(*) FROM classes" => "Classes count",
            "SELECT COUNT(*) FROM fee_types" => "Fee types count"
        ];
        
        $results[] = "<br><strong>Table Data Summary:</strong>";
        foreach ($testQueries as $query => $description) {
            try {
                $stmt = $pdo->query($query);
                $count = $stmt->fetchColumn();
                $results[] = "📊 $description: $count";
            } catch (Exception $e) {
                $errors[] = "Query failed '$description': " . $e->getMessage();
            }
        }
        
        if (empty($errors)) {
            $success = true;
            $results[] = "<br>✅ <strong>Database repair completed successfully!</strong>";
        }
        
    } catch (Exception $e) {
        $errors[] = "Database repair failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Database Repair</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-database text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Database Repair Tool</h1>
                <p class="text-gray-600 mt-2">Fix database issues and ensure proper schema</p>
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

                <?php if (!empty($warnings)): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>Warnings:</h4>
                    <ul class="mt-2 text-sm">
                        <?php foreach ($warnings as $warning): ?>
                        <li>• <?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($results)): ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-4">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-info-circle mr-2"></i>Repair Results:</h4>
                    <div class="mt-2 text-sm">
                        <?php foreach ($results as $result): ?>
                        <div><?php echo $result; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>Repair Successful!</h4>
                    <p class="text-sm mt-2">Database has been repaired and is ready to use.</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="db-check.php" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-search mr-2"></i>Check Database
                    </a>
                    <a href="index.php" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center">
                        <i class="fas fa-home mr-2"></i>Go to Dashboard
                    </a>
                </div>
                <?php else: ?>
                <button onclick="location.reload()" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition-colors flex items-center">
                    <i class="fas fa-redo mr-2"></i>Try Again
                </button>
                <?php endif; ?>

            <?php else: ?>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">What This Tool Does:</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Checks for missing database tables
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Creates missing tables from schema
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Inserts default data if missing
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Fixes common column/structure issues
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Validates admin user account
                        </li>
                    </ul>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <div>
                            <h4 class="font-semibold">Before Running:</h4>
                            <p class="text-sm">This will modify your database structure. Make sure you have a backup if needed.</p>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center text-lg">
                        <i class="fas fa-wrench mr-2"></i>Run Database Repair
                    </button>
                </form>
                
            <?php endif; ?>
            
            <div class="mt-8 text-center">
                <div class="flex justify-center space-x-4 text-sm">
                    <a href="db-check.php" class="text-blue-600 hover:text-blue-800">Database Check</a>
                    <a href="debug.php" class="text-blue-600 hover:text-blue-800">Debug Info</a>
                    <a href="test.php" class="text-blue-600 hover:text-blue-800">System Test</a>
                </div>
                <div class="mt-4 text-xs text-gray-500">
                    <?php echo APP_NAME . ' v' . APP_VERSION; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>