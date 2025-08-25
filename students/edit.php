<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Student.php';
require_once '../classes/Fee.php';

requireRole('admin');

$student = new Student();
$fee = new Fee();
$studentId = intval($_GET['id'] ?? 0);

if (!$studentId) {
    header('Location: list.php?error=Invalid student ID');
    exit();
}

$studentData = $student->getById($studentId);
if (!$studentData) {
    header('Location: list.php?error=Student not found');
    exit();
}

$classes = $fee->getClasses();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address'])
    ];
    
    $studentDataUpdate = [
        'admission_number' => trim($_POST['admission_number']),
        'class_id' => intval($_POST['class_id']),
        'roll_number' => trim($_POST['roll_number']),
        'date_of_birth' => $_POST['date_of_birth'],
        'gender' => $_POST['gender'],
        'blood_group' => $_POST['blood_group'],
        'father_name' => trim($_POST['father_name']),
        'mother_name' => trim($_POST['mother_name']),
        'guardian_phone' => trim($_POST['guardian_phone']),
        'emergency_contact' => trim($_POST['emergency_contact']),
        'medical_conditions' => trim($_POST['medical_conditions']),
        'transport_required' => isset($_POST['transport_required']) ? 1 : 0
    ];
    
    // Basic validation
    $required_fields = ['name', 'admission_number', 'class_id', 'roll_number'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        $value = $field === 'class_id' ? $studentDataUpdate[$field] : 
                (isset($userData[$field]) ? $userData[$field] : $studentDataUpdate[$field]);
        if (empty($value)) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } elseif (!empty($userData['email']) && !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        $result = $student->update($studentId, $userData, $studentDataUpdate);
        if ($result['success']) {
            $success_message = 'Student profile updated successfully!';
            // Refresh data
            $studentData = $student->getById($studentId);
        } else {
            $error_message = 'Failed to update student: ' . $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Edit Student</title>
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
                    <a href="view.php?id=<?php echo $studentId; ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Edit Student</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($studentData['name']); ?></p>
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
                    <a href="view.php?id=<?php echo $studentId; ?>" class="text-green-800 underline">View Profile</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <form method="POST" class="space-y-8">
                        <!-- Basic Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input type="text" name="name" required 
                                           value="<?php echo htmlspecialchars($studentData['name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Admission Number *</label>
                                    <input type="text" name="admission_number" required 
                                           value="<?php echo htmlspecialchars($studentData['admission_number']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Class *</label>
                                    <select name="class_id" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo $studentData['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Roll Number *</label>
                                    <input type="text" name="roll_number" required 
                                           value="<?php echo htmlspecialchars($studentData['roll_number']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" name="date_of_birth" 
                                           value="<?php echo $studentData['date_of_birth']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                    <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo $studentData['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $studentData['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo $studentData['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Blood Group</label>
                                    <select name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Blood Group</option>
                                        <?php 
                                        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                        foreach ($bloodGroups as $bg): ?>
                                        <option value="<?php echo $bg; ?>" <?php echo $studentData['blood_group'] === $bg ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                    <input type="email" name="email" 
                                           value="<?php echo htmlspecialchars($studentData['email']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" name="phone" 
                                           value="<?php echo htmlspecialchars($studentData['phone']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="address" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($studentData['address']); ?></textarea>
                            </div>
                        </div>

                        <!-- Guardian Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Guardian Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Father's Name</label>
                                    <input type="text" name="father_name" 
                                           value="<?php echo htmlspecialchars($studentData['father_name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mother's Name</label>
                                    <input type="text" name="mother_name" 
                                           value="<?php echo htmlspecialchars($studentData['mother_name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Guardian Phone</label>
                                    <input type="tel" name="guardian_phone" 
                                           value="<?php echo htmlspecialchars($studentData['guardian_phone']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact</label>
                                    <input type="tel" name="emergency_contact" 
                                           value="<?php echo htmlspecialchars($studentData['emergency_contact']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Medical Conditions</label>
                                    <textarea name="medical_conditions" rows="2" 
                                              placeholder="Any medical conditions, allergies, or special needs..."
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($studentData['medical_conditions']); ?></textarea>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="transport_required" value="1" 
                                           <?php echo $studentData['transport_required'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label class="ml-2 text-sm text-gray-700">Transport Required</label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <a href="view.php?id=<?php echo $studentId; ?>" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Update Student
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
                                <h3 class="text-sm font-medium text-gray-900">Deactivate Student Account</h3>
                                <p class="text-sm text-gray-500">This will prevent the student from logging in but preserve all data.</p>
                            </div>
                            <button onclick="toggleStatus(<?php echo $studentId; ?>, '<?php echo $studentData['status']; ?>')" 
                                    class="px-4 py-2 <?php echo $studentData['status'] === 'active' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> text-white rounded-md transition-colors">
                                <?php echo $studentData['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function toggleStatus(studentId, currentStatus) {
        const action = currentStatus === 'active' ? 'deactivate' : 'activate';
        const confirmMessage = `Are you sure you want to ${action} this student account?`;
        
        if (confirm(confirmMessage)) {
            fetch('../api/toggle-user-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    user_type: 'student',
                    user_id: studentId,
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