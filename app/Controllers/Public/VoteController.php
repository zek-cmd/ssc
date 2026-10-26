<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\SecurityService;
use App\Services\VoteService;
use App\Services\EmailService;
use App\Services\TurnstileService;
use App\Core\Model;

class VoteController extends Controller {
    private $voteService;
    private $emailService;
    
    
    public function __construct() {
        $this->voteService = new VoteService();
        $this->emailService = new EmailService();
    }
    
    private function isTestVoteEnabled() {
        $pdo = Model::db();
        $stmt = $pdo->query("SELECT enable_test_vote FROM votingswitch LIMIT 1");
        $result = $stmt->fetch();
        return $result && $result['enable_test_vote'] == 1;
    }
    
    public function index() {
        $voterData = SessionService::getVoterData();
        
        if (!$voterData || SessionService::isSessionExpired()) {
            SessionService::voterLogout();
            $this->redirect(APP_URL . '/');
            return;
        }
        
        $cooldownUntil = $_SESSION['global_vote_cooldown_until'] ?? null;
        if ($cooldownUntil && $cooldownUntil > time()) {
            $_SESSION['global_vote_cooldown_until'] = time() + 60;
            $this->redirect(APP_URL . '/');
            return;
        }
        
        unset($_SESSION['global_vote_cooldown_until']);
        
        if (!$this->voteService->isVotingEnabled()) {
            $this->redirect(APP_URL . '/');
            return;
        }
        
        $nonce = SecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $turnstileSiteKey = TURNSTILE_SITE_KEY;
        $turnstileService = new TurnstileService();
        $isOffline = $turnstileService->isOfflineBypassed();
        $selections = [];
        $isTestVote = $this->isTestVoteEnabled();
        
        $title = APP_NAME . ' - Vote';
        
        ob_start();
        require APP_PATH . '/Views/public/vote.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/public.php';
    }
    
    public function getElectionData() {
        if (!SessionService::isAuthenticated() || SessionService::isSessionExpired()) {
            return $this->json(['success' => false, 'error' => 'Session expired'], 401);
        }
        
        $pdo = Model::db();
        
        $positions = $pdo->query("SELECT id, name, description, sort_order FROM positions ORDER BY sort_order ASC")->fetchAll();
        
        $result = [];
        foreach ($positions as $position) {
            $stmt = $pdo->prepare("
                SELECT c.id, c.name, c.platform, c.image_path, c.is_default_winner, pl.name as partylist_name, pl.badge_color, pl.badge_text_color
                FROM candidates c
                LEFT JOIN partylists pl ON c.partylist_id = pl.id
                WHERE c.position_id = ?
                ORDER BY pl.sort_order ASC, pl.name ASC, c.name ASC
            ");
            $stmt->execute([$position['id']]);
            $candidates = $stmt->fetchAll();
            
            $result[] = [
                'id' => $position['id'],
                'name' => $position['name'],
                'description' => $position['description'],
                'candidates' => $candidates
            ];
        }
        
        return $this->json(['success' => true, 'data' => $result]);
    }
    
  public function submitVote() {
    if (!SessionService::isAuthenticated() || SessionService::isSessionExpired()) {
        return $this->json(['success' => false, 'error' => 'Session expired'], 401);
    }
    
    if (!$this->voteService->isVotingEnabled()) {
        return $this->json(['success' => false, 'error' => 'Voting is currently closed'], 503);
    }
    
    $isTestVote = $this->isTestVoteEnabled();
    
    $selections = $_POST['selections'] ?? [];
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    if (!$isTestVote) {
        if (empty($name)) {
            return $this->json(['success' => false, 'error' => 'Please enter your full name'], 422);
        }
        
        if (empty($email)) {
            return $this->json(['success' => false, 'error' => 'Please enter your email address'], 422);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'error' => 'Please enter a valid email address'], 422);
        }
    } else {
        $name = 'TEST VOTE';
        $email = null;
    }
    
    if (empty($selections) || !is_array($selections)) {
        return $this->json(['success' => false, 'error' => 'Please select candidates for all positions'], 422);
    }
    
    $pdo = Model::db();
    
    $positionCount = (int)$pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn();
    if (count($selections) < $positionCount) {
        return $this->json(['success' => false, 'error' => 'Please select a candidate for each position'], 422);
    }
    
    $voterIdentifier = bin2hex(random_bytes(6));
    
    $positionData = [];
    foreach ($selections as $positionId => $candidateId) {
        $stmt = $pdo->prepare("SELECT id, name FROM candidates WHERE id = ? AND position_id = ?");
        $stmt->execute([$candidateId, $positionId]);
        $candidate = $stmt->fetch();
        if (!$candidate) {
            return $this->json(['success' => false, 'error' => 'Invalid candidate selection'], 422);
        }
        $positionData[$positionId] = [
            'position_id' => $positionId,
            'candidate_id' => $candidateId,
            'candidate_name' => $candidate['name']
        ];
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO votes (voter_identifier, voter_name, email, voted_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$voterIdentifier, $name, $email]);
        $voteId = $pdo->lastInsertId();
        
        $detailStmt = $pdo->prepare("INSERT INTO vote_details (vote_id, position_id, candidate_id) VALUES (?, ?, ?)");
        foreach ($selections as $positionId => $candidateId) {
            $detailStmt->execute([$voteId, $positionId, $candidateId]);
        }
        
        $pdo->commit();
        
        if (!$isTestVote) {
            $_SESSION['global_vote_cooldown_until'] = time() + 60;
        }
        
        $receiptToken = bin2hex(random_bytes(32));
        SessionService::setVoteData('receipt_token', $receiptToken);
        SessionService::setVoteData('voter_identifier', $voterIdentifier);
        
        if (!$isTestVote) {
            $candidatePartylistMap = [];
            $candidatesWithPL = $pdo->query("SELECT c.id, pl.name as partylist_name, pl.badge_color, pl.badge_text_color FROM candidates c LEFT JOIN partylists pl ON c.partylist_id = pl.id")->fetchAll();
            foreach ($candidatesWithPL as $cp) {
                $candidatePartylistMap[$cp['id']] = [
                    'name' => $cp['partylist_name'] ?? '',
                    'badge_color' => $cp['badge_color'] ?? '#1e40af',
                    'badge_text_color' => $cp['badge_text_color'] ?? '#ffffff'
                ];
            }
            
            $positionNameMap = [];
            $allPositions = $pdo->query("SELECT id, name FROM positions")->fetchAll();
            foreach ($allPositions as $pos) {
                $positionNameMap[$pos['id']] = $pos['name'];
            }
            
            $namedVoteData = [];
            foreach ($positionData as $posId => $data) {
                $positionName = $positionNameMap[$posId] ?? "Position $posId";
                $plInfo = $candidatePartylistMap[$data['candidate_id']] ?? ['name' => '', 'badge_color' => '#1e40af', 'badge_text_color' => '#ffffff'];
                $namedVoteData[$positionName] = [
                    'candidate_name' => $data['candidate_name'] ?? 'Unknown',
                    'partylist_name' => $plInfo['name'],
                    'partylist_badge_color' => $plInfo['badge_color'],
                    'partylist_badge_text_color' => $plInfo['badge_text_color']
                ];
            }
            
            $voteDataForEmail = [
                'voter_name' => $name,
                'voted_at' => date('F j, Y g:i A'),
                'named_vote_data' => $namedVoteData
            ];
            
            $this->emailService->queueEmail($voterIdentifier, $email, $voteDataForEmail);
        }
        
        return $this->json([
            'success' => true,
            'redirect' => APP_URL . '/vote/success?token=' . $receiptToken
        ]);
        
    } catch (\Exception $e) {
        $pdo->rollBack();
        error_log("Vote submission error: " . $e->getMessage());
        return $this->json(['success' => false, 'error' => 'An error occurred while submitting your vote'], 500);
    }
}
    
    public function success() {
        if (!SessionService::isAuthenticated() || SessionService::isSessionExpired()) {
            $this->redirect(APP_URL . '/');
            return;
        }
        
        $token = $_GET['token'] ?? '';
        $storedToken = SessionService::getVoteData('receipt_token');
        
        if (empty($token) || empty($storedToken) || !hash_equals($storedToken, $token)) {
            $this->redirect(APP_URL . '/');
            return;
        }
        
        unset($_SESSION['vote_data']['receipt_token']);
        
        $voterIdentifier = SessionService::getVoteData('voter_identifier');
        $nonce = SecurityService::getNonce();
        
        $title = APP_NAME . ' - Vote Submitted';
        
        ob_start();
        require APP_PATH . '/Views/public/success.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/public.php';
    }
    
    public function clearSession() {
        SessionService::clearVoteData();
        return $this->json(['success' => true]);
    }
    
    public function voterDetails() {
        if (!SessionService::isAuthenticated() || SessionService::isSessionExpired()) {
            return $this->json(['success' => false, 'error' => 'Session expired'], 401);
        }
        
        $voterIdentifier = $_GET['voter_id'] ?? '';
        if (empty($voterIdentifier)) {
            return $this->json(['success' => false, 'error' => 'Missing voter ID'], 422);
        }
        
        $pdo = Model::db();
        $stmt = $pdo->prepare("SELECT voter_name FROM votes WHERE voter_identifier = ? LIMIT 1");
        $stmt->execute([$voterIdentifier]);
        $vote = $stmt->fetch();
        
        if ($vote) {
            return $this->json(['success' => true, 'voter_name' => $vote['voter_name']]);
        }
        
        return $this->json(['success' => false, 'error' => 'Vote not found'], 404);
    }
}