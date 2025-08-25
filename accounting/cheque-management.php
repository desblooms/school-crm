<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Accounting.php';

requirePermission('view_accounts');

$accounting = new Accounting();
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get cheque transactions
$chequeTransactions = $accounting->getChequeTransactions($startDate, $endDate);

// Categorize cheques by status
$pendingCheques = array_filter($chequeTransactions, function($cheque) {
    return $cheque['status'] === 'pending';
});

$clearedCheques = array_filter($chequeTransactions, function($cheque) {
    return $cheque['status'] === 'cleared';
});

$totalPendingAmount = array_sum(array_column($pendingCheques, 'amount'));
$totalClearedAmount = array_sum(array_column($clearedCheques, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Cheque Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Cheque Management</h1>
                        <p class="text-gray-600">Track and manage cheque payments</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700" onclick="showAddChequeModal()">
                            <i class="fas fa-plus mr-2"></i>Add Cheque
                        </button>
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

            <!-- Cheque Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending Cheques</p>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo count($pendingCheques); ?></p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">₹<?php echo number_format($totalPendingAmount, 2); ?></p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Cleared Cheques</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo count($clearedCheques); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">₹<?php echo number_format($totalClearedAmount, 2); ?></p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Cheques</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo count($chequeTransactions); ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">All transactions</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Value</p>
                            <p class="text-2xl font-bold text-purple-600">₹<?php echo number_format($totalPendingAmount + $totalClearedAmount, 2); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Combined value</p>
                </div>
            </div>

            <!-- Cheque Status Tabs -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex space-x-4">
                        <button class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600 bg-blue-50 rounded-t" onclick="showTab('all')">
                            All Cheques (<?php echo count($chequeTransactions); ?>)
                        </button>
                        <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-yellow-600" onclick="showTab('pending')">
                            Pending (<?php echo count($pendingCheques); ?>)
                        </button>
                        <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-green-600" onclick="showTab('cleared')">
                            Cleared (<?php echo count($clearedCheques); ?>)
                        </button>
                    </div>
                </div>

                <!-- All Cheques Tab -->
                <div id="all-tab" class="tab-content">
                    <div class="overflow-x-auto">
                        <?php if (!empty($chequeTransactions)): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cheque No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($chequeTransactions as $cheque): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($cheque['transaction_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                        <?php echo htmlspecialchars($cheque['cheque_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $cheque['type'] === 'INCOME' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $cheque['type']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($cheque['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $cheque['type'] === 'INCOME' ? 'text-green-600' : 'text-red-600'; ?>">
                                        ₹<?php echo number_format($cheque['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $cheque['status'] === 'cleared' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo ucfirst($cheque['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($cheque['status'] === 'pending'): ?>
                                        <button class="text-green-600 hover:text-green-900 mr-3" onclick="clearCheque('<?php echo $cheque['cheque_number']; ?>')">
                                            <i class="fas fa-check"></i> Clear
                                        </button>
                                        <button class="text-red-600 hover:text-red-900" onclick="bounceCheque('<?php echo $cheque['cheque_number']; ?>')">
                                            <i class="fas fa-times"></i> Bounce
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="p-6 text-center">
                            <i class="fas fa-file-invoice text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No cheque transactions found for the selected period</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Tab -->
                <div id="pending-tab" class="tab-content hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-yellow-600 mb-4">Pending Cheques</h3>
                        <?php if (!empty($pendingCheques)): ?>
                            <?php foreach ($pendingCheques as $cheque): ?>
                            <div class="border border-yellow-200 rounded-lg p-4 mb-4 bg-yellow-50">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-gray-900">Cheque #<?php echo htmlspecialchars($cheque['cheque_number']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($cheque['description']); ?></p>
                                        <p class="text-xs text-gray-500">Date: <?php echo date('M j, Y', strtotime($cheque['transaction_date'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-gray-900">₹<?php echo number_format($cheque['amount'], 2); ?></p>
                                        <div class="mt-2 space-x-2">
                                            <button class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700" onclick="clearCheque('<?php echo $cheque['cheque_number']; ?>')">
                                                Clear
                                            </button>
                                            <button class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700" onclick="bounceCheque('<?php echo $cheque['cheque_number']; ?>')">
                                                Bounce
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No pending cheques</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cleared Tab -->
                <div id="cleared-tab" class="tab-content hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-green-600 mb-4">Cleared Cheques</h3>
                        <?php if (!empty($clearedCheques)): ?>
                            <?php foreach ($clearedCheques as $cheque): ?>
                            <div class="border border-green-200 rounded-lg p-4 mb-4 bg-green-50">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-gray-900">Cheque #<?php echo htmlspecialchars($cheque['cheque_number']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($cheque['description']); ?></p>
                                        <p class="text-xs text-gray-500">Cleared: <?php echo date('M j, Y', strtotime($cheque['transaction_date'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-green-600">₹<?php echo number_format($cheque['amount'], 2); ?></p>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 mt-2">
                                            <i class="fas fa-check mr-1"></i>Cleared
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No cleared cheques</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            // Update button styles
            document.querySelectorAll('button[onclick^="showTab"]').forEach(btn => {
                btn.className = 'px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600';
            });
            event.target.className = 'px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600 bg-blue-50 rounded-t';
        }

        function clearCheque(chequeNumber) {
            if (confirm('Mark this cheque as cleared?')) {
                // In a real application, this would make an AJAX call
                alert('Cheque ' + chequeNumber + ' marked as cleared');
                location.reload();
            }
        }

        function bounceCheque(chequeNumber) {
            if (confirm('Mark this cheque as bounced?')) {
                // In a real application, this would make an AJAX call
                alert('Cheque ' + chequeNumber + ' marked as bounced');
                location.reload();
            }
        }

        function showAddChequeModal() {
            alert('Add Cheque functionality would open a modal here');
        }
    </script>
</body>
</html>