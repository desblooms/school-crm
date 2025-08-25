<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO events (
                    title, description, event_date, start_time, end_time, 
                    event_type, location, target_audience, created_by, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $result = $stmt->execute([
                $data['title'],
                $data['description'],
                $data['event_date'],
                $data['start_time'],
                $data['end_time'],
                $data['event_type'],
                $data['location'],
                $data['target_audience'],
                $data['created_by']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'id' => $this->db->lastInsertId(),
                    'message' => 'Event created successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create event'];
            }
        } catch (Exception $e) {
            error_log('Event create error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getAll($limit = 50, $offset = 0, $filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['event_type'])) {
                $whereConditions[] = "event_type = ?";
                $params[] = $filters['event_type'];
            }
            
            if (!empty($filters['target_audience'])) {
                $whereConditions[] = "target_audience = ?";
                $params[] = $filters['target_audience'];
            }
            
            if (!empty($filters['month'])) {
                $whereConditions[] = "DATE_FORMAT(event_date, '%Y-%m') = ?";
                $params[] = $filters['month'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->db->prepare("
                SELECT e.*, u.name as created_by_name
                FROM events e
                JOIN users u ON e.created_by = u.id
                $whereClause
                ORDER BY e.event_date ASC, e.start_time ASC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Event getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, u.name as created_by_name
                FROM events e
                JOIN users u ON e.created_by = u.id
                WHERE e.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Event getById error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE events 
                SET title = ?, description = ?, event_date = ?, start_time = ?, 
                    end_time = ?, event_type = ?, location = ?, target_audience = ?, 
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['title'],
                $data['description'],
                $data['event_date'],
                $data['start_time'],
                $data['end_time'],
                $data['event_type'],
                $data['location'],
                $data['target_audience'],
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Event updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update event'];
            }
        } catch (Exception $e) {
            error_log('Event update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM events WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Event deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete event'];
            }
        } catch (Exception $e) {
            error_log('Event delete error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getUpcoming($limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, u.name as created_by_name
                FROM events e
                JOIN users u ON e.created_by = u.id
                WHERE e.event_date >= CURDATE() AND e.status = 'active'
                ORDER BY e.event_date ASC, e.start_time ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Event getUpcoming error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getCalendarEvents($month = null) {
        try {
            $month = $month ?: date('Y-m');
            
            $stmt = $this->db->prepare("
                SELECT id, title, event_date, start_time, end_time, event_type, status
                FROM events 
                WHERE DATE_FORMAT(event_date, '%Y-%m') = ? AND status = 'active'
                ORDER BY event_date ASC
            ");
            $stmt->execute([$month]);
            
            $events = $stmt->fetchAll();
            $calendar = [];
            
            foreach ($events as $event) {
                $date = $event['event_date'];
                if (!isset($calendar[$date])) {
                    $calendar[$date] = [];
                }
                $calendar[$date][] = $event;
            }
            
            return $calendar;
        } catch (Exception $e) {
            error_log('Event getCalendarEvents error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalCount($filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['event_type'])) {
                $whereConditions[] = "event_type = ?";
                $params[] = $filters['event_type'];
            }
            
            if (!empty($filters['target_audience'])) {
                $whereConditions[] = "target_audience = ?";
                $params[] = $filters['target_audience'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM events $whereClause");
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Event getTotalCount error: ' . $e->getMessage());
            return 0;
        }
    }
}
?>