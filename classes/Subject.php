<?php
require_once __DIR__ . '/../config/database.php';

class Subject {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAllSubjects() {
        try {
            $stmt = $this->db->query("SELECT * FROM subjects ORDER BY name");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Subject getAllSubjects error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getSubjectById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM subjects WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Subject getSubjectById error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subjects (name, code, description) 
                VALUES (?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['name'],
                $data['code'],
                $data['description'] ?? ''
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'id' => $this->db->lastInsertId(),
                    'message' => 'Subject created successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create subject'];
            }
        } catch (Exception $e) {
            error_log('Subject create error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE subjects 
                SET name = ?, code = ?, description = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['name'],
                $data['code'],
                $data['description'] ?? '',
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Subject updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update subject'];
            }
        } catch (Exception $e) {
            error_log('Subject update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function delete($id) {
        try {
            // Check if subject is assigned to any teacher
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE subject_id = ?");
            $checkStmt->execute([$id]);
            $assignmentCount = $checkStmt->fetchColumn();
            
            if ($assignmentCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete subject that is assigned to teachers'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM subjects WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Subject deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete subject'];
            }
        } catch (Exception $e) {
            error_log('Subject delete error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getSubjectsByClass($classId) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, ts.teacher_id, t.name as teacher_name, t.employee_id
                FROM subjects s
                LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id AND ts.class_id = ?
                LEFT JOIN teachers t ON ts.teacher_id = t.id
                ORDER BY s.name
            ");
            $stmt->execute([$classId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Subject getSubjectsByClass error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function isCodeUnique($code, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM subjects WHERE code = ?";
            $params = [$code];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log('Subject isCodeUnique error: ' . $e->getMessage());
            return false;
        }
    }
}