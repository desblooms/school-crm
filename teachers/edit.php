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

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address'])
    ];
    
    $teacherDataUpdate = [
        'qualification' => trim($_POST['qualification']),
        'experience_years' => intval($_POST['experience_years']),
        'specialization' => trim($_POST['specialization']),
        'salary' => floatval($_POST['salary']),
        'employment_type' => $_POST['employment_type']
    ];
    
    // Basic validation
    $required_fields = ['name', 'email'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($userData[$field])) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        $result = $teacher->update($teacherId, $userData, $teacherDataUpdate);
        if ($result['success']) {
            $success_message = 'Teacher profile updated successfully!';
            // Refresh data
            $teacherData = $teacher->getById($teacherId);
        } else {
            $error_message = 'Failed to update teacher: ' . $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Edit Teacher</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Edit Teacher</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($teacherData['name']); ?></p>
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
                    <a href="view.php?id=<?php echo $teacherId; ?>" class="text-green-800 underline">View Profile</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <form method="POST" class="space-y-8">
                        <!-- Personal Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input type="text" name="name" required 
                                           value="<?php echo htmlspecialchars($teacherData['name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                    <input type="email" name="email" required 
                                           value="<?php echo htmlspecialchars($teacherData['email']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" name="phone" 
                                           value="<?php echo htmlspecialchars($teacherData['phone']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID</label>
                                    <input type="text" value="<?php echo htmlspecialchars($teacherData['employee_id']); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Employee ID cannot be changed</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Joining Date</label>
                                    <input type="text" value="<?php echo $teacherData['joining_date'] ? date('M d, Y', strtotime($teacherData['joining_date'])) : 'Not set'; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Contact admin to change joining date</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                                    <select name="employment_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="full_time" <?php echo $teacherData['employment_type'] === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                        <option value="part_time" <?php echo $teacherData['employment_type'] === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                        <option value="contract" <?php echo $teacherData['employment_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Salary (₹)</label>
                                    <input type="number" name="salary" step="0.01" min="0"
                                           value="<?php echo htmlspecialchars($teacherData['salary']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="address" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($teacherData['address']); ?></textarea>
                            </div>
                        </div>

                        <!-- Qualifications -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Qualifications & Experience</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Qualification</label>
                                    <input type="text" name="qualification" 
                                           placeholder="e.g., M.Sc. Mathematics, B.Ed."
                                           value="<?php echo htmlspecialchars($teacherData['qualification']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Experience (Years)</label>
                                    <input type="number" name="experience_years" min="0" 
                                           value="<?php echo htmlspecialchars($teacherData['experience_years']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                                    <input type="text" name="specialization" 
                                           placeholder="e.g., Mathematics, Science, English Literature"
                                           value="<?php echo htmlspecialchars($teacherData['specialization']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <a href="view.php?id=<?php echo $teacherId; ?>" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Update Teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="mt-6 bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h2>
                    <div class="border border-red-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Deactivate Teacher Account</h3>
                                <p class="text-sm text-gray-500">This will prevent the teacher from logging in but preserve all data.</p>
                            </div>
                            <button onclick="toggleStatus(<?php echo $teacherId; ?>, '<?php echo $teacherData['status']; ?>')" 
                                    class="px-4 py-2 <?php echo $teacherData['status'] === 'active' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> text-white rounded-md transition-colors">
                                <?php echo $teacherData['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function toggleStatus(teacherId, currentStatus) {
        const action = currentStatus === 'active' ? 'deactivate' : 'activate';
        const confirmMessage = `Are you sure you want to ${action} this teacher account?`;
        
        if (confirm(confirmMessage)) {
            fetch('../api/toggle-user-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    user_type: 'teacher',
                    user_id: teacherId,
                    action: action 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update status: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating status');
            });
        }
    }
    </script>
</body>
</html>