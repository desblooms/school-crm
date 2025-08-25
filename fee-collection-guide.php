<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Fee.php';

$fee = new Fee();
$db = Database::getInstance()->getConnection();

// Get sample data for the guide
$stmt = $db->query("SELECT s.id, u.name, s.admission_number FROM students s JOIN users u ON s.user_id = u.id LIMIT 1");
$sampleStudent = $stmt->fetch();

$classes = $fee->getClasses();
$sampleClass = !empty($classes) ? $classes[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Fee Collection Guide</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-green-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-money-bill text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Fee Collection Complete Guide</h1>
                <p class="text-gray-600 mt-2">Step-by-step guide to collect student fees</p>
            </div>

            <div class="space-y-8">
                <!-- Issue Analysis -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-yellow-800 mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Why Your URL Isn't Working
                    </h2>
                    <p class="text-yellow-700 mb-4">
                        The URL <code>fees/collection.php?class_id=1&student_id=3</code> goes to the page but doesn't show the fee collection form because:
                    </p>
                    <ul class="list-disc ml-6 space-y-2 text-yellow-700">
                        <li><strong>No Fee Structure:</strong> Student ID 3 might not have fee structure configured</li>
                        <li><strong>Missing Data:</strong> Class ID 1 or Student ID 3 might not exist</li>
                        <li><strong>No Fee Types:</strong> System might not have fee types configured</li>
                    </ul>
                </div>

                <!-- Step-by-Step Process -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-blue-800 mb-4">
                        <i class="fas fa-list-ol mr-2"></i>Complete Fee Collection Process
                    </h2>
                    
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">1</div>
                            <div>
                                <h3 class="font-semibold text-blue-800">Setup Required Data</h3>
                                <p class="text-blue-700 text-sm">Ensure you have classes, students, and fee structure</p>
                                <div class="mt-2">
                                    <a href="seed-sample-data.php" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                        Create Sample Data
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">2</div>
                            <div>
                                <h3 class="font-semibold text-blue-800">Go to Fee Collection</h3>
                                <p class="text-blue-700 text-sm">Visit the fee collection page</p>
                                <div class="mt-2">
                                    <a href="fees/collection.php" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">
                                        Open Fee Collection
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">3</div>
                            <div>
                                <h3 class="font-semibold text-blue-800">Select Class</h3>
                                <p class="text-blue-700 text-sm">Choose a class from the dropdown - students will load automatically</p>
                                <?php if ($sampleClass): ?>
                                <div class="mt-2">
                                    <a href="fees/collection.php?class_id=<?php echo $sampleClass['id']; ?>" class="bg-purple-600 text-white px-3 py-1 rounded text-xs hover:bg-purple-700">
                                        Try Class: <?php echo $sampleClass['name']; ?>-<?php echo $sampleClass['section']; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">4</div>
                            <div>
                                <h3 class="font-semibold text-blue-800">Select Student</h3>
                                <p class="text-blue-700 text-sm">Click on a student card or use dropdown - fee status will appear</p>
                                <?php if ($sampleClass && $sampleStudent): ?>
                                <div class="mt-2">
                                    <a href="fees/collection.php?class_id=<?php echo $sampleClass['id']; ?>&student_id=<?php echo $sampleStudent['id']; ?>" class="bg-orange-600 text-white px-3 py-1 rounded text-xs hover:bg-orange-700">
                                        Try Student: <?php echo $sampleStudent['name']; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">5</div>
                            <div>
                                <h3 class="font-semibold text-blue-800">Collect Fee</h3>
                                <p class="text-blue-700 text-sm">Click "Collect" button for any pending fee - modal will open</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">6</div>
                            <div>
                                <h3 class="font-semibold text-blue-800">Fill Payment Details</h3>
                                <p class="text-blue-700 text-sm">Enter amount, payment method, and submit</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-red-800 mb-4">
                        <i class="fas fa-wrench mr-2"></i>Troubleshooting
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-red-800">Problem: No classes shown</h3>
                            <p class="text-red-700 text-sm">Solution: Create classes first or run sample data seeder</p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-red-800">Problem: No students in class</h3>
                            <p class="text-red-700 text-sm">Solution: Add students to classes or run sample data seeder</p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-red-800">Problem: "No fee information available"</h3>
                            <p class="text-red-700 text-sm">Solution: Set up fee structure for the class or run sample data seeder</p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-red-800">Problem: Modal doesn't open</h3>
                            <p class="text-red-700 text-sm">Solution: Check browser console for JavaScript errors</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-green-800 mb-4">
                        <i class="fas fa-rocket mr-2"></i>Quick Actions
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="seed-sample-data.php" class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-seedling mr-2"></i>Create Sample Data
                        </a>
                        
                        <a href="debug-specific-url.php" class="bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-bug mr-2"></i>Debug Your URL
                        </a>
                        
                        <a href="fees/collection.php" class="bg-purple-600 text-white px-4 py-3 rounded-md hover:bg-purple-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-money-bill mr-2"></i>Go to Fee Collection
                        </a>
                        
                        <a href="fees/collection.php?debug=1" class="bg-yellow-600 text-white px-4 py-3 rounded-md hover:bg-yellow-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-search mr-2"></i>Debug Mode
                        </a>
                        
                        <a href="debug-fee-collection.php" class="bg-orange-600 text-white px-4 py-3 rounded-md hover:bg-orange-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-tools mr-2"></i>Full Diagnosis
                        </a>
                        
                        <a href="index.php" class="bg-gray-600 text-white px-4 py-3 rounded-md hover:bg-gray-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                    </div>
                </div>

                <!-- Test URLs -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">
                        <i class="fas fa-link mr-2"></i>Test URLs
                    </h2>
                    
                    <div class="space-y-2 text-sm">
                        <?php if ($sampleClass && $sampleStudent): ?>
                        <p><strong>Working URL:</strong> 
                            <a href="fees/collection.php?class_id=<?php echo $sampleClass['id']; ?>&student_id=<?php echo $sampleStudent['id']; ?>" class="text-blue-600 hover:underline">
                                fees/collection.php?class_id=<?php echo $sampleClass['id']; ?>&student_id=<?php echo $sampleStudent['id']; ?>
                            </a>
                        </p>
                        <?php else: ?>
                        <p><strong>Your URL:</strong> <code>fees/collection.php?class_id=1&student_id=3</code></p>
                        <p class="text-red-600">⚠️ This URL may not work because class ID 1 or student ID 3 don't exist or have no fee structure.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>