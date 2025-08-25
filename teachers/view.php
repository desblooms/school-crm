<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Teacher.php';

requireRole('admin');

$teacher = new Teacher();
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

$teacherSubjects = $teacher->getTeacherSubjects($teacherId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Teacher Profile</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Teacher Profile</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($teacherData['name']); ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Teacher Info Card -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Personal Information</h2>
                            <a href="edit.php?id=<?php echo $teacherId; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </a>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['name']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Employee ID</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['employee_id']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['email']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['phone'] ?: 'Not provided'); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Joining Date</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo $teacherData['joining_date'] ? date('M d, Y', strtotime($teacherData['joining_date'])) : 'Not specified'; ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Employment Type</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                        <?php echo ucwords(str_replace('_', ' ', $teacherData['employment_type'])); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-500">Address</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['address'] ?: 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-6">Professional Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Qualification</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['qualification'] ?: 'Not specified'); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Experience</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <?php echo $teacherData['experience_years'] ? $teacherData['experience_years'] . ' years' : 'Not specified'; ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Specialization</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($teacherData['specialization'] ?: 'Not specified'); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Monthly Salary</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <?php echo $teacherData['salary'] ? '₹' . number_format($teacherData['salary']) : 'Not set'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Subject Assignments -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Subject Assignments</h2>
                            <a href="subjects.php?id=<?php echo $teacherId; ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-book mr-2"></i>Manage Subjects
                            </a>
                        </div>
                        
                        <?php if (empty($teacherSubjects)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-book text-4xl mb-4 text-gray-300"></i>
                            <p>No subjects assigned yet</p>
                            <a href="subjects.php?id=<?php echo $teacherId; ?>" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                Assign subjects →
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Subject</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Class</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Subject Code</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teacherSubjects as $subject): ?>
                                    <tr class="border-t">
                                        <td class="p-3 font-medium text-gray-800"><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                        <td class="p-3 text-gray-800"><?php echo htmlspecialchars($subject['class_name'] . ' - ' . $subject['section']); ?></td>
                                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions & Status -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Status</h2>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Account Status</span>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $teacherData['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($teacherData['status']); ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Subjects Assigned</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo count($teacherSubjects); ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Member Since</span>
                                <span class="text-sm text-gray-900"><?php echo date('M Y', strtotime($teacherData['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
                        
                        <div class="space-y-3">
                            <a href="edit.php?id=<?php echo $teacherId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Edit Profile
                            </a>
                            
                            <a href="subjects.php?id=<?php echo $teacherId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-book mr-2"></i>Manage Subjects
                            </a>
                            
                            <a href="attendance.php?teacher_id=<?php echo $teacherId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-calendar-check mr-2"></i>View Attendance
                            </a>
                            
                            <a href="payroll.php?id=<?php echo $teacherId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-money-bill mr-2"></i>Payroll Details
                            </a>
                            
                            <button onclick="resetPassword(<?php echo $teacherId; ?>)" class="w-full flex items-center justify-center px-4 py-2 border border-yellow-300 rounded-md text-yellow-700 hover:bg-yellow-50 transition-colors">
                                <i class="fas fa-key mr-2"></i>Reset Password
                            </button>
                        </div>
                    </div>

                    <!-- Contact Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Contact</h2>
                        
                        <div class="space-y-3">
                            <?php if ($teacherData['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($teacherData['email']); ?>" class="flex items-center text-blue-600 hover:text-blue-800">
                                <i class="fas fa-envelope mr-3"></i>
                                <span><?php echo htmlspecialchars($teacherData['email']); ?></span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($teacherData['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($teacherData['phone']); ?>" class="flex items-center text-green-600 hover:text-green-800">
                                <i class="fas fa-phone mr-3"></i>
                                <span><?php echo htmlspecialchars($teacherData['phone']); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function resetPassword(teacherId) {
        if (confirm('Are you sure you want to reset this teacher\'s password? They will need to use their employee ID to log in.')) {
            fetch('../api/reset-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    user_type: 'teacher',
                    user_id: teacherId 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password reset successfully! New password is the employee ID.');
                } else {
                    alert('Failed to reset password: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error resetting password');
            });
        }
    }
    </script>
</body>
</html>