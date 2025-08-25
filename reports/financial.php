<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Report.php';

requirePermission('view_reports');

$report = new Report();
$classes = $report->getClasses();

// Handle filters
$filters = [
    'class_id' => $_GET['class_id'] ?? '',
    'month_year' => $_GET['month_year'] ?? date('Y-m')
];

$feeReport = $report->getFeeReport($filters);
$financialSummary = $report->getFinancialSummary(explode('-', $filters['month_year'])[0]);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'financial_report_' . date('Y-m-d') . '.csv';
    $report->exportToCSV($feeReport, $filename);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Financial Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Financial Reports</h1>
                            <p class="text-gray-600">Fee collection and financial analysis</p>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button onclick="printReport()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                       class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </a>
                </div>
            </div>

            <!-- Financial Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-coins text-green-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Total Collected</h3>
                            <p class="text-2xl font-bold text-green-600">
                                ₹<?php echo number_format(array_sum(array_column($feeReport, 'paid_amount')), 2); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Pending Amount</h3>
                            <p class="text-2xl font-bold text-red-600">
                                ₹<?php echo number_format(array_sum(array_column($feeReport, 'pending_amount')), 2); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Collection Rate</h3>
                            <p class="text-2xl font-bold text-blue-600">
                                <?php 
                                $totalAmount = array_sum(array_column($feeReport, 'total_amount'));
                                $paidAmount = array_sum(array_column($feeReport, 'paid_amount'));
                                echo $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 1) : 0;
                                ?>%
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-users text-purple-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Students</h3>
                            <p class="text-2xl font-bold text-purple-600"><?php echo count($feeReport); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filters</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                        <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $filters['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Month/Year</label>
                        <input type="month" name="month_year" value="<?php echo $filters['month_year']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Collection Trend -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Collection Trend</h3>
                    <div style="width: 100%; height: 300px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <!-- Fee Type Distribution -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Fee Type Distribution</h3>
                    <div style="width: 100%; height: 300px;">
                        <canvas id="feeTypeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Payment Status Overview -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Status Overview</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-check text-green-600 text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Fully Paid</h4>
                        <p class="text-2xl font-bold text-green-600">
                            <?php echo count(array_filter($feeReport, fn($r) => $r['pending_amount'] == 0)); ?>
                        </p>
                        <p class="text-sm text-gray-500">Students</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Partially Paid</h4>
                        <p class="text-2xl font-bold text-yellow-600">
                            <?php echo count(array_filter($feeReport, fn($r) => $r['paid_amount'] > 0 && $r['pending_amount'] > 0)); ?>
                        </p>
                        <p class="text-sm text-gray-500">Students</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-times text-red-600 text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Not Paid</h4>
                        <p class="text-2xl font-bold text-red-600">
                            <?php echo count(array_filter($feeReport, fn($r) => $r['paid_amount'] == 0)); ?>
                        </p>
                        <p class="text-sm text-gray-500">Students</p>
                    </div>
                </div>
            </div>

            <!-- Detailed Fee Report -->
            <div class="bg-white rounded-lg shadow-md" id="reportTable">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Fee Collection Details</h3>
                </div>

                <?php if (empty($feeReport)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-chart-line text-4xl mb-4 text-gray-300"></i>
                    <p>No financial data found for the selected criteria</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Admission No.</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Student Name</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Class</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Total Amount</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Paid Amount</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Pending Amount</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feeReport as $fee): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4 font-medium text-blue-600">
                                    <a href="../students/view.php?id=<?php echo $fee['id']; ?>" class="hover:underline">
                                        <?php echo htmlspecialchars($fee['admission_number']); ?>
                                    </a>
                                </td>
                                <td class="p-4"><?php echo htmlspecialchars($fee['student_name']); ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($fee['class_name'] . ' - ' . $fee['section']); ?></td>
                                <td class="p-4 font-medium">₹<?php echo number_format($fee['total_amount'], 2); ?></td>
                                <td class="p-4 text-green-600 font-medium">₹<?php echo number_format($fee['paid_amount'], 2); ?></td>
                                <td class="p-4 text-red-600 font-medium">₹<?php echo number_format($fee['pending_amount'], 2); ?></td>
                                <td class="p-4">
                                    <?php if ($fee['pending_amount'] == 0): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Paid</span>
                                    <?php elseif ($fee['paid_amount'] > 0): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Partial</span>
                                    <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr class="font-semibold">
                                <td class="p-4" colspan="3">TOTAL</td>
                                <td class="p-4">₹<?php echo number_format(array_sum(array_column($feeReport, 'total_amount')), 2); ?></td>
                                <td class="p-4 text-green-600">₹<?php echo number_format(array_sum(array_column($feeReport, 'paid_amount')), 2); ?></td>
                                <td class="p-4 text-red-600">₹<?php echo number_format(array_sum(array_column($feeReport, 'pending_amount')), 2); ?></td>
                                <td class="p-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Monthly Collection Trend Chart
    const monthlyData = <?php echo json_encode($financialSummary['monthly_collections'] ?? []); ?>;
    
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => {
                const [year, month] = item.month.split('-');
                return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
            }),
            datasets: [{
                label: 'Collection Amount (₹)',
                data: monthlyData.map(item => parseFloat(item.total)),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Fee Type Distribution Chart
    const feeTypeData = <?php echo json_encode($financialSummary['fee_type_breakdown'] ?? []); ?>;
    
    const feeTypeCtx = document.getElementById('feeTypeChart').getContext('2d');
    new Chart(feeTypeCtx, {
        type: 'doughnut',
        data: {
            labels: feeTypeData.map(item => item.name),
            datasets: [{
                data: feeTypeData.map(item => parseFloat(item.total)),
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    function printReport() {
        const printContent = document.getElementById('reportTable').outerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Financial Report - <?php echo date('Y-m-d'); ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .header { text-align: center; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1><?php echo APP_NAME; ?></h1>
                        <h2>Financial Report</h2>
                        <p>Generated on: <?php echo date('F d, Y'); ?></p>
                        <p>Period: <?php echo date('F Y', strtotime($filters['month_year'] . '-01')); ?></p>
                    </div>
                    ${printContent}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
    </script>
</body>
</html>