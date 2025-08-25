<?php
require_once __DIR__ . '/../config/database.php';

class Notice {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notices (
                    title, content, priority, target_audience, 
                    expiry_date, created_by, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $result = $stmt->execute([
                $data['title'],
                $data['content'],
                $data['priority'],
                $data['target_audience'],
                $data['expiry_date'],
                $data['created_by']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'id' => $this->db->lastInsertId(),
                    'message' => 'Notice created successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create notice'];
            }
        } catch (Exception $e) {
            error_log('Notice create error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getAll($limit = 20, $offset = 0, $filters = []) {
        try {
            $whereConditions = ['status = "active"'];
            $params = [];
            
            if (!empty($filters['priority'])) {
                $whereConditions[] = "priority = ?";
                $params[] = $filters['priority'];
            }
            
            if (!empty($filters['target_audience'])) {
                $whereConditions[] = "target_audience = ?";
                $params[] = $filters['target_audience'];
            }
            
            // Only show non-expired notices
            $whereConditions[] = "(expiry_date IS NULL OR expiry_date >= CURDATE())";
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $stmt = $this->db->prepare("
                SELECT n.*, u.name as created_by_name
                FROM notices n
                JOIN users u ON n.created_by = u.id
                $whereClause
                ORDER BY n.priority DESC, n.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Notice getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT n.*, u.name as created_by_name
                FROM notices n
                JOIN users u ON n.created_by = u.id
                WHERE n.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Notice getById error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notices 
                SET title = ?, content = ?, priority = ?, target_audience = ?, 
                    expiry_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['title'],
                $data['content'],
                $data['priority'],
                $data['target_audience'],
                $data['expiry_date'],
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Notice updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update notice'];
            }
        } catch (Exception $e) {
            error_log('Notice update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function delete($id) {
        try {
            // Soft delete by setting status to inactive
            $stmt = $this->db->prepare("UPDATE notices SET status = 'inactive' WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Notice deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete notice'];
            }
        } catch (Exception $e) {
            error_log('Notice delete error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getActive($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT n.*, u.name as created_by_name
                FROM notices n
                JOIN users u ON n.created_by = u.id
                WHERE n.status = 'active' 
                AND (n.expiry_date IS NULL OR n.expiry_date >= CURDATE())
                ORDER BY n.priority DESC, n.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Notice getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByTargetAudience($audience, $limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT n.*, u.name as created_by_name
                FROM notices n
                JOIN users u ON n.created_by = u.id
                WHERE n.status = 'active' 
                AND (n.target_audience = ? OR n.target_audience = 'all')
                AND (n.expiry_date IS NULL OR n.expiry_date >= CURDATE())
                ORDER BY n.priority DESC, n.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$audience, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Notice getByTargetAudience error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalCount($filters = []) {
        try {
            $whereConditions = ['status = "active"'];
            $params = [];
            
            if (!empty($filters['priority'])) {
                $whereConditions[] = "priority = ?";
                $params[] = $filters['priority'];
            }
            
            if (!empty($filters['target_audience'])) {
                $whereConditions[] = "target_audience = ?";
                $params[] = $filters['target_audience'];
            }
            
            $whereConditions[] = "(expiry_date IS NULL OR expiry_date >= CURDATE())";
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM notices $whereClause");
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Notice getTotalCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function cleanupExpired() {
        try {
            $stmt = $this->db->prepare("
                UPDATE notices 
                SET status = 'expired' 
                WHERE expiry_date < CURDATE() AND status = 'active'
            ");
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Expired notices cleaned up'];
        } catch (Exception $e) {
            error_log('Notice cleanupExpired error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cleanup expired notices'];
        }
    }
}
?>