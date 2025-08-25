<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Accounting.php';

requirePermission('view_accounts');

$accounting = new Accounting();
$currentMonth = date('Y-m');
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get financial data
$cashFlow = $accounting->getCashFlowSummary($startDate, $endDate);
$paymentBreakdown = $accounting->getPaymentMethodBreakdown($startDate, $endDate);
$monthlyFinancial = $accounting->getMonthlyFinancialSummary();
$pendingPayments = $accounting->getPendingPayments();

// Calculate totals
$totalIncome = array_sum(array_column($cashFlow['income'] ?? [], 'total_income'));
$totalExpenses = array_sum(array_column($cashFlow['expenses'] ?? [], 'total_expenses'));
$totalPayroll = array_sum(array_column($cashFlow['payroll'] ?? [], 'total_payroll'));
$netProfit = $totalIncome - $totalExpenses - $totalPayroll;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Accounting Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Accounting Dashboard</h1>
                        <p class="text-gray-600">Financial overview and management</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <a href="reports.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-chart-line mr-2"></i>Reports
                        </a>
                        <a href="transactions.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            <i class="fas fa-exchange-alt mr-2"></i>Transactions
                        </a>
                    </div>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Period</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-filter mr-2"></i>Apply Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Financial Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Income</p>
                            <p class="text-2xl font-bold text-green-600">₹<?php echo number_format($totalIncome, 2); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-arrow-up text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <?php echo date('M j', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate)); ?>
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Expenses</p>
                            <p class="text-2xl font-bold text-red-600">₹<?php echo number_format($totalExpenses, 2); ?></p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-arrow-down text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Operating expenses</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Payroll</p>
                            <p class="text-2xl font-bold text-orange-600">₹<?php echo number_format($totalPayroll, 2); ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-users text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Teacher salaries</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Net Profit</p>
                            <p class="text-2xl font-bold <?php echo $netProfit >= 0 ? 'text-blue-600' : 'text-red-600'; ?>">
                                ₹<?php echo number_format($netProfit, 2); ?>
                            </p>
                        </div>
                        <div class="<?php echo $netProfit >= 0 ? 'bg-blue-100' : 'bg-red-100'; ?> p-3 rounded-full">
                            <i class="fas fa-chart-line <?php echo $netProfit >= 0 ? 'text-blue-600' : 'text-red-600'; ?> text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <?php echo $netProfit >= 0 ? 'Profitable period' : 'Loss period'; ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Payment Methods Breakdown -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Payment Methods</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($paymentBreakdown)): ?>
                        <div class="space-y-4">
                            <?php foreach ($paymentBreakdown as $method): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full <?php 
                                        echo match($method['payment_method']) {
                                            'cash' => 'bg-green-500',
                                            'bank_transfer', 'online' => 'bg-blue-500',
                                            'cheque' => 'bg-yellow-500',
                                            'card' => 'bg-purple-500',
                                            default => 'bg-gray-500'
                                        };
                                    ?>"></div>
                                    <div>
                                        <p class="font-medium text-gray-800 capitalize"><?php echo str_replace('_', ' ', $method['payment_method']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo $method['transaction_count']; ?> transactions</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-800">₹<?php echo number_format($method['total_amount'], 2); ?></p>
                                    <p class="text-sm text-gray-500">Avg: ₹<?php echo number_format($method['average_amount'], 2); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-500 text-center py-4">No payment data for selected period</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Payments -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Pending Payments</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($pendingPayments)): ?>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            <?php foreach (array_slice($pendingPayments, 0, 5) as $payment): ?>
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($payment['student_name']); ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($payment['admission_number']); ?> | 
                                        Invoice: <?php echo htmlspecialchars($payment['invoice_number']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-red-600">₹<?php echo number_format($payment['total_amount'], 2); ?></p>
                                    <p class="text-xs text-red-500">
                                        <?php 
                                        if ($payment['days_overdue'] > 0) {
                                            echo $payment['days_overdue'] . ' days overdue';
                                        } else {
                                            echo 'Due ' . date('M j', strtotime($payment['due_date']));
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($pendingPayments) > 5): ?>
                        <div class="mt-4 text-center">
                            <a href="pending-payments.php" class="text-blue-600 hover:text-blue-800 text-sm">
                                View all <?php echo count($pendingPayments); ?> pending payments
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <p class="text-gray-500 text-center py-4">No pending payments</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="cash-book.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-money-bill text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Cash Book</p>
                            <p class="text-sm text-gray-500">Manage cash transactions</p>
                        </div>
                    </a>

                    <a href="bank-reconciliation.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-university text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Bank Reconciliation</p>
                            <p class="text-sm text-gray-500">Match bank statements</p>
                        </div>
                    </a>

                    <a href="cheque-management.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-yellow-100 p-3 rounded-full mr-4">
                            <i class="fas fa-file-invoice text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Cheque Management</p>
                            <p class="text-sm text-gray-500">Track cheque payments</p>
                        </div>
                    </a>

                    <a href="financial-reports.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-purple-100 p-3 rounded-full mr-4">
                            <i class="fas fa-chart-bar text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Financial Reports</p>
                            <p class="text-sm text-gray-500">Detailed analysis</p>
                        </div>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>