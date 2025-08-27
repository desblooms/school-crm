<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Only admin can run this
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Access denied. Admin access required.');
}

echo "<h1>School CRM Database Installation</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>1. Checking Existing Database Structure</h2>";
    
    // Check existing tables
    $stmt = $db->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Found " . count($existing_tables) . " existing tables:</p>";
    if (!empty($existing_tables)) {
        echo "<ul>";
        foreach ($existing_tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>2. Creating/Updating Database Tables</h2>";
    
    // Users table (should already exist)
    echo "<h3>Users Table</h3>";
    if (!in_array('users', $existing_tables)) {
        $db->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'teacher', 'accountant', 'student', 'parent') DEFAULT 'student',
            status ENUM('active', 'inactive') DEFAULT 'active',
            failed_login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "<p>✅ Users table created</p>";
    } else {
        echo "<p>✅ Users table exists</p>";
    }
    
    // Classes table
    echo "<h3>Classes Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_name VARCHAR(100) NOT NULL,
        section VARCHAR(10) NOT NULL,
        capacity INT DEFAULT 30,
        teacher_id INT NULL,
        academic_year VARCHAR(20) DEFAULT '" . date('Y') . "-" . (date('Y') + 1) . "',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Classes table created/updated</p>";
    
    // Students table
    echo "<h3>Students Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(255) NOT NULL,
        admission_number VARCHAR(50) UNIQUE NOT NULL,
        class_id INT NULL,
        date_of_birth DATE NULL,
        gender ENUM('male', 'female', 'other') NULL,
        phone VARCHAR(20) NULL,
        email VARCHAR(255) NULL,
        address TEXT NULL,
        parent_name VARCHAR(255) NULL,
        parent_phone VARCHAR(20) NULL,
        parent_email VARCHAR(255) NULL,
        status ENUM('active', 'inactive', 'graduated', 'transferred') DEFAULT 'active',
        admission_date DATE DEFAULT (CURRENT_DATE),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_class_id (class_id),
        INDEX idx_admission_number (admission_number),
        INDEX idx_status (status)
    )");
    echo "<p>✅ Students table created/updated</p>";
    
    // Teachers table
    echo "<h3>Teachers Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_name VARCHAR(255) NOT NULL,
        employee_id VARCHAR(50) UNIQUE NOT NULL,
        subject VARCHAR(100) NULL,
        phone VARCHAR(20) NULL,
        email VARCHAR(255) NULL,
        address TEXT NULL,
        salary DECIMAL(10,2) DEFAULT 0,
        joining_date DATE DEFAULT (CURRENT_DATE),
        status ENUM('active', 'inactive', 'resigned') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_employee_id (employee_id),
        INDEX idx_status (status)
    )");
    echo "<p>✅ Teachers table created/updated</p>";
    
    // Subjects table
    echo "<h3>Subjects Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_name VARCHAR(100) NOT NULL,
        subject_code VARCHAR(20) UNIQUE NOT NULL,
        description TEXT NULL,
        class_id INT NULL,
        teacher_id INT NULL,
        credits INT DEFAULT 1,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_subject_code (subject_code),
        INDEX idx_class_id (class_id),
        INDEX idx_teacher_id (teacher_id)
    )");
    echo "<p>✅ Subjects table created/updated</p>";
    
    // Fee types table
    echo "<h3>Fee Types Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS fee_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fee_name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        amount DECIMAL(10,2) DEFAULT 0,
        frequency ENUM('monthly', 'quarterly', 'yearly', 'one_time') DEFAULT 'monthly',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Fee Types table created/updated</p>";
    
    // Fee structure table
    echo "<h3>Fee Structure Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS fee_structure (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        fee_type_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        academic_year VARCHAR(20) DEFAULT '" . date('Y') . "-" . (date('Y') + 1) . "',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_class_fee_year (class_id, fee_type_id, academic_year),
        INDEX idx_class_id (class_id),
        INDEX idx_fee_type_id (fee_type_id)
    )");
    echo "<p>✅ Fee Structure table created/updated</p>";
    
    // Fee payments table
    echo "<h3>Fee Payments Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS fee_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        fee_type_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'card', 'online', 'cheque', 'bank_transfer') NOT NULL,
        payment_date DATE DEFAULT (CURRENT_DATE),
        month_year VARCHAR(10) NOT NULL,
        transaction_id VARCHAR(100) NULL,
        remarks TEXT NULL,
        receipt_number VARCHAR(50) UNIQUE NOT NULL,
        collected_by INT NOT NULL,
        status ENUM('completed', 'pending', 'failed', 'refunded') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_student_id (student_id),
        INDEX idx_fee_type_id (fee_type_id),
        INDEX idx_payment_date (payment_date),
        INDEX idx_receipt_number (receipt_number),
        INDEX idx_collected_by (collected_by)
    )");
    echo "<p>✅ Fee Payments table created/updated</p>";
    
    // Settings table
    echo "<h3>Settings Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT NULL,
        setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
        description TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Settings table created/updated</p>";
    
    // Activity logs table
    echo "<h3>Activity Logs Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )");
    echo "<p>✅ Activity Logs table created/updated</p>";
    
    echo "<h2>3. Inserting Sample Data</h2>";
    
    // Sample classes
    echo "<h3>Sample Classes</h3>";
    $sample_classes = [
        ['Grade 1', 'A'],
        ['Grade 1', 'B'],
        ['Grade 2', 'A'],
        ['Grade 3', 'A'],
        ['Grade 4', 'A'],
        ['Grade 5', 'A']
    ];
    
    $inserted_classes = 0;
    foreach ($sample_classes as $class_data) {
        try {
            $stmt = $db->prepare("INSERT INTO classes (class_name, section) VALUES (?, ?)");
            if ($stmt->execute($class_data)) {
                $inserted_classes++;
            }
        } catch (Exception $e) {
            // Class might already exist
        }
    }
    echo "<p>✅ Inserted $inserted_classes sample classes</p>";
    
    // Sample students
    echo "<h3>Sample Students</h3>";
    $sample_students = [
        ['John Doe', 'STD001', 1, '2010-05-15', 'male', '9876543210', 'john@example.com', 'Mr. Robert Doe', '9876543211'],
        ['Jane Smith', 'STD002', 1, '2010-08-22', 'female', '9876543212', 'jane@example.com', 'Mrs. Lisa Smith', '9876543213'],
        ['Mike Johnson', 'STD003', 2, '2009-12-10', 'male', '9876543214', 'mike@example.com', 'Mr. David Johnson', '9876543215'],
        ['Emily Davis', 'STD004', 2, '2009-03-18', 'female', '9876543216', 'emily@example.com', 'Mrs. Sarah Davis', '9876543217'],
        ['Alex Wilson', 'STD005', 3, '2008-07-25', 'male', '9876543218', 'alex@example.com', 'Mr. Tom Wilson', '9876543219']
    ];
    
    $inserted_students = 0;
    foreach ($sample_students as $student_data) {
        try {
            $stmt = $db->prepare("INSERT INTO students (student_name, admission_number, class_id, date_of_birth, gender, phone, email, parent_name, parent_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute($student_data)) {
                $inserted_students++;
            }
        } catch (Exception $e) {
            // Student might already exist
        }
    }
    echo "<p>✅ Inserted $inserted_students sample students</p>";
    
    // Sample fee types
    echo "<h3>Sample Fee Types</h3>";
    $sample_fee_types = [
        ['Tuition Fee', 'Monthly tuition fee', 1000.00, 'monthly'],
        ['Transport Fee', 'Monthly transport fee', 500.00, 'monthly'],
        ['Activity Fee', 'Quarterly activity fee', 300.00, 'quarterly'],
        ['Admission Fee', 'One-time admission fee', 2000.00, 'one_time'],
        ['Exam Fee', 'Yearly examination fee', 800.00, 'yearly']
    ];
    
    $inserted_fee_types = 0;
    foreach ($sample_fee_types as $fee_type_data) {
        try {
            $stmt = $db->prepare("INSERT INTO fee_types (fee_name, description, amount, frequency) VALUES (?, ?, ?, ?)");
            if ($stmt->execute($fee_type_data)) {
                $inserted_fee_types++;
            }
        } catch (Exception $e) {
            // Fee type might already exist
        }
    }
    echo "<p>✅ Inserted $inserted_fee_types sample fee types</p>";
    
    echo "<hr>";
    echo "<h2>🎉 Database Installation Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<p><strong>Installation Summary:</strong></p>";
    echo "<ul>";
    echo "<li>All database tables created/updated</li>";
    echo "<li>$inserted_classes classes added</li>";
    echo "<li>$inserted_students students added</li>";
    echo "<li>$inserted_fee_types fee types added</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<p>1. <a href='fees/collection.php'>Test Fee Collection</a></p>";
    echo "<p>2. <a href='students/list.php'>View Students</a></p>";
    echo "<p>3. <a href='index.php'>Go to Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>