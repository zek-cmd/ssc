<?php
namespace App\Models;

use App\Core\Model;

class Candidate extends Model {
    protected $table = 'candidates';
    protected $primaryKey = 'id';
    
    public function getAll($page = 1, $limit = 15, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} c LEFT JOIN positions p ON c.position_id = p.id LEFT JOIN partylists pl ON c.partylist_id = pl.id";
        $dataSql = "SELECT c.*, p.name as position_name, pl.name as partylist_name, pl.badge_color as partylist_badge_color, pl.badge_text_color as partylist_badge_text_color FROM {$this->table} c LEFT JOIN positions p ON c.position_id = p.id LEFT JOIN partylists pl ON c.partylist_id = pl.id";
        
        if (!empty($search)) {
            $where = " WHERE c.name LIKE ? OR c.platform LIKE ? OR p.name LIKE ?";
            $countSql .= $where;
            $dataSql .= $where;
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $dataSql .= " ORDER BY p.sort_order ASC, c.id ASC LIMIT ? OFFSET ?";
        
        $total = self::db()->prepare($countSql);
        $total->execute($params);
        $totalCount = $total->fetchColumn();
        
        $dataParams = array_merge($params, [$limit, $offset]);
        $stmt = self::db()->prepare($dataSql);
        $stmt->execute($dataParams);
        $data = $stmt->fetchAll();
        
        return ['data' => $data, 'total' => (int)$totalCount, 'page' => $page, 'total_pages' => ceil($totalCount / $limit)];
    }
}