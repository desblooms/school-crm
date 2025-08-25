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
        
        // Check if we already have data
        $stmt = $db->query("SELECT COUNT(*) FROM classes");
        $classCount = $stmt->fetchColumn();
        
        $stmt = $db->query("SELECT COUNT(*) FROM students");
        $studentCount = $stmt->fetchColumn();
        
        if ($classCount > 0 && $studentCount > 0) {
            $results[] = "⚠️ Sample data already exists ($classCount classes, $studentCount students)";
        } else {
            $results[] = "<br><strong>🌱 Creating Sample Data:</strong>";
            
            // Create sample classes if they don't exist
            if ($classCount == 0) {
                $sampleClasses = [
                    ['Class 1', 'A'],
                    ['Class 1', 'B'],
                    ['Class 2', 'A'],
                    ['Class 3', 'A'],
                    ['Class 4', 'A'],
                ];
                
                foreach ($sampleClasses as $classData) {
                    $stmt = $db->prepare("INSERT INTO classes (name, section) VALUES (?, ?)");
                    $stmt->execute($classData);
                }
                $results[] = "✅ Created " . count($sampleClasses) . " sample classes";
            }
            
            // Create sample fee types if they don't exist
            $stmt = $db->query("SELECT COUNT(*) FROM fee_types");
            $feeTypeCount = $stmt->fetchColumn();
            
            if ($feeTypeCount == 0) {
                $sampleFeeTypes = [
                    ['Tuition Fee', 'Monthly tuition fee'],
                    ['Library Fee', 'Library maintenance fee'],
                    ['Sports Fee', 'Sports activities fee'],
                    ['Exam Fee', 'Examination fee'],
                ];
                
                foreach ($sampleFeeTypes as $feeType) {
                    $stmt = $db->prepare("INSERT INTO fee_types (name, description) VALUES (?, ?)");
                    $stmt->execute($feeType);
                }
                $results[] = "✅ Created " . count($sampleFeeTypes) . " fee types";
            }
            
            // Create sample students if they don't exist
            if ($studentCount == 0) {
                // First create sample users
                $sampleStudents = [
                    ['John Doe', 'john.doe@example.com', '1234567890', '123 Main St'],
                    ['Jane Smith', 'jane.smith@example.com', '0987654321', '456 Oak Ave'],
                    ['Mike Johnson', 'mike.johnson@example.com', '1122334455', '789 Pine St'],
                    ['Sarah Wilson', 'sarah.wilson@example.com', '2233445566', '321 Elm St'],
                    ['David Brown', 'david.brown@example.com', '3344556677', '654 Maple Ave'],
                ];
                
                $classIds = [];
                $stmt = $db->query("SELECT id FROM classes");
                while ($row = $stmt->fetch()) {
                    $classIds[] = $row['id'];
                }
                
                $studentIds = [];
                foreach ($sampleStudents as $index => $userData) {
                    // Create user
                    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, 'student', ?, ?)");
                    $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
                    $stmt->execute([
                        $userData[0],
                        $userData[1],
                        $hashedPassword,
                        $userData[2],
                        $userData[3]
                    ]);
                    
                    $userId = $db->lastInsertId();
                    
                    // Create student
                    $admissionNumber = 'STU' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                    $classId = $classIds[array_rand($classIds)]; // Random class
                    
                    $stmt = $db->prepare("INSERT INTO students (user_id, admission_number, class_id, roll_number) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $admissionNumber, $classId, $index + 1]);
                    
                    $studentIds[] = $db->lastInsertId();
                }
                
                $results[] = "✅ Created " . count($sampleStudents) . " sample students";
                
                // Create fee structure
                $feeTypeIds = [];
                $stmt = $db->query("SELECT id FROM fee_types");
                while ($row = $stmt->fetch()) {
                    $feeTypeIds[] = $row['id'];
                }
                
                if (!empty($feeTypeIds) && !empty($classIds)) {
                    $academicYear = date('Y') . '-' . (date('Y') + 1);
                    
                    foreach ($classIds as $classId) {
                        foreach ($feeTypeIds as $feeTypeId) {
                            $amount = rand(500, 2000); // Random fee amount
                            $stmt = $db->prepare("INSERT INTO fee_structure (class_id, fee_type_id, amount, academic_year) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$classId, $feeTypeId, $amount, $academicYear]);
                        }
                    }
                    $results[] = "✅ Created fee structure for all classes";
                }
            }
        }
        
        // Verify the data
        $results[] = "<br><strong>📊 Final Data Count:</strong>";
        $tables = ['classes', 'students', 'fee_types', 'fee_structure'];
        foreach ($tables as $table) {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            $results[] = "✅ $table: $count records";
        }
        
        $success = true;
        $results[] = "<br>🎉 <strong>Sample data setup completed!</strong>";
        $results[] = "You can now test the fee collection functionality.";
        
    } catch (Exception $e) {
        $errors[] = "❌ Error: " . $e->getMessage();
        $results[] = "File: " . $e->getFile() . " Line: " . $e->getLine();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Seed Sample Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-green-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-seedling text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Seed Sample Data</h1>
                <p class="text-gray-600 mt-2">Create sample classes, students, and fee structure for testing</p>
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
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4 max-h-96 overflow-y-auto">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-info-circle mr-2"></i>Results:</h4>
                    <div class="mt-2 text-sm space-y-1">
                        <?php foreach ($results as $result): ?>
                        <div><?php echo $result; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <h4 class="font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>Sample Data Created!</h4>
                    <p class="text-sm mt-2">Your School CRM now has sample data for testing.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="fees/collection.php" class="bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-money-bill mr-2"></i>Test Fee Collection
                    </a>
                    <a href="students/list.php" class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-users mr-2"></i>View Students
                    </a>
                    <a href="debug-fee-collection.php" class="bg-purple-600 text-white px-4 py-3 rounded-md hover:bg-purple-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-bug mr-2"></i>Debug Info
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
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">This will create sample data for testing:</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-medium text-blue-800 mb-2"><i class="fas fa-school mr-2"></i>Classes</h4>
                            <ul class="space-y-1 text-sm text-blue-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Class 1-A, 1-B</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Class 2-A</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Class 3-A, 4-A</li>
                            </ul>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-2"><i class="fas fa-users mr-2"></i>Students</h4>
                            <ul class="space-y-1 text-sm text-green-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>5 sample students</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>With user accounts</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Assigned to random classes</li>
                            </ul>
                        </div>
                        
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <h4 class="font-medium text-purple-800 mb-2"><i class="fas fa-money-bill mr-2"></i>Fee Types</h4>
                            <ul class="space-y-1 text-sm text-purple-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Tuition Fee</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Library Fee</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Sports & Exam Fees</li>
                            </ul>
                        </div>
                        
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <h4 class="font-medium text-orange-800 mb-2"><i class="fas fa-cogs mr-2"></i>Fee Structure</h4>
                            <ul class="space-y-1 text-sm text-orange-700">
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>Random fee amounts</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>For current academic year</li>
                                <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>All classes & fee types</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-semibold">Note:</h4>
                            <ul class="text-sm mt-2 space-y-1">
                                <li>• This will create sample data only if tables are empty</li>
                                <li>• Student password will be: password123</li>
                                <li>• This is safe to run multiple times</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="text-center">
                    <button type="submit" class="bg-green-600 text-white px-8 py-4 rounded-lg hover:bg-green-700 transition-colors flex items-center mx-auto text-lg font-medium">
                        <i class="fas fa-seedling mr-3"></i>Create Sample Data
                    </button>
                </form>
                
            <?php endif; ?>
            
            <div class="mt-8 text-center border-t pt-6">
                <div class="flex justify-center space-x-6 text-sm">
                    <a href="debug-fee-collection.php" class="text-blue-600 hover:text-blue-800">Debug Fee Collection</a>
                    <a href="apply-migrations.php" class="text-blue-600 hover:text-blue-800">Database Migrations</a>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>