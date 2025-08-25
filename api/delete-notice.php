<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Notice.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !hasPermission('manage_notices')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['notice_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Notice ID is required']);
    exit();
}

$noticeId = intval($input['notice_id']);

try {
    $notice = new Notice();
    $result = $notice->delete($noticeId);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Notice deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log('Delete notice error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>