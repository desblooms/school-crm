<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Only admin can run this
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Access denied. Admin access required.');
}

echo "<h1>Creating Sample Data</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>1. Creating Classes Table</h2>";
    $db->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        section VARCHAR(10) NOT NULL,
        capacity INT DEFAULT 30,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Classes table created/verified</p>";
    
    echo "<h2>2. Creating Students Table</h2>";
    $db->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        admission_number VARCHAR(50) UNIQUE NOT NULL,
        class_id INT,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        parent_name VARCHAR(200),
        parent_phone VARCHAR(20),
        status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )");
    echo "<p>✅ Students table created/verified</p>";
    
    echo "<h2>3. Creating Fee Types Table</h2>";
    $db->exec("CREATE TABLE IF NOT EXISTS fee_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        amount DECIMAL(10,2) DEFAULT 0,
        frequency ENUM('monthly', 'quarterly', 'yearly', 'one_time') DEFAULT 'monthly',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Fee types table created/verified</p>";
    
    echo "<h2>4. Creating Fee Payments Table</h2>";
    $db->exec("CREATE TABLE IF NOT EXISTS fee_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        fee_type_id INT DEFAULT 1,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        month_year VARCHAR(10) NOT NULL,
        transaction_id VARCHAR(100),
        remarks TEXT,
        receipt_number VARCHAR(50) NOT NULL UNIQUE,
        collected_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (fee_type_id) REFERENCES fee_types(id),
        FOREIGN KEY (collected_by) REFERENCES users(id)
    )");
    echo "<p>✅ Fee payments table created/verified</p>";
    
    echo "<h2>5. Inserting Sample Classes</h2>";
    $classes_data = [
        ['Grade 1', 'A'],
        ['Grade 1', 'B'],
        ['Grade 2', 'A'],
        ['Grade 3', 'A'],
        ['Grade 4', 'A'],
        ['Grade 5', 'A']
    ];
    
    foreach ($classes_data as $class_data) {
        $stmt = $db->prepare("INSERT IGNORE INTO classes (name, section) VALUES (?, ?)");
        $stmt->execute($class_data);
    }
    echo "<p>✅ Sample classes inserted</p>";
    
    echo "<h2>6. Inserting Sample Students</h2>";
    $students_data = [
        ['John Doe', 'STD001', 1, '2010-05-15', 'male', '9876543210', 'john@example.com', 'Mr. Robert Doe', '9876543211'],
        ['Jane Smith', 'STD002', 1, '2010-08-22', 'female', '9876543212', 'jane@example.com', 'Mrs. Lisa Smith', '9876543213'],
        ['Mike Johnson', 'STD003', 2, '2009-12-10', 'male', '9876543214', 'mike@example.com', 'Mr. David Johnson', '9876543215'],
        ['Emily Davis', 'STD004', 2, '2009-03-18', 'female', '9876543216', 'emily@example.com', 'Mrs. Sarah Davis', '9876543217'],
        ['Alex Wilson', 'STD005', 3, '2008-07-25', 'male', '9876543218', 'alex@example.com', 'Mr. Tom Wilson', '9876543219']
    ];
    
    foreach ($students_data as $student_data) {
        $stmt = $db->prepare("INSERT IGNORE INTO students (name, admission_number, class_id, date_of_birth, gender, phone, email, parent_name, parent_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($student_data);
    }
    echo "<p>✅ Sample students inserted</p>";
    
    echo "<h2>7. Inserting Sample Fee Types</h2>";
    $fee_types_data = [
        ['Tuition Fee', 'Monthly tuition fee', 1000.00, 'monthly'],
        ['Transport Fee', 'Monthly transport fee', 500.00, 'monthly'],
        ['Activity Fee', 'Quarterly activity fee', 300.00, 'quarterly'],
        ['Admission Fee', 'One-time admission fee', 2000.00, 'one_time'],
        ['Exam Fee', 'Yearly examination fee', 800.00, 'yearly']
    ];
    
    foreach ($fee_types_data as $fee_type_data) {
        $stmt = $db->prepare("INSERT IGNORE INTO fee_types (name, description, amount, frequency) VALUES (?, ?, ?, ?)");
        $stmt->execute($fee_type_data);
    }
    echo "<p>✅ Sample fee types inserted</p>";
    
    echo "<h2>8. Creating Settings Table</h2>";
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Settings table created/verified</p>";
    
    echo "<hr>";
    echo "<h2>🎉 Sample Data Created Successfully!</h2>";
    echo "<p>Your school CRM now has:</p>";
    echo "<ul>";
    echo "<li>6 Classes (Grade 1A, 1B, 2A, 3A, 4A, 5A)</li>";
    echo "<li>5 Sample Students</li>";
    echo "<li>5 Fee Types (Tuition, Transport, Activity, Admission, Exam)</li>";
    echo "<li>All necessary database tables</li>";
    echo "</ul>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<p>1. <a href='fees/collection.php'>Test Fee Collection</a> - Try collecting fees from students</p>";
    echo "<p>2. <a href='students/list.php'>View Students</a> - See all students</p>";
    echo "<p>3. <a href='index.php'>Go to Dashboard</a> - Return to main dashboard</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>