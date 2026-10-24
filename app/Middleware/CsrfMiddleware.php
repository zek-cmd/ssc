<?php
namespace App\Middleware;

use App\Services\SessionService;

class CsrfMiddleware {
    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            
            if (!SessionService::validateCSRF($token)) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid security token']);
                exit;
            }
        }
    }
}