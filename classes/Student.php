<?php
require_once __DIR__ . '/../config/database.php';

class Student {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($userData, $studentData) {
        try {
            $this->db->beginTransaction();
            
            // Create user account
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, role, phone, address) 
                VALUES (?, ?, ?, 'student', ?, ?)
            ");
            $hashedPassword = password_hash($studentData['admission_number'], PASSWORD_DEFAULT);
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $hashedPassword,
                $userData['phone'],
                $userData['address']
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Create student record
            $stmt = $this->db->prepare("
                INSERT INTO students (
                    user_id, admission_number, class_id, roll_number, date_of_birth, 
                    gender, blood_group, guardian_name, guardian_phone, guardian_email,
                    emergency_contact, medical_conditions, admission_date, 
                    transport_required, hostel_required
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $studentData['admission_number'],
                $studentData['class_id'],
                $studentData['roll_number'],
                $studentData['date_of_birth'],
                $studentData['gender'],
                $studentData['blood_group'] ?? null,
                $studentData['guardian_name'],
                $studentData['guardian_phone'],
                $studentData['guardian_email'],
                $studentData['emergency_contact'],
                $studentData['medical_conditions'] ?? null,
                $studentData['admission_date'],
                $studentData['transport_required'] ?? false,
                $studentData['hostel_required'] ?? false
            ]);
            
            $this->db->commit();
            return ['success' => true, 'student_id' => $this->db->lastInsertId(), 'user_id' => $userId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getAll($limit = 50, $offset = 0, $search = '') {
        try {
            $searchQuery = '';
            $params = [];
            
            if (!empty($search)) {
                $searchQuery = " AND (u.name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?)";
                $params = ["%$search%", "%$search%", "%$search%"];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    s.*, u.name, u.email, u.phone, u.address, u.status,
                    c.name as class_name, c.section
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE 1=1 $searchQuery
                ORDER BY s.admission_number
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    s.*, u.name, u.email, u.phone, u.address, u.status,
                    c.name as class_name, c.section
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function update($id, $userData, $studentData) {
        try {
            $this->db->beginTransaction();
            
            // Get student's user_id
            $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $student = $stmt->fetch();
            
            if (!$student) {
                throw new Exception('Student not found');
            }
            
            // Update user account
            $stmt = $this->db->prepare("
                UPDATE users 
                SET name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $userData['phone'],
                $userData['address'],
                $student['user_id']
            ]);
            
            // Update student record
            $stmt = $this->db->prepare("
                UPDATE students 
                SET class_id = ?, roll_number = ?, date_of_birth = ?, gender = ?,
                    blood_group = ?, guardian_name = ?, guardian_phone = ?, guardian_email = ?,
                    emergency_contact = ?, medical_conditions = ?, transport_required = ?,
                    hostel_required = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $studentData['class_id'],
                $studentData['roll_number'],
                $studentData['date_of_birth'],
                $studentData['gender'],
                $studentData['blood_group'] ?? null,
                $studentData['guardian_name'],
                $studentData['guardian_phone'],
                $studentData['guardian_email'],
                $studentData['emergency_contact'],
                $studentData['medical_conditions'] ?? null,
                $studentData['transport_required'] ?? false,
                $studentData['hostel_required'] ?? false,
                $id
            ]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Get student's user_id
            $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $student = $stmt->fetch();
            
            if (!$student) {
                throw new Exception('Student not found');
            }
            
            // Delete student record (user will be deleted by CASCADE)
            $stmt = $this->db->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function generateAdmissionNumber() {
        $year = date('Y');
        $prefix = "ADM$year";
        
        $stmt = $this->db->prepare("
            SELECT admission_number FROM students 
            WHERE admission_number LIKE ? 
            ORDER BY admission_number DESC LIMIT 1
        ");
        $stmt->execute(["$prefix%"]);
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber) {
            $number = intval(substr($lastNumber, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    public function getClasses() {
        try {
            $stmt = $this->db->query("SELECT * FROM classes ORDER BY name, section");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getTotalCount() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM students");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getAttendance($studentId, $date = null) {
        try {
            $dateQuery = $date ? "AND date = ?" : "AND date = CURDATE()";
            $params = [$studentId];
            if ($date) $params[] = $date;
            
            $stmt = $this->db->prepare("
                SELECT * FROM student_attendance 
                WHERE student_id = ? $dateQuery
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function markAttendance($studentId, $classId, $status, $markedBy, $date = null, $checkInTime = null, $checkOutTime = null, $remarks = '') {
        try {
            $date = $date ?: date('Y-m-d');
            
            // Check if columns exist before using them
            $stmt = $this->db->query("SHOW COLUMNS FROM student_attendance LIKE 'check_in_time'");
            $hasCheckInTime = $stmt->fetch();
            
            if ($hasCheckInTime) {
                $stmt = $this->db->prepare("
                    INSERT INTO student_attendance (student_id, class_id, date, status, check_in_time, check_out_time, marked_by, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    status = VALUES(status),
                    check_in_time = VALUES(check_in_time),
                    check_out_time = VALUES(check_out_time),
                    marked_by = VALUES(marked_by),
                    remarks = VALUES(remarks),
                    created_at = NOW()
                ");
                $stmt->execute([$studentId, $classId, $date, $status, $checkInTime, $checkOutTime, $markedBy, $remarks]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO student_attendance (student_id, class_id, date, status, marked_by, remarks)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    marked_by = VALUES(marked_by),
                    remarks = VALUES(remarks),
                    created_at = NOW()
                ");
                $stmt->execute([$studentId, $classId, $date, $status, $markedBy, $remarks]);
            }
            
            return ['success' => true, 'message' => 'Attendance marked successfully'];
            
        } catch (Exception $e) {
            error_log('Student markAttendance error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
        }
    }
    
    public function getAttendanceByMonth($studentId, $month) {
        try {
            // Check if check_in_time and check_out_time columns exist
            $stmt = $this->db->query("SHOW COLUMNS FROM student_attendance LIKE 'check_in_time'");
            $hasCheckInTime = $stmt->fetch();
            
            if ($hasCheckInTime) {
                $stmt = $this->db->prepare("
                    SELECT sa.*, u.name as marked_by_name
                    FROM student_attendance sa
                    LEFT JOIN users u ON sa.marked_by = u.id
                    WHERE sa.student_id = ? AND DATE_FORMAT(sa.date, '%Y-%m') = ?
                    ORDER BY sa.date DESC
                ");
            } else {
                $stmt = $this->db->prepare("
                    SELECT sa.*, u.name as marked_by_name,
                           NULL as check_in_time, NULL as check_out_time
                    FROM student_attendance sa
                    LEFT JOIN users u ON sa.marked_by = u.id
                    WHERE sa.student_id = ? AND DATE_FORMAT(sa.date, '%Y-%m') = ?
                    ORDER BY sa.date DESC
                ");
            }
            
            $stmt->execute([$studentId, $month]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Student getAttendanceByMonth error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getAttendanceSummary($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused,
                    COUNT(*) as total
                FROM student_attendance 
                WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            ");
            $stmt->execute([$studentId]);
            $result = $stmt->fetch();
            
            if ($result && $result['total'] > 0) {
                $result['percentage'] = round((($result['present'] + $result['late']) / $result['total']) * 100, 1);
            } else {
                $result = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0, 'percentage' => 0];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Student getAttendanceSummary error: ' . $e->getMessage());
            return ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0, 'percentage' => 0];
        }
    }
    
    public function getStudentsByClass($classId) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.id, s.admission_number, s.roll_number, u.name
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.class_id = ? AND u.status = 'active'
                ORDER BY s.roll_number, u.name
            ");
            $stmt->execute([$classId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getClassAttendanceByDate($classId, $date) {
        try {
            $stmt = $this->db->prepare("
                SELECT sa.*, s.id as student_id
                FROM student_attendance sa
                JOIN students s ON sa.student_id = s.id
                WHERE s.class_id = ? AND sa.date = ?
            ");
            $stmt->execute([$classId, $date]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>