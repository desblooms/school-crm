<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only admin can access settings
if ($_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied. Admin access required.');
}

$success_message = '';
$error_message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'config/database.php';
        $db = Database::getInstance()->getConnection();
        
        $app_name = trim($_POST['app_name'] ?? '');
        $timezone = trim($_POST['timezone'] ?? '');
        $max_file_size = intval($_POST['max_file_size'] ?? 5);
        
        if (!empty($app_name) && !empty($timezone)) {
            // Update or insert settings
            $settings = [
                'app_name' => $app_name,
                'timezone' => $timezone,
                'max_file_size' => $max_file_size
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success_message = 'Settings updated successfully!';
        } else {
            $error_message = 'Please fill in all required fields.';
        }
    } catch (Exception $e) {
        $error_message = 'Error updating settings: ' . $e->getMessage();
    }
}

// Load current settings
$current_settings = [
    'app_name' => APP_NAME,
    'timezone' => date_default_timezone_get(),
    'max_file_size' => 5
];

try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Settings table might not exist, use defaults
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">System Settings</h1>
                <p class="text-gray-600">Configure system-wide settings</p>
            </div>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">General Settings</h2>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Application Name
                                </label>
                                <input type="text" id="app_name" name="app_name" 
                                       value="<?php echo htmlspecialchars($current_settings['app_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       required>
                            </div>
                            
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Timezone
                                </label>
                                <select id="timezone" name="timezone" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    <option value="Asia/Kolkata" <?php echo $current_settings['timezone'] === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata</option>
                                    <option value="UTC" <?php echo $current_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    <option value="America/New_York" <?php echo $current_settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                    <option value="Europe/London" <?php echo $current_settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="max_file_size" class="block text-sm font-medium text-gray-700 mb-2">
                                    Max File Size (MB)
                                </label>
                                <input type="number" id="max_file_size" name="max_file_size" 
                                       value="<?php echo htmlspecialchars($current_settings['max_file_size']); ?>"
                                       min="1" max="50"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       required>
                            </div>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                                <i class="fas fa-save mr-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Information -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">System Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-server text-blue-500 text-xl mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">PHP Version</p>
                                    <p class="font-semibold"><?php echo PHP_VERSION; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-database text-green-500 text-xl mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">Database</p>
                                    <p class="font-semibold">MySQL/MariaDB</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-code text-purple-500 text-xl mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">Version</p>
                                    <p class="font-semibold"><?php echo APP_VERSION; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-orange-500 text-xl mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">Server Time</p>
                                    <p class="font-semibold"><?php echo date('Y-m-d H:i:s'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-memory text-red-500 text-xl mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">Memory Limit</p>
                                    <p class="font-semibold"><?php echo ini_get('memory_limit'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-upload text-indigo-500 text-xl mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">Upload Limit</p>
                                    <p class="font-semibold"><?php echo ini_get('upload_max_filesize'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>