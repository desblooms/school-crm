<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$dbMonitor = new DatabaseMonitor();

// Get system information
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'session_save_path' => session_save_path(),
    'timezone' => date_default_timezone_get(),
    'current_time' => date('Y-m-d H:i:s'),
    'environment' => ENVIRONMENT,
    'debug_mode' => DEBUG_MODE ? 'Enabled' : 'Disabled',
    'app_version' => APP_VERSION
];

// Database health check
$dbHealth = $dbMonitor->healthCheck();

// Check disk space
$diskSpace = [
    'total' => disk_total_space('.'),
    'free' => disk_free_space('.'),
    'used' => disk_total_space('.') - disk_free_space('.')
];

// Check directory permissions
$directories = [
    'uploads' => UPLOAD_PATH,
    'invoices' => INVOICE_PATH,
    'logs' => LOG_PATH,
    'backups' => BACKUP_PATH
];

$dirPermissions = [];
foreach ($directories as $name => $path) {
    $dirPermissions[$name] = [
        'path' => $path,
        'exists' => is_dir($path),
        'writable' => is_writable($path),
        'readable' => is_readable($path),
        'size' => is_dir($path) ? formatBytes(dirSize($path)) : 'N/A'
    ];
}

// Check log files
$logFiles = [];
if (is_dir(LOG_PATH)) {
    $logs = glob(LOG_PATH . '*.log');
    foreach ($logs as $log) {
        $logFiles[basename($log)] = [
            'size' => formatBytes(filesize($log)),
            'modified' => date('Y-m-d H:i:s', filemtime($log)),
            'lines' => countLines($log)
        ];
    }
}

// Recent error logs
$recentErrors = [];
$errorLog = LOG_PATH . 'php_errors.log';
if (file_exists($errorLog)) {
    $errors = file($errorLog);
    $recentErrors = array_slice(array_reverse($errors), 0, 10);
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function dirSize($directory) {
    $size = 0;
    if (is_dir($directory)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    }
    return $size;
}

function countLines($file) {
    $lines = 0;
    if (file_exists($file)) {
        $handle = fopen($file, "r");
        while(!feof($handle)){
            $line = fgets($handle);
            $lines++;
        }
        fclose($handle);
    }
    return $lines;
}

function getStatusBadge($status) {
    switch (strtolower($status)) {
        case 'healthy':
        case 'success':
        case 'ok':
            return 'bg-green-100 text-green-800';
        case 'warning':
            return 'bg-yellow-100 text-yellow-800';
        case 'critical':
        case 'error':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - System Health</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function refreshData() {
            location.reload();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshData, 30000);
    </script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">System Health Dashboard</h1>
                        <p class="text-gray-600">Monitor system performance and health metrics</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <button onclick="refreshData()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">System Status</p>
                            <p class="text-2xl font-bold text-green-600">Healthy</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-heartbeat text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Database</p>
                            <p class="text-2xl font-bold <?php echo $dbHealth['overall_status'] === 'healthy' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo ucfirst($dbHealth['overall_status']); ?>
                            </p>
                        </div>
                        <div class="<?php echo $dbHealth['overall_status'] === 'healthy' ? 'bg-green-100' : 'bg-red-100'; ?> p-3 rounded-full">
                            <i class="fas fa-database <?php echo $dbHealth['overall_status'] === 'healthy' ? 'text-green-600' : 'text-red-600'; ?> text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Disk Space</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo formatBytes($diskSpace['free']); ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-hdd text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Free of <?php echo formatBytes($diskSpace['total']); ?></p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Environment</p>
                            <p class="text-2xl font-bold text-purple-600"><?php echo strtoupper(ENVIRONMENT); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-cog text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">System Information</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($systemInfo as $key => $value): ?>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600 capitalize"><?php echo str_replace('_', ' ', $key); ?>:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Database Health</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600">Connection Status:</span>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($dbHealth['checks']['connection']['status']); ?>">
                                    <?php echo ucfirst($dbHealth['checks']['connection']['status']); ?>
                                </span>
                            </div>
                            
                            <?php if (isset($dbHealth['checks']['connection']['version'])): ?>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">MySQL Version:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($dbHealth['checks']['connection']['version']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Table Integrity:</h4>
                                <div class="space-y-2">
                                    <?php foreach ($dbHealth['checks']['table_integrity'] as $table => $status): ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-600"><?php echo htmlspecialchars($table); ?></span>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($status); ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Directory Permissions -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Directory Permissions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Directory</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Path</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exists</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Readable</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Writable</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dirPermissions as $name => $info): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">
                                    <?php echo $name; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($info['path']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $info['exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $info['exists'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $info['readable'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $info['readable'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $info['writable'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $info['writable'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $info['size']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Log Files & Recent Errors -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Log Files -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Log Files</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($logFiles)): ?>
                        <div class="space-y-3">
                            <?php foreach ($logFiles as $filename => $info): ?>
                            <div class="border border-gray-200 rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($filename); ?></span>
                                    <span class="text-xs text-gray-500"><?php echo $info['size']; ?></span>
                                </div>
                                <div class="mt-1 text-xs text-gray-600">
                                    Last modified: <?php echo $info['modified']; ?> | Lines: <?php echo $info['lines']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-500 text-center py-4">No log files found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Errors -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Errors</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($recentErrors)): ?>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <?php foreach ($recentErrors as $error): ?>
                            <div class="text-xs text-red-600 bg-red-50 p-2 rounded border">
                                <?php echo htmlspecialchars(trim($error)); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-green-600 text-center py-4">No recent errors found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>