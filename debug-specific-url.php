<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Fee.php';
require_once __DIR__ . '/classes/Student.php';

echo "<h1>Debug Specific URL: fees/collection.php?class_id=1&student_id=3</h1>";

try {
    $fee = new Fee();
    $student = new Student();
    
    $selectedClass = 1;
    $selectedStudent = 3;
    
    echo "<h2>Step 1: Check if class exists</h2>";
    $classes = $fee->getClasses();
    $classFound = false;
    foreach ($classes as $class) {
        if ($class['id'] == $selectedClass) {
            echo "✅ Class found: {$class['name']}-{$class['section']}<br>";
            $classFound = true;
            break;
        }
    }
    if (!$classFound) {
        echo "❌ Class with ID $selectedClass not found<br>";
        echo "Available classes:<br>";
        foreach ($classes as $class) {
            echo "• ID: {$class['id']}, Name: {$class['name']}-{$class['section']}<br>";
        }
    }
    
    echo "<h2>Step 2: Check if student exists</h2>";
    $studentData = $student->getById($selectedStudent);
    if ($studentData) {
        echo "✅ Student found: {$studentData['name']} ({$studentData['admission_number']})<br>";
        echo "• Class ID: {$studentData['class_id']}<br>";
        echo "• User ID: {$studentData['user_id']}<br>";
    } else {
        echo "❌ Student with ID $selectedStudent not found<br>";
        
        // Show available students
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT s.id, u.name, s.admission_number FROM students s JOIN users u ON s.user_id = u.id LIMIT 5");
        $availableStudents = $stmt->fetchAll();
        
        echo "Available students:<br>";
        foreach ($availableStudents as $s) {
            echo "• ID: {$s['id']}, Name: {$s['name']}, Admission: {$s['admission_number']}<br>";
        }
    }
    
    echo "<h2>Step 3: Check students in selected class</h2>";
    $studentsInClass = $fee->getStudentsByClass($selectedClass);
    echo "Found " . count($studentsInClass) . " students in class $selectedClass:<br>";
    foreach ($studentsInClass as $s) {
        echo "• ID: {$s['id']}, Name: {$s['name']}, Admission: {$s['admission_number']}<br>";
    }
    
    echo "<h2>Step 4: Check fee status for student</h2>";
    if ($studentData) {
        $feeStatus = $fee->getStudentFeeStatus($selectedStudent);
        echo "Found " . count($feeStatus) . " fee status records:<br>";
        
        if (empty($feeStatus)) {
            echo "❌ No fee status found. This could mean:<br>";
            echo "• No fee structure set up for this student's class<br>";
            echo "• No fee types configured<br>";
            echo "• Student not properly linked to a class<br>";
            
            // Check fee structure
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT COUNT(*) FROM fee_structure WHERE class_id = {$studentData['class_id']}");
            $feeStructureCount = $stmt->fetchColumn();
            echo "• Fee structure records for this class: $feeStructureCount<br>";
            
            $stmt = $db->query("SELECT COUNT(*) FROM fee_types");
            $feeTypeCount = $stmt->fetchColumn();
            echo "• Total fee types in system: $feeTypeCount<br>";
            
        } else {
            foreach ($feeStatus as $status) {
                echo "• {$status['fee_type_name']}: ₹{$status['fee_amount']} (Paid: ₹{$status['paid_amount']}, Pending: ₹{$status['pending_amount']}) - Status: {$status['status']}<br>";
            }
        }
    }
    
    echo "<h2>Step 5: Suggested Fix</h2>";
    if (!$classFound) {
        echo "🔧 Create class with ID 1 or use an existing class ID<br>";
    }
    if (!$studentData) {
        echo "🔧 Create student with ID 3 or use an existing student ID<br>";
    }
    if ($studentData && empty($feeStatus)) {
        echo "🔧 Set up fee structure for the student's class<br>";
        echo "• Go to fee structure management<br>";
        echo "• Or run the sample data seeder<br>";
    }
    
    echo "<h2>Test URLs with existing data:</h2>";
    if (!empty($classes) && !empty($studentsInClass)) {
        $testClassId = $classes[0]['id'];
        $testStudentId = $studentsInClass[0]['id'];
        echo "• <a href='fees/collection.php?class_id=$testClassId&student_id=$testStudentId' target='_blank'>fees/collection.php?class_id=$testClassId&student_id=$testStudentId</a><br>";
    }
    echo "• <a href='seed-sample-data.php' target='_blank'>Create Sample Data</a><br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h1, h2 { color: #333; }
a { color: #0066cc; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>