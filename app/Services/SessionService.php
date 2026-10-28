<?php
// app/Services/SessionService.php
namespace App\Services;

class SessionService {
    private static $namespace = '__ssc_voter';
    private static $adminNamespace = '__admin';
    private static $maintenanceNamespace = '__maintenance';

    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', SESSION_GC_MAXLIFETIME);
            
            session_set_cookie_params([
                'lifetime' => SESSION_COOKIE_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            // Keep sessions in the database so admins can list and revoke them
            DbSessionHandler::ensureTable();
            session_set_save_handler(new DbSessionHandler(), true);
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            self::generateCSRFToken();
        }
        
        // Rotate session ID periodically (every 30 minutes)
        self::rotateSessionId();
    }
    
    // Who is logged in on the current session (used to fill the sessions table)
    public static function currentOwner() {
        $voter = null;
        if (isset($_SESSION[self::$namespace])) {
            $voter = self::decrypt($_SESSION[self::$namespace]);
        }
        if ($voter && ($voter['logged_in'] ?? false) === true) {
            return ['user_type' => 'voter', 'user_id' => (int)($voter['user_id'] ?? 0) ?: null];
        }
        $admin = null;
        if (isset($_SESSION[self::$adminNamespace])) {
            $admin = self::decrypt($_SESSION[self::$adminNamespace]);
        }
        if ($admin && ($admin['logged_in'] ?? false) === true) {
            return ['user_type' => 'admin', 'user_id' => (int)($admin['user_id'] ?? 0) ?: null];
        }
        return ['user_type' => 'guest', 'user_id' => null];
    }

    private static function rotateSessionId() {
        $lastRotation = $_SESSION['session_rotated'] ?? 0;
        if (time() - $lastRotation > 1800) {
            session_regenerate_id(true);
            $_SESSION['session_rotated'] = time();
        }
    }
    
    private static function getEncryptionKey() {
        $key = SESSION_ENCRYPTION_KEY;
        $decoded = base64_decode($key, true);
        if ($decoded !== false && strlen($decoded) === 32) {
            return $decoded;
        }
        return hash('sha256', $key, true);
    }
    
    private static function encrypt($data) {
        $key = self::getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(json_encode($data), 'aes-256-gcm', $key, 0, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }
    
    private static function decrypt($encryptedData) {
        $key = self::getEncryptionKey();
        $data = base64_decode($encryptedData);
        if (strlen($data) < 32) return null;
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, 0, $iv, $tag);
        if ($decrypted === false) return null;
        return json_decode($decrypted, true);
    }
    
    public static function isAuthenticated() {
        $data = self::getVoterData();
        return $data && isset($data['logged_in']) && $data['logged_in'] === true;
    }
    
    public static function setVoterSession($userId, $username, $role) {
        $data = [
            'logged_in' => true,
            'user_id' => $userId,
            'username' => $username,
            'role' => $role,
            'login_time' => time(),
            'last_activity' => time(),
            'ip' => \App\Services\SecurityService::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        $_SESSION[self::$namespace] = self::encrypt($data);
    }
    
    public static function getVoterData() {
        if (!isset($_SESSION[self::$namespace])) return null;
        $decrypted = self::decrypt($_SESSION[self::$namespace]);
        return $decrypted;
    }
    
    public static function updateActivity() {
        if (isset($_SESSION[self::$namespace])) {
            $data = self::getVoterData();
            if ($data) {
                $data['last_activity'] = time();
                $_SESSION[self::$namespace] = self::encrypt($data);
            }
        }
        if (isset($_SESSION[self::$adminNamespace])) {
            $data = self::getAdminData();
            if ($data) {
                $data['last_activity'] = time();
                $_SESSION[self::$adminNamespace] = self::encrypt($data);
            }
        }
        if (isset($_SESSION[self::$maintenanceNamespace])) {
            $data = self::getMaintenanceData();
            if ($data) {
                $data['last_activity'] = time();
                $_SESSION[self::$maintenanceNamespace] = self::encrypt($data);
            }
        }
    }
    
    public static function isSessionExpired() {
        if (!self::isAuthenticated()) {
            return true;
        }
        
        $data = self::getVoterData();
        $lastActivity = $data['last_activity'] ?? 0;
        return (time() - $lastActivity) > SESSION_LIFETIME;
    }
    
    public static function isAdminAuthenticated() {
        $data = self::getAdminData();
        
        if (!$data || !isset($data['logged_in']) || $data['logged_in'] !== true) {
            return false;
        }
        
        // Check inactivity timeout
        $lastActivity = $data['last_activity'] ?? 0;
        $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;
        
        if ((time() - $lastActivity) > $sessionLifetime) {
            self::adminLogout();
            return false;
        }
        
        return true;
    }
    
    public static function validateSession() {
        $data = self::getAdminData();
        
        if (!$data) {
            return false;
        }
        
        // User-Agent check (strict)
        if (($data['user_agent'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            self::adminLogout();
            return false;
        }
        
        return true;
    }
    
   public static function setAdminSession($username, $userId, $role, $canManageAuditLogs = 0, $canManageAccounts = 0, $name = '', $canDeleteElection = 0, $canExportCsv = 0, $canEditInfo = 0, $canManageVote = 0, $canModifyOwnPasswordOnly = 0, $canManageSessions = 0) {
    $data = [
        'logged_in' => true,
        'username' => $username,
        'name' => $name ?: $username,
        'user_id' => $userId,
        'user_role' => $role,
        'can_manage_audit_logs' => $canManageAuditLogs,
        'can_manage_accounts' => $canManageAccounts,
        'can_delete_election' => $canDeleteElection,
        'can_export_csv' => $canExportCsv,
        'can_edit_info' => $canEditInfo,
        'can_manage_vote' => $canManageVote,
        'can_modify_own_password_only' => $canModifyOwnPasswordOnly,
        'can_manage_sessions' => $canManageSessions,
        'login_time' => time(),
        'last_activity' => time(),
        'ip' => \App\Services\SecurityService::getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    $_SESSION[self::$adminNamespace] = self::encrypt($data);
}
    
    public static function getAdminData() {
        if (!isset($_SESSION[self::$adminNamespace])) return null;
        $decrypted = self::decrypt($_SESSION[self::$adminNamespace]);
        return $decrypted;
    }
    
    public static function getAdminUsername() {
        $data = self::getAdminData();
        return $data['name'] ?? $data['username'] ?? 'Admin';
    }
    
    public static function getAdminRole() {
        $data = self::getAdminData();
        return $data['user_role'] ?? null;
    }

    public static function refreshAdminSession() {
        $adminData = self::getAdminData();
        if (!$adminData) return false;
        $userId = (int)$adminData['user_id'];
        $pdo = \App\Core\Model::db();
        if (!$pdo || !$userId) return false;
        $stmt = $pdo->prepare("SELECT role, can_manage_audit_logs, can_manage_accounts, can_delete_election, can_export_csv, can_edit_info, can_manage_vote, can_manage_sessions, can_modify_own_password_only FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return false;
        $changed = ($adminData['user_role'] ?? '') !== $row['role'] ||
                   (int)($adminData['can_manage_accounts'] ?? 0) !== (int)$row['can_manage_accounts'] ||
                   (int)($adminData['can_manage_sessions'] ?? 0) !== (int)$row['can_manage_sessions'] ||
                   (int)($adminData['can_modify_own_password_only'] ?? 0) !== (int)$row['can_modify_own_password_only'];
        if ($changed) {
            self::setAdminSession(
                $adminData['username'], $userId, $row['role'],
                (int)$row['can_manage_audit_logs'], (int)$row['can_manage_accounts'],
                $adminData['name'] ?? '', (int)$row['can_delete_election'], (int)$row['can_export_csv'],
                (int)$row['can_edit_info'], (int)$row['can_manage_vote'], (int)$row['can_modify_own_password_only'],
                (int)$row['can_manage_sessions']
            );
            return true;
        }
        return false;
    }
    
    // ===== MAINTENANCE SESSION =====
    
    public static function isMaintenanceAuthenticated() {
        $data = self::getMaintenanceData();
        return $data && isset($data['logged_in']) && $data['logged_in'] === true;
    }
    
    public static function setMaintenanceSession($username, $userId, $role, $name = '') {
        $data = [
            'logged_in' => true,
            'username' => $username,
            'name' => $name ?: $username,
            'user_id' => $userId,
            'user_role' => $role,
            'login_time' => time(),
            'last_activity' => time(),
            'ip' => \App\Services\SecurityService::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        $_SESSION[self::$maintenanceNamespace] = self::encrypt($data);
    }
    
    public static function getMaintenanceData() {
        if (!isset($_SESSION[self::$maintenanceNamespace])) return null;
        $decrypted = self::decrypt($_SESSION[self::$maintenanceNamespace]);
        return $decrypted;
    }
    
    public static function maintenanceLogout() {
        unset($_SESSION[self::$maintenanceNamespace]);
    }
    
    public static function generateCSRFToken() {
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
    
    public static function getCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || 
            (time() - ($_SESSION['csrf_token_time'] ?? 0)) > 3600) {
            self::generateCSRFToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function generateNonce() {
        $nonce = base64_encode(random_bytes(NONCE_LENGTH));
        $_SESSION['script_nonce'] = $nonce;
        return $nonce;
    }
    
    public static function getNonce() {
        if (!isset($_SESSION['script_nonce'])) {
            self::generateNonce();
        }
        return $_SESSION['script_nonce'];
    }
    
    public static function setVoteData($key, $value) {
        $_SESSION['vote_data'][$key] = $value;
    }
    
    public static function getVoteData($key, $default = null) {
        return $_SESSION['vote_data'][$key] ?? $default;
    }
    
    public static function clearVoteData() {
        $cooldownUntil = $_SESSION['vote_data']['vote_cooldown_until'] ?? null;
        unset($_SESSION['vote_data']);
        if ($cooldownUntil) {
            $_SESSION['vote_data']['vote_cooldown_until'] = $cooldownUntil;
        }
    }
    
    public static function voterLogout() {
        unset($_SESSION[self::$namespace]);
        unset($_SESSION['vote_data']);
    }
    
    public static function adminLogout() {
        // Clear entire session to prevent stale data
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    public static function destroy() {
        session_unset();
        session_destroy();
    }
}