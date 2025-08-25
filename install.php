<?php
require_once 'config/config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create database connection directly to the existing database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Execute tables creation first
        $tables_sql = file_get_contents('database/tables.sql');
        $pdo->exec($tables_sql);
        
        // Execute data insertion
        $data_sql = file_get_contents('database/data.sql');
        $pdo->exec($data_sql);
        
        // Hash the default password
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Update admin password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@school.com'");
        $stmt->execute([$hashedPassword]);
        
        $success = true;
        
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
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Installation Requirements:</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        PHP 8.0 or higher
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        MySQL 5.7 or higher
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        PDO MySQL extension
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Write permissions on uploads/ and invoices/ directories
                    </li>
                </ul>
            </div>
            
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
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    <i class="fas fa-download mr-2"></i>Install School CRM
                </button>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center text-xs text-gray-500">
            <?php echo APP_NAME . ' v' . APP_VERSION; ?>
        </div>
    </div>
</body>
</html>