<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Get total students
    $stmt = $db->query("SELECT COUNT(*) FROM students");
    $totalStudents = $stmt->fetchColumn();
    
    // Get total teachers
    $stmt = $db->query("SELECT COUNT(*) FROM teachers");
    $totalTeachers = $stmt->fetchColumn();
    
    // Get monthly fee collection (current month)
    $currentMonth = date('Y-m');
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) 
        FROM fee_payments 
        WHERE DATE_FORMAT(payment_date, '%Y-%m') = ? 
        AND status = 'paid'
    ");
    $stmt->execute([$currentMonth]);
    $monthlyCollection = $stmt->fetchColumn();
    
    // Get pending fees (current month)
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(fs.amount), 0) 
        FROM fee_structure fs
        JOIN students s ON s.class_id = fs.class_id
        LEFT JOIN fee_payments fp ON fp.student_id = s.id 
            AND fp.fee_type_id = fs.fee_type_id 
            AND fp.month_year = ?
            AND fp.status = 'paid'
        WHERE fs.academic_year = ? 
        AND fp.id IS NULL
    ");
    $academicYear = date('Y') . '-' . (date('Y') + 1);
    $stmt->execute([$currentMonth, $academicYear]);
    $pendingFees = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_students' => intval($totalStudents),
            'total_teachers' => intval($totalTeachers),
            'monthly_collection' => number_format($monthlyCollection, 2),
            'pending_fees' => number_format($pendingFees, 2)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard statistics'
    ]);
}
?>