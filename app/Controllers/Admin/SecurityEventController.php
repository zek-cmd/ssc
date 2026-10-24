<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\AdminSecurityService;
use App\Services\SecurityEventService;

class SecurityEventController extends Controller {
    
    private function canManageAuditLogs() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        if (!$adminId) return false;
        $stmt = \App\Core\Model::db()->prepare("SELECT can_manage_audit_logs FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        return $row && $row['can_manage_audit_logs'] == 1;
    }
    
    private function checkAccess() {
        $adminRole = SessionService::getAdminRole();
        if ($adminRole !== 'superadmin') {
            return false;
        }
        if (!$this->canManageAuditLogs()) {
            return false;
        }
        return true;
    }
    
    private function computeAdaptiveLimit($total, $target = 12, $min = 8, $max = 20) {
        $total = (int)$total;
        if ($total <= 0) {
            return ['limit' => $target, 'pad' => false];
        }
        if ($total <= $max) {
            return ['limit' => $total, 'pad' => false];
        }
        $bestDivisor = 0;
        for ($d = $min; $d <= $max; $d++) {
            if ($total % $d === 0) {
                $bestDivisor = $d;
            }
        }
        if ($bestDivisor > 0) {
            return ['limit' => $bestDivisor, 'pad' => false];
        }
        return ['limit' => $target, 'pad' => true];
    }
    
    public function index() {
        if (!$this->checkAccess()) {
            $this->redirect(APP_URL . '/admin/dashboard');
            return;
        }
        
        AdminSecurityService::init();
        
        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $adminUsername = SessionService::getAdminUsername();
        $adminRole = SessionService::getAdminRole();
        $isSuperAdmin = true;
        
        $title = APP_NAME . ' - Security Events';
        
        ob_start();
        require APP_PATH . '/Views/admin/security_events.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/admin.php';
    }
    
    public function getEvents() {
        if (!$this->checkAccess()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $page = $this->getPage();
        $target = min(30, max(5, (int)($_GET['target'] ?? 12)));
        $search = $this->getSearch();
        
        $securityEventService = new SecurityEventService();
        $total = $securityEventService->getTotalCount($search);
        $adaptive = $this->computeAdaptiveLimit($total, $target, 8, 20);
        $limit = $adaptive['limit'];
        $result = $securityEventService->getEvents($page, $limit, $search);
        
        return $this->json([
            'success' => true,
            'limit' => $limit,
            'pad' => $adaptive['pad'],
            ...$result
        ]);
    }
}