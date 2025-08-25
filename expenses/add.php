<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Expense.php';

requirePermission('manage_fees');

$expense = new Expense();
$users = $expense->getUsers('admin'); // Only admins can approve expenses

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'category' => $_POST['category'],
        'description' => trim($_POST['description']),
        'amount' => floatval($_POST['amount']),
        'expense_date' => $_POST['expense_date'],
        'payment_method' => $_POST['payment_method'],
        'receipt_number' => trim($_POST['receipt_number']) ?: $expense->generateReceiptNumber(),
        'vendor_name' => trim($_POST['vendor_name']),
        'approved_by' => !empty($_POST['approved_by']) ? intval($_POST['approved_by']) : null,
        'recorded_by' => $_SESSION['user_id']
    ];
    
    // Validation
    $required_fields = ['category', 'description', 'amount', 'expense_date', 'payment_method'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } elseif ($data['amount'] <= 0) {
        $error_message = 'Amount must be greater than zero';
    } else {
        $result = $expense->create($data);
        if ($result['success']) {
            $success_message = 'Expense recorded successfully! Receipt Number: ' . $data['receipt_number'];
            // Clear form data
            $_POST = [];
        } else {
            $error_message = 'Failed to record expense: ' . $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Add Expense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <div class="flex items-center space-x-4">
                    <a href="list.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Add Expense</h1>
                        <p class="text-gray-600">Record a new expense transaction</p>
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
                <div class="mt-2">
                    <a href="list.php" class="text-green-800 underline">View All Expenses</a> |
                    <a href="add.php" class="text-green-800 underline">Add Another Expense</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Expense Details</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                    <select name="category" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Category</option>
                                        <option value="salary" <?php echo ($_POST['category'] ?? '') === 'salary' ? 'selected' : ''; ?>>Salary</option>
                                        <option value="utilities" <?php echo ($_POST['category'] ?? '') === 'utilities' ? 'selected' : ''; ?>>Utilities</option>
                                        <option value="maintenance" <?php echo ($_POST['category'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="supplies" <?php echo ($_POST['category'] ?? '') === 'supplies' ? 'selected' : ''; ?>>Supplies</option>
                                        <option value="events" <?php echo ($_POST['category'] ?? '') === 'events' ? 'selected' : ''; ?>>Events</option>
                                        <option value="other" <?php echo ($_POST['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₹) *</label>
                                    <input type="number" name="amount" step="0.01" min="0" required 
                                           value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Expense Date *</label>
                                    <input type="date" name="expense_date" required 
                                           value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                                    <select name="payment_method" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Method</option>
                                        <option value="cash" <?php echo ($_POST['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="card" <?php echo ($_POST['payment_method'] ?? '') === 'card' ? 'selected' : ''; ?>>Card</option>
                                        <option value="cheque" <?php echo ($_POST['payment_method'] ?? '') === 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                                        <option value="bank_transfer" <?php echo ($_POST['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Receipt Number</label>
                                    <input type="text" name="receipt_number" 
                                           value="<?php echo htmlspecialchars($_POST['receipt_number'] ?? $expense->generateReceiptNumber()); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Auto-generated if left empty</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vendor/Supplier</label>
                                    <input type="text" name="vendor_name" 
                                           value="<?php echo htmlspecialchars($_POST['vendor_name'] ?? ''); ?>"
                                           placeholder="e.g., ABC Suppliers, XYZ Ltd"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                                <textarea name="description" rows="3" required 
                                          placeholder="Detailed description of the expense..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Approval Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Approval</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Approved By</label>
                                    <select name="approved_by" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pending Approval</option>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo ($_POST['approved_by'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Select if expense is already approved</p>
                                </div>
                                
                                <div class="flex items-center">
                                    <div class="bg-blue-50 p-4 rounded-md">
                                        <div class="flex items-center">
                                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                            <div class="text-sm text-blue-700">
                                                <p class="font-medium">Recording Information</p>
                                                <p>Recorded by: <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                                                <p>Date: <?php echo date('M d, Y'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <a href="list.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Record Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">
                    <i class="fas fa-lightbulb mr-1"></i>Quick Tips
                </h3>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• Keep receipts and invoices for all recorded expenses</li>
                    <li>• Use clear, descriptive descriptions for easy tracking</li>
                    <li>• Vendor information helps in generating reports and analysis</li>
                    <li>• Get approval before recording large expenses</li>
                </ul>
            </div>
        </main>
    </div>

    <script>
    // Auto-suggest vendors based on previous entries
    const vendorInput = document.querySelector('input[name="vendor_name"]');
    if (vendorInput) {
        vendorInput.addEventListener('input', function() {
            // Implementation for vendor auto-suggest can be added here
        });
    }

    // Calculate and show expense summary
    const amountInput = document.querySelector('input[name="amount"]');
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            // Can add real-time calculation display here
        });
    }
    </script>
</body>
</html>