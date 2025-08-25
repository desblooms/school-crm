<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Notice.php';

requirePermission('manage_notices');

$notice = new Notice();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noticeData = [
        'title' => trim($_POST['title']),
        'content' => trim($_POST['content']),
        'priority' => $_POST['priority'],
        'target_audience' => $_POST['target_audience'],
        'expiry_date' => $_POST['expiry_date'] ?: null,
        'created_by' => $_SESSION['user_id']
    ];
    
    // Validation
    $required_fields = ['title', 'content', 'priority', 'target_audience'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($noticeData[$field])) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } else {
        $result = $notice->create($noticeData);
        if ($result['success']) {
            $success_message = 'Notice created successfully and is now visible to the target audience!';
            // Clear form data
            $_POST = [];
        } else {
            $error_message = 'Failed to create notice: ' . $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Create Notice</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Create Notice</h1>
                        <p class="text-gray-600">Publish a new announcement or notice</p>
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
                    <a href="list.php" class="text-green-800 underline">View All Notices</a> |
                    <a href="create.php" class="text-green-800 underline">Create Another Notice</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Notice Details -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Notice Details</h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notice Title *</label>
                                    <input type="text" name="title" required 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                           placeholder="Enter notice title..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notice Content *</label>
                                    <textarea name="content" rows="8" required 
                                              placeholder="Enter the detailed content of the notice..."
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level *</label>
                                        <select name="priority" required 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Priority</option>
                                            <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High Priority</option>
                                            <option value="medium" <?php echo ($_POST['priority'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                                            <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">High priority notices appear at the top</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience *</label>
                                        <select name="target_audience" required 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Audience</option>
                                            <option value="all" <?php echo ($_POST['target_audience'] ?? '') === 'all' ? 'selected' : ''; ?>>All Users</option>
                                            <option value="students" <?php echo ($_POST['target_audience'] ?? '') === 'students' ? 'selected' : ''; ?>>Students Only</option>
                                            <option value="teachers" <?php echo ($_POST['target_audience'] ?? '') === 'teachers' ? 'selected' : ''; ?>>Teachers Only</option>
                                            <option value="parents" <?php echo ($_POST['target_audience'] ?? '') === 'parents' ? 'selected' : ''; ?>>Parents Only</option>
                                            <option value="staff" <?php echo ($_POST['target_audience'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff Only</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Optional)</label>
                                        <input type="date" name="expiry_date" 
                                               value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>"
                                               min="<?php echo date('Y-m-d'); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <p class="text-xs text-gray-500 mt-1">Notice will be hidden after this date</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Preview</h3>
                            <div id="noticePreview" class="bg-gray-50 rounded-lg p-4 border">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 id="previewTitle" class="font-semibold text-gray-800">Notice Title</h4>
                                    <span id="previewPriority" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Priority</span>
                                </div>
                                <p id="previewContent" class="text-gray-700 mb-2">Notice content will appear here...</p>
                                <div class="text-sm text-gray-500">
                                    <span>For: <span id="previewAudience">Target Audience</span></span>
                                    <span class="ml-4">By: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <a href="list.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-bullhorn mr-2"></i>Publish Notice
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Guidelines -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Notice Guidelines
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Keep titles clear and concise for better readability</li>
                    <li>• Use high priority only for urgent or important announcements</li>
                    <li>• Set expiry dates for time-sensitive notices</li>
                    <li>• Target specific audiences to reduce notification noise</li>
                    <li>• Include all necessary details in the content section</li>
                </ul>
            </div>
        </main>
    </div>

    <script>
    // Live preview functionality
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.querySelector('input[name="title"]');
        const contentInput = document.querySelector('textarea[name="content"]');
        const prioritySelect = document.querySelector('select[name="priority"]');
        const audienceSelect = document.querySelector('select[name="target_audience"]');
        
        const previewTitle = document.getElementById('previewTitle');
        const previewContent = document.getElementById('previewContent');
        const previewPriority = document.getElementById('previewPriority');
        const previewAudience = document.getElementById('previewAudience');
        
        function updatePreview() {
            previewTitle.textContent = titleInput.value || 'Notice Title';
            previewContent.textContent = contentInput.value || 'Notice content will appear here...';
            
            const priority = prioritySelect.value;
            if (priority) {
                previewPriority.textContent = priority.charAt(0).toUpperCase() + priority.slice(1) + ' Priority';
                previewPriority.className = `px-2 py-1 text-xs rounded-full ${
                    priority === 'high' ? 'bg-red-100 text-red-800' :
                    priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-blue-100 text-blue-800'
                }`;
            } else {
                previewPriority.textContent = 'Priority';
                previewPriority.className = 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800';
            }
            
            const audience = audienceSelect.value;
            previewAudience.textContent = audience ? 
                audience.charAt(0).toUpperCase() + audience.slice(1) + (audience === 'all' ? ' Users' : '') : 
                'Target Audience';
        }
        
        // Add event listeners
        titleInput.addEventListener('input', updatePreview);
        contentInput.addEventListener('input', updatePreview);
        prioritySelect.addEventListener('change', updatePreview);
        audienceSelect.addEventListener('change', updatePreview);
        
        // Initial preview update
        updatePreview();
    });
    </script>
</body>
</html>