<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Teacher.php';

requirePermission('manage_payroll');

$teacher = new Teacher();
$teacherId = intval($_GET['id'] ?? 0);

if (!$teacherId) {
    header('Location: list.php?error=Invalid teacher ID');
    exit();
}

$teacherData = $teacher->getById($teacherId);
if (!$teacherData) {
    header('Location: list.php?error=Teacher not found');
    exit();
}

// Get payroll data
$currentMonth = date('Y-m');
$selectedMonth = $_GET['month'] ?? $currentMonth;

// Calculate basic payroll information
$basicSalary = floatval($teacherData['salary']);
$workingDays = 26; // Standard working days per month
$attendanceData = $teacher->getAttendanceSummary($teacherId, $selectedMonth);

$error_message = '';
$success_message = '';

// Handle payroll generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_payroll'])) {
    $payrollData = [
        'teacher_id' => $teacherId,
        'month_year' => $_POST['month_year'],
        'basic_salary' => floatval($_POST['basic_salary']),
        'allowances' => floatval($_POST['allowances']),
        'deductions' => floatval($_POST['deductions']),
        'overtime_hours' => floatval($_POST['overtime_hours']),
        'overtime_rate' => floatval($_POST['overtime_rate']),
        'present_days' => intval($_POST['present_days']),
        'working_days' => intval($_POST['working_days']),
        'remarks' => trim($_POST['remarks'])
    ];
    
    $result = $teacher->generatePayroll($payrollData, $_SESSION['user_id']);
    if ($result['success']) {
        $success_message = 'Payroll generated successfully!';
    } else {
        $error_message = 'Failed to generate payroll: ' . $result['message'];
    }
}

// Get existing payroll records
$payrollRecords = $teacher->getPayrollHistory($teacherId, 12); // Last 12 months
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Teacher Payroll</title>
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
                    <a href="view.php?id=<?php echo $teacherId; ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Teacher Payroll</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($teacherData['name']); ?> - <?php echo htmlspecialchars($teacherData['employee_id']); ?></p>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Salary Overview -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Basic Salary</p>
                            <p class="text-2xl font-bold text-gray-900">₹<?php echo number_format($basicSalary, 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Present Days</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $attendanceData['present'] ?? 0; ?>/<?php echo $workingDays; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Employment Type -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-briefcase text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Employment Type</p>
                            <p class="text-lg font-bold text-gray-900"><?php echo ucwords(str_replace('_', ' ', $teacherData['employment_type'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generate Payroll -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Generate Payroll</h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="generate_payroll" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Month/Year *</label>
                            <input type="month" name="month_year" required value="<?php echo $selectedMonth; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Basic Salary *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                <input type="number" name="basic_salary" step="0.01" required 
                                       value="<?php echo $basicSalary; ?>"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Allowances</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                <input type="number" name="allowances" step="0.01" min="0" value="0"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deductions</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                <input type="number" name="deductions" step="0.01" min="0" value="0"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Present Days *</label>
                            <input type="number" name="present_days" min="0" max="31" required 
                                   value="<?php echo $attendanceData['present'] ?? $workingDays; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Working Days *</label>
                            <input type="number" name="working_days" min="1" max="31" required 
                                   value="<?php echo $workingDays; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Hours</label>
                            <input type="number" name="overtime_hours" step="0.5" min="0" value="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Rate (per hour)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                <input type="number" name="overtime_rate" step="0.01" min="0" value="100"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                        <textarea name="remarks" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Optional remarks..."></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-calculator mr-2"></i>Generate Payroll
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payroll History -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Payroll History</h2>
                </div>
                
                <?php if (empty($payrollRecords)): ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-file-invoice text-4xl mb-4 text-gray-300"></i>
                    <p>No payroll records found</p>
                    <p class="text-sm">Generate the first payroll using the form above</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Month/Year</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Basic Salary</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Allowances</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Deductions</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Net Salary</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Status</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payrollRecords as $record): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4 font-medium">
                                    <?php echo date('M Y', strtotime($record['month_year'] . '-01')); ?>
                                </td>
                                <td class="p-4">₹<?php echo number_format($record['basic_salary'], 2); ?></td>
                                <td class="p-4">₹<?php echo number_format($record['allowances'], 2); ?></td>
                                <td class="p-4">₹<?php echo number_format($record['deductions'], 2); ?></td>
                                <td class="p-4 font-semibold">₹<?php echo number_format($record['net_salary'], 2); ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        <?php echo ucfirst($record['status'] ?? 'generated'); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <a href="payslip.php?id=<?php echo $record['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-file-pdf mr-1"></i>View Payslip
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Help Section -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Payroll Information
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Basic Salary:</strong> Monthly fixed salary as per contract</li>
                    <li>• <strong>Allowances:</strong> Additional payments (transport, meal, etc.)</li>
                    <li>• <strong>Deductions:</strong> Amounts to be deducted (tax, PF, etc.)</li>
                    <li>• <strong>Overtime:</strong> Additional payment for extra hours worked</li>
                    <li>• Salary is calculated as: (Basic Salary × Present Days / Working Days) + Allowances + Overtime - Deductions</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>