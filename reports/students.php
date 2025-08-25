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
    'gender' => $_GET['gender'] ?? '',
    'admission_year' => $_GET['admission_year'] ?? ''
];

$students = $report->getStudentReport($filters);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'student_report_' . date('Y-m-d') . '.csv';
    $report->exportToCSV($students, $filename);
}

$reportType = $_GET['type'] ?? 'list';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Student Reports</title>
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
                            <h1 class="text-2xl font-bold text-gray-800">Student Reports</h1>
                            <p class="text-gray-600">Comprehensive student data and analytics</p>
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

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-users text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Total Students</h3>
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($students); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-venus-mars text-purple-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Male/Female</h3>
                            <p class="text-lg font-bold text-gray-900">
                                <?php 
                                $maleCount = count(array_filter($students, fn($s) => $s['gender'] === 'Male'));
                                $femaleCount = count(array_filter($students, fn($s) => $s['gender'] === 'Female'));
                                echo "$maleCount / $femaleCount";
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-chart-pie text-green-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Classes</h3>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo count(array_unique(array_column($students, 'class_id'))); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <i class="fas fa-birthday-cake text-orange-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Avg Age</h3>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php 
                                $ages = array_filter(array_column($students, 'age'));
                                echo $ages ? round(array_sum($ages) / count($ages), 1) : '0';
                                ?> years
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filters</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                        <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Genders</option>
                            <option value="Male" <?php echo $filters['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $filters['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $filters['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Admission Year</label>
                        <select name="admission_year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Years</option>
                            <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                            <option value="<?php echo $year; ?>" <?php echo $filters['admission_year'] == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
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
                <!-- Gender Distribution Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Gender Distribution</h3>
                    <div style="width: 100%; height: 300px;">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>

                <!-- Class-wise Distribution Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Class-wise Distribution</h3>
                    <div style="width: 100%; height: 300px;">
                        <canvas id="classChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Student List -->
            <div class="bg-white rounded-lg shadow-md" id="reportTable">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Student Details (<?php echo count($students); ?> students)</h3>
                </div>

                <?php if (empty($students)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                    <p>No students found matching the criteria</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Admission No.</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Name</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Class</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Roll No.</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Gender</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Age</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Phone</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4 font-medium text-blue-600">
                                    <a href="../students/view.php?id=<?php echo $student['id']; ?>" class="hover:underline">
                                        <?php echo htmlspecialchars($student['admission_number']); ?>
                                    </a>
                                </td>
                                <td class="p-4"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section']); ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $student['gender'] === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                        <?php echo htmlspecialchars($student['gender'] ?: 'Not specified'); ?>
                                    </span>
                                </td>
                                <td class="p-4"><?php echo $student['age'] ?? 'N/A'; ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($student['phone'] ?: 'Not provided'); ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($student['email'] ?: 'Not provided'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Gender Distribution Chart
    const genderData = <?php
        $genderCount = [];
        foreach ($students as $student) {
            $gender = $student['gender'] ?: 'Not specified';
            $genderCount[$gender] = ($genderCount[$gender] ?? 0) + 1;
        }
        echo json_encode($genderCount);
    ?>;

    const genderCtx = document.getElementById('genderChart').getContext('2d');
    new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(genderData),
            datasets: [{
                data: Object.values(genderData),
                backgroundColor: ['#3B82F6', '#EC4899', '#10B981', '#F59E0B'],
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

    // Class Distribution Chart
    const classData = <?php
        $classCount = [];
        foreach ($students as $student) {
            $className = $student['class_name'] . ' - ' . $student['section'];
            $classCount[$className] = ($classCount[$className] ?? 0) + 1;
        }
        echo json_encode($classCount);
    ?>;

    const classCtx = document.getElementById('classChart').getContext('2d');
    new Chart(classCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(classData),
            datasets: [{
                label: 'Number of Students',
                data: Object.values(classData),
                backgroundColor: '#3B82F6',
                borderColor: '#1E40AF',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
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
                    <title>Student Report - <?php echo date('Y-m-d'); ?></title>
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
                        <h2>Student Report</h2>
                        <p>Generated on: <?php echo date('F d, Y'); ?></p>
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