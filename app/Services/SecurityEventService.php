<?php
namespace App\Services;

use App\Core\Model;

class SecurityEventService {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Model::db();
    }
    
    public function log($eventType, $description) {
        $adminData = SessionService::getAdminData();
        $adminId = $adminData['user_id'] ?? 0;
        
        $ip = SecurityService::getClientIP();
        
        $stmt = $this->pdo->prepare("
            INSERT INTO security_events (admin_id, event_type, description, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $adminId,
            $eventType,
            $description,
            $ip
        ]);
    }
    
    public function getTotalCount($search = '') {
        $sql = "SELECT COUNT(*) FROM security_events se LEFT JOIN users u ON se.admin_id = u.id";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE se.description LIKE ? OR se.event_type LIKE ? OR u.name LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
    
    public function getEvents($page = 1, $limit = 15, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $countSql = "SELECT COUNT(*) FROM security_events se LEFT JOIN users u ON se.admin_id = u.id";
        $dataSql = "SELECT se.*, u.name as admin_name FROM security_events se LEFT JOIN users u ON se.admin_id = u.id";
        
        if (!empty($search)) {
            $where = " WHERE se.description LIKE ? OR se.event_type LIKE ? OR u.name LIKE ?";
            $countSql .= $where;
            $dataSql .= $where;
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        $dataSql .= " ORDER BY se.created_at DESC LIMIT ? OFFSET ?";
        
        $total = $this->pdo->prepare($countSql);
        $total->execute($params);
        $totalCount = $total->fetchColumn();
        
        $dataParams = array_merge($params, [$limit, $offset]);
        $stmt = $this->pdo->prepare($dataSql);
        $stmt->execute($dataParams);
        $data = $stmt->fetchAll();
        
        foreach ($data as &$row) {
            $row['created_at'] = date('F j, Y g:i A', strtotime($row['created_at']));
        }
        
        return [
            'data' => $data,
            'total' => (int)$totalCount,
            'page' => $page,
            'total_pages' => ceil($totalCount / $limit)
        ];
    }
}