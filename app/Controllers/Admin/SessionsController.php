<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\AdminSecurityService;

// Admin page that lists active voter sessions with device details and lets an admin revoke one.
class SessionsController extends Controller {

    private function canManageVoteSessions() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        if (!$adminId) return false;
        $stmt = \App\Core\Model::db()->prepare("SELECT can_manage_sessions FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        return $row && $row['can_manage_sessions'] == 1;
    }

    public function index() {
        if (!$this->canManageVoteSessions()) {
            $this->redirect(APP_URL . '/admin/dashboard');
            return;
        }

        AdminSecurityService::init();

        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $adminUsername = SessionService::getAdminUsername();
        $adminRole = SessionService::getAdminRole();
        $isSuperAdmin = $adminRole === 'superadmin';

        $title = APP_NAME . ' - Sessions';

        ob_start();
        require APP_PATH . '/Views/admin/sessions.php';
        $content = ob_get_clean();

        require APP_PATH . '/Views/layouts/admin.php';
    }
}
