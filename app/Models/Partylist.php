<?php
namespace App\Models;

use App\Core\Model;

class Partylist extends Model {
    protected $table = 'partylists';
    protected $primaryKey = 'id';
    
    public function getAll($page = 1, $limit = 15, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        $dataSql = "SELECT * FROM {$this->table}";
        
        if (!empty($search)) {
            $where = " WHERE name LIKE ? OR description LIKE ?";
            $countSql .= $where;
            $dataSql .= $where;
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $dataSql .= " ORDER BY sort_order ASC LIMIT ? OFFSET ?";
        
        $total = self::db()->prepare($countSql);
        $total->execute($params);
        $totalCount = $total->fetchColumn();
        
        $dataParams = array_merge($params, [$limit, $offset]);
        $stmt = self::db()->prepare($dataSql);
        $stmt->execute($dataParams);
        $data = $stmt->fetchAll();
        
        return ['data' => $data, 'total' => (int)$totalCount, 'page' => $page, 'total_pages' => ceil($totalCount / $limit)];
    }
    
    public function getAllOptions() {
        $stmt = self::db()->query("SELECT id, name, sort_order, badge_color, badge_text_color FROM {$this->table} ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    }
}