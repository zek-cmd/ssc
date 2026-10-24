<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\AdminSecurityService;
use App\Core\Model;

class DashboardController extends Controller {
    
    public function index() {
        AdminSecurityService::init();
        
        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $adminUsername = SessionService::getAdminUsername();
        $adminRole = SessionService::getAdminRole();
        $isSuperAdmin = ($adminRole === 'superadmin');
        
        $adminData = SessionService::getAdminData();
        error_log('Admin data: ' . print_r($adminData, true));
        $pdo = Model::db();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        $flags = ['can_manage_audit_logs'=>0,'can_manage_accounts'=>0,'can_edit_info'=>0,'can_manage_vote'=>0,'can_modify_own_password_only'=>0];
        if ($adminId) {
            $stmt = $pdo->prepare("SELECT can_manage_audit_logs, can_manage_accounts, can_edit_info, can_manage_vote, can_modify_own_password_only FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $row = $stmt->fetch();
            if ($row) {
                $flags = $row;
            }
        }
        $canManageAuditLogs = isset($flags['can_manage_audit_logs']) && $flags['can_manage_audit_logs'] == 1;
        $canManageAccounts = isset($flags['can_manage_accounts']) && $flags['can_manage_accounts'] == 1;
        $canEditInfo = isset($flags['can_edit_info']) && $flags['can_edit_info'] == 1;
        $canManageVote = isset($flags['can_manage_vote']) && $flags['can_manage_vote'] == 1;
        
        // Check site status
        $stmt = $pdo->query("SELECT enable_site FROM votingswitch LIMIT 1");
        $siteEnabled = (int)$stmt->fetchColumn();
        
        // Superadmin + Site DOWN → Redirect to maintenance
        if (!$siteEnabled && $isSuperAdmin) {
            $this->redirect(APP_URL . '/admin/maintenance');
            return;
        }
        
        $positionsCount = $pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn();
        $candidatesCount = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
        $partylistsCount = $pdo->query("SELECT COUNT(*) FROM partylists")->fetchColumn();
        
        $title = APP_NAME . ' - Admin Dashboard';
        
        ob_start();
        require APP_PATH . '/Views/admin/dashboard.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/admin.php';
    }
}