<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Only admin and accountant can access fee collection
if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied. Admin or Accountant access required.');
}

// Database connection
require_once '../config/database.php';
$db = Database::getInstance()->getConnection();

// Get classes from database
$classes = [];
try {
    $stmt = $db->query("SELECT id, name, section FROM classes ORDER BY name, section");
    $classes = $stmt->fetchAll();
} catch (Exception $e) {
    // Classes table might not exist
}

// Get fee types from database
$feeTypes = [];
try {
    $stmt = $db->query("SELECT id, name, description FROM fee_types ORDER BY name");
    $feeTypes = $stmt->fetchAll();
} catch (Exception $e) {
    // Fee types table might not exist
}

$success_message = '';
$error_message = '';
$selectedClass = $_GET['class_id'] ?? '';
$selectedStudent = $_GET['student_id'] ?? '';
$students = [];

// Debug information (remove in production)
$debug = $_GET['debug'] ?? false;

// Get students by class
$students = [];
if ($selectedClass) {
    try {
        $stmt = $db->prepare("SELECT id, name, admission_number FROM students WHERE class_id = ? ORDER BY name");
        $stmt->execute([$selectedClass]);
        $students = $stmt->fetchAll();
        if ($debug) {
            error_log("Selected class: $selectedClass, Found students: " . count($students));
        }
    } catch (Exception $e) {
        // Students table might not exist
    }
}

// Get student fee status
$studentFeeStatus = [];
if ($selectedStudent) {
    try {
        // Basic fee structure query - adapt as needed based on your actual database structure
        $stmt = $db->prepare("
            SELECT 
                s.id as student_id,
                s.name as student_name,
                s.admission_number,
                'Tuition Fee' as fee_type_name,
                1 as fee_type_id,
                1000.00 as fee_amount,
                COALESCE(SUM(fp.amount), 0) as paid_amount,
                (1000.00 - COALESCE(SUM(fp.amount), 0)) as pending_amount,
                CASE 
                    WHEN COALESCE(SUM(fp.amount), 0) >= 1000.00 THEN 'Paid'
                    WHEN COALESCE(SUM(fp.amount), 0) > 0 THEN 'Partial'
                    ELSE 'Pending'
                END as status
            FROM students s
            LEFT JOIN fee_payments fp ON s.id = fp.student_id
            WHERE s.id = ?
            GROUP BY s.id, s.name, s.admission_number
        ");
        $stmt->execute([$selectedStudent]);
        $studentFeeStatus = $stmt->fetchAll();
        
        if ($debug) {
            error_log("Selected student: $selectedStudent, Found fee status: " . count($studentFeeStatus));
        }
        
        // If no fee status found, set a helpful error message
        if (empty($studentFeeStatus) && !$debug) {
            $error_message = "No fee information found for this student. Please ensure fee structure is set up for their class, or contact administrator.";
        }
    } catch (Exception $e) {
        // Fee related tables might not exist
        if ($debug) {
            error_log("Fee status query error: " . $e->getMessage());
        }
    }
}

// Check if we have basic data
if (empty($classes) && !$debug) {
    $error_message = 'No classes found. Please create some classes first or contact administrator.';
}

if ($selectedClass && empty($students) && !$debug) {
    $error_message = 'No students found in the selected class.';
}

// Handle fee collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_fee'])) {
    $student_id = intval($_POST['student_id']);
    $fee_type_id = intval($_POST['fee_type_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = trim($_POST['payment_method']);
    $month_year = trim($_POST['month_year']);
    $transaction_id = trim($_POST['transaction_id']);
    $remarks = trim($_POST['remarks']);
    
    if ($student_id && $fee_type_id && $amount > 0 && $payment_method && $month_year) {
        try {
            // Generate receipt number
            $receipt_number = 'RCP' . date('Ymd') . sprintf('%04d', $student_id);
            
            // Insert fee payment
            $stmt = $db->prepare("
                INSERT INTO fee_payments 
                (student_id, fee_type_id, amount, payment_method, month_year, transaction_id, remarks, receipt_number, collected_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $student_id, 
                $fee_type_id, 
                $amount, 
                $payment_method, 
                $month_year, 
                $transaction_id, 
                $remarks, 
                $receipt_number, 
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $success_message = 'Fee collected successfully! Receipt Number: ' . $receipt_number;
                
                // Refresh student fee status
                try {
                    $stmt = $db->prepare("
                        SELECT 
                            s.id as student_id,
                            s.name as student_name,
                            s.admission_number,
                            'Tuition Fee' as fee_type_name,
                            1 as fee_type_id,
                            1000.00 as fee_amount,
                            COALESCE(SUM(fp.amount), 0) as paid_amount,
                            (1000.00 - COALESCE(SUM(fp.amount), 0)) as pending_amount,
                            CASE 
                                WHEN COALESCE(SUM(fp.amount), 0) >= 1000.00 THEN 'Paid'
                                WHEN COALESCE(SUM(fp.amount), 0) > 0 THEN 'Partial'
                                ELSE 'Pending'
                            END as status
                        FROM students s
                        LEFT JOIN fee_payments fp ON s.id = fp.student_id
                        WHERE s.id = ?
                        GROUP BY s.id, s.name, s.admission_number
                    ");
                    $stmt->execute([$selectedStudent]);
                    $studentFeeStatus = $stmt->fetchAll();
                } catch (Exception $e) {
                    // Ignore refresh error
                }
            } else {
                $error_message = 'Failed to record fee payment';
            }
        } catch (Exception $e) {
            $error_message = 'Error collecting fee: ' . $e->getMessage();
            
            // If fee_payments table doesn't exist, create it
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                try {
                    $db->exec("
                        CREATE TABLE IF NOT EXISTS fee_payments (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            student_id INT NOT NULL,
                            fee_type_id INT DEFAULT 1,
                            amount DECIMAL(10,2) NOT NULL,
                            payment_method VARCHAR(50) NOT NULL,
                            month_year VARCHAR(10) NOT NULL,
                            transaction_id VARCHAR(100),
                            remarks TEXT,
                            receipt_number VARCHAR(50) NOT NULL UNIQUE,
                            collected_by INT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    $success_message = 'Fee payments table created. Please try collecting the fee again.';
                } catch (Exception $e2) {
                    $error_message = 'Failed to create fee payments table: ' . $e2->getMessage();
                }
            }
        }
    } else {
        $error_message = 'Please fill in all required fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Fee Collection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Fee Collection</h1>
                <p class="text-gray-600">Collect fees from students</p>
                <?php if ($debug): ?>
                <div class="mt-4 p-3 bg-yellow-100 border border-yellow-400 rounded text-sm">
                    <strong>Debug Info:</strong> 
                    Classes: <?php echo count($classes); ?> | 
                    Students: <?php echo count($students); ?> | 
                    Selected Class: <?php echo $selectedClass ?: 'None'; ?> | 
                    Selected Student: <?php echo $selectedStudent ?: 'None'; ?>
                    <br>
                    <a href="?debug=1" class="text-blue-600 underline">Refresh with debug</a> | 
                    <a href="../debug-fee-collection.php" class="text-blue-600 underline">Full Debug</a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <!-- Student Selection -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Student</h2>
                
                <!-- Class Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
                    <form method="GET" id="classForm">
                        <select name="class_id" onchange="document.getElementById('classForm').submit()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" 
                                    <?php echo $selectedClass == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if ($selectedClass && !empty($students)): ?>
                <!-- Student Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Student</label>
                    <form method="GET" id="studentForm">
                        <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                        <select name="student_id" onchange="document.getElementById('studentForm').submit()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>" 
                                    <?php echo $selectedStudent == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['admission_number'] . ' - ' . $s['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <!-- Students List -->
                <div class="mt-6">
                    <h3 class="text-md font-medium text-gray-700 mb-3">
                        Students in <?php 
                        $selectedClassName = '';
                        foreach ($classes as $class) {
                            if ($class['id'] == $selectedClass) {
                                $selectedClassName = $class['name'] . ' - ' . $class['section'];
                                break;
                            }
                        }
                        echo htmlspecialchars($selectedClassName); 
                        ?>
                    </h3>
                    <div class="max-h-64 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <?php foreach ($students as $s): ?>
                            <div class="border border-gray-200 rounded-md p-3 hover:bg-gray-50 cursor-pointer" 
                                 onclick="window.location.href='?class_id=<?php echo $selectedClass; ?>&student_id=<?php echo $s['id']; ?>'">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-xs font-medium">
                                            <?php echo strtoupper(substr($s['name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($s['admission_number']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php elseif ($selectedClass && empty($students)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No students found in the selected class.</p>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-graduation-cap text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Please select a class to view students.</p>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($selectedStudent && empty($studentFeeStatus)): ?>
            <!-- No Fee Data Message -->
            <div class="bg-yellow-100 border border-yellow-400 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800 mb-2">No Fee Information Available</h3>
                        <p class="text-yellow-700 mb-4">
                            Student ID <?php echo $selectedStudent; ?> was selected, but no fee structure is configured for this student.
                        </p>
                        <div class="space-y-2 text-sm text-yellow-700">
                            <p><strong>Possible reasons:</strong></p>
                            <ul class="list-disc ml-5 space-y-1">
                                <li>No fee structure set up for the student's class</li>
                                <li>No fee types configured in the system</li>
                                <li>Student not properly assigned to a class</li>
                            </ul>
                        </div>
                        <div class="mt-4 flex space-x-3">
                            <a href="../seed-sample-data.php" class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 text-sm">
                                <i class="fas fa-seedling mr-2"></i>Create Sample Data
                            </a>
                            <a href="../debug-specific-url.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
                                <i class="fas fa-bug mr-2"></i>Debug This Issue
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif (!empty($studentFeeStatus)): ?>
            <!-- Student Fee Status -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Fee Status - <?php echo htmlspecialchars($studentFeeStatus[0]['student_name']); ?>
                    <span class="text-sm font-normal text-gray-600">
                        (<?php echo htmlspecialchars($studentFeeStatus[0]['admission_number']); ?>)
                    </span>
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Fee Type</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Total Amount</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Paid Amount</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Pending</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentFeeStatus as $feeStatus): ?>
                            <tr class="border-t">
                                <td class="p-3 font-medium text-gray-800">
                                    <?php echo htmlspecialchars($feeStatus['fee_type_name']); ?>
                                </td>
                                <td class="p-3 text-gray-800">
                                    ₹<?php echo number_format($feeStatus['fee_amount'], 2); ?>
                                </td>
                                <td class="p-3 text-gray-800">
                                    ₹<?php echo number_format($feeStatus['paid_amount'], 2); ?>
                                </td>
                                <td class="p-3 text-gray-800">
                                    ₹<?php echo number_format($feeStatus['pending_amount'], 2); ?>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $feeStatus['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 
                                            ($feeStatus['status'] === 'Partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                    ?>">
                                        <?php echo $feeStatus['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <?php if ($feeStatus['pending_amount'] > 0): ?>
                                    <button onclick="openCollectionModal(<?php echo $feeStatus['student_id']; ?>, <?php echo $feeStatus['fee_type_id']; ?>, '<?php echo htmlspecialchars($feeStatus['fee_type_name']); ?>', <?php echo $feeStatus['pending_amount']; ?>)" 
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-money-bill mr-1"></i>Collect
                                    </button>
                                    <?php else: ?>
                                    <span class="text-green-600 text-sm">
                                        <i class="fas fa-check mr-1"></i>Paid
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Fee Collection Modal -->
    <div id="collectionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Collect Fee Payment</h3>
                        <button onclick="closeCollectionModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="collect_fee" value="1">
                        <input type="hidden" name="student_id" id="modal_student_id">
                        <input type="hidden" name="fee_type_id" id="modal_fee_type_id">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fee Type</label>
                            <div class="text-gray-800 font-medium" id="modal_fee_type_name"></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₹)</label>
                            <input type="number" name="amount" id="modal_amount" step="0.01" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <select name="payment_method" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Month/Year</label>
                            <input type="month" name="month_year" required 
                                   value="<?php echo date('Y-m'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Transaction ID (Optional)</label>
                            <input type="text" name="transaction_id" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Remarks (Optional)</label>
                            <textarea name="remarks" rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" onclick="closeCollectionModal()" 
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Collect Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openCollectionModal(studentId, feeTypeId, feeTypeName, pendingAmount) {
        document.getElementById('modal_student_id').value = studentId;
        document.getElementById('modal_fee_type_id').value = feeTypeId;
        document.getElementById('modal_fee_type_name').textContent = feeTypeName;
        document.getElementById('modal_amount').value = pendingAmount.toFixed(2);
        document.getElementById('collectionModal').classList.remove('hidden');
    }

    function closeCollectionModal() {
        document.getElementById('collectionModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('collectionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCollectionModal();
        }
    });
    </script>
</body>
</html>