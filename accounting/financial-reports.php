<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Accounting.php';

requirePermission('view_accounts');

$accounting = new Accounting();
$year = $_GET['year'] ?? date('Y');
$reportType = $_GET['report_type'] ?? 'monthly';

// Get yearly comparison
$yearlyData = $accounting->getYearlyComparison([$year - 1, $year]);

// Get current month data
$currentMonthData = $accounting->getMonthlyFinancialSummary();

// Calculate year-to-date figures
$ytdIncome = 0;
$ytdExpenses = 0;
$ytdPayroll = 0;

if (isset($yearlyData[$year])) {
    for ($month = 1; $month <= date('n'); $month++) {
        if (isset($yearlyData[$year][$month])) {
            $ytdIncome += $yearlyData[$year][$month]['income'];
            $ytdExpenses += $yearlyData[$year][$month]['expenses'];
            $ytdPayroll += $yearlyData[$year][$month]['payroll'];
        }
    }
}
$ytdProfit = $ytdIncome - $ytdExpenses - $ytdPayroll;

// Prepare chart data
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$incomeData = [];
$expenseData = [];
$profitData = [];

for ($month = 1; $month <= 12; $month++) {
    $monthData = $yearlyData[$year][$month] ?? null;
    $incomeData[] = $monthData ? $monthData['income'] : 0;
    $expenseData[] = $monthData ? ($monthData['expenses'] + $monthData['payroll']) : 0;
    $profitData[] = $monthData ? $monthData['net_profit'] : 0;
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
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Financial Reports</h1>
                        <p class="text-gray-600">Comprehensive financial analysis and reporting</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700" onclick="exportReport()">
                            <i class="fas fa-download mr-2"></i>Export PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Report Configuration</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                        <select name="report_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="monthly" <?php echo $reportType === 'monthly' ? 'selected' : ''; ?>>Monthly Analysis</option>
                            <option value="quarterly" <?php echo $reportType === 'quarterly' ? 'selected' : ''; ?>>Quarterly Summary</option>
                            <option value="yearly" <?php echo $reportType === 'yearly' ? 'selected' : ''; ?>>Yearly Comparison</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-chart-line mr-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Year-to-Date Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">YTD Income</p>
                            <p class="text-2xl font-bold text-green-600">₹<?php echo number_format($ytdIncome, 2); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-arrow-up text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">As of <?php echo date('M Y'); ?></p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">YTD Expenses</p>
                            <p class="text-2xl font-bold text-red-600">₹<?php echo number_format($ytdExpenses + $ytdPayroll, 2); ?></p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-arrow-down text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Including payroll</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">YTD Profit</p>
                            <p class="text-2xl font-bold <?php echo $ytdProfit >= 0 ? 'text-blue-600' : 'text-red-600'; ?>">
                                ₹<?php echo number_format($ytdProfit, 2); ?>
                            </p>
                        </div>
                        <div class="<?php echo $ytdProfit >= 0 ? 'bg-blue-100' : 'bg-red-100'; ?> p-3 rounded-full">
                            <i class="fas fa-chart-line <?php echo $ytdProfit >= 0 ? 'text-blue-600' : 'text-red-600'; ?> text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Net profit/loss</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Profit Margin</p>
                            <p class="text-2xl font-bold text-purple-600">
                                <?php echo $ytdIncome > 0 ? number_format(($ytdProfit / $ytdIncome) * 100, 1) : '0.0'; ?>%
                            </p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-percentage text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Profitability ratio</p>
                </div>
            </div>

            <!-- Financial Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Trend Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Financial Trend - <?php echo $year; ?></h3>
                    <canvas id="monthlyTrendChart" width="400" height="200"></canvas>
                </div>

                <!-- Profit Analysis Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Profit Analysis</h3>
                    <canvas id="profitChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Quarterly Breakdown -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Quarterly Performance - <?php echo $year; ?></h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quarter</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expenses</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payroll</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Profit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Growth</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $quarters = [
                                ['Q1', 'Jan - Mar', [1, 2, 3]],
                                ['Q2', 'Apr - Jun', [4, 5, 6]],
                                ['Q3', 'Jul - Sep', [7, 8, 9]],
                                ['Q4', 'Oct - Dec', [10, 11, 12]]
                            ];
                            
                            $prevQuarterProfit = 0;
                            foreach ($quarters as $quarter):
                                [$qName, $period, $qMonths] = $quarter;
                                $qIncome = $qExpenses = $qPayroll = 0;
                                
                                foreach ($qMonths as $month) {
                                    if (isset($yearlyData[$year][$month])) {
                                        $qIncome += $yearlyData[$year][$month]['income'];
                                        $qExpenses += $yearlyData[$year][$month]['expenses'];
                                        $qPayroll += $yearlyData[$year][$month]['payroll'];
                                    }
                                }
                                
                                $qProfit = $qIncome - $qExpenses - $qPayroll;
                                $growth = $prevQuarterProfit > 0 ? (($qProfit - $prevQuarterProfit) / $prevQuarterProfit) * 100 : 0;
                                $prevQuarterProfit = $qProfit;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $qName; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $period; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">₹<?php echo number_format($qIncome, 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">₹<?php echo number_format($qExpenses, 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600">₹<?php echo number_format($qPayroll, 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $qProfit >= 0 ? 'text-blue-600' : 'text-red-600'; ?>">
                                    ₹<?php echo number_format($qProfit, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $growth >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php if ($growth != 0): ?>
                                        <i class="fas fa-arrow-<?php echo $growth >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                                        <?php echo number_format(abs($growth), 1); ?>%
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Financial Metrics -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Key Financial Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-600 mb-2">Revenue Growth Rate</h4>
                        <p class="text-2xl font-bold text-blue-600">
                            <?php
                            $lastYearIncome = 0;
                            if (isset($yearlyData[$year - 1])) {
                                foreach ($yearlyData[$year - 1] as $monthData) {
                                    $lastYearIncome += $monthData['income'];
                                }
                            }
                            $growthRate = $lastYearIncome > 0 ? (($ytdIncome - $lastYearIncome) / $lastYearIncome) * 100 : 0;
                            echo ($growthRate >= 0 ? '+' : '') . number_format($growthRate, 1);
                            ?>%
                        </p>
                        <p class="text-xs text-gray-500">vs. last year</p>
                    </div>
                    
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-600 mb-2">Operating Ratio</h4>
                        <p class="text-2xl font-bold text-green-600">
                            <?php echo $ytdIncome > 0 ? number_format((($ytdExpenses + $ytdPayroll) / $ytdIncome) * 100, 1) : '0'; ?>%
                        </p>
                        <p class="text-xs text-gray-500">expense to income</p>
                    </div>
                    
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-600 mb-2">Break-even Point</h4>
                        <p class="text-2xl font-bold text-purple-600">
                            ₹<?php echo number_format($ytdExpenses + $ytdPayroll, 0); ?>
                        </p>
                        <p class="text-xs text-gray-500">monthly requirement</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Monthly Trend Chart
        const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Income',
                    data: <?php echo json_encode($incomeData); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: false
                }, {
                    label: 'Expenses',
                    data: <?php echo json_encode($expenseData); ?>,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: false
                }]
            },
            options: {
                responsive: true,
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

        // Profit Chart
        const profitCtx = document.getElementById('profitChart').getContext('2d');
        new Chart(profitCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Net Profit',
                    data: <?php echo json_encode($profitData); ?>,
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        return value >= 0 ? 'rgba(59, 130, 246, 0.8)' : 'rgba(239, 68, 68, 0.8)';
                    },
                    borderColor: function(context) {
                        const value = context.parsed.y;
                        return value >= 0 ? 'rgb(59, 130, 246)' : 'rgb(239, 68, 68)';
                    },
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
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

        function exportReport() {
            alert('Report export functionality would generate PDF here');
        }
    </script>
</body>
</html>