<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Event.php';

requirePermission('view_events');

$event = new Event();

// Get current month or selected month
$currentMonth = $_GET['month'] ?? date('Y-m');
$calendarEvents = $event->getCalendarEvents($currentMonth);

// Calendar generation
$year = date('Y', strtotime($currentMonth . '-01'));
$month = date('n', strtotime($currentMonth . '-01'));
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$monthName = date('F Y', $firstDay);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay); // 0 = Sunday

// Navigation dates
$prevMonth = date('Y-m', strtotime($currentMonth . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($currentMonth . '-01 +1 month'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Event Calendar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .calendar-day {
            min-height: 120px;
        }
        .event-item {
            font-size: 10px;
            padding: 2px 4px;
            margin: 1px 0;
            border-radius: 3px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .event-academic { background-color: #dbeafe; color: #1e40af; }
        .event-sports { background-color: #dcfce7; color: #166534; }
        .event-cultural { background-color: #f3e8ff; color: #7c3aed; }
        .event-meeting { background-color: #fef3c7; color: #92400e; }
        .event-holiday { background-color: #fee2e2; color: #dc2626; }
        .event-other { background-color: #f3f4f6; color: #374151; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Event Calendar</h1>
                    <p class="text-gray-600">Monthly view of all events</p>
                </div>
                <div class="flex space-x-2">
                    <a href="list.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors flex items-center">
                        <i class="fas fa-list mr-2"></i>List View
                    </a>
                    <?php if (hasPermission('manage_events')): ?>
                    <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Add Event
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar Header -->
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="?month=<?php echo $prevMonth; ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <h2 class="text-xl font-semibold text-gray-800"><?php echo $monthName; ?></h2>
                        <a href="?month=<?php echo $nextMonth; ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <input type="month" value="<?php echo $currentMonth; ?>" 
                               onchange="window.location.href='?month=' + this.value"
                               class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <a href="?month=<?php echo date('Y-m'); ?>" class="text-blue-600 hover:text-blue-800">Today</a>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="p-4">
                    <!-- Days of Week Header -->
                    <div class="grid grid-cols-7 gap-px mb-2">
                        <?php $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; ?>
                        <?php foreach ($days as $day): ?>
                        <div class="bg-gray-100 p-2 text-center text-sm font-medium text-gray-600"><?php echo $day; ?></div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Calendar Days -->
                    <div class="grid grid-cols-7 gap-px border border-gray-200">
                        <?php
                        // Empty cells for days before the first day of the month
                        for ($i = 0; $i < $startDay; $i++) {
                            echo '<div class="calendar-day bg-gray-50 p-2 border-r border-b border-gray-200"></div>';
                        }

                        // Days of the month
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $currentDate = sprintf('%s-%02d', $currentMonth, $day);
                            $dayEvents = $calendarEvents[$currentDate] ?? [];
                            $isToday = $currentDate === date('Y-m-d');
                            
                            echo '<div class="calendar-day bg-white p-2 border-r border-b border-gray-200 relative' . ($isToday ? ' ring-2 ring-blue-500' : '') . '">';
                            echo '<div class="text-sm font-medium text-gray-800 mb-1">' . $day . '</div>';
                            
                            // Display events for this day
                            foreach ($dayEvents as $evt) {
                                $eventClass = 'event-' . $evt['event_type'];
                                echo '<div class="event-item ' . $eventClass . '" title="' . htmlspecialchars($evt['title'] . ' - ' . date('g:i A', strtotime($evt['start_time']))) . '">';
                                echo '<a href="view.php?id=' . $evt['id'] . '" class="text-inherit no-underline">';
                                echo htmlspecialchars(substr($evt['title'], 0, 20));
                                if (strlen($evt['title']) > 20) echo '...';
                                echo '</a>';
                                echo '</div>';
                            }
                            
                            if (count($dayEvents) > 3) {
                                echo '<div class="text-xs text-gray-500 mt-1">+' . (count($dayEvents) - 3) . ' more</div>';
                            }
                            
                            echo '</div>';
                        }

                        // Fill remaining cells
                        $remainingCells = 42 - ($daysInMonth + $startDay); // 6 rows x 7 days = 42 cells
                        for ($i = 0; $i < $remainingCells; $i++) {
                            echo '<div class="calendar-day bg-gray-50 p-2 border-r border-b border-gray-200"></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Event Legend -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Event Types</h3>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-blue-200 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Academic</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-200 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Sports</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-purple-200 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Cultural</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-yellow-200 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Meeting</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-200 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Holiday</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-gray-200 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Other</span>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Upcoming Events</h3>
                </div>
                <div class="p-6">
                    <?php
                    $upcomingEvents = $event->getUpcoming(5);
                    if (empty($upcomingEvents)):
                    ?>
                    <p class="text-gray-500 text-center py-4">No upcoming events</p>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcomingEvents as $evt): ?>
                        <div class="flex items-start space-x-4 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-calendar text-blue-600"></i>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($evt['title']); ?></h4>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($evt['description'], 0, 100) . '...'); ?></p>
                                <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                    <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($evt['event_date'])); ?></span>
                                    <span><i class="fas fa-clock mr-1"></i><?php echo date('g:i A', strtotime($evt['start_time'])); ?></span>
                                    <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($evt['location'] ?: 'TBA'); ?></span>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="view.php?id=<?php echo $evt['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>