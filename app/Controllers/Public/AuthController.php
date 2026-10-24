<?php
// app/Controllers/Public/AuthController.php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Model;
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\RateLimitService;
use App\Services\TurnstileService;
use App\Services\SecurityService;

class AuthController extends Controller {
    private $authService;
    private $rateLimitService;
    private $turnstileService;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->rateLimitService = new RateLimitService();
        $this->turnstileService = new TurnstileService();
    }
    
    public function index() {
        if (SessionService::isAuthenticated() && !SessionService::isSessionExpired()) {
            return $this->showHomepage();
        }
        
        $pdo = Model::db();
        $stmt = $pdo->query("SELECT enable_login, enable_guest FROM votingswitch LIMIT 1");
        $result = $stmt->fetch();
        
        $loginEnabled = $result && $result['enable_login'] == 1;
        $guestEnabled = $result && $result['enable_guest'] == 1;
        
      if (!$loginEnabled) { http_response_code(503); $nonce = \App\Services\SecurityService::getNonce(); echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>503 - Service Unavailable</title><style nonce="' . htmlspecialchars($nonce) . '">body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; } .error { text-align: center; padding: 40px; } h1 { font-size: 72px; margin: 0; color: #cc0000; } p { color: #666; margin: 10px 0 30px; }</style></head><body><div class="error"><h1>503</h1><p>Service Unavailable</p></div></body></html>'; return; }
        
        if ($guestEnabled) {
            $this->createGuestSession();
            return $this->showHomepage();
        }
        
        $message = $_GET['message'] ?? '';
        return $this->showLoginForm($message);
    }
    
   public function login() {
    $clientIP = $_SERVER['REMOTE_ADDR'];
    
    $pdo = Model::db();
    $stmt = $pdo->query("SELECT enable_login FROM votingswitch LIMIT 1");
    $loginEnabled = $stmt->fetchColumn();
    
    if (!$loginEnabled) {
        return $this->json(['success' => false, 'error' => 'Login is temporarily unavailable'], 503);
    }
    
    $turnstileResult = $this->turnstileService->verifyToken(
        $_POST['cf-turnstile-response'] ?? '',
        $clientIP
    );
    
    if (!$turnstileResult['success']) {
        return $this->json(['success' => false, 'error' => 'Please complete the verification challenge'], 422);
    }
    
    $rateLimit = $this->rateLimitService->checkRateLimit($clientIP);
    if ($rateLimit['is_locked_out']) {
        $remainingSeconds = $rateLimit['remaining_time'];
        return $this->json([
            'success' => false,
            'error' => "Too many attempts. Try again in {$remainingSeconds} seconds."
        ], 429);
    }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $this->rateLimitService->incrementAttempts($clientIP);
        return $this->json(['success' => false, 'error' => 'Username and password required'], 422);
    }
    
    $result = $this->authService->authenticateVoter($username, $password);
    
    if ($result['success']) {
        $this->rateLimitService->resetAttempts($clientIP);
        return $this->json(['success' => true, 'redirect' => APP_URL . '/']);
    }
    
    $this->rateLimitService->incrementAttempts($clientIP);
    
    return $this->json(['success' => false, 'error' => $result['error']], 401);
}
    
public function logout() {
    SessionService::voterLogout();
    $this->redirect(APP_URL . '/');
}
    
public function heartbeat() {
    header('Content-Type: application/json');
    
    if (!SessionService::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        return;
    }
    
    if (SessionService::isSessionExpired()) {
        SessionService::voterLogout();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session expired',
            'session_expires' => 0
        ]);
        return;
    }
    

    
    $voterData = SessionService::getVoterData();
    $lastActivity = $voterData['last_activity'] ?? time();
    $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;
    $expiresAt = $lastActivity + $sessionLifetime;
    $remaining = $expiresAt - time();
    
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'last_activity' => $lastActivity,
        'session_expires' => $remaining > 0 ? $remaining : 0,
        'session_lifetime' => $sessionLifetime
    ]);
}
    
    private function createGuestSession() {
        $guestId = 'guest_' . bin2hex(random_bytes(5));
        SessionService::setVoterSession(0, $guestId, 'voter');
    }
    
    private function showHomepage() {
        $voterData = SessionService::getVoterData();
        $nonce = SecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        
        $voteService = new \App\Services\VoteService();
        $votingEnabled = $voteService->isVotingEnabled();
        
        $cooldownRemaining = 0;
        $cooldownUntil = $_SESSION['global_vote_cooldown_until'] ?? null;
        if ($cooldownUntil && $cooldownUntil > time()) {
            $cooldownRemaining = $cooldownUntil - time();
        } else {
            unset($_SESSION['global_vote_cooldown_until']);
        }
        
        $title = APP_NAME . ' - Home';
        
        ob_start();
        require APP_PATH . '/Views/public/home.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/public.php';
    }
    
    private function showLoginForm($message = '') {
        $nonce = SecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $turnstileSiteKey = TURNSTILE_SITE_KEY;
        $isOffline = $this->turnstileService->isOfflineBypassed();
        
        $title = APP_NAME . ' - Login';
        
        ob_start();
        require APP_PATH . '/Views/public/login.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/public.php';
    }
}