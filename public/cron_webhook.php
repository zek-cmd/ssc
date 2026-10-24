<?php
set_time_limit(120);
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../bootstrap.php';
    
    // Get token from phpdotenv ($_ENV)
    $ALLOWED_TOKEN = $_ENV['CRON_WEBHOOK_TOKEN'] ?? '';
    
    // Get token from multiple sources
    $provided_token = '';
    
    // 1. Authorization header (Bearer token) - preferred
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $provided_token = substr($authHeader, 7);
    }
    
    // 2. POST body (JSON or form data)
    if (empty($provided_token) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $postData = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $provided_token = $postData['token'] ?? '';
        } else {
            $provided_token = $_POST['token'] ?? '';
        }
    }
    
    // 3. GET query parameter (fallback)
    if (empty($provided_token)) {
        $provided_token = $_GET['token'] ?? '';
    }
    
    // Validate token
    if (empty($ALLOWED_TOKEN) || empty($provided_token) || !hash_equals($ALLOWED_TOKEN, $provided_token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid token']));
    }
    
    require_once __DIR__ . '/../app/Services/Mailer.php';
    require_once __DIR__ . '/../app/Services/EmailService.php';
    
    $emailService = new \App\Services\EmailService();
    $startTime = microtime(true);
    $statsBefore = $emailService->getQueueStats();
    
    $stuckReset = $emailService->resetStuckEmails(1);
    
    $result = $emailService->processQueue(50);
    $executionTime = round(microtime(true) - $startTime, 2);
    
    $sentCleaned = $emailService->cleanupSentEmails(10);
    
    $failedCleaned = $emailService->cleanupFailedEmails(7);
    
    $statsAfter = $emailService->getQueueStats();
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'processed' => $result['processed'],
        'failed' => $result['failed'],
        'pending_before' => $statsBefore['pending'],
        'pending_after' => $statsAfter['pending'],
        'total_sent' => $statsAfter['sent'],
        'total_failed' => $statsAfter['failed'],
        'execution_time_seconds' => $executionTime,
        'stuck_reset' => $stuckReset,
        'cleanup' => [
            'sent_cleaned' => $sentCleaned,
            'failed_cleaned' => $failedCleaned
        ]
    ]);
    
} catch (\Throwable $e) {
    // Log actual error to server log
    error_log('Email cron webhook error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return generic error to caller
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}