<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Expense.php';

requirePermission('manage_fees');

$expense = new Expense();
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$month = $_GET['month'] ?? date('Y-m');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$expenses = $expense->getAll($limit, $offset, $search, $category, $month);
$totalExpenses = $expense->getTotalCount($search, $category, $month);
$totalPages = ceil($totalExpenses / $limit);

$monthlyStats = $expense->getMonthlyStats($month);

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Expenses</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Expenses</h1>
                        <p class="text-gray-600">Track and manage school expenses</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <a href="add.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Expense
                        </a>
                    </div>
                </div>
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

            <!-- Monthly Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-500 text-white rounded-full">
                            <i class="fas fa-receipt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total Expenses</h3>
                            <p class="text-2xl font-bold text-red-600">₹<?php echo number_format($monthlyStats['total_amount'], 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-500 text-white rounded-full">
                            <i class="fas fa-list text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total Records</h3>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $monthlyStats['total_count']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-500 text-white rounded-full">
                            <i class="fas fa-money-bill text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Average Expense</h3>
                            <p class="text-2xl font-bold text-green-600">₹<?php echo number_format($monthlyStats['avg_amount'], 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-500 text-white rounded-full">
                            <i class="fas fa-chart-pie text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Top Category</h3>
                            <p class="text-lg font-bold text-yellow-600"><?php echo ucfirst($monthlyStats['top_category'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" 
                               placeholder="Description, vendor..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            <option value="salary" <?php echo $category === 'salary' ? 'selected' : ''; ?>>Salary</option>
                            <option value="utilities" <?php echo $category === 'utilities' ? 'selected' : ''; ?>>Utilities</option>
                            <option value="maintenance" <?php echo $category === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="supplies" <?php echo $category === 'supplies' ? 'selected' : ''; ?>>Supplies</option>
                            <option value="events" <?php echo $category === 'events' ? 'selected' : ''; ?>>Events</option>
                            <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <input type="month" name="month" 
                               value="<?php echo htmlspecialchars($month); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                    
                    <div class="flex items-end">
                        <a href="list.php" class="w-full bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors text-center">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Expenses Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Expenses (<?php echo $totalExpenses; ?> total)
                        </h2>
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Date</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Category</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Description</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Vendor</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Amount</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Payment Method</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Receipt</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="8" class="text-center p-8 text-gray-500">
                                        <i class="fas fa-receipt text-4xl mb-4 text-gray-300"></i>
                                        <p>No expenses found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($expenses as $exp): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-sm text-gray-800">
                                        <?php echo date('M d, Y', strtotime($exp['expense_date'])); ?>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            <?php echo ucfirst($exp['category']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-sm text-gray-800">
                                        <?php echo htmlspecialchars(substr($exp['description'], 0, 50)); ?>
                                        <?php if (strlen($exp['description']) > 50): ?>...<?php endif; ?>
                                    </td>
                                    <td class="p-3 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($exp['vendor_name'] ?: 'N/A'); ?>
                                    </td>
                                    <td class="p-3 text-sm font-medium text-gray-800">
                                        ₹<?php echo number_format($exp['amount'], 2); ?>
                                    </td>
                                    <td class="p-3 text-sm text-gray-600">
                                        <?php echo ucwords(str_replace('_', ' ', $exp['payment_method'])); ?>
                                    </td>
                                    <td class="p-3 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($exp['receipt_number'] ?: 'N/A'); ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center space-x-2">
                                            <a href="view.php?id=<?php echo $exp['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $exp['id']; ?>" 
                                               class="text-green-600 hover:text-green-800" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deleteExpense(<?php echo $exp['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between mt-6 pt-4 border-t">
                        <div class="text-sm text-gray-600">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalExpenses); ?> of <?php echo $totalExpenses; ?> expenses
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>" 
                               class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Previous
                            </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php $params = $_GET; $params['page'] = $i; ?>
                            <a href="?<?php echo http_build_query($params); ?>" 
                               class="px-3 py-2 text-sm <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <?php $params = $_GET; $params['page'] = $page + 1; ?>
                            <a href="?<?php echo http_build_query($params); ?>" 
                               class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    function deleteExpense(id) {
        if (confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
            fetch('delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete expense: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting expense');
            });
        }
    }
    </script>
</body>
</html>