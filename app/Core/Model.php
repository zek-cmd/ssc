<?php
namespace App\Core;

use PDO;

class Model {
    protected static $pdo = null;
    
    public static function initDatabase($config) {
        try {
            self::$pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            // Set session variables
            self::$pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            self::$pdo->exec("SET NAMES {$config['charset']} COLLATE {$config['collation']}");
            self::$pdo->exec("SET time_zone = '+08:00'");
            
            return self::$pdo;
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    // Make this public so services can access it
    public static function db() {
        if (self::$pdo === null) {
            throw new \Exception('Database not initialized. Call Model::initDatabase() first.');
        }
        return self::$pdo;
    }
    
    protected $table;
    protected $primaryKey = 'id';
    
    public function find($id) {
        $stmt = self::db()->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findAll($conditions = [], $orderBy = '') {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute(array_values($data));
    }
    
    public function update($id, $data) {
        $fields = implode(' = ?, ', array_keys($data)) . ' = ?';
        $values = array_values($data);
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET $fields WHERE {$this->primaryKey} = ?";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($values);
    }
    
    public function delete($id) {
        $stmt = self::db()->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
}