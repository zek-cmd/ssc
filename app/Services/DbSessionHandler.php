<?php
// app/Services/DbSessionHandler.php
// Stores PHP sessions in the `sessions` database table instead of files,
// so an admin can list and revoke voter sessions.
namespace App\Services;

use App\Core\Model;
use SessionHandlerInterface;

class DbSessionHandler implements SessionHandlerInterface {

    public static function ensureTable() {
        $pdo = Model::db();
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            payload MEDIUMTEXT NOT NULL,
            user_type VARCHAR(20) NOT NULL DEFAULT 'guest',
            user_id INT NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            last_activity INT UNSIGNED NOT NULL,
            INDEX idx_sessions_last_activity (last_activity),
            INDEX idx_sessions_user_type (user_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function open($path, $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string|false {
        $stmt = Model::db()->prepare("SELECT payload FROM sessions WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['payload'] : '';
    }

    public function write($id, $data): bool {
        // Who is logged in on this session? Filled from the encrypted voter data.
        $owner = SessionService::currentOwner();

        $stmt = Model::db()->prepare(
            "INSERT INTO sessions (id, payload, user_type, user_id, last_activity, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE payload = VALUES(payload), user_type = VALUES(user_type),
                user_id = VALUES(user_id), last_activity = VALUES(last_activity)"
        );
        return $stmt->execute([
            $id,
            $data,
            $owner['user_type'],
            $owner['user_id'],
            time(),
            \App\Services\SecurityService::getClientIP(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    public function destroy($id): bool {
        $stmt = Model::db()->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($max_lifetime): int|false {
        $stmt = Model::db()->prepare("DELETE FROM sessions WHERE last_activity < ?");
        $stmt->execute([time() - (int)$max_lifetime]);
        return $stmt->rowCount();
    }
}
