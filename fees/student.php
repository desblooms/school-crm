<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Student.php';
require_once '../classes/Fee.php';

requirePermission('view_fees');

$student = new Student();
$fee = new Fee();
$studentId = intval($_GET['id'] ?? 0);

if (!$studentId) {
    header('Location: ../students/list.php?error=Invalid student ID');
    exit();
}

$studentData = $student->getById($studentId);
if (!$studentData) {
    header('Location: ../students/list.php?error=Student not found');
    exit();
}

$feeStatus = $fee->getStudentFeeStatus($studentId);
$paymentHistory = $fee->getPaymentHistory($studentId, 20, 0);
$currentMonthPayments = $fee->getMonthlyPayments($studentId, date('Y-m'));

// Calculate totals
$totalPaid = 0;
$totalPending = 0;
foreach ($feeStatus as $status) {
    $totalPaid += $status['paid_amount'];
    $totalPending += $status['pending_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Student Fee Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="../students/view.php?id=<?php echo $studentId; ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Fee Details</h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($studentData['name']); ?> - <?php echo htmlspecialchars($studentData['admission_number']); ?></p>
                        </div>
                    </div>
                    <a href="collection.php?student_id=<?php echo $studentId; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Collect Fee
                    </a>
                </div>
            </div>

            <!-- Fee Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Paid</p>
                            <p class="text-2xl font-bold text-green-600">₹<?php echo number_format($totalPaid, 2); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending Amount</p>
                            <p class="text-2xl font-bold text-red-600">₹<?php echo number_format($totalPending, 2); ?></p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-clock text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">This Month</p>
                            <p class="text-2xl font-bold text-blue-600">₹<?php echo number_format(array_sum(array_column($currentMonthPayments, 'amount')), 2); ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-calendar text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Status Table -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Fee Status by Type</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($feeStatus)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No fee structure found for this student</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($feeStatus as $status): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($status['fee_type_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₹<?php echo number_format($status['fee_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        ₹<?php echo number_format($status['paid_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                        ₹<?php echo number_format($status['pending_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($status['status'] === 'Paid'): ?>
                                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Paid</span>
                                        <?php elseif ($status['status'] === 'Partial'): ?>
                                            <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Partial</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($status['pending_amount'] > 0): ?>
                                            <a href="collection.php?student_id=<?php echo $studentId; ?>&fee_type_id=<?php echo $status['fee_type_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-plus"></i> Collect
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">Complete</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Payment History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected By</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($paymentHistory)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No payment history found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($paymentHistory as $payment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($payment['fee_type_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        ₹<?php echo number_format($payment['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="capitalize"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <a href="../invoices/view.php?receipt=<?php echo urlencode($payment['receipt_number']); ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <?php echo htmlspecialchars($payment['receipt_number']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($payment['collected_by_name'] ?? 'N/A'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>