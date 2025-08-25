<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Invoice.php';
require_once '../classes/Student.php';
require_once '../classes/Fee.php';

requirePermission('view_invoices');

$invoice = new Invoice();
$student = new Student();
$fee = new Fee();

// Handle both invoice ID and receipt number
$invoiceId = intval($_GET['id'] ?? 0);
$receiptNumber = $_GET['receipt'] ?? '';

$invoiceData = null;
$paymentData = null;

if ($invoiceId > 0) {
    $invoiceData = $invoice->getById($invoiceId);
    if (!$invoiceData) {
        header('Location: list.php?error=Invoice not found');
        exit();
    }
} elseif ($receiptNumber) {
    // Get payment data by receipt number
    $paymentData = $fee->getPaymentByReceipt($receiptNumber);
    if (!$paymentData) {
        header('Location: list.php?error=Receipt not found');
        exit();
    }
} else {
    header('Location: list.php?error=Invalid invoice ID or receipt number');
    exit();
}

// If we have payment data, get student info
if ($paymentData) {
    $studentData = $student->getById($paymentData['student_id']);
}

// If we have invoice data, get student info
if ($invoiceData) {
    $studentData = $student->getById($invoiceData['student_id']);
    $invoiceItems = $invoice->getInvoiceItems($invoiceId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $paymentData ? 'Receipt' : 'Invoice'; ?> Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="no-print">
        <?php include '../includes/header.php'; ?>
    </div>
    
    <div class="flex">
        <div class="no-print">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6 no-print">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="list.php" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">
                                <?php echo $paymentData ? 'Payment Receipt' : 'Invoice Details'; ?>
                            </h1>
                            <p class="text-gray-600">
                                <?php echo $paymentData ? 'Receipt #' . htmlspecialchars($receiptNumber) : 'Invoice #' . $invoiceId; ?>
                            </p>
                        </div>
                    </div>
                    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </div>
            </div>

            <!-- Invoice/Receipt Content -->
            <div class="bg-white rounded-lg shadow max-w-4xl mx-auto">
                <!-- Header -->
                <div class="border-b border-gray-200 p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900"><?php echo APP_NAME; ?></h2>
                            <p class="text-gray-600 mt-2">School Management System</p>
                        </div>
                        <div class="text-right">
                            <h3 class="text-2xl font-bold text-blue-600">
                                <?php echo $paymentData ? 'RECEIPT' : 'INVOICE'; ?>
                            </h3>
                            <p class="text-gray-600">
                                <?php echo $paymentData ? 'Receipt #' . htmlspecialchars($receiptNumber) : 'Invoice #' . $invoiceId; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Student Info -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Student Information</h4>
                            <div class="space-y-2">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($studentData['name'] ?? 'N/A'); ?></p>
                                <p><strong>Admission No:</strong> <?php echo htmlspecialchars($studentData['admission_number'] ?? 'N/A'); ?></p>
                                <p><strong>Class:</strong> <?php echo htmlspecialchars($studentData['class_name'] ?? 'N/A'); ?> <?php echo htmlspecialchars($studentData['section'] ?? ''); ?></p>
                                <p><strong>Roll No:</strong> <?php echo htmlspecialchars($studentData['roll_number'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">
                                <?php echo $paymentData ? 'Payment Information' : 'Invoice Information'; ?>
                            </h4>
                            <div class="space-y-2">
                                <?php if ($paymentData): ?>
                                    <p><strong>Payment Date:</strong> <?php echo date('M j, Y', strtotime($paymentData['payment_date'])); ?></p>
                                    <p><strong>Payment Method:</strong> <span class="capitalize"><?php echo htmlspecialchars($paymentData['payment_method']); ?></span></p>
                                    <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($paymentData['transaction_id'] ?? 'N/A'); ?></p>
                                    <p><strong>Status:</strong> <span class="text-green-600 font-semibold">PAID</span></p>
                                <?php else: ?>
                                    <p><strong>Issue Date:</strong> <?php echo date('M j, Y', strtotime($invoiceData['created_at'])); ?></p>
                                    <p><strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($invoiceData['due_date'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="<?php echo $invoiceData['status'] === 'paid' ? 'text-green-600' : 'text-red-600'; ?> font-semibold">
                                            <?php echo strtoupper($invoiceData['status']); ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">
                        <?php echo $paymentData ? 'Payment Details' : 'Invoice Items'; ?>
                    </h4>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Description</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($paymentData): ?>
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-gray-900">
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($paymentData['fee_type_name'] ?? 'Fee Payment'); ?></p>
                                                <p class="text-gray-600">Month: <?php echo htmlspecialchars($paymentData['month_year'] ?? date('Y-m')); ?></p>
                                                <?php if ($paymentData['remarks']): ?>
                                                    <p class="text-gray-600 text-xs mt-1"><?php echo htmlspecialchars($paymentData['remarks']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-right font-medium text-gray-900">
                                            ₹<?php echo number_format($paymentData['amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php elseif (isset($invoiceItems)): ?>
                                    <?php foreach ($invoiceItems as $item): ?>
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-gray-900">
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($item['fee_type_name']); ?></p>
                                                <?php if ($item['description']): ?>
                                                    <p class="text-gray-600 text-xs"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-right font-medium text-gray-900">
                                            ₹<?php echo number_format($item['amount'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-center text-gray-500">No items found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total -->
                <div class="border-t border-gray-200 p-6">
                    <div class="flex justify-end">
                        <div class="text-right">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-lg font-semibold text-gray-900">Total Amount:</span>
                                <span class="text-2xl font-bold text-blue-600">
                                    ₹<?php 
                                    if ($paymentData) {
                                        echo number_format($paymentData['amount'], 2);
                                    } elseif ($invoiceData) {
                                        echo number_format($invoiceData['total_amount'], 2);
                                    } else {
                                        echo '0.00';
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if ($paymentData): ?>
                                <p class="text-sm text-green-600 font-medium">Amount Received: ₹<?php echo number_format($paymentData['amount'], 2); ?></p>
                            <?php elseif ($invoiceData && $invoiceData['status'] === 'paid'): ?>
                                <p class="text-sm text-green-600 font-medium">Paid in Full</p>
                            <?php elseif ($invoiceData): ?>
                                <p class="text-sm text-red-600 font-medium">Amount Due: ₹<?php echo number_format($invoiceData['total_amount'], 2); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 p-6 bg-gray-50">
                    <div class="text-center text-sm text-gray-600">
                        <p>Thank you for your payment!</p>
                        <p class="mt-1">This is a computer-generated document and does not require a signature.</p>
                        <p class="mt-2 text-xs">
                            Generated on: <?php echo date('F j, Y g:i A'); ?> | 
                            <?php echo $paymentData ? 'Collected by: ' . htmlspecialchars($paymentData['collected_by_name'] ?? 'System') : 'Generated by: ' . htmlspecialchars($_SESSION['user_name'] ?? 'System'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>