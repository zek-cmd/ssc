<?php
// app/Services/VoteService.php
namespace App\Services;

use App\Core\Model;

class VoteService {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Model::db();
    }
    
    public function isVotingEnabled() {
        $stmt = $this->pdo->query("SELECT enable_vote FROM votingswitch LIMIT 1");
        $result = $stmt->fetch();
        return $result && $result['enable_vote'] == 1;
    }
    
    public function loadElectionData() {
        $stmt = $this->pdo->query("
            SELECT 
                p.id, p.name, p.description, p.sort_order,
                c.id as candidate_id, c.name as candidate_name, 
                c.platform, c.image_path,
                pl.name as partylist_name, pl.sort_order as partylist_sort_order,
                pl.badge_color, pl.badge_text_color
            FROM positions p
            LEFT JOIN candidates c ON p.id = c.position_id
            LEFT JOIN partylists pl ON c.partylist_id = pl.id
            ORDER BY p.sort_order ASC, pl.sort_order ASC, pl.name ASC, c.name ASC
        ");
        
        $positions = [];
        while ($row = $stmt->fetch()) {
            $positionId = $row['id'];
            
            if (!isset($positions[$positionId])) {
                $positions[$positionId] = [
                    'id' => $positionId,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'sort_order' => $row['sort_order'],
                    'candidates' => []
                ];
            }
            
            if ($row['candidate_id']) {
                $positions[$positionId]['candidates'][] = [
                    'id' => $row['candidate_id'],
                    'name' => $row['candidate_name'],
                    'platform' => $row['platform'],
                    'image_path' => $row['image_path'],
                    'partylist_name' => $row['partylist_name'],
                    'partylist_badge_color' => $row['badge_color'] ?? '#1e40af',
                    'partylist_badge_text_color' => $row['badge_text_color'] ?? '#ffffff'
                ];
            }
        }
        
        return array_values($positions);
    }
    
    public function submitVote($email, $selections) {
        try {
            $this->pdo->beginTransaction();
            
            $voterIdentifier = bin2hex(random_bytes(6));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO votes (voter_identifier, email, voted_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([
                $voterIdentifier,
                !empty($email) ? $email : null
            ]);
            $voteId = $this->pdo->lastInsertId();
            
            $detailStmt = $this->pdo->prepare("
                INSERT INTO vote_details (vote_id, position_id, candidate_id) 
                VALUES (?, ?, ?)
            ");
            foreach ($selections as $positionId => $candidateId) {
                $detailStmt->execute([$voteId, (int)$positionId, (int)$candidateId]);
            }
            
            if (!empty($email)) {
                $emailService = new \App\Services\EmailService($this->pdo);
                $emailService->queueEmail($voterIdentifier, $email, [
                    'voted_at' => date('F j, Y g:i A'),
                    'named_vote_data' => $this->getNamedVoteData($selections)
                ]);
            }
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Vote submitted successfully'];
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getNamedVoteData($selections) {
        $namedData = [];
        foreach ($selections as $positionId => $candidateId) {
            $stmt = $this->pdo->prepare("SELECT name FROM positions WHERE id = ?");
            $stmt->execute([$positionId]);
            $position = $stmt->fetch();
            
            $stmt = $this->pdo->prepare("
                SELECT c.name, pl.name as partylist_name, pl.badge_color, pl.badge_text_color
                FROM candidates c 
                LEFT JOIN partylists pl ON c.partylist_id = pl.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$candidateId]);
            $candidate = $stmt->fetch();
            
            $positionName = $position['name'] ?? 'Position ' . $positionId;
            $namedData[$positionName] = [
                'candidate_name' => $candidate['name'] ?? 'Unknown',
                'partylist_name' => $candidate['partylist_name'] ?? '',
                'partylist_badge_color' => $candidate['badge_color'] ?? '#1e40af',
                'partylist_badge_text_color' => $candidate['badge_text_color'] ?? '#ffffff'
            ];
        }
        return $namedData;
    }
    
    public function getVoteDetails($voterIdentifier) {
       $stmt = $this->pdo->prepare("
    SELECT id, voter_identifier, voter_name, email, voted_at
    FROM votes 
    WHERE voter_identifier = ?
");
        $stmt->execute([$voterIdentifier]);
        $vote = $stmt->fetch();
        
        if (!$vote) {
            return null;
        }
        
        $detailStmt = $this->pdo->prepare("
            SELECT vd.position_id, vd.candidate_id,
                   p.name as position_name,
                   c.name as candidate_name, c.image_path,
                   pl.name as partylist_name,
                   pl.badge_color,
                   pl.badge_text_color
            FROM vote_details vd
            JOIN positions p ON vd.position_id = p.id
            JOIN candidates c ON vd.candidate_id = c.id
            LEFT JOIN partylists pl ON c.partylist_id = pl.id
            WHERE vd.vote_id = ?
            ORDER BY p.sort_order ASC
        ");
        $detailStmt->execute([$vote['id']]);
        $details_rows = $detailStmt->fetchAll();
        
        $positions = [];
        foreach ($details_rows as $row) {
            $positions[] = [
                'position_id' => $row['position_id'],
                'position_name' => $row['position_name'],
                'candidate_name' => $row['candidate_name'],
                'image_path' => $row['image_path'],
                'partylist_name' => $row['partylist_name'],
                'partylist_badge_color' => $row['badge_color'] ?? '#1e40af',
                'partylist_badge_text_color' => $row['badge_text_color'] ?? '#ffffff'
            ];
        }
        
        return [
            'voter_identifier' => $vote['voter_identifier'],
              'voter_name' => $vote['voter_name'],
            'email' => $vote['email'],
            'voted_at' => date('F j, Y g:i A', strtotime($vote['voted_at'])),
            'positions' => $positions
        ];
    }
}