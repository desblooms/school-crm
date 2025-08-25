<?php
require_once __DIR__ . '/../config/database.php';

class Accounting {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // ============ INCOME TRACKING ============
    
    public function getFeeCollectionSummary($startDate = null, $endDate = null, $paymentMethod = null) {
        try {
            $startDate = $startDate ?: date('Y-m-01'); // First day of current month
            $endDate = $endDate ?: date('Y-m-t');     // Last day of current month
            
            $query = "
                SELECT 
                    payment_method,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount,
                    ft.name as fee_type_name,
                    ft.id as fee_type_id
                FROM fee_payments fp
                JOIN fee_types ft ON fp.fee_type_id = ft.id
                WHERE fp.payment_date BETWEEN ? AND ?
                AND fp.status = 'paid'
            ";
            
            $params = [$startDate, $endDate];
            
            if ($paymentMethod) {
                $query .= " AND payment_method = ?";
                $params[] = $paymentMethod;
            }
            
            $query .= " GROUP BY payment_method, fee_type_id ORDER BY total_amount DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log('Accounting getFeeCollectionSummary error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getCashFlowSummary($startDate = null, $endDate = null) {
        try {
            $startDate = $startDate ?: date('Y-m-01');
            $endDate = $endDate ?: date('Y-m-t');
            
            // Income from fees
            $incomeStmt = $this->db->prepare("
                SELECT 
                    SUM(amount) as total_income,
                    payment_method,
                    COUNT(*) as transaction_count
                FROM fee_payments 
                WHERE payment_date BETWEEN ? AND ? 
                AND status = 'paid'
                GROUP BY payment_method
            ");
            $incomeStmt->execute([$startDate, $endDate]);
            $income = $incomeStmt->fetchAll();
            
            // Expenses
            $expenseStmt = $this->db->prepare("
                SELECT 
                    SUM(amount) as total_expenses,
                    payment_method,
                    category,
                    COUNT(*) as transaction_count
                FROM expenses 
                WHERE expense_date BETWEEN ? AND ?
                GROUP BY payment_method, category
            ");
            $expenseStmt->execute([$startDate, $endDate]);
            $expenses = $expenseStmt->fetchAll();
            
            // Teacher Payroll
            $payrollStmt = $this->db->prepare("
                SELECT 
                    SUM(net_salary) as total_payroll,
                    payment_method,
                    COUNT(*) as teacher_count
                FROM teacher_payroll 
                WHERE payment_date BETWEEN ? AND ?
                AND status = 'paid'
                GROUP BY payment_method
            ");
            $payrollStmt->execute([$startDate, $endDate]);
            $payroll = $payrollStmt->fetchAll();
            
            return [
                'income' => $income,
                'expenses' => $expenses,
                'payroll' => $payroll,
                'period' => ['start' => $startDate, 'end' => $endDate]
            ];
            
        } catch (Exception $e) {
            error_log('Accounting getCashFlowSummary error: ' . $e->getMessage());
            return [];
        }
    }
    
    // ============ PAYMENT METHOD ANALYSIS ============
    
    public function getPaymentMethodBreakdown($startDate = null, $endDate = null) {
        try {
            $startDate = $startDate ?: date('Y-m-01');
            $endDate = $endDate ?: date('Y-m-t');
            
            $stmt = $this->db->prepare("
                SELECT 
                    payment_method,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    MIN(amount) as min_amount,
                    MAX(amount) as max_amount
                FROM fee_payments 
                WHERE payment_date BETWEEN ? AND ? 
                AND status = 'paid'
                GROUP BY payment_method
                ORDER BY total_amount DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log('Accounting getPaymentMethodBreakdown error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getPendingPayments() {
        try {
            // Outstanding invoices
            $stmt = $this->db->prepare("
                SELECT 
                    i.invoice_number,
                    i.total_amount,
                    i.due_date,
                    s.admission_number,
                    u.name as student_name,
                    c.name as class_name,
                    c.section,
                    DATEDIFF(CURDATE(), i.due_date) as days_overdue
                FROM invoices i
                JOIN students s ON i.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE i.status = 'pending'
                ORDER BY days_overdue DESC, i.due_date ASC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log('Accounting getPendingPayments error: ' . $e->getMessage());
            return [];
        }
    }
    
    // ============ BANK RECONCILIATION ============
    
    public function getBankTransactions($startDate = null, $endDate = null) {
        try {
            $startDate = $startDate ?: date('Y-m-01');
            $endDate = $endDate ?: date('Y-m-t');
            
            // Fee payments via bank
            $feePayments = $this->db->prepare("
                SELECT 
                    'INCOME' as type,
                    'Fee Payment' as category,
                    amount,
                    payment_date as transaction_date,
                    receipt_number as reference,
                    transaction_id,
                    CONCAT('Fee from ', u.name, ' (', s.admission_number, ')') as description
                FROM fee_payments fp
                JOIN students s ON fp.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE fp.payment_method IN ('bank_transfer', 'online') 
                AND fp.payment_date BETWEEN ? AND ?
                AND fp.status = 'paid'
            ");
            $feePayments->execute([$startDate, $endDate]);
            $feeData = $feePayments->fetchAll();
            
            // Expenses via bank
            $expensePayments = $this->db->prepare("
                SELECT 
                    'EXPENSE' as type,
                    category,
                    amount,
                    expense_date as transaction_date,
                    receipt_number as reference,
                    NULL as transaction_id,
                    description
                FROM expenses
                WHERE payment_method IN ('bank_transfer') 
                AND expense_date BETWEEN ? AND ?
            ");
            $expensePayments->execute([$startDate, $endDate]);
            $expenseData = $expensePayments->fetchAll();
            
            // Payroll via bank
            $payrollPayments = $this->db->prepare("
                SELECT 
                    'EXPENSE' as type,
                    'Salary' as category,
                    net_salary as amount,
                    payment_date as transaction_date,
                    CONCAT('PAY-', tp.id) as reference,
                    NULL as transaction_id,
                    CONCAT('Salary for ', u.name, ' - ', tp.month_year) as description
                FROM teacher_payroll tp
                JOIN teachers t ON tp.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE tp.payment_method = 'bank_transfer' 
                AND tp.payment_date BETWEEN ? AND ?
                AND tp.status = 'paid'
            ");
            $payrollPayments->execute([$startDate, $endDate]);
            $payrollData = $payrollPayments->fetchAll();
            
            // Combine all transactions
            $allTransactions = array_merge($feeData, $expenseData, $payrollData);
            
            // Sort by date
            usort($allTransactions, function($a, $b) {
                return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
            });
            
            return $allTransactions;
            
        } catch (Exception $e) {
            error_log('Accounting getBankTransactions error: ' . $e->getMessage());
            return [];
        }
    }
    
    // ============ CASH MANAGEMENT ============
    
    public function getCashTransactions($startDate = null, $endDate = null) {
        try {
            $startDate = $startDate ?: date('Y-m-01');
            $endDate = $endDate ?: date('Y-m-t');
            
            // Cash fee payments
            $stmt = $this->db->prepare("
                SELECT 
                    'INCOME' as type,
                    amount,
                    payment_date as transaction_date,
                    receipt_number as reference,
                    CONCAT('Fee from ', u.name, ' (', s.admission_number, ') - ', ft.name) as description,
                    uc.name as collected_by
                FROM fee_payments fp
                JOIN students s ON fp.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN fee_types ft ON fp.fee_type_id = ft.id
                LEFT JOIN users uc ON fp.collected_by = uc.id
                WHERE fp.payment_method = 'cash' 
                AND fp.payment_date BETWEEN ? AND ?
                AND fp.status = 'paid'
                ORDER BY fp.payment_date DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log('Accounting getCashTransactions error: ' . $e->getMessage());
            return [];
        }
    }
    
    // ============ CHEQUE MANAGEMENT ============
    
    public function getChequeTransactions($startDate = null, $endDate = null) {
        try {
            $startDate = $startDate ?: date('Y-m-01');
            $endDate = $endDate ?: date('Y-m-t');
            
            // Cheque fee payments
            $feeStmt = $this->db->prepare("
                SELECT 
                    'INCOME' as type,
                    amount,
                    payment_date as transaction_date,
                    transaction_id as cheque_number,
                    receipt_number as reference,
                    CONCAT('Fee from ', u.name, ' (', s.admission_number, ') - ', ft.name) as description,
                    'pending' as status
                FROM fee_payments fp
                JOIN students s ON fp.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN fee_types ft ON fp.fee_type_id = ft.id
                WHERE fp.payment_method = 'cheque' 
                AND fp.payment_date BETWEEN ? AND ?
            ");
            $feeStmt->execute([$startDate, $endDate]);
            $feeData = $feeStmt->fetchAll();
            
            // Cheque expenses
            $expenseStmt = $this->db->prepare("
                SELECT 
                    'EXPENSE' as type,
                    amount,
                    expense_date as transaction_date,
                    receipt_number as cheque_number,
                    receipt_number as reference,
                    description,
                    'cleared' as status
                FROM expenses
                WHERE payment_method = 'cheque' 
                AND expense_date BETWEEN ? AND ?
            ");
            $expenseStmt->execute([$startDate, $endDate]);
            $expenseData = $expenseStmt->fetchAll();
            
            return array_merge($feeData, $expenseData);
            
        } catch (Exception $e) {
            error_log('Accounting getChequeTransactions error: ' . $e->getMessage());
            return [];
        }
    }
    
    // ============ FINANCIAL REPORTS ============
    
    public function getMonthlyFinancialSummary($year = null, $month = null) {
        try {
            $year = $year ?: date('Y');
            $month = $month ?: date('m');
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Total income
            $incomeStmt = $this->db->prepare("
                SELECT SUM(amount) as total 
                FROM fee_payments 
                WHERE payment_date BETWEEN ? AND ? 
                AND status = 'paid'
            ");
            $incomeStmt->execute([$startDate, $endDate]);
            $totalIncome = $incomeStmt->fetchColumn() ?: 0;
            
            // Total expenses
            $expenseStmt = $this->db->prepare("
                SELECT SUM(amount) as total 
                FROM expenses 
                WHERE expense_date BETWEEN ? AND ?
            ");
            $expenseStmt->execute([$startDate, $endDate]);
            $totalExpenses = $expenseStmt->fetchColumn() ?: 0;
            
            // Total payroll
            $payrollStmt = $this->db->prepare("
                SELECT SUM(net_salary) as total 
                FROM teacher_payroll 
                WHERE payment_date BETWEEN ? AND ?
                AND status = 'paid'
            ");
            $payrollStmt->execute([$startDate, $endDate]);
            $totalPayroll = $payrollStmt->fetchColumn() ?: 0;
            
            return [
                'period' => ['year' => $year, 'month' => $month],
                'income' => $totalIncome,
                'expenses' => $totalExpenses,
                'payroll' => $totalPayroll,
                'net_profit' => $totalIncome - $totalExpenses - $totalPayroll,
                'formatted_period' => date('F Y', strtotime($startDate))
            ];
            
        } catch (Exception $e) {
            error_log('Accounting getMonthlyFinancialSummary error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function getYearlyComparison($years = null) {
        try {
            $years = $years ?: [date('Y') - 1, date('Y')];
            $comparison = [];
            
            foreach ($years as $year) {
                $comparison[$year] = [];
                
                for ($month = 1; $month <= 12; $month++) {
                    $monthData = $this->getMonthlyFinancialSummary($year, str_pad($month, 2, '0', STR_PAD_LEFT));
                    $comparison[$year][$month] = $monthData;
                }
            }
            
            return $comparison;
            
        } catch (Exception $e) {
            error_log('Accounting getYearlyComparison error: ' . $e->getMessage());
            return [];
        }
    }
}