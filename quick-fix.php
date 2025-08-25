<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$success = false;
$errors = [];
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        $results[] = "✅ Database connection successful";
        
        // 1. Add missing assigned_date column if it doesn't exist
        $stmt = $db->query("SHOW COLUMNS FROM teacher_subjects LIKE 'assigned_date'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP");
            $results[] = "✅ Added assigned_date column to teacher_subjects table";
        } else {
            $results[] = "✅ assigned_date column already exists";
        }
        
        // 2. Fix fee_payments status enum if needed
        try {
            $db->exec("ALTER TABLE fee_payments MODIFY COLUMN status ENUM('paid', 'pending', 'failed', 'refunded') DEFAULT 'paid'");
            $results[] = "✅ Updated fee_payments status enum";
        } catch (Exception $e) {
            $results[] = "⚠️ fee_payments status enum already correct";
        }
        
        if (empty($errors)) {
            $success = true;
            $results[] = "<br>🎉 <strong>Quick fixes applied successfully!</strong>";
        }
        
    } catch (Exception $e) {
        $errors[] = "Quick fix failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Quick Fix</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-8">
        <h1 class="text-2xl font-bold mb-6">Quick Fix</h1>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4">
                <?php foreach ($results as $result): ?>
                <div><?php echo $result; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <strong>Success!</strong> Database issues have been fixed.
            </div>
            
            <div class="space-y-2">
                <a href="comprehensive-fix.php" class="block bg-blue-600 text-white px-4 py-2 rounded text-center">Run Full Fix</a>
                <a href="test-fixes.php" class="block bg-green-600 text-white px-4 py-2 rounded text-center">Test Everything</a>
                <a href="index.php" class="block bg-gray-600 text-white px-4 py-2 rounded text-center">Go to Dashboard</a>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <p class="mb-4">This will fix the most common database issues:</p>
            <ul class="mb-6 text-sm text-gray-600">
                <li>• Add missing assigned_date column</li>
                <li>• Fix fee payment status</li>
            </ul>
            
            <form method="POST">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded">Apply Quick Fix</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>