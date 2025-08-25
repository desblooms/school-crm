<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
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

if (!$input || !isset($input['user_type'], $input['user_id'], $input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$userType = $input['user_type'];
$userId = intval($input['user_id']);
$action = $input['action'];

// Validate user type
$allowedTypes = ['student', 'teacher'];
if (!in_array($userType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit();
}

// Validate action
if (!in_array($action, ['activate', 'deactivate'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Determine table and new status
    $table = $userType === 'student' ? 'students' : 'teachers';
    $newStatus = $action === 'activate' ? 'active' : 'inactive';
    
    // Check if user exists
    $checkStmt = $db->prepare("SELECT id FROM $table WHERE id = ?");
    $checkStmt->execute([$userId]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => ucfirst($userType) . ' not found']);
        exit();
    }
    
    // Update status
    $updateStmt = $db->prepare("UPDATE $table SET status = ? WHERE id = ?");
    $result = $updateStmt->execute([$newStatus, $userId]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($userType) . ' status updated successfully',
            'new_status' => $newStatus
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
} catch (Exception $e) {
    error_log('Toggle user status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>