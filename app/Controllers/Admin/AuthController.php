<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\RateLimitService;
use App\Services\TurnstileService;
use App\Services\AdminSecurityService;
use App\Core\Model;

class AuthController extends Controller {
    private $authService;
    private $rateLimitService;
    private $turnstileService;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->rateLimitService = new RateLimitService(5);
        $this->turnstileService = new TurnstileService();
    }
    
    public function showLogin() {
        if (SessionService::isAdminAuthenticated()) {
            $this->redirect(APP_URL . '/admin/dashboard');
            return;
        }
        
        AdminSecurityService::init();
        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $turnstileSiteKey = TURNSTILE_SITE_KEY;
        $isOffline = $this->turnstileService->isOfflineBypassed();
        
        $title = APP_NAME . ' - Admin Login';
        
        ob_start();
        require APP_PATH . '/Views/admin/login.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/admin.php';
    }
    
    public function login() {
        $clientIP = $_SERVER['REMOTE_ADDR'];
        
        $rateLimit = $this->rateLimitService->checkRateLimit('admin_' . $clientIP);
        if ($rateLimit['is_locked_out']) {
            $remainingSeconds = $rateLimit['remaining_time'];
            return $this->json([
                'success' => false,
                'error' => "Too many attempts. Try again in {$remainingSeconds} seconds."
            ], 429);
        }
        
        $turnstileResult = $this->turnstileService->verifyToken(
            $_POST['cf-turnstile-response'] ?? '',
            $clientIP
        );
        
        if (!$turnstileResult['success']) {
            return $this->json(['success' => false, 'error' => 'Please complete the verification challenge'], 422);
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $this->rateLimitService->incrementAttempts('admin_' . $clientIP);
            return $this->json(['success' => false, 'error' => 'Username and password required'], 422);
        }
        
        $result = $this->authService->authenticateAdmin($username, $password);
        
        if ($result['success']) {
            $this->rateLimitService->resetAttempts('admin_' . $clientIP);
            
            // Check site status
            $pdo = Model::db();
            $stmt = $pdo->query("SELECT enable_site FROM votingswitch LIMIT 1");
            $siteEnabled = $stmt->fetchColumn();
            
            // Check if superadmin
            $adminData = SessionService::getAdminData();
            $isSuperAdmin = ($adminData['user_role'] ?? '') === 'superadmin';
            
            if (!$siteEnabled && $isSuperAdmin) {
                $redirect = APP_URL . '/admin/maintenance';
            } else {
                $redirect = APP_URL . '/admin/dashboard';
            }
            
            return $this->json(['success' => true, 'redirect' => $redirect]);
        }
        
        $this->rateLimitService->incrementAttempts('admin_' . $clientIP);
        return $this->json(['success' => false, 'error' => $result['error']], 401);
    }
    
 public function logout() {
    SessionService::adminLogout();
    $this->redirect(APP_URL . '/admin');
}
    
 public function heartbeat() {
    header('Content-Type: application/json');
    
    if (!SessionService::isAdminAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        return;
    }
    

    
    $adminData = SessionService::getAdminData();
    $lastActivity = $adminData['last_activity'] ?? time();
    $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;
    $expiresAt = $lastActivity + $sessionLifetime;
    $remaining = $expiresAt - time();
    
    if ($remaining <= 0) {
        SessionService::adminLogout();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session expired',
            'session_expires' => 0
        ]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'last_activity' => $lastActivity,
        'session_expires' => $remaining,
        'session_lifetime' => $sessionLifetime
    ]);
}

public function verifyPassword() {
    $adminData = SessionService::getAdminData();
    $adminId = (int)($adminData['user_id'] ?? 0);
    
    if (!$adminId) {
        return $this->json(['success' => false, 'message' => 'Session invalid'], 401);
    }
    
    // Rate limit: 3 attempts, LOCKOUT_DURATION from config
    $rateLimitService = new \App\Services\RateLimitService(3, 30);
    $rateIdentifier = 'verify_password_' . $adminId;
    
    // Check if locked out
    $rateLimit = $rateLimitService->checkRateLimit($rateIdentifier);
    if ($rateLimit['is_locked_out']) {
        return $this->json([
            'success' => false,
            'message' => 'Too many incorrect attempts. Please wait ' . $rateLimit['remaining_time'] . ' seconds before trying again.',
            'remaining_seconds' => $rateLimit['remaining_time']
        ], 429);
    }
    
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        return $this->json(['success' => false, 'message' => 'Password is required.'], 400);
    }
    
    $isValid = $this->authService->verifyAdminPassword($adminId, $password);
    
    if (!$isValid) {
        // Increment failed attempts
        $rateLimitService->incrementAttempts($rateIdentifier);
        
        // Get remaining attempts
        $updatedLimit = $rateLimitService->checkRateLimit($rateIdentifier);
        
        if ($updatedLimit['is_locked_out']) {
            return $this->json([
                'success' => false,
                'message' => 'Too many incorrect attempts. Please wait ' . $updatedLimit['remaining_time'] . ' seconds before trying again.',
                'remaining_seconds' => $updatedLimit['remaining_time']
            ], 429);
        }
        
        return $this->json([
            'success' => false,
            'message' => 'Incorrect password.',
            'remaining_attempts' => $updatedLimit['remaining_attempts']
        ], 401);
    }
    
    // Password correct - reset attempts
    $rateLimitService->resetAttempts($rateIdentifier);
    
    return $this->json(['success' => true]);
}
}