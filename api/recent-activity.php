<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT 
            al.action,
            al.description,
            al.created_at,
            u.name as user_name
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    // Format created_at
    foreach ($activities as &$activity) {
        $activity['created_at'] = date('M d, H:i', strtotime($activity['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load recent activity'
    ]);
}
?>