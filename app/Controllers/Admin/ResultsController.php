<?php
// app/Controllers/Admin/ResultsController.php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\AdminSecurityService;
use App\Core\Model;
use App\Services\AuditService;

class ResultsController extends Controller {
    
    private function audit($entityType, $action, $entityId, $oldValues = null, $newValues = null, $description = '') {
        (new AuditService())->log($entityType, $action, $entityId, $oldValues, $newValues, $description);
    }
    
    public function index() {
        AdminSecurityService::init();
        
        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $adminUsername = SessionService::getAdminUsername();
        $adminRole = SessionService::getAdminRole();
        $isSuperAdmin = ($adminRole === 'superadmin');
        $adminId = (int)(SessionService::getAdminData()['user_id'] ?? 0);
        $pdo = Model::db();
        $flags = ['can_delete_election'=>0,'can_export_csv'=>0,'can_modify_own_password_only'=>0];
        if ($adminId) {
            $stmt = $pdo->prepare("SELECT can_delete_election, can_export_csv, can_modify_own_password_only FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $row = $stmt->fetch();
            if ($row) $flags = $row;
        }
        $canDeleteElection = isset($flags['can_delete_election']) && $flags['can_delete_election'] == 1;
        $canExportCsv = isset($flags['can_export_csv']) && $flags['can_export_csv'] == 1;
        
        $title = APP_NAME . ' - Election Results';
        
        ob_start();
        require APP_PATH . '/Views/admin/results.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/admin.php';
    }
    
    public function getResults() {
        $pdo = Model::db();
        
        $totalVotes = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
        
        $stmt = $pdo->query("
            SELECT 
                p.id as position_id, p.name as position_name, p.sort_order,
                c.id as candidate_id, c.name as candidate_name, c.image_path,
                pl.name as partylist_name, pl.badge_color as partylist_badge_color, pl.badge_text_color as partylist_badge_text_color, pl.sort_order as partylist_sort_order,
                COALESCE(vc.vote_count, 0) as vote_count
            FROM positions p
            LEFT JOIN candidates c ON p.id = c.position_id
            LEFT JOIN partylists pl ON c.partylist_id = pl.id
            LEFT JOIN vote_counts vc ON c.id = vc.candidate_id AND vc.position_id = p.id
            ORDER BY p.sort_order ASC, pl.sort_order ASC, pl.name ASC, vc.vote_count DESC, c.name ASC
        ");
        
        $positions = [];
        while ($row = $stmt->fetch()) {
            $pid = $row['position_id'];
            if (!isset($positions[$pid])) {
                $positions[$pid] = [
                    'id' => $pid,
                    'name' => $row['position_name'],
                    'sort_order' => $row['sort_order'],
                    'total_votes' => 0,
                    'candidates' => []
                ];
            }
            if ($row['candidate_id']) {
                $positions[$pid]['total_votes'] += (int)$row['vote_count'];
                $positions[$pid]['candidates'][] = [
                    'id' => $row['candidate_id'],
                    'name' => $row['candidate_name'],
                    'image_path' => $row['image_path'],
                    'partylist_name' => $row['partylist_name'],
                    'partylist_badge_color' => $row['partylist_badge_color'] ?? '#1e40af',
                    'partylist_badge_text_color' => $row['partylist_badge_text_color'] ?? '#ffffff',
                    'vote_count' => (int)$row['vote_count']
                ];
            }
        }
        
        // Determine winning candidate per position (highest vote count, no ties)
        foreach ($positions as $pid => $pos) {
            $maxVotes = 0;
            foreach ($pos['candidates'] as $c) {
                if ($c['vote_count'] > $maxVotes) $maxVotes = $c['vote_count'];
            }
            $maxCount = 0;
            foreach ($pos['candidates'] as $c) {
                if ($c['vote_count'] === $maxVotes) $maxCount++;
            }
            $positions[$pid]['candidates'] = array_map(function($c) use ($maxVotes, $maxCount) {
                $c['is_winning'] = ($maxVotes > 0 && $c['vote_count'] === $maxVotes && $maxCount === 1);
                return $c;
            }, $pos['candidates']);
        }
        
        return $this->json([
            'success' => true,
            'total_votes' => $totalVotes,
            'positions' => array_values($positions),
            'generated_at' => date('F j, Y g:i A')
        ]);
    }
    
  public function exportCSV() {
    $adminId = (int)(SessionService::getAdminData()['user_id'] ?? 0);
    $canExport = false;
    if ($adminId) {
        $stmt = \App\Core\Model::db()->prepare("SELECT can_export_csv FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        $canExport = $row && $row['can_export_csv'] == 1;
    }
    if (!$canExport) {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    $pdo = Model::db();
    
    $positions = $pdo->query("SELECT id, name FROM positions ORDER BY sort_order ASC")->fetchAll();
    
    $totalVotes = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
    
    $exportDir = STORAGE_PATH . '/exports';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    // Cleanup stale exports (older than 1 hour)
    $this->cleanupStaleExports($exportDir, 3600);
    
    // Generate unique export ID and filename
    $exportId = bin2hex(random_bytes(8));
    $filename = 'export_results_' . date('Ymd_His') . '_' . $exportId . '.csv';
    $filepath = $exportDir . '/' . $filename;
    
    $output = fopen($filepath, 'w');
    
    $headers = ['Voter ID', 'Name', 'Email', 'Voted At'];
    foreach ($positions as $pos) {
        $headers[] = $pos['name'];
    }
    fputcsv($output, $headers, ',', '"', '\\');
    
    $candidateMap = [];
    $candidates = $pdo->query("
        SELECT c.id, c.name, pl.name AS partylist_name
        FROM candidates c
        LEFT JOIN partylists pl ON c.partylist_id = pl.id
    ")->fetchAll();
    foreach ($candidates as $c) {
        $candidateMap[$c['id']] = $c['name'] . ($c['partylist_name'] ? ' (' . $c['partylist_name'] . ')' : '');
    }
    
    $offset = 0;
    $chunkSize = 100;
    $processed = 0;
    
    $progressFile = $exportDir . '/progress_' . $exportId . '.json';
    
    file_put_contents($progressFile, json_encode([
        'progress' => 0,
        'total' => $totalVotes,
        'processed' => 0,
        'status' => 'processing',
        'download_url' => null
    ]), LOCK_EX);
    
    while (true) {
        $stmt = $pdo->prepare("
            SELECT v.id, v.voter_identifier, v.voter_name, v.email, v.voted_at
            FROM votes v
            ORDER BY v.voted_at ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$chunkSize, $offset]);
        $votes = $stmt->fetchAll();
        
        if (empty($votes)) {
            break;
        }
        
        $voteIds = array_column($votes, 'id');
        $placeholders = implode(',', array_fill(0, count($voteIds), '?'));
        
        $detailStmt = $pdo->prepare("
            SELECT vd.vote_id, vd.position_id, vd.candidate_id
            FROM vote_details vd
            WHERE vd.vote_id IN ($placeholders)
        ");
        $detailStmt->execute($voteIds);
        $allDetails = $detailStmt->fetchAll();
        
        $detailsByVote = [];
        foreach ($allDetails as $d) {
            $detailsByVote[$d['vote_id']][$d['position_id']] = $d['candidate_id'];
        }
        
        foreach ($votes as $vote) {
            $row = [
                $vote['voter_identifier'],
                $vote['voter_name'] ?? '',
                $vote['email'] ?? '',
                "'" . date('F j, Y g:i A', strtotime($vote['voted_at']))
            ];
            
            $selectionsByPosition = $detailsByVote[$vote['id']] ?? [];
            
            foreach ($positions as $pos) {
                $candidateName = '';
                $candidateId = $selectionsByPosition[$pos['id']] ?? null;
                if ($candidateId && isset($candidateMap[$candidateId])) {
                    $candidateName = $candidateMap[$candidateId];
                }
                $row[] = $candidateName;
            }
            
            fputcsv($output, $row, ',', '"', '\\');
            $processed++;
        }
        
        $offset += $chunkSize;
        
        file_put_contents($progressFile, json_encode([
            'progress' => $totalVotes > 0 ? round(($processed / $totalVotes) * 100) : 100,
            'total' => $totalVotes,
            'processed' => $processed,
            'status' => 'processing',
            'download_url' => null
        ]), LOCK_EX);
    }
    
    fclose($output);
    
    file_put_contents($progressFile, json_encode([
        'progress' => 100,
        'total' => $totalVotes,
        'processed' => $processed,
        'status' => 'complete',
        'download_url' => APP_URL . '/admin/api/results/download?file=' . $filename
    ]), LOCK_EX);
    
    return $this->json([
        'success' => true,
        'export_id' => $exportId,
        'message' => 'Export started'
    ]);
}

public function downloadCSV() {
    $adminRole = SessionService::getAdminRole();
    if ($adminRole !== 'superadmin') {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    $file = $_GET['file'] ?? '';
    
    // Updated regex to allow timestamp + random ID in filename
    if (!preg_match('/^export_results_\d{8}_\d{6}_[a-f0-9]{16}\.csv$/', $file)) {
        return $this->json(['success' => false, 'error' => 'Invalid file'], 422);
    }
    
    $filepath = STORAGE_PATH . '/exports/' . $file;
    
    if (!file_exists($filepath)) {
        return $this->json(['success' => false, 'error' => 'File not found'], 404);
    }
    
    $timestamp = date('Ymd_His');
    $downloadName = 'election_results_' . $timestamp . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($filepath);
    exit;
}

public function exportProgress() {
    $exportDir = STORAGE_PATH . '/exports';
    $exportId = $_GET['export_id'] ?? '';
    
    // Validate export ID
    if (!preg_match('/^[a-f0-9]{16}$/', $exportId)) {
        return $this->json(['success' => false, 'error' => 'Invalid export ID'], 422);
    }
    
    $progressFile = $exportDir . '/progress_' . $exportId . '.json';
    
    if (!file_exists($progressFile)) {
        return $this->json(['success' => false, 'error' => 'Export not found'], 404);
    }
    
    $progress = json_decode(file_get_contents($progressFile), true);
    
    if (!$progress) {
        return $this->json(['success' => false, 'error' => 'Export not found'], 404);
    }
    
    return $this->json([
        'success' => true,
        'progress' => $progress['progress'],
        'total' => $progress['total'],
        'processed' => $progress['processed'],
        'status' => $progress['status'],
        'download_url' => $progress['download_url']
    ]);
}

private function cleanupStaleExports($exportDir, $maxAge = 3600) {
    $files = glob($exportDir . '/*');
    $now = time();
    $deleted = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileAge = $now - filemtime($file);
            if ($fileAge > $maxAge) {
                unlink($file);
                $deleted++;
            }
        }
    }
    
    return $deleted;
}
    
    
public function deleteElectionData() {
    $adminId = (int)(SessionService::getAdminData()['user_id'] ?? 0);
    $canDelete = false;
    if ($adminId) {
        $stmt = \App\Core\Model::db()->prepare("SELECT can_delete_election FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        $canDelete = $row && $row['can_delete_election'] == 1;
    }
    if (!$canDelete) {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    
    $confirmation = $_POST['confirmation'] ?? '';
    $requiredPhrase = 'DELETE_ALL_ELECTION_DATA';
    
    if ($confirmation !== $requiredPhrase) {
        return $this->json([
            'success' => false,
            'error' => 'Please type DELETE_ALL_ELECTION_DATA to confirm deletion'
        ], 422);
    }
    
    $pdo = Model::db();
    
    try {
        $pdo->beginTransaction();
        
        // 1. Count votes before deletion
        $voteCount = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
        
        // 2. Delete election data
        $pdo->exec("DELETE FROM votes");
        $pdo->exec("DELETE FROM vote_counts");
        
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
            'ELECTION_DATA_DELETED',
            "Cleared all election data: {$voteCount} votes",
            $ip
        ]);
        
        // 4. Commit both deletion AND security event
        $pdo->commit();
        
        // 5. Audit log (separate - can fail without data loss)
        $this->audit('election_data', 'DELETE', 'all', ['votes' => $voteCount], null, "Cleared all election data: {$voteCount} votes");
        
        return $this->json(['success' => true, 'message' => 'Election data cleared successfully']);
    } catch (\Exception $e) {
        $pdo->rollBack();
        return $this->json(['success' => false, 'error' => 'Failed to clear election data'], 500);
    }
}
    
 public function searchVotes() {
    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 2) {
        return $this->json(['success' => true, 'votes' => []]);
    }

    $limit = $this->getLimit(20, 20);
    
    $pdo = Model::db();
    $stmt = $pdo->prepare("
        SELECT id, voter_identifier, voter_name, email, voted_at 
        FROM votes 
        WHERE voter_name LIKE ? OR email LIKE ?
        ORDER BY voted_at DESC 
        LIMIT ?
    ");
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm, $limit]);
    $votes = $stmt->fetchAll();
    
    return $this->json(['success' => true, 'votes' => $votes]);
}
    
    public function voteDetail() {
        $voterIdentifier = $_GET['voter_id'] ?? '';
        
        if (empty($voterIdentifier)) {
            return $this->json(['success' => false, 'error' => 'Missing voter ID'], 422);
        }
        
        $voteService = new \App\Services\VoteService();
        $details = $voteService->getVoteDetails($voterIdentifier);
        
        if (!$details) {
            return $this->json(['success' => false, 'error' => 'Vote not found'], 404);
        }
        
        return $this->json(['success' => true, 'vote' => $details]);
    }
}

