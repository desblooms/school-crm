<?php
require_once __DIR__ . '/../config/database.php';

class Invoice {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($studentId, $feeTypes, $generatedBy, $dueDate = null) {
        try {
            $this->db->beginTransaction();
            
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();
            $issueDate = date('Y-m-d');
            $dueDate = $dueDate ?: date('Y-m-d', strtotime('+30 days'));
            
            // Calculate total amount
            $totalAmount = 0;
            foreach ($feeTypes as $feeType) {
                $totalAmount += $feeType['amount'];
            }
            
            // Create invoice record
            $stmt = $this->db->prepare("
                INSERT INTO invoices (
                    student_id, invoice_number, issue_date, due_date, 
                    total_amount, generated_by
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $studentId, $invoiceNumber, $issueDate, $dueDate,
                $totalAmount, $generatedBy
            ]);
            
            $invoiceId = $this->db->lastInsertId();
            
            // Add invoice items
            $stmt = $this->db->prepare("
                INSERT INTO invoice_items (invoice_id, fee_type_id, description, amount)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($feeTypes as $feeType) {
                // Ensure amount is not null
                $amount = isset($feeType['amount']) ? floatval($feeType['amount']) : 0;
                if ($amount <= 0) {
                    throw new Exception("Invalid amount for fee type: " . ($feeType['fee_type_name'] ?? 'Unknown'));
                }
                
                $stmt->execute([
                    $invoiceId,
                    $feeType['fee_type_id'],
                    $feeType['description'] ?? ($feeType['fee_type_name'] ?? 'Fee Payment'),
                    $amount
                ]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getAll($limit = 50, $offset = 0, $search = '', $status = '', $month = '') {
        try {
            $query = "
                SELECT 
                    i.*, s.admission_number, u.name as student_name,
                    c.name as class_name, c.section,
                    ug.name as generated_by_name
                FROM invoices i
                JOIN students s ON i.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                JOIN users ug ON i.generated_by = ug.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND (i.invoice_number LIKE ? OR u.name LIKE ? OR s.admission_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($status)) {
                $query .= " AND i.status = ?";
                $params[] = $status;
            }
            
            if (!empty($month)) {
                $query .= " AND DATE_FORMAT(i.issue_date, '%Y-%m') = ?";
                $params[] = $month;
            }
            
            $query .= " ORDER BY i.issue_date DESC, i.created_at DESC LIMIT ? OFFSET ?";
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
                    i.*, s.admission_number, u.name as student_name, u.email as student_email,
                    s.guardian_name, s.guardian_email, s.guardian_phone,
                    c.name as class_name, c.section, s.roll_number,
                    ug.name as generated_by_name
                FROM invoices i
                JOIN students s ON i.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                JOIN users ug ON i.generated_by = ug.id
                WHERE i.id = ?
            ");
            $stmt->execute([$id]);
            $invoice = $stmt->fetch();
            
            if ($invoice) {
                // Get invoice items
                $stmt = $this->db->prepare("
                    SELECT ii.*, ft.name as fee_type_name
                    FROM invoice_items ii
                    JOIN fee_types ft ON ii.fee_type_id = ft.id
                    WHERE ii.invoice_id = ?
                ");
                $stmt->execute([$id]);
                $invoice['items'] = $stmt->fetchAll();
            }
            
            return $invoice;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function updateStatus($id, $status, $paidAmount = null) {
        try {
            $query = "UPDATE invoices SET status = ?";
            $params = [$status];
            
            if ($paidAmount !== null) {
                $query .= ", paid_amount = ?";
                $params[] = $paidAmount;
            }
            
            $query .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function markAsPaid($id, $paidAmount) {
        try {
            $stmt = $this->db->prepare("
                UPDATE invoices 
                SET status = 'paid', paid_amount = ?
                WHERE id = ?
            ");
            $stmt->execute([$paidAmount, $id]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Delete invoice items first
            $stmt = $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt->execute([$id]);
            
            // Delete invoice
            $stmt = $this->db->prepare("DELETE FROM invoices WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function generateInvoiceNumber() {
        $year = date('Y');
        $month = date('m');
        $prefix = "INV$year$month";
        
        $stmt = $this->db->prepare("
            SELECT invoice_number FROM invoices 
            WHERE invoice_number LIKE ? 
            ORDER BY invoice_number DESC LIMIT 1
        ");
        $stmt->execute(["$prefix%"]);
        $lastInvoice = $stmt->fetchColumn();
        
        if ($lastInvoice) {
            $number = intval(substr($lastInvoice, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    public function generatePDF($invoiceId) {
        $invoice = $this->getById($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found'];
        }
        
        try {
            // Create PDF using TCPDF or similar library
            $pdf = $this->createPDF($invoice);
            
            // Save PDF file
            $filename = 'invoice_' . $invoice['invoice_number'] . '.pdf';
            $filepath = INVOICE_PATH . $filename;
            
            file_put_contents($filepath, $pdf);
            
            // Update invoice with file path
            $stmt = $this->db->prepare("UPDATE invoices SET file_path = ? WHERE id = ?");
            $stmt->execute([$filename, $invoiceId]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function createPDF($invoice) {
        // Simple HTML to PDF conversion (in production, use proper PDF library)
        $html = $this->generateInvoiceHTML($invoice);
        
        // For now, return HTML (in production, convert to PDF)
        return $html;
    }
    
    public function generateInvoiceHTML($invoice) {
        $school_name = 'Sample School'; // Get from settings
        $school_address = '123 Education Street, Learning City';
        $school_phone = '+1-234-567-8900';
        $school_email = 'info@school.com';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Invoice ' . htmlspecialchars($invoice['invoice_number']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .school-name { font-size: 24px; font-weight: bold; color: #333; }
                .school-details { font-size: 12px; color: #666; margin-top: 10px; }
                .invoice-details { margin: 20px 0; }
                .invoice-title { font-size: 18px; font-weight: bold; color: #333; text-align: center; margin: 20px 0; }
                .details-table { width: 100%; margin-bottom: 20px; }
                .details-table td { padding: 5px 0; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                .items-table th { background-color: #f5f5f5; font-weight: bold; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
                .status { padding: 5px 10px; border-radius: 3px; font-weight: bold; }
                .status-pending { background-color: #fff3cd; color: #856404; }
                .status-paid { background-color: #d4edda; color: #155724; }
                .status-overdue { background-color: #f8d7da; color: #721c24; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="school-name">' . htmlspecialchars($school_name) . '</div>
                <div class="school-details">
                    ' . htmlspecialchars($school_address) . '<br>
                    Phone: ' . htmlspecialchars($school_phone) . ' | Email: ' . htmlspecialchars($school_email) . '
                </div>
            </div>
            
            <div class="invoice-title">STUDENT FEE INVOICE</div>
            
            <table class="details-table">
                <tr>
                    <td><strong>Invoice Number:</strong></td>
                    <td>' . htmlspecialchars($invoice['invoice_number']) . '</td>
                    <td><strong>Issue Date:</strong></td>
                    <td>' . date('d/m/Y', strtotime($invoice['issue_date'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Due Date:</strong></td>
                    <td>' . date('d/m/Y', strtotime($invoice['due_date'])) . '</td>
                    <td><strong>Status:</strong></td>
                    <td><span class="status status-' . $invoice['status'] . '">' . strtoupper($invoice['status']) . '</span></td>
                </tr>
            </table>
            
            <table class="details-table">
                <tr>
                    <td colspan="4"><strong>Student Details:</strong></td>
                </tr>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>' . htmlspecialchars($invoice['student_name']) . '</td>
                    <td><strong>Admission No:</strong></td>
                    <td>' . htmlspecialchars($invoice['admission_number']) . '</td>
                </tr>
                <tr>
                    <td><strong>Class:</strong></td>
                    <td>' . htmlspecialchars($invoice['class_name'] . ' - ' . $invoice['section']) . '</td>
                    <td><strong>Roll No:</strong></td>
                    <td>' . htmlspecialchars($invoice['roll_number'] ?: 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Guardian:</strong></td>
                    <td>' . htmlspecialchars($invoice['guardian_name']) . '</td>
                    <td><strong>Contact:</strong></td>
                    <td>' . htmlspecialchars($invoice['guardian_phone']) . '</td>
                </tr>
            </table>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Fee Type</th>
                        <th>Description</th>
                        <th>Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>';
        
        $sno = 1;
        foreach ($invoice['items'] as $item) {
            $html .= '<tr>
                <td>' . $sno++ . '</td>
                <td>' . htmlspecialchars($item['fee_type_name']) . '</td>
                <td>' . htmlspecialchars($item['description'] ?: 'Standard fee') . '</td>
                <td>₹' . number_format($item['amount'], 2) . '</td>
            </tr>';
        }
        
        $html .= '
                    <tr class="total-row">
                        <td colspan="3"><strong>Total Amount</strong></td>
                        <td><strong>₹' . number_format($invoice['total_amount'], 2) . '</strong></td>
                    </tr>';
        
        if ($invoice['paid_amount'] > 0) {
            $html .= '
                    <tr>
                        <td colspan="3">Paid Amount</td>
                        <td>₹' . number_format($invoice['paid_amount'], 2) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3"><strong>Balance Due</strong></td>
                        <td><strong>₹' . number_format($invoice['total_amount'] - $invoice['paid_amount'], 2) . '</strong></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p><strong>Payment Instructions:</strong></p>
                <p>Please pay by ' . date('d/m/Y', strtotime($invoice['due_date'])) . ' to avoid late fees.</p>
                <p>For any queries, please contact the accounts department.</p>
                <br>
                <p>Thank you for your prompt payment!</p>
                <p style="margin-top: 20px; font-size: 10px;">
                    Generated on ' . date('d/m/Y H:i:s') . ' by ' . htmlspecialchars($invoice['generated_by_name']) . '
                </p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    public function sendEmail($invoiceId, $toEmail = null) {
        $invoice = $this->getById($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found'];
        }
        
        $toEmail = $toEmail ?: ($invoice['guardian_email'] ?: $invoice['student_email']);
        
        if (!$toEmail) {
            return ['success' => false, 'message' => 'No email address found'];
        }
        
        try {
            // Generate PDF if not exists
            if (!$invoice['file_path']) {
                $pdfResult = $this->generatePDF($invoiceId);
                if (!$pdfResult['success']) {
                    return ['success' => false, 'message' => 'Failed to generate PDF'];
                }
                $invoice['file_path'] = $pdfResult['filename'];
            }
            
            // Send email using PHP mail or PHPMailer
            $subject = 'Fee Invoice - ' . $invoice['invoice_number'] . ' - ' . $invoice['student_name'];
            $message = $this->generateEmailBody($invoice);
            $headers = array(
                'From' => 'accounts@school.com',
                'Content-Type' => 'text/html; charset=UTF-8'
            );
            
            // Attachment would be added here in production
            $emailSent = mail($toEmail, $subject, $message, $headers);
            
            if ($emailSent) {
                // Mark as email sent
                $stmt = $this->db->prepare("UPDATE invoices SET email_sent = 1 WHERE id = ?");
                $stmt->execute([$invoiceId]);
                
                return ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to send email'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function generateEmailBody($invoice) {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <h2>Fee Invoice - ' . htmlspecialchars($invoice['student_name']) . '</h2>
            
            <p>Dear ' . htmlspecialchars($invoice['guardian_name'] ?: 'Parent/Guardian') . ',</p>
            
            <p>Please find attached the fee invoice for <strong>' . htmlspecialchars($invoice['student_name']) . '</strong> (' . htmlspecialchars($invoice['admission_number']) . ').</p>
            
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Invoice Details:</h3>
                <p><strong>Invoice Number:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</p>
                <p><strong>Issue Date:</strong> ' . date('d/m/Y', strtotime($invoice['issue_date'])) . '</p>
                <p><strong>Due Date:</strong> ' . date('d/m/Y', strtotime($invoice['due_date'])) . '</p>
                <p><strong>Total Amount:</strong> ₹' . number_format($invoice['total_amount'], 2) . '</p>
                <p><strong>Status:</strong> ' . strtoupper($invoice['status']) . '</p>
            </div>
            
            <p>Please ensure payment is made by the due date to avoid any late fees.</p>
            
            <p>If you have any questions regarding this invoice, please contact our accounts department.</p>
            
            <p>Thank you for your prompt attention to this matter.</p>
            
            <p>Best regards,<br>
            <strong>Accounts Department</strong><br>
            Sample School</p>
        </body>
        </html>';
    }
    
    public function getTotalCount($search = '', $status = '', $month = '') {
        try {
            $query = "
                SELECT COUNT(*) 
                FROM invoices i
                JOIN students s ON i.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE 1=1
            ";
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND (i.invoice_number LIKE ? OR u.name LIKE ? OR s.admission_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($status)) {
                $query .= " AND i.status = ?";
                $params[] = $status;
            }
            
            if (!empty($month)) {
                $query .= " AND DATE_FORMAT(i.issue_date, '%Y-%m') = ?";
                $params[] = $month;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getInvoiceItems($invoiceId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ii.*, 
                    ft.name as fee_type_name,
                    ft.description as fee_type_description
                FROM invoice_items ii
                JOIN fee_types ft ON ii.fee_type_id = ft.id
                WHERE ii.invoice_id = ?
                ORDER BY ii.id
            ");
            $stmt->execute([$invoiceId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Invoice getInvoiceItems error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getStats() {
        try {
            $stats = [];
            
            // Total invoices
            $stmt = $this->db->query("SELECT COUNT(*) FROM invoices");
            $stats['total_invoices'] = $stmt->fetchColumn();
            
            // Paid invoices
            $stmt = $this->db->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid'");
            $stats['paid_invoices'] = $stmt->fetchColumn();
            
            // Pending invoices
            $stmt = $this->db->query("SELECT COUNT(*) FROM invoices WHERE status = 'pending'");
            $stats['pending_invoices'] = $stmt->fetchColumn();
            
            // Overdue invoices
            $stmt = $this->db->query("SELECT COUNT(*) FROM invoices WHERE status = 'overdue' OR (status = 'pending' AND due_date < CURDATE())");
            $stats['overdue_invoices'] = $stmt->fetchColumn();
            
            // Total amount
            $stmt = $this->db->query("SELECT SUM(total_amount) FROM invoices");
            $stats['total_amount'] = $stmt->fetchColumn() ?: 0;
            
            // Paid amount
            $stmt = $this->db->query("SELECT SUM(paid_amount) FROM invoices");
            $stats['paid_amount'] = $stmt->fetchColumn() ?: 0;
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
}
?>