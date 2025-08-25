<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Teacher.php';
require_once '../classes/Subject.php';
require_once '../classes/Fee.php';

requireRole('admin');

$teacher = new Teacher();
$subject = new Subject();
$fee = new Fee();

$teacherId = intval($_GET['id'] ?? 0);

if (!$teacherId) {
    header('Location: list.php?error=Invalid teacher ID');
    exit();
}

$teacherData = $teacher->getById($teacherId);
if (!$teacherData) {
    header('Location: list.php?error=Teacher not found');
    exit();
}

// Get current teacher subjects and available subjects
$currentSubjects = $teacher->getTeacherSubjects($teacherId);
$allSubjects = $subject->getAllSubjects();
$classes = $fee->getClasses();

$error_message = '';
$success_message = '';

// Handle subject assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subject'])) {
    $subjectId = intval($_POST['subject_id']);
    $classId = intval($_POST['class_id']);
    
    if ($subjectId && $classId) {
        $result = $teacher->assignSubject($teacherId, $subjectId, $classId);
        if ($result['success']) {
            $success_message = 'Subject assigned successfully!';
            $currentSubjects = $teacher->getTeacherSubjects($teacherId); // Refresh
        } else {
            $error_message = 'Failed to assign subject: ' . $result['message'];
        }
    } else {
        $error_message = 'Please select both subject and class';
    }
}

// Handle subject removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_subject'])) {
    $assignmentId = intval($_POST['assignment_id']);
    
    $result = $teacher->removeSubject($assignmentId);
    if ($result['success']) {
        $success_message = 'Subject removed successfully!';
        $currentSubjects = $teacher->getTeacherSubjects($teacherId); // Refresh
    } else {
        $error_message = 'Failed to remove subject: ' . $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Manage Teacher Subjects</title>
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
                    <a href="view.php?id=<?php echo $teacherId; ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Manage Subject Assignments</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($teacherData['name']); ?> (<?php echo htmlspecialchars($teacherData['employee_id']); ?>)</p>
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
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Assign New Subject -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Assign New Subject</h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="assign_subject" value="1">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                            <select name="subject_id" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Subject</option>
                                <?php foreach ($allSubjects as $subj): ?>
                                <option value="<?php echo $subj['id']; ?>">
                                    <?php echo htmlspecialchars($subj['name'] . ' (' . $subj['code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Class *</label>
                            <select name="class_id" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Assign Subject
                        </button>
                    </form>
                </div>

                <!-- Teacher Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Teacher Summary</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total Subjects Assigned</span>
                            <span class="text-lg font-semibold text-blue-600"><?php echo count($currentSubjects); ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Employee ID</span>
                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($teacherData['employee_id']); ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Specialization</span>
                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['specialization'] ?: 'Not specified'); ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Experience</span>
                            <span class="text-sm text-gray-900">
                                <?php echo $teacherData['experience_years'] ? $teacherData['experience_years'] . ' years' : 'Not specified'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t">
                        <a href="view.php?id=<?php echo $teacherId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-eye mr-2"></i>View Full Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Current Assignments -->
            <div class="mt-6 bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Current Subject Assignments</h2>
                </div>
                
                <?php if (empty($currentSubjects)): ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-book text-4xl mb-4 text-gray-300"></i>
                    <p>No subjects assigned yet</p>
                    <p class="text-sm">Use the form above to assign subjects to this teacher</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Subject</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Subject Code</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Class</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Assigned Date</th>
                                <th class="text-left p-4 text-sm font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentSubjects as $assignment): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4">
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($assignment['subject_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($assignment['subject_description'] ?? ''); ?></div>
                                </td>
                                <td class="p-4 text-gray-600">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                        <?php echo htmlspecialchars($assignment['subject_code']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-800">
                                    <?php echo htmlspecialchars($assignment['class_name'] . ' - ' . $assignment['section']); ?>
                                </td>
                                <td class="p-4 text-gray-600 text-sm">
                                    <?php echo isset($assignment['assigned_date']) ? date('M d, Y', strtotime($assignment['assigned_date'])) : 'N/A'; ?>
                                </td>
                                <td class="p-4">
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this subject assignment?')">
                                        <input type="hidden" name="remove_subject" value="1">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-trash mr-1"></i>Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Help Section -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Subject Assignment Guidelines
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Teachers can be assigned multiple subjects across different classes</li>
                    <li>• Each subject-class combination can only be assigned to one teacher</li>
                    <li>• Removing a subject assignment will affect related attendance and gradebook entries</li>
                    <li>• Teachers can only view and manage classes for their assigned subjects</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>