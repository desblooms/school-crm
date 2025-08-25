<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Student.php';

requirePermission('view_students');

$student = new Student();
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$students = $student->getAll($limit, $offset, $search);
$totalStudents = $student->getTotalCount();
$totalPages = ceil($totalStudents / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Students List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Students</h1>
                        <p class="text-gray-600">Manage student records and profiles</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <a href="admission.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>New Admission
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" 
                               placeholder="Search by name, admission number, or email..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="list.php" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Students List (<?php echo $totalStudents; ?> total)
                        </h2>
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Photo</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Admission No.</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Name</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Class</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Roll No.</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Guardian</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                                    <th class="text-left p-3 text-sm font-medium text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" class="text-center p-8 text-gray-500">
                                        <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                        <p><?php echo empty($search) ? 'No students found' : 'No students match your search'; ?></p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-white text-sm font-medium">
                                                <?php echo strtoupper(substr($s['name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($s['admission_number']); ?></span>
                                    </td>
                                    <td class="p-3">
                                        <div>
                                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($s['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($s['email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="text-gray-800">
                                            <?php echo $s['class_name'] ? htmlspecialchars($s['class_name'] . '-' . $s['section']) : 'Not Assigned'; ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <span class="text-gray-800"><?php echo htmlspecialchars($s['roll_number'] ?: 'N/A'); ?></span>
                                    </td>
                                    <td class="p-3">
                                        <div>
                                            <div class="text-sm text-gray-800"><?php echo htmlspecialchars($s['guardian_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($s['guardian_phone']); ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $s['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($s['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center space-x-2">
                                            <a href="view.php?id=<?php echo $s['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800" title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $s['id']; ?>" 
                                               class="text-green-600 hover:text-green-800" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="attendance.php?id=<?php echo $s['id']; ?>" 
                                               class="text-purple-600 hover:text-purple-800" title="Attendance">
                                                <i class="fas fa-calendar-check"></i>
                                            </a>
                                            <a href="../fees/student.php?id=<?php echo $s['id']; ?>" 
                                               class="text-yellow-600 hover:text-yellow-800" title="Fee Details">
                                                <i class="fas fa-money-bill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between mt-6 pt-4 border-t">
                        <div class="text-sm text-gray-600">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalStudents); ?> of <?php echo $totalStudents; ?> students
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Previous
                            </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-3 py-2 text-sm <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>