<?php
namespace App\Services;

use App\Core\Model;

class EmailService {
    private $pdo;
    private $ses;
    private $maxRetries = 5;
    private $batchSize = 10;
    private $delaySeconds = 5;
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo ?? Model::db();
        $this->ses = new Mailer();
    }
    
    public function queueEmail($voterId, $email, $voteData) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Email queue skipped - invalid format: $email");
            return false;
        }
        
        $checkStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM email_queue 
             WHERE student_id = ? 
             AND status IN ('pending', 'processing', 'sent')"
        );
        $checkStmt->execute([$voterId]);
        if ($checkStmt->fetchColumn() > 0) {
            error_log("Email queue skipped - duplicate voter: $voterId");
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO email_queue (student_id, email, vote_data, status, attempts, created_at) 
                 VALUES (?, ?, ?, 'pending', 0, NOW())"
            );
            $stmt->execute([$voterId, $email, json_encode($voteData)]);
            return true;
        } catch (\PDOException $e) {
            error_log("Email queue error: " . $e->getMessage());
            return false;
        }
    }
    
    public function processQueue($maxEmails = 50) {
        $batchSize = $this->batchSize;
        $delaySeconds = $this->delaySeconds;
        $totalProcessed = 0;
        $totalFailed = 0;
        
        while ($totalProcessed < $maxEmails) {
            $remaining = $maxEmails - $totalProcessed;
            $currentBatchSize = min($batchSize, $remaining);
            
            $stmt = $this->pdo->prepare(
                "SELECT * FROM email_queue 
                 WHERE status = 'pending' 
                 AND attempts < ? 
                 AND (next_retry_at IS NULL OR next_retry_at <= NOW())
                 ORDER BY 
                    CASE WHEN attempts = 0 THEN 0 ELSE 1 END,
                    created_at ASC 
                 LIMIT ?"
            );
            $stmt->execute([$this->maxRetries, $currentBatchSize]);
            $queue = $stmt->fetchAll();
            
            if (empty($queue)) {
                break;
            }
            
            $ids = array_column($queue, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $updateStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'processing', attempts = attempts + 1 WHERE id IN ($placeholders)"
            );
            $updateStmt->execute($ids);
            
            $emails = [];
            foreach ($queue as $item) {
                $voteData = json_decode($item['vote_data'], true) ?: [];
                $emails[] = [
                    'queue_id' => $item['id'],
                    'email' => $item['email'],
                    'subject' => 'Vote Receipt - ' . APP_NAME,
                    'html_body' => $this->buildReceiptHTML($item['student_id'], $voteData),
                    'text_body' => $this->buildReceiptText($item['student_id'], $voteData)
                ];
            }
            
            $results = $this->ses->sendParallelEmails($emails);
            
            $batchProcessed = 0;
            $batchFailed = 0;
            
            foreach ($queue as $item) {
                if (!empty($results[$item['id']])) {
                    $updateStmt = $this->pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$item['id']]);
                    $batchProcessed++;
                } else {
                    $this->handleFailedSend($item, new \Exception("SendGrid parallel send failed"));
                    $batchFailed++;
                }
            }
            
            $totalProcessed += $batchProcessed + $batchFailed;
            $totalFailed += $batchFailed;
            
            error_log("Batch processed: {$batchProcessed} sent, {$batchFailed} failed, batch size: " . count($queue));
            
            if ($totalProcessed < $maxEmails && $batchProcessed + $batchFailed > 0) {
                sleep($delaySeconds);
            }
        }
        
        $pendingStmt = $this->pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
        $pending = (int)$pendingStmt->fetchColumn();
        
        return [
            'processed' => $totalProcessed,
            'failed' => $totalFailed,
            'pending' => $pending
        ];
    }
    
    private function handleFailedSend($item, $exception) {
        $attempts = $item['attempts'] + 1;
        $errorMsg = substr($exception->getMessage(), 0, 500);
        
        if ($attempts >= $this->maxRetries) {
            $updateStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'failed', failed_at = NOW(), error_message = ? WHERE id = ?"
            );
            $updateStmt->execute([$errorMsg, $item['id']]);
        } else {
            $delayMinutes = pow(2, $attempts);
            $updateStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'pending', attempts = ?, next_retry_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), error_message = ? WHERE id = ?"
            );
            $updateStmt->execute([$attempts, $delayMinutes, $errorMsg, $item['id']]);
        }
    }
    
    public function sendDirectEmail($to, $subject, $htmlBody, $textBody = '') {
        return $this->ses->sendEmail(SMTP_FROM_EMAIL, $to, $subject, $htmlBody, $textBody);
    }
    
    public function getQueueStats() {
        $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM email_queue GROUP BY status");
        $stats = ['total' => 0, 'pending' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0];
        while ($row = $stmt->fetch()) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }
        return $stats;
    }
    
    public function cleanupSentEmails($minutesToKeep = 10) {
        $stmt = $this->pdo->prepare(
            "DELETE FROM email_queue WHERE status = 'sent' AND sent_at IS NOT NULL AND sent_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$minutesToKeep]);
        return $stmt->rowCount();
    }
    
    public function cleanupFailedEmails($daysToKeep = 30) {
        $stmt = $this->pdo->prepare(
            "DELETE FROM email_queue WHERE status = 'failed' AND failed_at IS NOT NULL AND failed_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$daysToKeep]);
        return $stmt->rowCount();
    }
    
    public function resetStuckEmails($hoursStuck = 1) {
        $stmt = $this->pdo->prepare(
            "UPDATE email_queue SET status = 'pending', attempts = 0, next_retry_at = NULL, error_message = NULL 
             WHERE status = 'processing' AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)"
        );
        $stmt->execute([$hoursStuck]);
        return $stmt->rowCount();
    }
    
    public function cleanupOldPendingEmails($daysToKeep = 14) {
        $stmt = $this->pdo->prepare(
            "DELETE FROM email_queue WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$daysToKeep]);
        return $stmt->rowCount();
    }
    
    public function retryFailedEmails() {
        $stmt = $this->pdo->prepare(
            "UPDATE email_queue SET status = 'pending', attempts = 0, next_retry_at = NULL, error_message = NULL 
             WHERE status = 'failed' AND attempts < ?"
        );
        $stmt->execute([$this->maxRetries]);
        return $stmt->rowCount();
    }
    
    private function getDBBadgeColors($partylistName) {
        if (empty($partylistName)) return ['bg' => null, 'fg' => null];
        $stmt = $this->pdo->prepare("SELECT badge_color, badge_text_color FROM partylists WHERE name = ? LIMIT 1");
        $stmt->execute([$partylistName]);
        $row = $stmt->fetch();
        return $row ? ['bg' => $row['badge_color'], 'fg' => $row['badge_text_color']] : ['bg' => null, 'fg' => null];
    }

    private function buildReceiptHTML($voterId, $voteData) {
        $voterName = $voteData['voter_name'] ?? '';
        $votedAt = $voteData['voted_at'] ?? date('F j, Y g:i A');
        $selections = $voteData['named_vote_data'] ?? [];
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light"><style>
            @media (prefers-color-scheme: dark) {
                .partylist { color: inherit !important; forced-color-adjust: none; -webkit-text-size-adjust: 100%; }
                body { background: #111827 !important; forced-color-adjust: none; }
                .container { background: #1f2937 !important; box-shadow: none !important; }
            }
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color-scheme: light; forced-color-adjust: none; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
            .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 20px; }
            .header h2 { color: #1e40af; margin: 0; }
            .header p { color: #6b7280; margin: 5px 0 0; }
            .info { background: #eff6ff; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
            .info p { margin: 5px 0; color: #374151; }
            .position-card { background: #f9fafb; border-radius: 8px; padding: 15px; margin-bottom: 10px; border-left: 4px solid #2563eb; }
            .position-name { font-weight: bold; color: #1f2937; margin: 0 0 5px; }
            .candidate-name { color: #4b5563; margin: 0; }
            .partylist { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px; }
            .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; }
        </style></head><body>
        <div class="container">
            <div class="header"><h2>Vote Receipt</h2><p>' . APP_NAME . '</p></div>
            <div class="info">
                <p><strong>Voter Name:</strong> ' . htmlspecialchars($voterName) . '</p>
                <p><strong>Voter ID:</strong> ' . htmlspecialchars($voterId) . '</p>
                <p><strong>Voted At:</strong> ' . $votedAt . '</p>
            </div>
            <div class="positions"><h3 style="color: #1f2937;">Your Selections:</h3>';
        
        foreach ($selections as $position => $data) {
            $candidateName = is_array($data) ? ($data['candidate_name'] ?? '') : $data;
            $partylistName = is_array($data) ? ($data['partylist_name'] ?? '') : '';
            $badges = [];
            if ($partylistName) {
                $dbCol = $this->getDBBadgeColors($partylistName);
                if (!$dbCol['bg'] || !$dbCol['fg']) {
                    // Fall back to voteData if DB lookup misses (shouldn't happen normally)
                    $badgeColor = $data['partylist_badge_color'] ?? '#1e40af';
                    $badgeTextColor = $data['partylist_badge_text_color'] ?? '#ffffff';
                } else {
                    $badgeColor = $dbCol['bg'];
                    $badgeTextColor = $dbCol['fg'];
                }
                $badges = ['bg' => $badgeColor, 'fg' => $badgeTextColor];
            } else {
                $badges = ['bg' => null, 'fg' => null];
            }

            $html .= '<div class="position-card"><p class="position-name">' . htmlspecialchars($position) . '</p><p class="candidate-name">' . htmlspecialchars($candidateName);
            if ($badges['bg']) {
                $html .= '<span class="partylist" style="background:' . htmlspecialchars($badges['bg']) . '!important;color:' . htmlspecialchars($badges['fg']) . '!important;">' . htmlspecialchars($partylistName) . '</span>';
            }
            $html .= '</p></div>';
        }
        
        $html .= '</div><div class="footer"><p>This is an automated receipt. Please do not reply to this email.</p><p>&copy; ' . date('Y') . ' SOFTNET Information Technology Center, Inc.</p></div></div></body></html>';
        return $html;
    }
    
    private function buildReceiptText($voterId, $voteData) {
        $voterName = $voteData['voter_name'] ?? '';
        $votedAt = $voteData['voted_at'] ?? date('F j, Y g:i A');
        $selections = $voteData['named_vote_data'] ?? [];
        
        $text = "VOTE RECEIPT\n=============\n\n";
        $text .= "Voter Name: {$voterName}\n";
        $text .= "Voter ID: {$voterId}\n";
        $text .= "Voted At: {$votedAt}\n\n";
        $text .= "Your Selections:\n----------------\n";
        
        foreach ($selections as $position => $data) {
            $candidateName = is_array($data) ? ($data['candidate_name'] ?? '') : $data;
            $partylistName = is_array($data) ? ($data['partylist_name'] ?? '') : '';
            $badgeColor = '#1e40af';
            $badgeTextColor = '#ffffff';
            if ($partylistName) {
                $dbCol = $this->getDBBadgeColors($partylistName);
                $badgeColor = ($dbCol['bg'] && $dbCol['fg']) ? $dbCol['bg'] : ($data['partylist_badge_color'] ?? '#1e40af');
                $badgeTextColor = ($dbCol['bg'] && $dbCol['fg']) ? $dbCol['fg'] : ($data['partylist_badge_text_color'] ?? '#ffffff');
            }
            $text .= "{$position}: {$candidateName}";
            if ($partylistName) $text .= " | Partylist: {$partylistName} (badge color: {$badgeColor}, text color: {$badgeTextColor})";
            $text .= "\n";
        }
        
        $text .= "\n---\nThis is an automated receipt. Please do not reply.\n(c) " . date('Y') . " SOFTNET Information Technology Center, Inc.\n";
        return $text;
    }
}