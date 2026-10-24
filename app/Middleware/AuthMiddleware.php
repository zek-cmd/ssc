<?php
namespace App\Middleware;

use App\Services\SessionService;

class AuthMiddleware {
    public function handle() {
        if (!SessionService::isAuthenticated()) {
            $this->handleUnauthorized();
            return;
        }
        
        if (SessionService::isSessionExpired()) {
            SessionService::voterLogout();
            $this->handleUnauthorized();
            return;
        }
        
        SessionService::updateActivity();
    }
    
    private function handleUnauthorized() {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && 
                   strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Not authenticated',
                'session_expires' => 0
            ]);
            exit;
        }
        
        header('Location: ' . APP_URL . '/');
        exit;
    }
}