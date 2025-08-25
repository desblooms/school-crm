<?php
require_once 'config/config.php';

$success = false;
$error = '';
$checks = [];

// Pre-installation checks
function performChecks() {
    global $checks;
    
    // PHP version check
    $checks['php_version'] = [
        'name' => 'PHP Version (' . phpversion() . ')',
        'status' => version_compare(phpversion(), '7.4.0', '>='),
        'message' => version_compare(phpversion(), '7.4.0', '>=') ? 'OK' : 'Requires PHP 7.4 or higher'
    ];
    
    // Required extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'mbstring'];
    foreach ($required_extensions as $ext) {
        $checks['ext_' . $ext] = [
            'name' => ucfirst($ext) . ' Extension',
            'status' => extension_loaded($ext),
            'message' => extension_loaded($ext) ? 'Loaded' : 'Not loaded'
        ];
    }
    
    // Database files check
    $checks['tables_sql'] = [
        'name' => 'Database Tables File',
        'status' => file_exists('database/tables.sql'),
        'message' => file_exists('database/tables.sql') ? 'Found' : 'Missing database/tables.sql'
    ];
    
    $checks['data_sql'] = [
        'name' => 'Database Data File', 
        'status' => file_exists('database/data.sql'),
        'message' => file_exists('database/data.sql') ? 'Found' : 'Missing database/data.sql'
    ];
    
    // Directory permissions
    $dirs_to_check = ['uploads', 'invoices', 'logs', 'backups'];
    foreach ($dirs_to_check as $dir) {
        $writable = false;
        if (!file_exists($dir)) {
            $writable = mkdir($dir, 0755, true);
        } else {
            $writable = is_writable($dir);
        }
        
        $checks['dir_' . $dir] = [
            'name' => ucfirst($dir) . ' Directory',
            'status' => $writable,
            'message' => $writable ? 'Writable' : 'Not writable (chmod 755 needed)'
        ];
    }
    
    return array_filter($checks, function($check) { return !$check['status']; });
}

$failed_checks = performChecks();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Test database connection first
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        
        // Check if database files exist
        if (!file_exists('database/tables.sql')) {
            throw new Exception('Database schema file (database/tables.sql) not found');
        }
        
        if (!file_exists('database/data.sql')) {
            throw new Exception('Database data file (database/data.sql) not found');
        }
        
        // Execute tables creation first
        $tables_sql = file_get_contents('database/tables.sql');
        if (empty($tables_sql)) {
            throw new Exception('Database tables.sql file is empty');
        }
        
        // Split and execute SQL statements
        $statements = explode(';', $tables_sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Execute data insertion
        $data_sql = file_get_contents('database/data.sql');
        if (!empty($data_sql)) {
            $statements = explode(';', $data_sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
        }
        
        // Hash the default password and update
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@school.com'");
        $result = $stmt->execute([$hashedPassword]);
        
        if (!$result) {
            throw new Exception('Failed to update admin password');
        }
        
        // Create required directories
        $dirs = ['uploads', 'invoices', 'logs', 'backups'];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Failed to create directory: $dir");
                }
            }
        }
        
        $success = true;
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-graduation-cap text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800"><?php echo APP_NAME; ?></h1>
            <p class="text-gray-600 mt-2">Installation Setup</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <div>
                        <h4 class="font-semibold">Installation Successful!</h4>
                        <p class="text-sm">Database has been created and configured.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-4">
                <h4 class="font-semibold mb-2">Default Login Credentials:</h4>
                <p class="text-sm"><strong>Email:</strong> admin@school.com</p>
                <p class="text-sm"><strong>Password:</strong> admin123</p>
            </div>
            
            <a href="login.php" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 text-center block">
                <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
            </a>
            
        <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <div>
                        <h4 class="font-semibold">Installation Failed</h4>
                        <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
            
            <button onclick="location.reload()" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200">
                <i class="fas fa-redo mr-2"></i>Try Again
            </button>
            
        <?php else: ?>
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">System Requirements Check:</h3>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($checks as $key => $check): ?>
                    <li class="flex items-center">
                        <?php if ($check['status']): ?>
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            <span class="text-gray-700"><?php echo htmlspecialchars($check['name']); ?></span>
                            <span class="ml-auto text-green-600 text-xs"><?php echo htmlspecialchars($check['message']); ?></span>
                        <?php else: ?>
                            <i class="fas fa-times text-red-500 mr-2"></i>
                            <span class="text-gray-700"><?php echo htmlspecialchars($check['name']); ?></span>
                            <span class="ml-auto text-red-600 text-xs"><?php echo htmlspecialchars($check['message']); ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if (!empty($failed_checks)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <div>
                        <h4 class="font-semibold">Installation Requirements Not Met</h4>
                        <p class="text-sm">Please fix the issues above before proceeding with installation.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <div>
                        <h4 class="font-semibold">Before Installation:</h4>
                        <p class="text-sm">Make sure your database credentials are correct in config/config.php</p>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <?php if (empty($failed_checks)): ?>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        <i class="fas fa-download mr-2"></i>Install School CRM
                    </button>
                <?php else: ?>
                    <button type="button" disabled class="w-full bg-gray-400 text-white py-2 px-4 rounded-md cursor-not-allowed">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Fix Requirements First
                    </button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center text-xs text-gray-500">
            <?php echo APP_NAME . ' v' . APP_VERSION; ?>
        </div>
    </div>
</body>
</html>