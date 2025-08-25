<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Student.php';
require_once '../classes/Fee.php';

requirePermission('view_students');

$student = new Student();
$fee = new Fee();

$studentId = intval($_GET['id'] ?? 0);

if (!$studentId) {
    header('Location: list.php?error=Invalid student ID');
    exit();
}

$studentData = $student->getById($studentId);
if (!$studentData) {
    header('Location: list.php?error=Student not found');
    exit();
}

// Get attendance data for current month
$currentMonth = date('Y-m');
$attendanceData = $student->getAttendanceByMonth($studentId, $currentMonth);

// Get attendance summary
$attendanceSummary = $student->getAttendanceSummary($studentId);

// Handle month filter
$selectedMonth = $_GET['month'] ?? $currentMonth;
if ($selectedMonth !== $currentMonth) {
    $attendanceData = $student->getAttendanceByMonth($studentId, $selectedMonth);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Student Attendance</title>
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
                    <a href="view.php?id=<?php echo $studentId; ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Student Attendance</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($studentData['name']); ?> - <?php echo htmlspecialchars($studentData['admission_number']); ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                <!-- Attendance Summary Cards -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Present Days</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $attendanceSummary['present'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Absent Days</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $attendanceSummary['absent'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Late Arrivals</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $attendanceSummary['late'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-percentage text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Attendance Rate</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $attendanceSummary['percentage'] ?? 0; ?>%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Month Filter -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Attendance Records</h2>
                    <form method="GET" class="flex items-center space-x-2">
                        <input type="hidden" name="id" value="<?php echo $studentId; ?>">
                        <label class="text-sm text-gray-600">Month:</label>
                        <input type="month" name="month" value="<?php echo $selectedMonth; ?>" 
                               onchange="this.form.submit()"
                               class="px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </form>
                </div>

                <?php if (empty($attendanceData)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-calendar-times text-4xl mb-4 text-gray-300"></i>
                    <p>No attendance records found for <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></p>
                    <p class="text-sm">Attendance records will appear here once they are marked</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Date</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Day</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Check-in Time</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Check-out Time</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Remarks</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Marked By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceData as $record): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3">
                                    <div class="font-medium text-gray-900"><?php echo date('M d, Y', strtotime($record['date'])); ?></div>
                                </td>
                                <td class="p-3 text-gray-600">
                                    <?php echo date('l', strtotime($record['date'])); ?>
                                </td>
                                <td class="p-3">
                                    <?php
                                    $statusClass = [
                                        'present' => 'bg-green-100 text-green-800',
                                        'absent' => 'bg-red-100 text-red-800',
                                        'late' => 'bg-yellow-100 text-yellow-800',
                                        'excused' => 'bg-blue-100 text-blue-800'
                                    ];
                                    $class = $statusClass[$record['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $class; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td class="p-3 text-gray-600">
                                    <?php echo $record['check_in_time'] ? date('g:i A', strtotime($record['check_in_time'])) : '-'; ?>
                                </td>
                                <td class="p-3 text-gray-600">
                                    <?php echo $record['check_out_time'] ? date('g:i A', strtotime($record['check_out_time'])) : '-'; ?>
                                </td>
                                <td class="p-3 text-gray-600">
                                    <?php echo htmlspecialchars($record['remarks'] ?: '-'); ?>
                                </td>
                                <td class="p-3 text-gray-600 text-sm">
                                    <?php echo htmlspecialchars($record['marked_by_name'] ?? 'System'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="view.php?id=<?php echo $studentId; ?>" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-user mr-2"></i>View Profile
                    </a>
                    
                    <a href="../fees/student-fees.php?student_id=<?php echo $studentId; ?>" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-money-bill mr-2"></i>Fee Status
                    </a>
                    
                    <a href="../reports/student-report.php?id=<?php echo $studentId; ?>" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chart-bar mr-2"></i>Generate Report
                    </a>
                </div>
            </div>

            <!-- Help Section -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Attendance Information
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Present:</strong> Student was in school and attended classes</li>
                    <li>• <strong>Absent:</strong> Student was not present in school</li>
                    <li>• <strong>Late:</strong> Student arrived after the designated time but attended classes</li>
                    <li>• <strong>Excused:</strong> Student was absent but with valid reason (sick leave, etc.)</li>
                    <li>• Attendance percentage is calculated based on total school days</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>