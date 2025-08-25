<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Event.php';

requirePermission('view_events');

$event = new Event();

// Pagination
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$filters = [
    'event_type' => $_GET['event_type'] ?? '',
    'target_audience' => $_GET['target_audience'] ?? '',
    'month' => $_GET['month'] ?? ''
];

$events = $event->getAll($limit, $offset, $filters);
$totalEvents = $event->getTotalCount($filters);
$totalPages = ceil($totalEvents / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Events</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Events Management</h1>
                    <p class="text-gray-600">Manage school events and activities</p>
                </div>
                <?php if (hasPermission('manage_events')): ?>
                <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add Event
                </a>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <a href="calendar.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-alt text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">Calendar View</h3>
                            <p class="text-sm text-gray-600">Monthly calendar</p>
                        </div>
                    </div>
                </a>

                <a href="../notices/list.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-bullhorn text-green-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">Notices</h3>
                            <p class="text-sm text-gray-600">View all notices</p>
                        </div>
                    </div>
                </a>

                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-chart-bar text-purple-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">Total Events</h3>
                            <p class="text-2xl font-bold text-purple-600"><?php echo $totalEvents; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-orange-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800">This Month</h3>
                            <p class="text-2xl font-bold text-orange-600">
                                <?php echo $event->getTotalCount(['month' => date('Y-m')]); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                        <select name="event_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="academic" <?php echo $filters['event_type'] === 'academic' ? 'selected' : ''; ?>>Academic</option>
                            <option value="sports" <?php echo $filters['event_type'] === 'sports' ? 'selected' : ''; ?>>Sports</option>
                            <option value="cultural" <?php echo $filters['event_type'] === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                            <option value="meeting" <?php echo $filters['event_type'] === 'meeting' ? 'selected' : ''; ?>>Meeting</option>
                            <option value="holiday" <?php echo $filters['event_type'] === 'holiday' ? 'selected' : ''; ?>>Holiday</option>
                            <option value="other" <?php echo $filters['event_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <input type="month" name="month" value="<?php echo $filters['month']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Events List -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Events List (<?php echo $totalEvents; ?> total)
                    </h2>
                </div>

                <?php if (empty($events)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-calendar text-4xl mb-4 text-gray-300"></i>
                    <p>No events found</p>
                    <?php if (hasPermission('manage_events')): ?>
                    <a href="create.php" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        Create your first event →
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Event</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Date & Time</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Type</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Audience</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Location</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Created By</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $evt): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($evt['title']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($evt['description'], 0, 60) . '...'); ?></div>
                                </td>
                                <td class="p-4">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($evt['event_date'])); ?></div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('g:i A', strtotime($evt['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($evt['end_time'])); ?>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $evt['event_type'] === 'academic' ? 'bg-blue-100 text-blue-800' : 
                                                 ($evt['event_type'] === 'sports' ? 'bg-green-100 text-green-800' : 
                                                 ($evt['event_type'] === 'cultural' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800')); ?>">
                                        <?php echo ucfirst($evt['event_type']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-sm text-gray-600"><?php echo ucfirst($evt['target_audience']); ?></td>
                                <td class="p-4 text-sm text-gray-600"><?php echo htmlspecialchars($evt['location'] ?: 'TBA'); ?></td>
                                <td class="p-4 text-sm text-gray-600"><?php echo htmlspecialchars($evt['created_by_name']); ?></td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <a href="view.php?id=<?php echo $evt['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (hasPermission('manage_events')): ?>
                                        <a href="edit.php?id=<?php echo $evt['id']; ?>" 
                                           class="text-green-600 hover:text-green-800 text-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteEvent(<?php echo $evt['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $totalEvents); ?> of <?php echo $totalEvents; ?> events
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
    function deleteEvent(eventId) {
        if (confirm('Are you sure you want to delete this event?')) {
            fetch('../api/delete-event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_id: eventId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete event: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting event');
            });
        }
    }
    </script>
</body>
</html>