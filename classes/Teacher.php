<?php
require_once __DIR__ . '/../config/database.php';

class Teacher {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($userData, $teacherData) {
        try {
            $this->db->beginTransaction();
            
            // Create user account
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, role, phone, address) 
                VALUES (?, ?, ?, 'teacher', ?, ?)
            ");
            $hashedPassword = password_hash($teacherData['employee_id'], PASSWORD_DEFAULT);
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $hashedPassword,
                $userData['phone'],
                $userData['address']
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Create teacher record
            $stmt = $this->db->prepare("
                INSERT INTO teachers (
                    user_id, employee_id, qualification, experience_years, 
                    specialization, salary, joining_date, employment_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $teacherData['employee_id'],
                $teacherData['qualification'],
                $teacherData['experience_years'],
                $teacherData['specialization'],
                $teacherData['salary'],
                $teacherData['joining_date'],
                $teacherData['employment_type']
            ]);
            
            $this->db->commit();
            return ['success' => true, 'teacher_id' => $this->db->lastInsertId(), 'user_id' => $userId];
            
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
                $searchQuery = " AND (u.name LIKE ? OR t.employee_id LIKE ? OR u.email LIKE ?)";
                $params = ["%$search%", "%$search%", "%$search%"];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    t.*, u.name, u.email, u.phone, u.address, u.status
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                WHERE 1=1 $searchQuery
                ORDER BY t.employee_id
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
                    t.*, u.name, u.email, u.phone, u.address, u.status
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function update($id, $userData, $teacherData) {
        try {
            $this->db->beginTransaction();
            
            // Get teacher's user_id
            $stmt = $this->db->prepare("SELECT user_id FROM teachers WHERE id = ?");
            $stmt->execute([$id]);
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                throw new Exception('Teacher not found');
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
                $teacher['user_id']
            ]);
            
            // Update teacher record
            $stmt = $this->db->prepare("
                UPDATE teachers 
                SET qualification = ?, experience_years = ?, specialization = ?, 
                    salary = ?, employment_type = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $teacherData['qualification'],
                $teacherData['experience_years'],
                $teacherData['specialization'],
                $teacherData['salary'],
                $teacherData['employment_type'],
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
            
            // Delete teacher record (user will be deleted by CASCADE)
            $stmt = $this->db->prepare("DELETE FROM teachers WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function generateEmployeeId() {
        $year = date('Y');
        $prefix = "EMP$year";
        
        $stmt = $this->db->prepare("
            SELECT employee_id FROM teachers 
            WHERE employee_id LIKE ? 
            ORDER BY employee_id DESC LIMIT 1
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
    
    public function getTotalCount() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM teachers");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getAttendance($teacherId, $date = null) {
        try {
            $dateQuery = $date ? "AND date = ?" : "AND date = CURDATE()";
            $params = [$teacherId];
            if ($date) $params[] = $date;
            
            $stmt = $this->db->prepare("
                SELECT * FROM teacher_attendance 
                WHERE teacher_id = ? $dateQuery
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function markAttendance($teacherId, $status, $markedBy, $date = null, $checkIn = null, $checkOut = null, $remarks = '') {
        try {
            $date = $date ?: date('Y-m-d');
            
            $stmt = $this->db->prepare("
                INSERT INTO teacher_attendance (teacher_id, date, status, check_in_time, check_out_time, marked_by, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                check_in_time = VALUES(check_in_time),
                check_out_time = VALUES(check_out_time),
                marked_by = VALUES(marked_by),
                remarks = VALUES(remarks),
                created_at = NOW()
            ");
            
            $stmt->execute([$teacherId, $date, $status, $checkIn, $checkOut, $markedBy, $remarks]);
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getSubjects() {
        try {
            $stmt = $this->db->query("SELECT * FROM subjects ORDER BY name");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getClasses() {
        try {
            $stmt = $this->db->query("SELECT * FROM classes ORDER BY name, section");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function assignSubjects($teacherId, $assignments) {
        try {
            $this->db->beginTransaction();
            
            // Remove existing assignments
            $stmt = $this->db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
            $stmt->execute([$teacherId]);
            
            // Add new assignments
            if (!empty($assignments)) {
                // Check if assigned_date column exists
                $columnStmt = $this->db->query("SHOW COLUMNS FROM teacher_subjects LIKE 'assigned_date'");
                $hasAssignedDate = $columnStmt->fetch();
                
                if ($hasAssignedDate) {
                    $stmt = $this->db->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) VALUES (?, ?, ?, NOW())");
                } else {
                    $stmt = $this->db->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES (?, ?, ?)");
                }
                
                foreach ($assignments as $assignment) {
                    $stmt->execute([$teacherId, $assignment['subject_id'], $assignment['class_id']]);
                }
            }
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getTeacherSubjects($teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ts.*, s.name as subject_name, s.code as subject_code,
                    c.name as class_name, c.section
                FROM teacher_subjects ts
                JOIN subjects s ON ts.subject_id = s.id
                JOIN classes c ON ts.class_id = c.id
                WHERE ts.teacher_id = ?
                ORDER BY c.name, c.section, s.name
            ");
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function assignSubject($teacherId, $subjectId, $classId) {
        try {
            // Check if assignment already exists
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM teacher_subjects 
                WHERE teacher_id = ? AND subject_id = ? AND class_id = ?
            ");
            $checkStmt->execute([$teacherId, $subjectId, $classId]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Subject already assigned to this teacher for this class'];
            }
            
            // Check if another teacher is already assigned to this subject-class combination
            $conflictStmt = $this->db->prepare("
                SELECT u.name, te.employee_id FROM teacher_subjects ts
                JOIN teachers te ON ts.teacher_id = te.id
                JOIN users u ON te.user_id = u.id
                WHERE ts.subject_id = ? AND ts.class_id = ? AND ts.teacher_id != ?
            ");
            $conflictStmt->execute([$subjectId, $classId, $teacherId]);
            $conflict = $conflictStmt->fetch();
            
            if ($conflict) {
                return ['success' => false, 'message' => 'This subject is already assigned to ' . $conflict['name'] . ' (' . $conflict['employee_id'] . ') for this class'];
            }
            
            // Assign the subject
            // Check if assigned_date column exists
            $columnStmt = $this->db->query("SHOW COLUMNS FROM teacher_subjects LIKE 'assigned_date'");
            $hasAssignedDate = $columnStmt->fetch();
            
            if ($hasAssignedDate) {
                $stmt = $this->db->prepare("
                    INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, assigned_date) 
                    VALUES (?, ?, ?, NOW())
                ");
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) 
                    VALUES (?, ?, ?)
                ");
            }
            
            $result = $stmt->execute([$teacherId, $subjectId, $classId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Subject assigned successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to assign subject'];
            }
        } catch (Exception $e) {
            error_log('Teacher assignSubject error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function removeSubject($assignmentId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM teacher_subjects WHERE id = ?");
            $result = $stmt->execute([$assignmentId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Subject removed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to remove subject assignment'];
            }
        } catch (Exception $e) {
            error_log('Teacher removeSubject error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getAttendanceSummary($teacherId, $monthYear) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    COUNT(*) as total
                FROM teacher_attendance 
                WHERE teacher_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
            ");
            $stmt->execute([$teacherId, $monthYear]);
            $result = $stmt->fetch();
            
            return $result ?: ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
        } catch (Exception $e) {
            error_log('Teacher getAttendanceSummary error: ' . $e->getMessage());
            return ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
        }
    }
    
    public function generatePayroll($payrollData, $generatedBy) {
        try {
            $this->db->beginTransaction();
            
            // Calculate overtime pay
            $overtimePay = $payrollData['overtime_hours'] * $payrollData['overtime_rate'];
            
            // Calculate prorated salary based on attendance
            $dailySalary = $payrollData['basic_salary'] / $payrollData['working_days'];
            $proratedSalary = $dailySalary * $payrollData['present_days'];
            
            // Calculate net salary
            $grossSalary = $proratedSalary + $payrollData['allowances'] + $overtimePay;
            $netSalary = $grossSalary - $payrollData['deductions'];
            
            // Check if payroll already exists for this month
            $checkStmt = $this->db->prepare("
                SELECT id FROM teacher_payroll 
                WHERE teacher_id = ? AND month_year = ?
            ");
            $checkStmt->execute([$payrollData['teacher_id'], $payrollData['month_year']]);
            
            if ($checkStmt->fetch()) {
                throw new Exception('Payroll already exists for this month');
            }
            
            // Insert payroll record
            $stmt = $this->db->prepare("
                INSERT INTO teacher_payroll (
                    teacher_id, month_year, basic_salary, allowances, deductions,
                    overtime_hours, overtime_rate, overtime_pay, present_days, 
                    working_days, gross_salary, net_salary, generated_by, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $payrollData['teacher_id'],
                $payrollData['month_year'],
                $payrollData['basic_salary'],
                $payrollData['allowances'],
                $payrollData['deductions'],
                $payrollData['overtime_hours'],
                $payrollData['overtime_rate'],
                $overtimePay,
                $payrollData['present_days'],
                $payrollData['working_days'],
                $grossSalary,
                $netSalary,
                $generatedBy,
                $payrollData['remarks']
            ]);
            
            if ($result) {
                $payrollId = $this->db->lastInsertId();
                $this->db->commit();
                return [
                    'success' => true, 
                    'payroll_id' => $payrollId,
                    'net_salary' => $netSalary,
                    'message' => 'Payroll generated successfully'
                ];
            } else {
                throw new Exception('Failed to insert payroll record');
            }
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Teacher generatePayroll error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getPayrollHistory($teacherId, $limit = 12) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM teacher_payroll 
                WHERE teacher_id = ? 
                ORDER BY month_year DESC 
                LIMIT ?
            ");
            $stmt->execute([$teacherId, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Teacher getPayrollHistory error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getPayrollById($payrollId) {
        try {
            $stmt = $this->db->prepare("
                SELECT tp.*, t.employee_id, u.name as teacher_name
                FROM teacher_payroll tp
                JOIN teachers t ON tp.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE tp.id = ?
            ");
            $stmt->execute([$payrollId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Teacher getPayrollById error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function getByUserId($userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM teachers WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function getTeacherClasses($teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT c.id, c.name, c.section
                FROM classes c
                JOIN teacher_subjects ts ON c.id = ts.class_id
                WHERE ts.teacher_id = ?
                ORDER BY c.name, c.section
            ");
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
}
?>