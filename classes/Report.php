<?php
require_once __DIR__ . '/../config/database.php';

class Report {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Students stats
            $stmt = $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'active'");
            $stats['total_students'] = $stmt->fetchColumn();
            
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM students 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stats['new_students_month'] = $stmt->fetchColumn();
            
            // Teachers stats
            $stmt = $this->db->query("SELECT COUNT(*) FROM teachers");
            $stats['total_teachers'] = $stmt->fetchColumn();
            
            // Fee stats
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(amount), 0) FROM fee_payments 
                WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND status = 'paid'
            ");
            $stats['monthly_collection'] = $stmt->fetchColumn();
            
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(amount), 0) FROM invoices 
                WHERE status = 'pending'
            ");
            $stats['pending_fees'] = $stmt->fetchColumn();
            
            // Attendance stats
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                FROM student_attendance 
                WHERE date = CURDATE()
            ");
            $attendanceToday = $stmt->fetch();
            $stats['attendance_rate'] = $attendanceToday['total'] > 0 ? 
                round(($attendanceToday['present'] / $attendanceToday['total']) * 100, 1) : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log('Report getDashboardStats error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getStudentReport($filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['class_id'])) {
                $whereConditions[] = "s.class_id = ?";
                $params[] = $filters['class_id'];
            }
            
            if (!empty($filters['gender'])) {
                $whereConditions[] = "s.gender = ?";
                $params[] = $filters['gender'];
            }
            
            if (!empty($filters['admission_year'])) {
                $whereConditions[] = "YEAR(s.admission_date) = ?";
                $params[] = $filters['admission_year'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->db->prepare("
                SELECT 
                    s.*, u.name, u.email, u.phone, u.address,
                    c.name as class_name, c.section,
                    YEAR(CURDATE()) - YEAR(s.date_of_birth) as age
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                $whereClause
                ORDER BY c.name, c.section, s.roll_number
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report getStudentReport error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getTeacherReport($filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['employment_type'])) {
                $whereConditions[] = "t.employment_type = ?";
                $params[] = $filters['employment_type'];
            }
            
            if (!empty($filters['specialization'])) {
                $whereConditions[] = "t.specialization LIKE ?";
                $params[] = "%{$filters['specialization']}%";
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->db->prepare("
                SELECT 
                    t.*, u.name, u.email, u.phone,
                    COUNT(ts.id) as subjects_count
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
                $whereClause
                GROUP BY t.id
                ORDER BY u.name
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report getTeacherReport error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getAttendanceReport($filters = []) {
        try {
            $dateCondition = '';
            $params = [];
            
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $dateCondition = "AND sa.date BETWEEN ? AND ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            } elseif (!empty($filters['month'])) {
                $dateCondition = "AND DATE_FORMAT(sa.date, '%Y-%m') = ?";
                $params[] = $filters['month'];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    s.admission_number,
                    u.name as student_name,
                    c.name as class_name,
                    c.section,
                    COUNT(sa.id) as total_days,
                    SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days,
                    ROUND((SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(sa.id)) * 100, 1) as attendance_percentage
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                LEFT JOIN student_attendance sa ON s.id = sa.student_id
                WHERE 1=1 $dateCondition
                GROUP BY s.id
                ORDER BY c.name, c.section, u.name
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report getAttendanceReport error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getFeeReport($filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['class_id'])) {
                $whereConditions[] = "s.class_id = ?";
                $params[] = $filters['class_id'];
            }
            
            if (!empty($filters['month_year'])) {
                $whereConditions[] = "i.created_at >= ? AND i.created_at < DATE_ADD(?, INTERVAL 1 MONTH)";
                $params[] = $filters['month_year'] . '-01';
                $params[] = $filters['month_year'] . '-01';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->db->prepare("
                SELECT 
                    s.admission_number,
                    u.name as student_name,
                    c.name as class_name,
                    c.section,
                    COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as paid_amount,
                    COALESCE(SUM(CASE WHEN i.status = 'pending' THEN i.total_amount ELSE 0 END), 0) as pending_amount,
                    COALESCE(SUM(i.total_amount), 0) as total_amount
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                LEFT JOIN invoices i ON s.id = i.student_id
                $whereClause
                GROUP BY s.id
                ORDER BY c.name, c.section, u.name
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report getFeeReport error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getFinancialSummary($year = null) {
        try {
            $year = $year ?: date('Y');
            
            $summary = [];
            
            // Monthly collections
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    SUM(amount) as total
                FROM fee_payments 
                WHERE YEAR(payment_date) = ? AND status = 'paid'
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month
            ");
            $stmt->execute([$year]);
            $summary['monthly_collections'] = $stmt->fetchAll();
            
            // Fee type breakdown
            $stmt = $this->db->prepare("
                SELECT 
                    ft.name,
                    SUM(fp.amount) as total
                FROM fee_payments fp
                JOIN invoices i ON fp.invoice_id = i.id
                JOIN invoice_items ii ON i.id = ii.invoice_id
                JOIN fee_types ft ON ii.fee_type_id = ft.id
                WHERE YEAR(fp.payment_date) = ? AND fp.status = 'paid'
                GROUP BY ft.id
                ORDER BY total DESC
            ");
            $stmt->execute([$year]);
            $summary['fee_type_breakdown'] = $stmt->fetchAll();
            
            // Outstanding amounts
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(total_amount) as total_outstanding
                FROM invoices 
                WHERE status = 'pending'
            ");
            $stmt->execute();
            $summary['outstanding_amount'] = $stmt->fetchColumn();
            
            return $summary;
        } catch (Exception $e) {
            error_log('Report getFinancialSummary error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function exportToCSV($data, $filename) {
        try {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            if (!empty($data)) {
                // Write header
                fputcsv($output, array_keys($data[0]));
                
                // Write data rows
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            }
            
            fclose($output);
            exit();
        } catch (Exception $e) {
            error_log('Report exportToCSV error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAttendanceStats($dateFrom, $dateTo, $classFilter = '') {
        try {
            $params = [$dateFrom, $dateTo];
            $classCondition = '';
            
            if ($classFilter) {
                $classCondition = " AND s.class_id = ?";
                $params[] = $classFilter;
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as total_present,
                    SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                    COUNT(DISTINCT sa.date) as school_days,
                    ROUND((SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as attendance_rate
                FROM student_attendance sa
                JOIN students s ON sa.student_id = s.id
                WHERE sa.date BETWEEN ? AND ?
                $classCondition
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Report getAttendanceStats error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getAttendanceTrends($dateFrom, $dateTo) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    sa.date,
                    SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent
                FROM student_attendance sa
                WHERE sa.date BETWEEN ? AND ?
                GROUP BY sa.date
                ORDER BY sa.date
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report getAttendanceTrends error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getClassAttendance($dateFrom, $dateTo) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.name as class_name,
                    COUNT(DISTINCT s.id) as total_students,
                    SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent,
                    ROUND((SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(sa.id)) * 100, 1) as attendance_rate
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id
                LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.date BETWEEN ? AND ?
                GROUP BY c.id
                ORDER BY c.name
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report getClassAttendance error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getClasses() {
        try {
            $stmt = $this->db->query("SELECT * FROM classes ORDER BY name, section");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report getClasses error: ' . $e->getMessage());
            return [];
        }
    }
}
?>