<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Student.php';

requirePermission('view_students');

$student = new Student();
$classes = $student->getClasses();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address'])
    ];
    
    $studentData = [
        'admission_number' => trim($_POST['admission_number']),
        'class_id' => intval($_POST['class_id']),
        'roll_number' => trim($_POST['roll_number']),
        'date_of_birth' => $_POST['date_of_birth'],
        'gender' => $_POST['gender'],
        'blood_group' => trim($_POST['blood_group']),
        'guardian_name' => trim($_POST['guardian_name']),
        'guardian_phone' => trim($_POST['guardian_phone']),
        'guardian_email' => trim($_POST['guardian_email']),
        'emergency_contact' => trim($_POST['emergency_contact']),
        'medical_conditions' => trim($_POST['medical_conditions']),
        'admission_date' => $_POST['admission_date'],
        'transport_required' => isset($_POST['transport_required']),
        'hostel_required' => isset($_POST['hostel_required'])
    ];
    
    // Basic validation
    $required_fields = ['name', 'email', 'admission_number', 'guardian_name', 'guardian_phone', 'admission_date'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($userData[$field] ?? $studentData[$field])) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        $result = $student->create($userData, $studentData);
        if ($result['success']) {
            $success_message = 'Student admission completed successfully! Admission Number: ' . $studentData['admission_number'];
            // Clear form data
            $_POST = [];
        } else {
            $error_message = $result['message'];
        }
    }
}

// Generate admission number for new form
$admissionNumber = $student->generateAdmissionNumber();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Student Admission</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Student Admission</h1>
                        <p class="text-gray-600">Register new student admission</p>
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
                    <a href="list.php" class="text-green-800 underline">View Students List</a> |
                    <a href="admission.php" class="text-green-800 underline">Add Another Student</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <form method="POST" class="space-y-8">
                        <!-- Student Basic Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Student Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input type="text" name="name" required 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                    <input type="email" name="email" required 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Admission Number *</label>
                                    <input type="text" name="admission_number" required 
                                           value="<?php echo htmlspecialchars($_POST['admission_number'] ?? $admissionNumber); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" name="date_of_birth" 
                                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                    <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Blood Group</label>
                                    <select name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Blood Group</option>
                                        <option value="A+" <?php echo ($_POST['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo ($_POST['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo ($_POST['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo ($_POST['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo ($_POST['blood_group'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo ($_POST['blood_group'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo ($_POST['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo ($_POST['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Admission Date *</label>
                                    <input type="date" name="admission_date" required 
                                           value="<?php echo htmlspecialchars($_POST['admission_date'] ?? date('Y-m-d')); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="address" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Academic Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                                    <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo ($_POST['class_id'] ?? '') == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Roll Number</label>
                                    <input type="text" name="roll_number" 
                                           value="<?php echo htmlspecialchars($_POST['roll_number'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Guardian Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Guardian Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Guardian Name *</label>
                                    <input type="text" name="guardian_name" required 
                                           value="<?php echo htmlspecialchars($_POST['guardian_name'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Guardian Phone *</label>
                                    <input type="tel" name="guardian_phone" required 
                                           value="<?php echo htmlspecialchars($_POST['guardian_phone'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Guardian Email</label>
                                    <input type="email" name="guardian_email" 
                                           value="<?php echo htmlspecialchars($_POST['guardian_email'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact</label>
                                    <input type="tel" name="emergency_contact" 
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h2>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Medical Conditions</label>
                                <textarea name="medical_conditions" rows="3" 
                                          placeholder="Any medical conditions or allergies..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($_POST['medical_conditions'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center">
                                    <input type="checkbox" name="transport_required" 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           <?php echo isset($_POST['transport_required']) ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-gray-700">Transport Required</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="hostel_required" 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           <?php echo isset($_POST['hostel_required']) ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-gray-700">Hostel Required</span>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <a href="list.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Complete Admission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>