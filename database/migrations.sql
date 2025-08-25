-- Database Migrations to Fix School CRM Issues
-- Run this script to add missing columns and update table structures

-- Fix 0: Add employee_id column to teachers table if missing
ALTER TABLE teachers ADD COLUMN employee_id VARCHAR(20) UNIQUE;

-- Fix 1: Add assigned_date column to teacher_subjects table
ALTER TABLE teacher_subjects ADD COLUMN assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Fix 2: Add check_in_time and check_out_time columns to student_attendance table
ALTER TABLE student_attendance ADD COLUMN check_in_time TIME NULL AFTER status;
ALTER TABLE student_attendance ADD COLUMN check_out_time TIME NULL AFTER check_in_time;

-- Fix 3: Update student_attendance status enum to include 'excused'
ALTER TABLE student_attendance MODIFY COLUMN status ENUM('present', 'absent', 'late', 'half_day', 'excused') NOT NULL;

-- Fix 4: Create teacher_payroll table if it doesn't exist
CREATE TABLE IF NOT EXISTS teacher_payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    overtime_hours DECIMAL(4,2) DEFAULT 0,
    overtime_rate DECIMAL(10,2) DEFAULT 0,
    overtime_pay DECIMAL(10,2) DEFAULT 0,
    present_days INT DEFAULT 0,
    working_days INT DEFAULT 0,
    gross_salary DECIMAL(10,2) NOT NULL,
    net_salary DECIMAL(10,2) NOT NULL,
    payment_date DATE NULL,
    payment_method ENUM('cash', 'bank_transfer', 'cheque') DEFAULT 'bank_transfer',
    status ENUM('pending', 'paid') DEFAULT 'pending',
    generated_by INT NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    UNIQUE KEY unique_teacher_month (teacher_id, month_year)
);

-- Fix 5: Update fee_payments table status enum to ensure 'paid' is included
ALTER TABLE fee_payments MODIFY COLUMN status ENUM('paid', 'pending', 'failed', 'refunded') DEFAULT 'paid';

-- Fix 6: Add unique constraint to teacher_subjects if it doesn't exist
-- Note: This may fail if duplicate data exists, but the PHP code handles this
ALTER TABLE teacher_subjects ADD UNIQUE KEY unique_teacher_subject_class (teacher_id, subject_id, class_id);