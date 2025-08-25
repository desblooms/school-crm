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

if (!$input || !isset($input['user_type'], $input['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$userType = $input['user_type'];
$userId = intval($input['user_id']);

// Validate user type
$allowedTypes = ['student', 'teacher'];
if (!in_array($userType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Determine table and ID field for new password
    $table = $userType === 'student' ? 'students' : 'teachers';
    $idField = $userType === 'student' ? 'admission_number' : 'employee_id';
    
    // Get user data
    $userStmt = $db->prepare("SELECT id, $idField FROM $table WHERE id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch();
    
    if (!$userData) {
        echo json_encode(['success' => false, 'message' => ucfirst($userType) . ' not found']);
        exit();
    }
    
    // New password will be the admission_number or employee_id
    $newPassword = $userData[$idField];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password in users table
    $updateStmt = $db->prepare("
        UPDATE users 
        SET password = ? 
        WHERE user_type = ? AND user_id = ?
    ");
    $result = $updateStmt->execute([$hashedPassword, $userType, $userId]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Password reset successfully. New password is the ' . str_replace('_', ' ', $idField),
            'new_password' => $newPassword
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
    }
    
} catch (Exception $e) {
    error_log('Reset password error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>