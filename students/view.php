<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Student.php';
require_once '../classes/Fee.php';

requirePermission('view_students');

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

$feeStatus = $fee->getStudentFeeStatus($studentId);
$recentPayments = $fee->getPaymentHistory($studentId, 5, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Student Profile</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Student Profile</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($studentData['name']); ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Student Info Card -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Personal Information</h2>
                            <a href="edit.php?id=<?php echo $studentId; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </a>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['name']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Admission Number</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['admission_number']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['email']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['phone'] ?: 'Not provided'); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Date of Birth</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo $studentData['date_of_birth'] ? date('M d, Y', strtotime($studentData['date_of_birth'])) : 'Not specified'; ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Gender</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo $studentData['gender'] ? ucfirst($studentData['gender']) : 'Not specified'; ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Blood Group</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['blood_group'] ?: 'Not specified'); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Admission Date</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo $studentData['admission_date'] ? date('M d, Y', strtotime($studentData['admission_date'])) : 'Not specified'; ?></p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-500">Address</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['address'] ?: 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-6">Academic Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Class</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <?php echo $studentData['class_name'] ? htmlspecialchars($studentData['class_name'] . ' - ' . $studentData['section']) : 'Not assigned'; ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Roll Number</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['roll_number'] ?: 'Not assigned'); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Transport Required</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $studentData['transport_required'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $studentData['transport_required'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Hostel Required</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $studentData['hostel_required'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $studentData['hostel_required'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($studentData['medical_conditions']): ?>
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-500">Medical Conditions</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['medical_conditions']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Guardian Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-6">Guardian Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Guardian Name</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['guardian_name']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Guardian Phone</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['guardian_phone']); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Guardian Email</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['guardian_email'] ?: 'Not provided'); ?></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Emergency Contact</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($studentData['emergency_contact'] ?: 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Status -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Fee Status</h2>
                            <a href="../fees/collection.php?student_id=<?php echo $studentId; ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-money-bill mr-2"></i>Collect Fee
                            </a>
                        </div>
                        
                        <?php if (empty($feeStatus)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-money-bill text-4xl mb-4 text-gray-300"></i>
                            <p>No fee structure configured for this student's class</p>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Fee Type</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Amount</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Paid</th>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($feeStatus as $fee): ?>
                                    <tr class="border-t">
                                        <td class="p-3 font-medium text-gray-800"><?php echo htmlspecialchars($fee['fee_type_name']); ?></td>
                                        <td class="p-3 text-gray-800">₹<?php echo number_format($fee['fee_amount'], 2); ?></td>
                                        <td class="p-3 text-gray-800">₹<?php echo number_format($fee['paid_amount'], 2); ?></td>
                                        <td class="p-3">
                                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                                echo $fee['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 
                                                    ($fee['status'] === 'Partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                            ?>">
                                                <?php echo $fee['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Payments -->
                    <?php if (!empty($recentPayments)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-6">Recent Payments</h2>
                        
                        <div class="space-y-4">
                            <?php foreach ($recentPayments as $payment): ?>
                            <div class="flex items-center justify-between border-b pb-4">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($payment['fee_type_name']); ?></p>
                                    <p class="text-sm text-gray-600">Receipt: <?php echo htmlspecialchars($payment['receipt_number']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-green-600">₹<?php echo number_format($payment['amount'], 2); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions & Status -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Status</h2>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Account Status</span>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $studentData['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($studentData['status']); ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Total Payments</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo count($recentPayments); ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Member Since</span>
                                <span class="text-sm text-gray-900"><?php echo date('M Y', strtotime($studentData['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
                        
                        <div class="space-y-3">
                            <a href="edit.php?id=<?php echo $studentId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Edit Profile
                            </a>
                            
                            <a href="../fees/collection.php?student_id=<?php echo $studentId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-money-bill mr-2"></i>Collect Fee
                            </a>
                            
                            <a href="../invoices/create.php?student_id=<?php echo $studentId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-file-invoice mr-2"></i>Generate Invoice
                            </a>
                            
                            <a href="attendance.php?student_id=<?php echo $studentId; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-calendar-check mr-2"></i>View Attendance
                            </a>
                            
                            <button onclick="resetPassword(<?php echo $studentId; ?>)" class="w-full flex items-center justify-center px-4 py-2 border border-yellow-300 rounded-md text-yellow-700 hover:bg-yellow-50 transition-colors">
                                <i class="fas fa-key mr-2"></i>Reset Password
                            </button>
                        </div>
                    </div>

                    <!-- Contact Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Contact</h2>
                        
                        <div class="space-y-3">
                            <?php if ($studentData['guardian_email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($studentData['guardian_email']); ?>" class="flex items-center text-blue-600 hover:text-blue-800">
                                <i class="fas fa-envelope mr-3"></i>
                                <span><?php echo htmlspecialchars($studentData['guardian_email']); ?></span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($studentData['guardian_phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($studentData['guardian_phone']); ?>" class="flex items-center text-green-600 hover:text-green-800">
                                <i class="fas fa-phone mr-3"></i>
                                <span><?php echo htmlspecialchars($studentData['guardian_phone']); ?></span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($studentData['emergency_contact']): ?>
                            <a href="tel:<?php echo htmlspecialchars($studentData['emergency_contact']); ?>" class="flex items-center text-red-600 hover:text-red-800">
                                <i class="fas fa-exclamation-triangle mr-3"></i>
                                <span><?php echo htmlspecialchars($studentData['emergency_contact']); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function resetPassword(studentId) {
        if (confirm('Are you sure you want to reset this student\'s password? They will need to use their admission number to log in.')) {
            fetch('../api/reset-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    user_type: 'student',
                    user_id: studentId 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password reset successfully! New password is the admission number.');
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