<?php
namespace App\Middleware;

use App\Services\RateLimitService;

class RateLimitMiddleware {
    public function handle() {
        $rateLimitService = new RateLimitService();
        $clientIP = $_SERVER['REMOTE_ADDR'];
        
        $check = $rateLimitService->checkRateLimit($clientIP);
        
        if ($check['is_locked_out']) {
            header('Content-Type: application/json');
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Too many attempts. Please try again later.'
            ]);
            exit;
        }
    }
}