<?php
require_once __DIR__ . '/../config/database.php';

class Fee {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getFeeTypes() {
        try {
            $stmt = $this->db->query("SELECT * FROM fee_types ORDER BY name");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getFeeStructure($classId = null, $academicYear = null) {
        try {
            $academicYear = $academicYear ?: date('Y') . '-' . (date('Y') + 1);
            
            $query = "
                SELECT 
                    fs.*, ft.name as fee_type_name, ft.description, ft.is_mandatory,
                    c.name as class_name, c.section
                FROM fee_structure fs
                JOIN fee_types ft ON fs.fee_type_id = ft.id
                JOIN classes c ON fs.class_id = c.id
                WHERE fs.academic_year = ?
            ";
            
            $params = [$academicYear];
            
            if ($classId) {
                $query .= " AND fs.class_id = ?";
                $params[] = $classId;
            }
            
            $query .= " ORDER BY c.name, c.section, ft.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function setFeeStructure($classId, $feeTypeId, $amount, $dueDateDay, $academicYear) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fee_structure (class_id, fee_type_id, amount, due_date_day, academic_year)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount), 
                due_date_day = VALUES(due_date_day)
            ");
            
            $stmt->execute([$classId, $feeTypeId, $amount, $dueDateDay, $academicYear]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function collectFee($studentId, $feeTypeId, $amount, $paymentMethod, $collectedBy, $monthYear, $transactionId = null, $remarks = '') {
        try {
            $this->db->beginTransaction();
            
            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();
            
            // Insert payment record with explicit status
            $stmt = $this->db->prepare("
                INSERT INTO fee_payments (
                    student_id, fee_type_id, amount, payment_method, transaction_id,
                    payment_date, month_year, collected_by, receipt_number, remarks, status
                ) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, 'paid')
            ");
            
            $result = $stmt->execute([
                $studentId, $feeTypeId, $amount, $paymentMethod, $transactionId,
                $monthYear, $collectedBy, $receiptNumber, $remarks
            ]);
            
            if (!$result) {
                throw new Exception('Failed to insert payment record');
            }
            
            $paymentId = $this->db->lastInsertId();
            
            // Auto-generate invoice after successful payment
            $invoiceResult = $this->generateInvoiceForPayment($studentId, $feeTypeId, $amount, $receiptNumber, $collectedBy);
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'payment_id' => $paymentId, 
                'receipt_number' => $receiptNumber,
                'invoice_number' => $invoiceResult['invoice_number'] ?? null,
                'message' => 'Fee collected successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Fee collectFee error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
        }
    }
    
    public function getStudentFeeStatus($studentId, $academicYear = null) {
        try {
            $academicYear = $academicYear ?: date('Y') . '-' . (date('Y') + 1);
            
            $stmt = $this->db->prepare("
                SELECT 
                    s.id as student_id, s.admission_number, u.name as student_name,
                    c.name as class_name, c.section,
                    fs.fee_type_id, ft.name as fee_type_name, fs.amount as fee_amount,
                    COALESCE(SUM(fp.amount), 0) as paid_amount,
                    (fs.amount - COALESCE(SUM(fp.amount), 0)) as pending_amount,
                    CASE 
                        WHEN COALESCE(SUM(fp.amount), 0) >= fs.amount THEN 'Paid'
                        WHEN COALESCE(SUM(fp.amount), 0) > 0 THEN 'Partial'
                        ELSE 'Pending'
                    END as status
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                JOIN fee_structure fs ON c.id = fs.class_id
                JOIN fee_types ft ON fs.fee_type_id = ft.id
                LEFT JOIN fee_payments fp ON s.id = fp.student_id AND fs.fee_type_id = fp.fee_type_id
                WHERE s.id = ? AND fs.academic_year = ?
                GROUP BY s.id, fs.fee_type_id
                ORDER BY ft.name
            ");
            
            $stmt->execute([$studentId, $academicYear]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getPaymentHistory($studentId = null, $limit = 50, $offset = 0) {
        try {
            $query = "
                SELECT 
                    fp.*, s.admission_number, u.name as student_name,
                    ft.name as fee_type_name, uc.name as collected_by_name
                FROM fee_payments fp
                JOIN students s ON fp.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN fee_types ft ON fp.fee_type_id = ft.id
                JOIN users uc ON fp.collected_by = uc.id
            ";
            
            $params = [];
            
            if ($studentId) {
                $query .= " WHERE fp.student_id = ?";
                $params[] = $studentId;
            }
            
            $query .= " ORDER BY fp.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getDashboardStats($monthYear = null) {
        try {
            $monthYear = $monthYear ?: date('Y-m');
            
            // Total collection for the month
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_collection
                FROM fee_payments 
                WHERE DATE_FORMAT(payment_date, '%Y-%m') = ? AND status = 'paid'
            ");
            $stmt->execute([$monthYear]);
            $totalCollection = $stmt->fetchColumn();
            
            // Pending fees for current academic year
            $academicYear = date('Y') . '-' . (date('Y') + 1);
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(fs.amount - COALESCE(paid.amount, 0)), 0) as pending_fees
                FROM fee_structure fs
                JOIN students s ON s.class_id = fs.class_id
                LEFT JOIN (
                    SELECT student_id, fee_type_id, SUM(amount) as amount
                    FROM fee_payments 
                    WHERE status = 'paid'
                    GROUP BY student_id, fee_type_id
                ) paid ON paid.student_id = s.id AND paid.fee_type_id = fs.fee_type_id
                WHERE fs.academic_year = ? AND fs.amount > COALESCE(paid.amount, 0)
            ");
            $stmt->execute([$academicYear]);
            $pendingFees = $stmt->fetchColumn();
            
            // Payment count for today
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as today_payments
                FROM fee_payments 
                WHERE DATE(payment_date) = CURDATE()
            ");
            $stmt->execute();
            $todayPayments = $stmt->fetchColumn();
            
            return [
                'total_collection' => $totalCollection,
                'pending_fees' => $pendingFees,
                'today_payments' => $todayPayments
            ];
        } catch (Exception $e) {
            return [
                'total_collection' => 0,
                'pending_fees' => 0,
                'today_payments' => 0
            ];
        }
    }
    
    public function generateReceiptNumber() {
        $date = date('Ymd');
        $stmt = $this->db->prepare("
            SELECT receipt_number FROM fee_payments 
            WHERE receipt_number LIKE ? 
            ORDER BY receipt_number DESC LIMIT 1
        ");
        $stmt->execute(["RCT$date%"]);
        $lastReceipt = $stmt->fetchColumn();
        
        if ($lastReceipt) {
            $number = intval(substr($lastReceipt, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return "RCT$date" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    public function getClasses() {
        try {
            $stmt = $this->db->query("SELECT * FROM classes ORDER BY name, section");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getStudentsByClass($classId) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.id, s.admission_number, u.name, s.roll_number
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.class_id = ?
                ORDER BY s.roll_number, u.name
            ");
            $stmt->execute([$classId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getOverdueFees($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    s.id as student_id, s.admission_number, u.name as student_name,
                    c.name as class_name, c.section, u.phone, s.guardian_phone,
                    ft.name as fee_type_name, fs.amount,
                    DATEDIFF(CURDATE(), DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-', fs.due_date_day))) as days_overdue
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                JOIN fee_structure fs ON c.id = fs.class_id
                JOIN fee_types ft ON fs.fee_type_id = ft.id
                LEFT JOIN fee_payments fp ON s.id = fp.student_id 
                    AND fs.fee_type_id = fp.fee_type_id 
                    AND fp.status = 'paid'
                WHERE fp.id IS NULL
                AND DATEDIFF(CURDATE(), DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-', fs.due_date_day))) >= ?
                AND fs.academic_year = ?
                ORDER BY days_overdue DESC, u.name
            ");
            
            $academicYear = date('Y') . '-' . (date('Y') + 1);
            $stmt->execute([$days, $academicYear]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getPaymentByReceipt($receiptNumber) {
        try {
            $stmt = $this->db->prepare("
                SELECT fp.*, ft.name as fee_type_name, u.name as collected_by_name
                FROM fee_payments fp
                LEFT JOIN fee_types ft ON fp.fee_type_id = ft.id
                LEFT JOIN users u ON fp.collected_by = u.id
                WHERE fp.receipt_number = ?
            ");
            $stmt->execute([$receiptNumber]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function getMonthlyPayments($studentId, $monthYear) {
        try {
            $stmt = $this->db->prepare("
                SELECT fp.*, ft.name as fee_type_name
                FROM fee_payments fp
                LEFT JOIN fee_types ft ON fp.fee_type_id = ft.id
                WHERE fp.student_id = ? AND fp.month_year = ?
                ORDER BY fp.payment_date DESC
            ");
            $stmt->execute([$studentId, $monthYear]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function generateInvoiceForPayment($studentId, $feeTypeId, $amount, $receiptNumber, $collectedBy) {
        try {
            require_once __DIR__ . '/Invoice.php';
            $invoice = new Invoice();
            
            // Get fee type name
            $stmt = $this->db->prepare("SELECT name FROM fee_types WHERE id = ?");
            $stmt->execute([$feeTypeId]);
            $feeTypeName = $stmt->fetchColumn();
            
            // Prepare fee data for invoice
            $feeData = [[
                'fee_type_id' => $feeTypeId,
                'fee_type_name' => $feeTypeName,
                'amount' => $amount,
                'description' => "Payment via receipt: $receiptNumber"
            ]];
            
            return $invoice->create($studentId, $feeData, $collectedBy);
            
        } catch (Exception $e) {
            error_log('Auto-invoice generation error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>