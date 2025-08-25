<?php
/**
 * Production Cleanup Script
 * Removes debug/test files and prepares for deployment
 */

echo "=== School CRM Production Cleanup ===\n\n";

$files_to_remove = [
    'debug.php',
    'debug-subjects.php', 
    'debug-subject-assignment.php',
    'debug-teacher-columns.php',
    'debug-fee-collection.php',
    'debug-specific-url.php',
    'test.php',
    'test-fixes.php',
    'test-all-fixes.php', 
    'test-fee-collection-fix.php',
    'fix-subjects.php',
    'fix-subject-assignment.php',
    'fix-now.php',
    'repair-db.php',
    'check-setup.php',
    'diagnose-error.php',
    'quick-fix.php',
    'simple-debug.php',
    'comprehensive-fix.php',
    'apply-migrations.php',
    'db-check.php',
    'seed-sample-data.php',
    'fee-collection-guide.php',
    'clear-cache.html',
    'check-output.php',
    'hostinger-deploy.php', // Remove after deployment check
    'cleanup-for-production.php' // This file itself
];

$removed_count = 0;

foreach ($files_to_remove as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "✅ Removed: $file\n";
            $removed_count++;
        } else {
            echo "❌ Failed to remove: $file\n";
        }
    } else {
        echo "ℹ️  Not found: $file\n";
    }
}

echo "\n=== Cleanup Summary ===\n";
echo "Files removed: $removed_count\n";

// Check if config is set to production
if (file_exists('config/config.php')) {
    $config = file_get_contents('config/config.php');
    if (strpos($config, "'production'") !== false) {
        echo "✅ Environment set to production\n";
    } else {
        echo "⚠️  WARNING: Environment should be set to 'production'\n";
    }
}

// Check if .htaccess exists
if (file_exists('.htaccess')) {
    echo "✅ .htaccess file exists\n";
} else {
    echo "⚠️  WARNING: .htaccess file missing\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Update database credentials in config/config.php\n";
echo "2. Upload files to Hostinger public_html\n";
echo "3. Run hostinger-deploy.php to check deployment\n";
echo "4. Delete hostinger-deploy.php after successful check\n";
echo "5. Run install.php if needed\n";

echo "\n✅ Cleanup complete! Ready for production deployment.\n";
?>