<?php
require_once __DIR__ . '/../config/database.php';

class Expense {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO expenses (
                    category, description, amount, expense_date, payment_method,
                    receipt_number, vendor_name, approved_by, recorded_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['category'],
                $data['description'],
                $data['amount'],
                $data['expense_date'],
                $data['payment_method'],
                $data['receipt_number'],
                $data['vendor_name'],
                $data['approved_by'] ?? null,
                $data['recorded_by']
            ]);
            
            return ['success' => true, 'expense_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getAll($limit = 50, $offset = 0, $search = '', $category = '', $month = '') {
        try {
            $query = "
                SELECT 
                    e.*, 
                    ua.name as approved_by_name,
                    ur.name as recorded_by_name
                FROM expenses e
                LEFT JOIN users ua ON e.approved_by = ua.id
                JOIN users ur ON e.recorded_by = ur.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND (e.description LIKE ? OR e.vendor_name LIKE ? OR e.receipt_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($category)) {
                $query .= " AND e.category = ?";
                $params[] = $category;
            }
            
            if (!empty($month)) {
                $query .= " AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?";
                $params[] = $month;
            }
            
            $query .= " ORDER BY e.expense_date DESC, e.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
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
                    e.*, 
                    ua.name as approved_by_name,
                    ur.name as recorded_by_name
                FROM expenses e
                LEFT JOIN users ua ON e.approved_by = ua.id
                JOIN users ur ON e.recorded_by = ur.id
                WHERE e.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE expenses 
                SET category = ?, description = ?, amount = ?, expense_date = ?,
                    payment_method = ?, receipt_number = ?, vendor_name = ?, approved_by = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['category'],
                $data['description'],
                $data['amount'],
                $data['expense_date'],
                $data['payment_method'],
                $data['receipt_number'],
                $data['vendor_name'],
                $data['approved_by'] ?? null,
                $id
            ]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getTotalCount($search = '', $category = '', $month = '') {
        try {
            $query = "SELECT COUNT(*) FROM expenses WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND (description LIKE ? OR vendor_name LIKE ? OR receipt_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($category)) {
                $query .= " AND category = ?";
                $params[] = $category;
            }
            
            if (!empty($month)) {
                $query .= " AND DATE_FORMAT(expense_date, '%Y-%m') = ?";
                $params[] = $month;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getMonthlyStats($month = null) {
        try {
            $month = $month ?: date('Y-m');
            
            // Total amount
            $stmt = $this->db->prepare("
                SELECT 
                    COALESCE(SUM(amount), 0) as total_amount,
                    COUNT(*) as total_count,
                    COALESCE(AVG(amount), 0) as avg_amount
                FROM expenses 
                WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
            ");
            $stmt->execute([$month]);
            $stats = $stmt->fetch();
            
            // Top category
            $stmt = $this->db->prepare("
                SELECT category
                FROM expenses 
                WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
                GROUP BY category
                ORDER BY SUM(amount) DESC
                LIMIT 1
            ");
            $stmt->execute([$month]);
            $topCategory = $stmt->fetchColumn();
            
            return [
                'total_amount' => $stats['total_amount'],
                'total_count' => $stats['total_count'],
                'avg_amount' => $stats['avg_amount'],
                'top_category' => $topCategory
            ];
        } catch (Exception $e) {
            return [
                'total_amount' => 0,
                'total_count' => 0,
                'avg_amount' => 0,
                'top_category' => null
            ];
        }
    }
    
    public function getCategoryStats($year = null) {
        try {
            $year = $year ?: date('Y');
            
            $stmt = $this->db->prepare("
                SELECT 
                    category,
                    SUM(amount) as total_amount,
                    COUNT(*) as count
                FROM expenses 
                WHERE YEAR(expense_date) = ?
                GROUP BY category
                ORDER BY total_amount DESC
            ");
            $stmt->execute([$year]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getMonthlyTrends($year = null) {
        try {
            $year = $year ?: date('Y');
            
            $stmt = $this->db->prepare("
                SELECT 
                    MONTH(expense_date) as month,
                    SUM(amount) as total_amount,
                    COUNT(*) as count
                FROM expenses 
                WHERE YEAR(expense_date) = ?
                GROUP BY MONTH(expense_date)
                ORDER BY MONTH(expense_date)
            ");
            $stmt->execute([$year]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getTopVendors($limit = 10, $year = null) {
        try {
            $year = $year ?: date('Y');
            
            $stmt = $this->db->prepare("
                SELECT 
                    vendor_name,
                    SUM(amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM expenses 
                WHERE vendor_name IS NOT NULL 
                AND vendor_name != ''
                AND YEAR(expense_date) = ?
                GROUP BY vendor_name
                ORDER BY total_amount DESC
                LIMIT ?
            ");
            $stmt->execute([$year, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function generateReceiptNumber() {
        $date = date('Ymd');
        $stmt = $this->db->prepare("
            SELECT receipt_number FROM expenses 
            WHERE receipt_number LIKE ? 
            ORDER BY receipt_number DESC LIMIT 1
        ");
        $stmt->execute(["EXP$date%"]);
        $lastReceipt = $stmt->fetchColumn();
        
        if ($lastReceipt) {
            $number = intval(substr($lastReceipt, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return "EXP$date" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    public function getUsers($role = null) {
        try {
            $query = "SELECT id, name, role FROM users WHERE status = 'active'";
            $params = [];
            
            if ($role) {
                $query .= " AND role = ?";
                $params[] = $role;
            }
            
            $query .= " ORDER BY name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>