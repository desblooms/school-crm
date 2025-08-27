<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'config/database.php';
        $db = Database::getInstance()->getConnection();
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($name) || empty($email)) {
            $error_message = 'Name and email are required.';
        } else {
            // Check if email is already taken by another user
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                $error_message = 'This email is already taken by another user.';
            } else {
                // Update basic profile info
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $email, $_SESSION['user_id']]);
                
                // Update session
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                $success_message = 'Profile updated successfully!';
                
                // Handle password change if provided
                if (!empty($current_password) && !empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error_message = 'New passwords do not match.';
                    } else if (strlen($new_password) < 6) {
                        $error_message = 'New password must be at least 6 characters long.';
                    } else {
                        // Verify current password
                        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user = $stmt->fetch();
                        
                        if (password_verify($current_password, $user['password'])) {
                            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                            
                            $success_message = 'Profile and password updated successfully!';
                        } else {
                            $error_message = 'Current password is incorrect.';
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error_message = 'Error updating profile: ' . $e->getMessage();
    }
}

// Load current user data
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT name, email, role, created_at, updated_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: logout.php');
        exit();
    }
} catch (Exception $e) {
    $error_message = 'Error loading profile data.';
    $user = [
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
        'created_at' => '',
        'updated_at' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">My Profile</h1>
                <p class="text-gray-600">Manage your account settings and personal information</p>
            </div>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h2>
                            
                            <form method="POST" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Full Name *
                                        </label>
                                        <input type="text" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               required>
                                    </div>
                                    
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email Address *
                                        </label>
                                        <input type="email" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               required>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h3 class="text-md font-semibold text-gray-800">Change Password</h3>
                                <p class="text-sm text-gray-600">Leave password fields empty if you don't want to change your password</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Current Password
                                        </label>
                                        <input type="password" id="current_password" name="current_password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            New Password
                                        </label>
                                        <input type="password" id="new_password" name="new_password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               minlength="6">
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm New Password
                                        </label>
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               minlength="6">
                                    </div>
                                </div>
                                
                                <div class="pt-4">
                                    <button type="submit" 
                                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                                        <i class="fas fa-save mr-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Account Summary -->
                <div>
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Account Summary</h2>
                            
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-lg font-bold"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></p>
                                        <p class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="border-t pt-4">
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm text-gray-600">Email</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-sm text-gray-600">Role</p>
                                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php 
                                                switch($user['role']) {
                                                    case 'admin': echo 'bg-red-100 text-red-800'; break;
                                                    case 'teacher': echo 'bg-green-100 text-green-800'; break;
                                                    case 'accountant': echo 'bg-blue-100 text-blue-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($user['created_at']): ?>
                                        <div>
                                            <p class="text-sm text-gray-600">Member Since</p>
                                            <p class="font-medium"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['updated_at']): ?>
                                        <div>
                                            <p class="text-sm text-gray-600">Last Updated</p>
                                            <p class="font-medium"><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-md mt-6">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
                            
                            <div class="space-y-2">
                                <a href="index.php" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition duration-200">
                                    <i class="fas fa-tachometer-alt mr-2 text-blue-500"></i>Dashboard
                                </a>
                                
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="settings.php" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition duration-200">
                                    <i class="fas fa-cog mr-2 text-gray-500"></i>System Settings
                                </a>
                                <?php endif; ?>
                                
                                <a href="logout.php" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md transition duration-200">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value && this.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    </script>
</body>
</html>