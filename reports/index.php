<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Report.php';

requirePermission('view_reports');

$report = new Report();
$dashboardStats = $report->getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Reports Dashboard</title>
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
                <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
                <p class="text-gray-600">Comprehensive insights and data analysis</p>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Students</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboardStats['total_students'] ?? 0); ?></p>
                            <p class="text-xs text-green-600">+<?php echo $dashboardStats['new_students_month'] ?? 0; ?> this month</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Teachers</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboardStats['total_teachers'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Monthly Collection</p>
                            <p class="text-2xl font-bold text-gray-900">₹<?php echo number_format($dashboardStats['monthly_collection'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Attendance Rate</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $dashboardStats['attendance_rate'] ?? 0; ?>%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Student Reports -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-users text-blue-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Student Reports</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Comprehensive student data and analytics</p>
                    <div class="space-y-2">
                        <a href="students.php" class="block w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md">
                            <i class="fas fa-list mr-2"></i>Student Master List
                        </a>
                        <a href="students.php?type=admission" class="block w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md">
                            <i class="fas fa-user-plus mr-2"></i>Admission Report
                        </a>
                        <a href="students.php?type=class_wise" class="block w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md">
                            <i class="fas fa-chart-bar mr-2"></i>Class-wise Analysis
                        </a>
                    </div>
                </div>

                <!-- Teacher Reports -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-chalkboard-teacher text-green-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Teacher Reports</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Staff information and performance data</p>
                    <div class="space-y-2">
                        <a href="teachers.php" class="block w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-md">
                            <i class="fas fa-list mr-2"></i>Teacher Master List
                        </a>
                        <a href="teachers.php?type=subjects" class="block w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-md">
                            <i class="fas fa-book mr-2"></i>Subject Allocation
                        </a>
                        <a href="payroll.php" class="block w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-md">
                            <i class="fas fa-money-bill mr-2"></i>Payroll Summary
                        </a>
                    </div>
                </div>

                <!-- Attendance Reports -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-calendar-check text-purple-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Attendance Reports</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Daily, weekly and monthly attendance</p>
                    <div class="space-y-2">
                        <a href="attendance.php" class="block w-full text-left px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded-md">
                            <i class="fas fa-calendar-day mr-2"></i>Daily Attendance
                        </a>
                        <a href="attendance.php?type=monthly" class="block w-full text-left px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded-md">
                            <i class="fas fa-calendar mr-2"></i>Monthly Summary
                        </a>
                        <a href="attendance.php?type=defaulters" class="block w-full text-left px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded-md">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Low Attendance
                        </a>
                    </div>
                </div>

                <!-- Financial Reports -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-chart-line text-yellow-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Financial Reports</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Fee collection and financial analysis</p>
                    <div class="space-y-2">
                        <a href="financial.php" class="block w-full text-left px-3 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded-md">
                            <i class="fas fa-coins mr-2"></i>Collection Summary
                        </a>
                        <a href="financial.php?type=pending" class="block w-full text-left px-3 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded-md">
                            <i class="fas fa-exclamation-circle mr-2"></i>Pending Fees
                        </a>
                        <a href="financial.php?type=monthly" class="block w-full text-left px-3 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded-md">
                            <i class="fas fa-chart-bar mr-2"></i>Monthly Analysis
                        </a>
                    </div>
                </div>

                <!-- Academic Reports -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-graduation-cap text-red-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Academic Reports</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Academic performance and progress</p>
                    <div class="space-y-2">
                        <a href="academic.php" class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md">
                            <i class="fas fa-chart-line mr-2"></i>Performance Overview
                        </a>
                        <a href="academic.php?type=class_wise" class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md">
                            <i class="fas fa-users mr-2"></i>Class Performance
                        </a>
                        <a href="academic.php?type=subjects" class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md">
                            <i class="fas fa-book mr-2"></i>Subject Analysis
                        </a>
                    </div>
                </div>

                <!-- Custom Reports -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-cogs text-gray-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Custom Reports</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Build and generate custom reports</p>
                    <div class="space-y-2">
                        <a href="custom.php" class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-md">
                            <i class="fas fa-plus mr-2"></i>Create Report
                        </a>
                        <a href="custom.php?saved=true" class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-md">
                            <i class="fas fa-save mr-2"></i>Saved Reports
                        </a>
                        <a href="export.php" class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-md">
                            <i class="fas fa-download mr-2"></i>Export Data
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <button onclick="generateQuickReport('students')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-users mr-2"></i>Student Summary
                    </button>
                    <button onclick="generateQuickReport('teachers')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>Teacher Summary
                    </button>
                    <button onclick="generateQuickReport('attendance')" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                        <i class="fas fa-calendar-check mr-2"></i>Today's Attendance
                    </button>
                    <button onclick="generateQuickReport('financial')" class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-chart-line mr-2"></i>Financial Summary
                    </button>
                </div>
            </div>

        </main>
    </div>

    <script>
    function generateQuickReport(type) {
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
        button.disabled = true;
        
        // Redirect to appropriate report page
        setTimeout(() => {
            switch(type) {
                case 'students':
                    window.location.href = 'students.php?quick=true';
                    break;
                case 'teachers':
                    window.location.href = 'teachers.php?quick=true';
                    break;
                case 'attendance':
                    window.location.href = 'attendance.php?date=' + new Date().toISOString().split('T')[0];
                    break;
                case 'financial':
                    window.location.href = 'financial.php?quick=true';
                    break;
            }
        }, 1000);
    }
    </script>
</body>
</html>