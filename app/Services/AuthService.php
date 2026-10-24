<?php
// AuthService.php
namespace App\Services;

use App\Core\Model;

class AuthService {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Model::db();
    }
    
    public function authenticateVoter($username, $password) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, username, password, role, enabled FROM users WHERE username = ? LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            if (!$user['enabled']) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            $allowedRoles = ['voter', 'admin', 'superadmin'];
            if (!in_array($user['role'], $allowedRoles)) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            session_regenerate_id(true);
            SessionService::setVoterSession($user['id'], $user['username'], $user['role']);
            SessionService::generateCSRFToken();
            
            return ['success' => true];
            
        } catch (\PDOException $e) {
            error_log("Auth Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Authentication service unavailable'];
        }
    }
    
    public function authenticateAdmin($username, $password) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, username, password, role, name, can_manage_audit_logs, can_manage_accounts, can_delete_election, can_export_csv, can_edit_info, can_manage_vote, enabled 
                FROM users WHERE username = ? AND role IN ('admin', 'superadmin') LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            if (!$user['enabled']) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            session_regenerate_id(true);
            SessionService::setAdminSession(
                $user['username'],
                $user['id'],
                $user['role'],
                $user['can_manage_audit_logs'] ?? 0,
                $user['can_manage_accounts'] ?? 0,
                $user['name'],
                $user['can_delete_election'] ?? 0,
                $user['can_export_csv'] ?? 0,
                $user['can_edit_info'] ?? 0,
                $user['can_manage_vote'] ?? 0
            );
            SessionService::generateCSRFToken();
            
            return ['success' => true];
            
        } catch (\PDOException $e) {
            error_log("Admin Auth Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Authentication service unavailable'];
        }
    }
    
    public function verifyAdminPassword(int $adminId, string $password): bool {
    $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$adminId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    return password_verify($password, $user['password']);
}
}