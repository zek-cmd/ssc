<?php
namespace App\Models;

use App\Core\Model;

class Section extends Model {
    protected $table = 'sections';

    public function getAll($search = '') {
        $sql = "SELECT id, name, sort_order FROM {$this->table}";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE name LIKE ?";
            $params[] = "%{$search}%";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = self::db()->prepare("SELECT id, name, sort_order FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    public function nameExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE name = ?";
        $params = [$name];
        if ($excludeId) {
            $sql .= " AND id <> ?";
            $params[] = (int)$excludeId;
        }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createSection($name, $sortOrder) {
        $stmt = self::db()->prepare("INSERT INTO {$this->table} (name, sort_order, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $sortOrder]);
        return (int)self::db()->lastInsertId();
    }

    public function updateSection($id, $name, $sortOrder) {
        $stmt = self::db()->prepare("UPDATE {$this->table} SET name = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$name, $sortOrder, (int)$id]);
    }

    public function deleteSection($id) {
        $stmt = self::db()->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([(int)$id]);
    }
}
