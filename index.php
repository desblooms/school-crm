<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
                <!-- Students Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-500 text-white rounded-full">
                            <i class="fas fa-user-graduate text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total Students</h3>
                            <p class="text-2xl font-bold text-blue-600" id="total-students">0</p>
                        </div>
                    </div>
                </div>

                <!-- Teachers Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-500 text-white rounded-full">
                            <i class="fas fa-chalkboard-teacher text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total Teachers</h3>
                            <p class="text-2xl font-bold text-green-600" id="total-teachers">0</p>
                        </div>
                    </div>
                </div>

                <!-- Fee Collection Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-500 text-white rounded-full">
                            <i class="fas fa-money-bill text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Monthly Collection</h3>
                            <p class="text-2xl font-bold text-yellow-600" id="monthly-collection">₹0</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Fees Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-500 text-white rounded-full">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700">Pending Fees</h3>
                            <p class="text-2xl font-bold text-red-600" id="pending-fees">₹0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Recent Activity</h3>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Time</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Activity</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">User</th>
                                <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity">
                            <tr>
                                <td colspan="4" class="text-center p-4 text-gray-500">No recent activity</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>