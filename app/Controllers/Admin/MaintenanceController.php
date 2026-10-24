<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Model;
use App\Services\SessionService;
use App\Services\AdminSecurityService;
use App\Services\AuditService;

class MaintenanceController extends Controller {
    
    private function getSiteStatus() {
        $pdo = Model::db();
        $stmt = $pdo->query("SELECT enable_site FROM votingswitch LIMIT 1");
        return (int)$stmt->fetchColumn();
    }
    
    private function isSuperAdmin() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        if (!$adminId) return false;
        $stmt = Model::db()->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        return $row && ($row['role'] ?? '') === 'superadmin';
    }
    
    public function index() {
        if (!$this->isSuperAdmin()) {
            $this->redirect(APP_URL . '/admin/dashboard');
            return;
        }
        
        AdminSecurityService::init();
        
        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $adminUsername = SessionService::getAdminUsername();
        $adminRole = SessionService::getAdminRole();
        $isSuperAdmin = $this->isSuperAdmin();
        $siteEnabled = $this->getSiteStatus();
        
        $title = APP_NAME . ' - Maintenance';
        
        ob_start();
        require APP_PATH . '/Views/admin/maintenance.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/admin.php';
    }
    
public function toggle() {
    if (!$this->isSuperAdmin()) {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    $pdo = Model::db();
    
    try {
        $pdo->beginTransaction();
        
        // 1. Get current status
        $stmt = $pdo->query("SELECT enable_site FROM votingswitch LIMIT 1");
        $current = (int)$stmt->fetchColumn();
        $newStatus = ($current == 1) ? 0 : 1;
        
        // 2. Update site status
        $pdo->prepare("UPDATE votingswitch SET enable_site = ?")->execute([$newStatus]);
        
        // 3. Insert security event INSIDE transaction
        $adminData = SessionService::getAdminData();
        $adminId = $adminData['user_id'] ?? 0;
        $ip = \App\Services\SecurityService::getClientIP();
        
        $securityStmt = $pdo->prepare("
            INSERT INTO security_events (admin_id, event_type, description, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $securityStmt->execute([
            $adminId,
            $newStatus == 1 ? 'MAINTENANCE_DISABLED' : 'MAINTENANCE_ENABLED',
            "Site set to " . ($newStatus == 1 ? 'UP' : 'DOWN'),
            $ip
        ]);
        
        // 4. Commit both
        $pdo->commit();
        
        // 5. Audit log (separate - can fail without breaking transaction)
        $audit = new AuditService();
        $audit->log('votingswitch', 'UPDATE', 1, 
            ['enable_site' => $current], 
            ['enable_site' => $newStatus], 
            "Maintenance toggle: " . ($newStatus == 1 ? 'Site UP' : 'Site DOWN')
        );
        
        return $this->json([
            'success' => true,
            'site_enabled' => $newStatus == 1,
            'message' => $newStatus == 1 ? 'Site is now UP' : 'Site is now DOWN'
        ]);
    } catch (\Exception $e) {
        $pdo->rollBack();
        return $this->json(['success' => false, 'error' => 'Failed to toggle site status'], 500);
    }
}
}