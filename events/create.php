<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Event.php';

requirePermission('manage_events');

$event = new Event();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventData = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'event_date' => $_POST['event_date'],
        'start_time' => $_POST['start_time'],
        'end_time' => $_POST['end_time'],
        'event_type' => $_POST['event_type'],
        'location' => trim($_POST['location']),
        'target_audience' => $_POST['target_audience'],
        'created_by' => $_SESSION['user_id']
    ];
    
    // Validation
    $required_fields = ['title', 'event_date', 'start_time', 'end_time', 'event_type', 'target_audience'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($eventData[$field])) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } elseif (strtotime($eventData['start_time']) >= strtotime($eventData['end_time'])) {
        $error_message = 'End time must be after start time';
    } else {
        $result = $event->create($eventData);
        if ($result['success']) {
            $success_message = 'Event created successfully!';
            // Clear form data
            $_POST = [];
        } else {
            $error_message = 'Failed to create event: ' . $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Create Event</title>
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
                    <a href="list.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Create Event</h1>
                        <p class="text-gray-600">Schedule a new school event</p>
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
                <div class="mt-2">
                    <a href="list.php" class="text-green-800 underline">View All Events</a> |
                    <a href="calendar.php" class="text-green-800 underline">View Calendar</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Event Details</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Event Title *</label>
                                    <input type="text" name="title" required 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea name="description" rows="4" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              placeholder="Event description and details..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Event Date *</label>
                                    <input type="date" name="event_date" required 
                                           value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                    <input type="text" name="location" 
                                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                           placeholder="Event venue or location"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                                    <input type="time" name="start_time" required 
                                           value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                                    <input type="time" name="end_time" required 
                                           value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Event Type *</label>
                                    <select name="event_type" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Type</option>
                                        <option value="academic" <?php echo ($_POST['event_type'] ?? '') === 'academic' ? 'selected' : ''; ?>>Academic</option>
                                        <option value="sports" <?php echo ($_POST['event_type'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                                        <option value="cultural" <?php echo ($_POST['event_type'] ?? '') === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                                        <option value="meeting" <?php echo ($_POST['event_type'] ?? '') === 'meeting' ? 'selected' : ''; ?>>Meeting</option>
                                        <option value="holiday" <?php echo ($_POST['event_type'] ?? '') === 'holiday' ? 'selected' : ''; ?>>Holiday</option>
                                        <option value="other" <?php echo ($_POST['event_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience *</label>
                                    <select name="target_audience" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Audience</option>
                                        <option value="all" <?php echo ($_POST['target_audience'] ?? '') === 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="students" <?php echo ($_POST['target_audience'] ?? '') === 'students' ? 'selected' : ''; ?>>Students</option>
                                        <option value="teachers" <?php echo ($_POST['target_audience'] ?? '') === 'teachers' ? 'selected' : ''; ?>>Teachers</option>
                                        <option value="parents" <?php echo ($_POST['target_audience'] ?? '') === 'parents' ? 'selected' : ''; ?>>Parents</option>
                                        <option value="staff" <?php echo ($_POST['target_audience'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <a href="list.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Create Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Event Creation Tips
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Choose appropriate event types to help with organization and filtering</li>
                    <li>• Set proper target audience to ensure relevant notifications</li>
                    <li>• Include detailed descriptions to provide clear event information</li>
                    <li>• Events will appear on the calendar and in event listings</li>
                    <li>• All users with appropriate permissions will be able to view the event</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>