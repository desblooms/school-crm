<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Invoice.php';

requirePermission('manage_invoices');

$invoice = new Invoice();
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$month = $_GET['month'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$invoices = $invoice->getAll($limit, $offset, $search, $status, $month);
$totalInvoices = $invoice->getTotalCount($search, $status, $month);
$totalPages = ceil($totalInvoices / $limit);

$stats = $invoice->getStats();

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Invoices</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Invoices</h1>
                        <p class="text-gray-600">Manage student fee invoices</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Create Invoice
                        </a>
                        <a href="bulk.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-layer-group mr-2"></i>Bulk Generate
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-500 text-white rounded-full">
                            <i class="fas fa-file-invoice text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total Invoices</h3>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_invoices'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-500 text-white rounded-full">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Paid</h3>
                            <p class="text-2xl font-bold text-green-600"><?php echo $stats['paid_invoices'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-500 text-white rounded-full">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Pending</h3>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_invoices'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-500 text-white rounded-full">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Overdue</h3>
                            <p class="text-2xl font-bold text-red-600"><?php echo $stats['overdue_invoices'] ?? 0; ?></p>
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
                               placeholder="Invoice number, student name..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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

            <!-- Invoices Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Invoices (<?php echo $totalInvoices; ?> total)
                        </h2>
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Invoice #</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Student</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Class</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Issue Date</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Due Date</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Amount</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="8" class="text-center p-8 text-gray-500">
                                        <i class="fas fa-file-invoice text-4xl mb-4 text-gray-300"></i>
                                        <p>No invoices found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3">
                                        <div class="font-medium text-blue-600">
                                            <?php echo htmlspecialchars($inv['invoice_number']); ?>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div>
                                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($inv['student_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inv['admission_number']); ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-sm text-gray-800">
                                        <?php echo htmlspecialchars($inv['class_name'] . ' - ' . $inv['section']); ?>
                                    </td>
                                    <td class="p-3 text-sm text-gray-600">
                                        <?php echo date('M d, Y', strtotime($inv['issue_date'])); ?>
                                    </td>
                                    <td class="p-3 text-sm text-gray-600">
                                        <?php echo date('M d, Y', strtotime($inv['due_date'])); ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-sm font-medium text-gray-800">₹<?php echo number_format($inv['total_amount'], 2); ?></div>
                                        <?php if ($inv['paid_amount'] > 0): ?>
                                        <div class="text-xs text-green-600">Paid: ₹<?php echo number_format($inv['paid_amount'], 2); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs rounded-full <?php 
                                            echo $inv['status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                                ($inv['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                ($inv['status'] === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')); 
                                        ?>">
                                            <?php echo ucfirst($inv['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center space-x-2">
                                            <a href="view.php?id=<?php echo $inv['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800" title="View Invoice">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="download.php?id=<?php echo $inv['id']; ?>" 
                                               class="text-green-600 hover:text-green-800" title="Download PDF">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button onclick="sendEmail(<?php echo $inv['id']; ?>)" 
                                                    class="text-purple-600 hover:text-purple-800" title="Send Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <?php if ($inv['status'] !== 'paid'): ?>
                                            <button onclick="markAsPaid(<?php echo $inv['id']; ?>, <?php echo $inv['total_amount']; ?>)" 
                                                    class="text-green-600 hover:text-green-800" title="Mark as Paid">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button onclick="deleteInvoice(<?php echo $inv['id']; ?>)" 
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
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalInvoices); ?> of <?php echo $totalInvoices; ?> invoices
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                            <?php $params = $_GET; $params['page'] = $page - 1; ?>
                            <a href="?<?php echo http_build_query($params); ?>" 
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
    function sendEmail(invoiceId) {
        if (confirm('Send invoice email to student/guardian?')) {
            fetch('send-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ invoice_id: invoiceId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email sent successfully!');
                    location.reload();
                } else {
                    alert('Failed to send email: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error sending email');
            });
        }
    }

    function markAsPaid(invoiceId, amount) {
        const paidAmount = prompt('Enter paid amount:', amount);
        if (paidAmount !== null && parseFloat(paidAmount) > 0) {
            fetch('mark-paid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    invoice_id: invoiceId,
                    paid_amount: parseFloat(paidAmount)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Invoice marked as paid!');
                    location.reload();
                } else {
                    alert('Failed to update invoice: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating invoice');
            });
        }
    }

    function deleteInvoice(invoiceId) {
        if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
            fetch('delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ invoice_id: invoiceId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete invoice: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting invoice');
            });
        }
    }
    </script>
</body>
</html>