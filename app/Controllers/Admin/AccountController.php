<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\AdminSecurityService;
use App\Core\Model;
use App\Services\AuditService;

class AccountController extends Controller {
    
    private function audit($entityType, $action, $entityId, $oldValues = null, $newValues = null, $description = '') {
        (new AuditService())->log($entityType, $action, $entityId, $oldValues, $newValues, $description);
    }
    
    private function getCurrentUserId() {
        $adminData = SessionService::getAdminData();
        return $adminData['user_id'] ?? null;
    }

    private function getDBFlags() {
        $currentUserId = $this->getCurrentUserId();
        $flags = ['role'=>'', 'can_manage_accounts'=>0, 'can_modify_own_password_only'=>0];
        if ($currentUserId) {
            $stmt = Model::db()->prepare("SELECT role, can_manage_accounts, can_modify_own_password_only FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$currentUserId]);
            $row = $stmt->fetch();
            if ($row) $flags = $row;
        }
        return $flags;
    }
    
    private function canManageAccounts() {
        $flags = $this->getDBFlags();
        return isset($flags['can_manage_accounts']) && $flags['can_manage_accounts'] == 1;
    }
    
    public function index() {
        $flags = $this->getDBFlags();
        $isRestricted = isset($flags['can_modify_own_password_only']) && $flags['can_modify_own_password_only'] == 1;
        if (($flags['role'] ?? '') !== 'superadmin' && !$isRestricted) {
            $this->redirect(APP_URL . '/admin/dashboard');
            return;
        }
        
        if (!$this->canManageAccounts() && !$isRestricted) {
            $this->redirect(APP_URL . '/admin/dashboard');
            return;
        }
        
        AdminSecurityService::init();
        
        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $adminUsername = SessionService::getAdminUsername();
        $adminRole = SessionService::getAdminRole();
        $isSuperAdmin = ($adminRole === 'superadmin');
        
        $title = APP_NAME . ' - Account Management';
        
        ob_start();
        require APP_PATH . '/Views/admin/account_management.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/admin.php';
    }
    
    public function getUsers() {
        $flags = $this->getDBFlags();
        $isRestricted = isset($flags['can_modify_own_password_only']) && $flags['can_modify_own_password_only'] == 1;
        if (($flags['role'] ?? '') !== 'superadmin' && !$isRestricted) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        if (!$this->canManageAccounts() && !$isRestricted) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
  $page = $this->getPage();
$limit = $this->getLimit(15, 100);
$search = $this->getSearch();
        
        $pdo = Model::db();
        $params = [];
        
        $currentUserId = $this->getCurrentUserId();
        $countSql = "SELECT COUNT(*) FROM users";
        $dataSql = "SELECT id, username, name, role, can_manage_audit_logs, can_manage_accounts, can_delete_election, can_export_csv, can_edit_info, can_manage_vote, can_manage_sessions, enabled, can_modify_own_password_only, created_at FROM users";
        
        if ($isRestricted) {
            $where = " WHERE id = ?";
            $countSql .= $where;
            $dataSql .= $where;
            $params[] = (int)$currentUserId;
        } elseif (!empty($search)) {
            $where = " WHERE username LIKE ? OR name LIKE ? OR role LIKE ?";
            $countSql .= $where;
            $dataSql .= $where;
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $dataSql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $total = $pdo->prepare($countSql);
        $total->execute($params);
        $totalCount = $total->fetchColumn();
        
        $offset = ($page - 1) * $limit;
        $dataParams = array_merge($params, [$limit, $offset]);
        $stmt = $pdo->prepare($dataSql);
        $stmt->execute($dataParams);
        $data = $stmt->fetchAll();
        
        return $this->json([
            'success' => true,
            'data' => $data,
            'total' => (int)$totalCount,
            'page' => $page,
            'total_pages' => ceil($totalCount / $limit)
        ]);
    }
    
    public function saveUser() {
        $flags = $this->getDBFlags();
        $isRestricted = isset($flags['can_modify_own_password_only']) && $flags['can_modify_own_password_only'] == 1;
        if (($flags['role'] ?? '') !== 'superadmin' && !$isRestricted) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        if (!$this->canManageAccounts() && !$isRestricted) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $pdo = Model::db();
        $currentUserId = self::getCurrentUserId();
        
        $id = $_POST['id'] ?? null;
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        $canManageAuditLogs = isset($_POST['can_manage_audit_logs']) ? 1 : 0;
        $canManageAccounts = isset($_POST['can_manage_accounts']) ? 1 : 0;
        $canDeleteElection = isset($_POST['can_delete_election']) ? 1 : 0;
        $canExportCsv = isset($_POST['can_export_csv']) ? 1 : 0;
        $canEditInfo = isset($_POST['can_edit_info']) ? 1 : 0;
        $canManageVote = isset($_POST['can_manage_vote']) ? 1 : 0;
        $canManageSessions = isset($_POST['can_manage_sessions']) ? 1 : 0;
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (!empty($password) && $password !== $confirmPassword) {
            return $this->json(['success' => false, 'error' => 'Passwords do not match'], 422);
        }
        $canModifyOwnPasswordOnly = isset($_POST['can_modify_own_password_only']) ? 1 : 0;

        // Role = admin implies account management access + password-only restriction by default
        if ($role === 'admin') {
            $canManageAccounts = 1;
            $canModifyOwnPasswordOnly = 1;
        }
        
        if ($isRestricted) {
            if (empty($password)) return $this->json(['success' => false, 'error' => 'Password is required'], 422);
            if (!$id) return $this->json(['success' => false, 'error' => 'Creating users not allowed'], 403);
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$id]);
            $target = $stmt->fetch();
            if (!$target) return $this->json(['success' => false, 'error' => 'User not found'], 404);
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $id]);
            $this->audit('users', 'UPDATE', $id, ['message'=>'Password updated'], ['message'=>'Password updated'], 'Updated password');
            return $this->json(['success' => true, 'message' => 'Password updated successfully']);
        }
        
        if (empty($username) || empty($name)) {
            return $this->json(['success' => false, 'error' => 'Username and name are required'], 422);
        }
        
        if (!$id && empty($password)) {
            return $this->json(['success' => false, 'error' => 'Password is required for new users'], 422);
        }
        
        $allowedRoles = ['voter', 'admin', 'superadmin'];
        if (!in_array($role, $allowedRoles)) {
            return $this->json(['success' => false, 'error' => 'Invalid role'], 422);
        }
        
        if ($id && $id == $currentUserId && $enabled == 0) {
            return $this->json(['success' => false, 'error' => 'You cannot disable your own account'], 422);
        }

        if ($id && $id == $currentUserId && $canModifyOwnPasswordOnly == 1) {
            return $this->json(['success' => false, 'error' => 'You cannot enable password-only restriction on your own account'], 422);
        }

        if ($id && $id == $currentUserId && $role !== 'superadmin') {
            return $this->json(['success' => false, 'error' => 'You cannot change your own role'], 422);
        }

        if ($id && $id == $currentUserId && $canManageAccounts == 0) {
            return $this->json(['success' => false, 'error' => 'You cannot remove your own account management access'], 422);
        }
        
        if ($id && $id == $currentUserId && $canDeleteElection == 0) {
            return $this->json(['success' => false, 'error' => 'You cannot remove your own delete election access'], 422);
        }
        
        if ($id && $id == $currentUserId && $canExportCsv == 0) {
            return $this->json(['success' => false, 'error' => 'You cannot remove your own export CSV access'], 422);
        }
        
        if ($id && $id == $currentUserId && $canEditInfo == 0) {
            return $this->json(['success' => false, 'error' => 'You cannot remove your own edit info access'], 422);
        }
        
        if ($id && $id == $currentUserId && $canManageVote == 0) {
            return $this->json(['success' => false, 'error' => 'You cannot remove your own manage vote access'], 422);
        }

        if ($id && $id == $currentUserId && $canManageSessions == 0) {
            return $this->json(['success' => false, 'error' => 'You cannot remove your own manage sessions access'], 422);
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare("SELECT username, name, role, can_manage_audit_logs, can_manage_accounts, can_delete_election, can_export_csv, can_edit_info, can_manage_vote, can_manage_sessions, enabled, can_modify_own_password_only FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                
                if ($isRestricted) {
                    if (empty($password)) return $this->json(['success' => false, 'error' => 'Password is required'], 422);
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $id]);
                    $message = 'Password updated successfully';
                    return $this->json(['success' => true, 'message' => $message]);
                }
                
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, name = ?, role = ?, can_manage_audit_logs = ?, can_manage_accounts = ?, can_delete_election = ?, can_export_csv = ?, can_edit_info = ?, can_manage_vote = ?, can_manage_sessions = ?, enabled = ?, can_modify_own_password_only = ? WHERE id = ?");
                    $stmt->execute([$username, $hashedPassword, $name, $role, $canManageAuditLogs, $canManageAccounts, $canDeleteElection, $canExportCsv, $canEditInfo, $canManageVote, $canManageSessions, $enabled, $canModifyOwnPasswordOnly, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, role = ?, can_manage_audit_logs = ?, can_manage_accounts = ?, can_delete_election = ?, can_export_csv = ?, can_edit_info = ?, can_manage_vote = ?, can_manage_sessions = ?, enabled = ?, can_modify_own_password_only = ? WHERE id = ?");
                    $stmt->execute([$username, $name, $role, $canManageAuditLogs, $canManageAccounts, $canDeleteElection, $canExportCsv, $canEditInfo, $canManageVote, $canManageSessions, $enabled, $canModifyOwnPasswordOnly, $id]);
                }
                
                $changed = ($existing['username'] !== $username || $existing['name'] !== $name || $existing['role'] !== $role || (int)$existing['can_manage_audit_logs'] !== $canManageAuditLogs || (int)$existing['can_manage_accounts'] !== $canManageAccounts || (int)$existing['can_delete_election'] !== $canDeleteElection || (int)$existing['can_export_csv'] !== $canExportCsv || (int)$existing['can_edit_info'] !== $canEditInfo || (int)$existing['can_manage_vote'] !== $canManageVote || (int)$existing['enabled'] !== $enabled || !empty($password));
                if ($changed) {
                    $oldValues = ['message' => 'Account details updated'];
                    $newValues = ['message' => 'Account details updated'];
                    $this->audit('users', 'UPDATE', $id, $oldValues, $newValues, "Updated user: {$name} ({$username})");
                }
                $message = 'User updated successfully';
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    return $this->json(['success' => false, 'error' => 'Username already exists'], 409);
                }
                
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, can_manage_audit_logs, can_manage_accounts, can_delete_election, can_export_csv, can_edit_info, can_manage_vote, can_manage_sessions, enabled, can_modify_own_password_only) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $name, $role, $canManageAuditLogs, $canManageAccounts, $canDeleteElection, $canExportCsv, $canEditInfo, $canManageVote, $canManageSessions, $enabled, $canModifyOwnPasswordOnly]);
                $newId = $pdo->lastInsertId();
                $newValues = ['message' => 'Account created'];
                $this->audit('users', 'CREATE', $newId, null, $newValues, "Created user: {$name} ({$username})");
                $message = 'User created successfully';
            }
            
            if ($id && $id == $currentUserId) {
                $adminData = SessionService::getAdminData();
                if ($adminData) {
                    SessionService::setAdminSession(
                        $adminData['username'] ?? $username,
                        (int)$id,
                        $role,
                        $canManageAuditLogs,
                        $canManageAccounts,
                        $adminData['name'] ?? $name,
                        $canDeleteElection,
                        $canExportCsv,
                        $canEditInfo,
                        $canManageVote,
                        $canModifyOwnPasswordOnly,
                        $canManageSessions
                    );
                }
            }
            
            return $this->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Failed to save user'], 500);
        }
    }
    
    public function deleteUser() {
        $flags = $this->getDBFlags();
        if (($flags['role'] ?? '') !== 'superadmin') {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        if (!$this->canManageAccounts()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $id = $_POST['id'] ?? null;
        if (!$id) return $this->json(['success' => false, 'error' => 'ID required'], 422);
        
        $currentUserId = self::getCurrentUserId();
        if ($id == $currentUserId) {
            return $this->json(['success' => false, 'error' => 'Cannot delete your own account'], 422);
        }
        
        $pdo = Model::db();
        $stmt = $pdo->prepare("SELECT username, name FROM users WHERE id = ?");
        $stmt->execute([(int)$id]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([(int)$id]);
        
        if ($user) {
            $oldValues = ['message' => 'Account deleted'];
            $this->audit('users', 'DELETE', $id, $oldValues, null, "Deleted user: {$user['name']} ({$user['username']})");
        }
        
        return $this->json(['success' => true, 'message' => 'User deleted']);
    }
    
public function bulkDeleteUsers() {
    $flags = $this->getDBFlags();
    if (($flags['role'] ?? '') !== 'superadmin') {
        return $this->error('Access denied', 403);
    }
    
    if (!$this->canManageAccounts()) {
        return $this->error('Access denied', 403);
    }
    
    $result = $this->getBulkIds(100);
    if (!$result['success']) {
        return $this->error($result['error'], 422);
    }
    $ids = $result['ids'];
    
    $currentUserId = self::getCurrentUserId();
    $pdo = Model::db();
    $deleted = 0;
    $skipped = 0;
    
    foreach ($ids as $id) {
        if ($id == $currentUserId) {
            $skipped++;
            continue;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $deleted++;
    }
    
    $msg = "{$deleted} deleted";
    if ($skipped > 0) $msg .= ", your own account was skipped";
    $this->audit('users', 'DELETE', 'bulk', null, null, "Bulk deleted {$deleted} users ({$skipped} skipped)");
    return $this->success([], $msg);
}
}