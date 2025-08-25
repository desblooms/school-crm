<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Notice.php';

requirePermission('view_notices');

$notice = new Notice();

// Pagination
$page = intval($_GET['page'] ?? 1);
$limit = 15;
$offset = ($page - 1) * $limit;

// Filters
$filters = [
    'priority' => $_GET['priority'] ?? '',
    'target_audience' => $_GET['target_audience'] ?? ''
];

$notices = $notice->getAll($limit, $offset, $filters);
$totalNotices = $notice->getTotalCount($filters);
$totalPages = ceil($totalNotices / $limit);

// Clean up expired notices
$notice->cleanupExpired();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Notices</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Notices & Announcements</h1>
                    <p class="text-gray-600">Digital notice board for school communications</p>
                </div>
                <?php if (hasPermission('manage_notices')): ?>
                <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add Notice
                </a>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-bullhorn text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">Active Notices</h3>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $totalNotices; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">High Priority</h3>
                            <p class="text-2xl font-bold text-red-600">
                                <?php echo $notice->getTotalCount(['priority' => 'high']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-users text-green-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">For All</h3>
                            <p class="text-2xl font-bold text-green-600">
                                <?php echo $notice->getTotalCount(['target_audience' => 'all']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-calendar text-purple-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">This Week</h3>
                            <p class="text-2xl font-bold text-purple-600">
                                <?php
                                $stmt = Database::getInstance()->getConnection()->query("
                                    SELECT COUNT(*) FROM notices 
                                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                                    AND status = 'active'
                                ");
                                echo $stmt->fetchColumn();
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Priorities</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High Priority</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                        <select name="target_audience" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Audiences</option>
                            <option value="all" <?php echo $filters['target_audience'] === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="students" <?php echo $filters['target_audience'] === 'students' ? 'selected' : ''; ?>>Students</option>
                            <option value="teachers" <?php echo $filters['target_audience'] === 'teachers' ? 'selected' : ''; ?>>Teachers</option>
                            <option value="parents" <?php echo $filters['target_audience'] === 'parents' ? 'selected' : ''; ?>>Parents</option>
                            <option value="staff" <?php echo $filters['target_audience'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notices List -->
            <div class="space-y-4">
                <?php if (empty($notices)): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
                    <i class="fas fa-clipboard-list text-4xl mb-4 text-gray-300"></i>
                    <p>No notices found</p>
                    <?php if (hasPermission('manage_notices')): ?>
                    <a href="create.php" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        Create your first notice →
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php foreach ($notices as $ntc): ?>
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-2 mb-2">
                                <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($ntc['title']); ?></h3>
                                
                                <!-- Priority Badge -->
                                <?php 
                                $priorityColors = [
                                    'high' => 'bg-red-100 text-red-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'low' => 'bg-blue-100 text-blue-800'
                                ];
                                $priorityColor = $priorityColors[$ntc['priority']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $priorityColor; ?>">
                                    <?php echo ucfirst($ntc['priority']); ?> Priority
                                </span>
                                
                                <!-- Audience Badge -->
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    <?php echo ucfirst($ntc['target_audience']); ?>
                                </span>
                            </div>
                            
                            <p class="text-gray-700 mb-3"><?php echo nl2br(htmlspecialchars($ntc['content'])); ?></p>
                            
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($ntc['created_by_name']); ?></span>
                                <span><i class="fas fa-clock mr-1"></i><?php echo date('M d, Y g:i A', strtotime($ntc['created_at'])); ?></span>
                                <?php if ($ntc['expiry_date']): ?>
                                <span><i class="fas fa-calendar-times mr-1"></i>Expires: <?php echo date('M d, Y', strtotime($ntc['expiry_date'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (hasPermission('manage_notices')): ?>
                        <div class="flex-shrink-0 ml-4">
                            <div class="flex space-x-2">
                                <a href="edit.php?id=<?php echo $ntc['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deleteNotice(<?php echo $ntc['id']; ?>)" 
                                        class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="bg-white rounded-lg shadow-md px-6 py-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $totalNotices); ?> of <?php echo $totalNotices; ?> notices
                        </p>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                               class="px-3 py-2 border rounded-md text-sm <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    function deleteNotice(noticeId) {
        if (confirm('Are you sure you want to delete this notice?')) {
            fetch('../api/delete-notice.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notice_id: noticeId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete notice: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting notice');
            });
        }
    }
    </script>
</body>
</html>