<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Invoice.php';
require_once '../classes/Student.php';
require_once '../classes/Fee.php';

requirePermission('manage_invoices');

$invoice = new Invoice();
$student = new Student();
$fee = new Fee();

$classes = $fee->getClasses();
$feeTypes = $fee->getFeeTypes();

$success_message = '';
$error_message = '';
$selectedClass = $_GET['class_id'] ?? '';
$selectedStudent = $_GET['student_id'] ?? '';
$students = [];

if ($selectedClass) {
    $students = $fee->getStudentsByClass($selectedClass);
}

$studentFeeStructure = [];
if ($selectedStudent) {
    $studentFeeStructure = $fee->getStudentFeeStatus($selectedStudent);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $studentId = intval($_POST['student_id']);
    $dueDate = $_POST['due_date'];
    $selectedFees = $_POST['selected_fees'] ?? [];
    
    if (empty($selectedFees)) {
        $error_message = 'Please select at least one fee type for the invoice';
    } else {
        $feeTypesForInvoice = [];
        
        foreach ($selectedFees as $feeTypeId) {
            $customAmount = floatval($_POST['custom_amount'][$feeTypeId] ?? 0);
            $description = trim($_POST['description'][$feeTypeId] ?? '');
            
            // Get fee type details
            foreach ($feeTypes as $ft) {
                if ($ft['id'] == $feeTypeId) {
                    $feeTypesForInvoice[] = [
                        'fee_type_id' => $feeTypeId,
                        'amount' => $customAmount > 0 ? $customAmount : $ft['default_amount'],
                        'description' => $description ?: $ft['name']
                    ];
                    break;
                }
            }
        }
        
        $result = $invoice->create($studentId, $feeTypesForInvoice, $_SESSION['user_id'], $dueDate);
        
        if ($result['success']) {
            $success_message = 'Invoice created successfully! Invoice Number: ' . $result['invoice_number'];
            // Clear form data
            $_POST = [];
        } else {
            $error_message = 'Failed to create invoice: ' . $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Create Invoice</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Create Invoice</h1>
                        <p class="text-gray-600">Generate new student fee invoice</p>
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
                    <a href="list.php" class="text-green-800 underline">View All Invoices</a> |
                    <a href="create.php" class="text-green-800 underline">Create Another Invoice</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Student Selection -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Student</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                        <select name="class_id" onchange="this.form.submit()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" 
                                    <?php echo $selectedClass == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Student</label>
                        <select name="student_id" onchange="this.form.submit()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                <?php echo empty($students) ? 'disabled' : ''; ?>>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>" 
                                    <?php echo $selectedStudent == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['admission_number'] . ' - ' . $s['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Load Student
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!empty($selectedStudent)): ?>
            <!-- Invoice Creation Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Create Invoice</h2>
                
                <form method="POST" id="invoiceForm">
                    <input type="hidden" name="create_invoice" value="1">
                    <input type="hidden" name="student_id" value="<?php echo $selectedStudent; ?>">
                    
                    <!-- Invoice Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Number</label>
                            <input type="text" value="<?php echo $invoice->generateInvoiceNumber(); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Issue Date</label>
                            <input type="date" value="<?php echo date('Y-m-d'); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Due Date *</label>
                            <input type="date" name="due_date" required 
                                   value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Generated By</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                        </div>
                    </div>
                    
                    <!-- Fee Types Selection -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Select Fee Types</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">
                                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Fee Type</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Default Amount</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Custom Amount</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($feeTypes as $feeType): ?>
                                    <?php 
                                    $defaultAmount = 0;
                                    // Find default amount from fee structure
                                    foreach ($studentFeeStructure as $fs) {
                                        if ($fs['fee_type_id'] == $feeType['id']) {
                                            $defaultAmount = $fs['fee_amount'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr class="border-t">
                                        <td class="p-3">
                                            <input type="checkbox" name="selected_fees[]" 
                                                   value="<?php echo $feeType['id']; ?>" 
                                                   class="fee-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="p-3">
                                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($feeType['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($feeType['description']); ?></div>
                                        </td>
                                        <td class="p-3 text-gray-800">
                                            ₹<?php echo number_format($defaultAmount, 2); ?>
                                        </td>
                                        <td class="p-3">
                                            <div class="relative">
                                                <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                                <input type="number" 
                                                       name="custom_amount[<?php echo $feeType['id']; ?>]" 
                                                       step="0.01" min="0"
                                                       placeholder="<?php echo $defaultAmount; ?>"
                                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <input type="text" 
                                                   name="description[<?php echo $feeType['id']; ?>]" 
                                                   placeholder="Optional description..."
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Total Calculation -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-800">Estimated Total Amount:</span>
                            <span class="text-2xl font-bold text-blue-600" id="totalAmount">₹0.00</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">This is an estimate based on default amounts. Custom amounts will be reflected in the final invoice.</p>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-4">
                        <a href="list.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" id="createBtn" disabled 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <i class="fas fa-file-invoice mr-2"></i>Create Invoice
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Help Section -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Invoice Creation Tips
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Select the appropriate fee types based on student's requirements</li>
                    <li>• Custom amounts override default amounts from fee structure</li>
                    <li>• Due date determines when the invoice becomes overdue</li>
                    <li>• Generated invoices can be emailed automatically to parents/guardians</li>
                    <li>• All invoices are saved as PDF for record keeping</li>
                </ul>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const feeCheckboxes = document.querySelectorAll('.fee-checkbox');
        const createBtn = document.getElementById('createBtn');
        const totalAmountSpan = document.getElementById('totalAmount');
        
        // Select all functionality
        selectAllCheckbox.addEventListener('change', function() {
            feeCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateCreateButton();
            calculateTotal();
        });
        
        // Individual checkbox change
        feeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateCreateButton();
                calculateTotal();
                
                // Update select all checkbox
                const checkedCount = document.querySelectorAll('.fee-checkbox:checked').length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < feeCheckboxes.length;
                selectAllCheckbox.checked = checkedCount === feeCheckboxes.length;
            });
        });
        
        // Custom amount inputs
        document.querySelectorAll('input[name*="custom_amount"]').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
        
        function updateCreateButton() {
            const checkedCount = document.querySelectorAll('.fee-checkbox:checked').length;
            createBtn.disabled = checkedCount === 0;
        }
        
        function calculateTotal() {
            let total = 0;
            
            feeCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const feeTypeId = checkbox.value;
                    const customAmountInput = document.querySelector(`input[name="custom_amount[${feeTypeId}]"]`);
                    const customAmount = parseFloat(customAmountInput.value) || 0;
                    
                    if (customAmount > 0) {
                        total += customAmount;
                    } else {
                        // Get default amount from placeholder
                        const defaultAmount = parseFloat(customAmountInput.placeholder) || 0;
                        total += defaultAmount;
                    }
                }
            });
            
            totalAmountSpan.textContent = '₹' + total.toFixed(2);
        }
        
        // Initialize
        updateCreateButton();
        calculateTotal();
    });
    </script>
</body>
</html>