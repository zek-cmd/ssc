<?php
namespace App\Middleware\Admin;

use App\Services\SessionService;

class AuthMiddleware {
    public function handle() {
        // Check if admin is authenticated (includes inactivity timeout)
        if (!SessionService::isAdminAuthenticated()) {
            $this->handleUnauthorized();
            return;
        }
        
      if (!SessionService::validateSession()) {
    SessionService::adminLogout();
    $this->handleUnauthorized();
    return;
}
        
        SessionService::updateActivity();
    }
    
    private function handleUnauthorized() {
        // Check if it's an AJAX/fetch request
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && 
                   strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($isAjax) {
            // Return JSON 401 for AJAX/fetch requests
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Not authenticated',
                'session_expires' => 0
            ]);
            exit;
        }
        
        // Redirect for browser page navigation
        header('Location: ' . APP_URL . '/admin');
        exit;
    }
}