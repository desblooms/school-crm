-- Insert default data
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO fee_types (name, description, is_mandatory) VALUES
('Tuition Fee', 'Monthly tuition fee for academic courses', TRUE),
('Transport Fee', 'Monthly transportation fee', FALSE),
('Exam Fee', 'Examination fee per semester', TRUE),
('Hostel Fee', 'Monthly hostel accommodation fee', FALSE),
('Activity Fee', 'Fee for extracurricular activities', FALSE);

INSERT INTO settings (setting_key, setting_value, description) VALUES
('school_name', 'Sample School', 'School name for branding'),
('school_address', '123 Education Street, Learning City', 'School address'),
('school_phone', '+1-234-567-8900', 'School contact number'),
('school_email', 'info@school.com', 'School email address'),
('academic_year', '2024-25', 'Current academic year'),
('currency_symbol', '₹', 'Currency symbol for fees'),
('timezone', 'Asia/Kolkata', 'School timezone');

INSERT INTO subjects (name, code, description) VALUES
('Mathematics', 'MATH', 'Basic and Advanced Mathematics'),
('English', 'ENG', 'English Language and Literature'),
('Science', 'SCI', 'General Science subjects'),
('Social Studies', 'SS', 'History, Geography, Civics'),
('Computer Science', 'CS', 'Computer programming and applications'),
('Physical Education', 'PE', 'Physical fitness and sports');

INSERT INTO classes (name, section) VALUES
('Class 1', 'A'),
('Class 1', 'B'),
('Class 2', 'A'),
('Class 2', 'B'),
('Class 3', 'A'),
('Class 4', 'A'),
('Class 5', 'A');