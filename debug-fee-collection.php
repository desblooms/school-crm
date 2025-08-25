<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Fee.php';
require_once __DIR__ . '/classes/Student.php';

echo "<h1>Fee Collection Debug</h1>";

try {
    $fee = new Fee();
    $student = new Student();
    
    echo "<h2>Step 1: Check Classes</h2>";
    $classes = $fee->getClasses();
    echo "Found " . count($classes) . " classes:<br>";
    foreach ($classes as $class) {
        echo "• ID: {$class['id']}, Name: {$class['name']}-{$class['section']}<br>";
    }
    
    if (!empty($classes)) {
        $firstClassId = $classes[0]['id'];
        echo "<h2>Step 2: Check Students in Class ID $firstClassId</h2>";
        
        $students = $fee->getStudentsByClass($firstClassId);
        echo "Found " . count($students) . " students:<br>";
        foreach ($students as $s) {
            echo "• ID: {$s['id']}, Name: {$s['name']}, Admission: {$s['admission_number']}<br>";
        }
        
        if (!empty($students)) {
            $firstStudentId = $students[0]['id'];
            echo "<h2>Step 3: Check Fee Status for Student ID $firstStudentId</h2>";
            
            $feeStatus = $fee->getStudentFeeStatus($firstStudentId);
            echo "Found " . count($feeStatus) . " fee status records:<br>";
            foreach ($feeStatus as $status) {
                echo "• Fee Type: {$status['fee_type_name']}, Amount: {$status['fee_amount']}, Status: {$status['status']}<br>";
            }
        }
    }
    
    echo "<h2>Step 4: Check Fee Types</h2>";
    $feeTypes = $fee->getFeeTypes();
    echo "Found " . count($feeTypes) . " fee types:<br>";
    foreach ($feeTypes as $type) {
        echo "• ID: {$type['id']}, Name: {$type['name']}<br>";
    }
    
    echo "<h2>Step 5: Check Tables Directly</h2>";
    $db = Database::getInstance()->getConnection();
    
    $tables = ['classes', 'students', 'users', 'fee_types', 'fee_structure'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "• $table: $count records<br>";
        } catch (Exception $e) {
            echo "• $table: ERROR - " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>Step 6: Test Sample Data</h2>";
    
    // Check if we have sample data
    $stmt = $db->query("SELECT s.id, s.admission_number, u.name, c.name as class_name, c.section 
                        FROM students s 
                        JOIN users u ON s.user_id = u.id 
                        LEFT JOIN classes c ON s.class_id = c.id 
                        LIMIT 5");
    $sampleStudents = $stmt->fetchAll();
    
    if (empty($sampleStudents)) {
        echo "❌ No students found in database. You need to add some sample data.<br>";
        echo "<strong>To fix this:</strong><br>";
        echo "1. Go to students/admission.php to add students<br>";
        echo "2. Or create some sample classes first<br>";
        echo "3. Ensure fee structure is set up<br>";
    } else {
        echo "✅ Found sample students:<br>";
        foreach ($sampleStudents as $s) {
            echo "• {$s['name']} ({$s['admission_number']}) - Class: {$s['class_name']}-{$s['section']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

echo "<br><strong>Test URL:</strong> <a href='fees/collection.php'>fees/collection.php</a>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h1, h2 { color: #333; }
</style>