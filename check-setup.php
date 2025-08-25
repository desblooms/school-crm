<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>School CRM Setup Check</h1>";

// Check if config files exist
echo "<h2>1. File Structure Check:</h2>";
$requiredFiles = [
    'config/config.php',
    'config/database.php',
    'classes/Subject.php',
    'classes/Teacher.php',
    'classes/Student.php',
    'classes/Fee.php'
];

foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Try to load config
echo "<h2>2. Configuration Check:</h2>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✅ config.php loaded successfully<br>";
    
    if (defined('DB_HOST')) {
        echo "✅ DB_HOST defined: " . DB_HOST . "<br>";
    } else {
        echo "❌ DB_HOST not defined<br>";
    }
    
    if (defined('DB_NAME')) {
        echo "✅ DB_NAME defined: " . DB_NAME . "<br>";
    } else {
        echo "❌ DB_NAME not defined<br>";
    }
    
    if (defined('APP_NAME')) {
        echo "✅ APP_NAME defined: " . APP_NAME . "<br>";
    } else {
        echo "❌ APP_NAME not defined<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Try to load database
echo "<h2>3. Database Check:</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "✅ database.php loaded successfully<br>";
    
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if required tables exist
    $tables = ['users', 'teachers', 'students', 'subjects', 'classes', 'fee_types', 'teacher_subjects', 'student_attendance', 'fee_payments'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Try to load classes
echo "<h2>4. Classes Check:</h2>";
try {
    require_once __DIR__ . '/classes/Subject.php';
    $subject = new Subject();
    echo "✅ Subject class loaded successfully<br>";
    
    require_once __DIR__ . '/classes/Teacher.php';
    $teacher = new Teacher();
    echo "✅ Teacher class loaded successfully<br>";
    
    require_once __DIR__ . '/classes/Student.php';
    $student = new Student();
    echo "✅ Student class loaded successfully<br>";
    
    require_once __DIR__ . '/classes/Fee.php';
    $fee = new Fee();
    echo "✅ Fee class loaded successfully<br>";
    
} catch (Exception $e) {
    echo "❌ Class loading error: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Current Working Directory:</h2>";
echo "Current dir: " . __DIR__ . "<br>";
echo "Script path: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

echo "<h2>6. Next Steps:</h2>";
echo "<p>If all checks pass, you can proceed with:</p>";
echo "<ul>";
echo "<li><a href='comprehensive-fix.php'>Run Comprehensive Fix</a></li>";
echo "<li><a href='test-fixes.php'>Test All Fixes</a></li>";
echo "<li><a href='index.php'>Go to Dashboard</a></li>";
echo "</ul>";
?>