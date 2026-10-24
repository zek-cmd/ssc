<?php
namespace App\Services;

use App\Core\Model;

class AuditService {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Model::db();
    }
    
    public function log($entityType, $action, $entityId, $oldValues = null, $newValues = null, $description = '') {
        $adminData = SessionService::getAdminData();
        $adminId = $adminData['user_id'] ?? null;
        if (!$adminId) return;
        
        $ip = SecurityService::getClientIP();
        
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, old_values, new_values, description, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $adminId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $description,
            $ip
        ]);
    }
    
    public function getLogs($page = 1, $limit = 15, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $countSql = "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.admin_id = u.id";
        $dataSql = "SELECT a.*, u.name as admin_name FROM audit_logs a LEFT JOIN users u ON a.admin_id = u.id";
        
        if (!empty($search)) {
            $where = " WHERE a.entity_type LIKE ? OR a.description LIKE ? OR u.name LIKE ? OR a.action LIKE ?";
            $countSql .= $where;
            $dataSql .= $where;
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $dataSql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        
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
    
    public function getTotalCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM audit_logs");
        return (int)$stmt->fetchColumn();
    }
    
    public function getLogsForExport($offset, $limit) {
        $stmt = $this->pdo->prepare("
            SELECT al.*, u.name as admin_name
            FROM audit_logs al
            LEFT JOIN users u ON al.admin_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $data = $stmt->fetchAll();
        
        return ['data' => $data];
    }
    
    public function clearLogs() {
        $stmt = $this->pdo->prepare("DELETE FROM audit_logs");
        $stmt->execute();
        return $stmt->rowCount();
    }
}