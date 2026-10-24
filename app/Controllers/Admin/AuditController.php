<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\AdminSecurityService;
use App\Services\AuditService;
use App\Core\Model;

class AuditController extends Controller {
    
    private function canManageAuditLogs() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        if (!$adminId) return false;
        $stmt = \App\Core\Model::db()->prepare("SELECT can_manage_audit_logs FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        return $row && $row['can_manage_audit_logs'] == 1;
    }
    
    private function checkAccess() {
        $adminRole = SessionService::getAdminRole();
        if ($adminRole !== 'superadmin') {
            return false;
        }
        if (!$this->canManageAuditLogs()) {
            return false;
        }
        return true;
    }
    
    private function computeAdaptiveLimit($total, $target = 12, $min = 8, $max = 20) {
        $total = (int)$total;
        if ($total <= 0) {
            return ['limit' => $target, 'pad' => false];
        }
        if ($total <= $max) {
            return ['limit' => $total, 'pad' => false];
        }
        $bestDivisor = 0;
        for ($d = $min; $d <= $max; $d++) {
            if ($total % $d === 0) {
                $bestDivisor = $d;
            }
        }
        if ($bestDivisor > 0) {
            return ['limit' => $bestDivisor, 'pad' => false];
        }
        return ['limit' => $target, 'pad' => true];
    }
    
    public function index() {
        if (!$this->checkAccess()) {
            $this->redirect(APP_URL . '/admin/dashboard');
            return;
        }
        
        AdminSecurityService::init();
        
        $nonce = AdminSecurityService::getNonce();
        $csrfToken = SessionService::getCSRFToken();
        $adminUsername = SessionService::getAdminUsername();
        $isSuperAdmin = true;
        $canClearAuditLogs = $this->canManageAuditLogs();
        
        $title = APP_NAME . ' - Audit Logs';
        
        ob_start();
        require APP_PATH . '/Views/admin/audit_logs.php';
        $content = ob_get_clean();
        
        require APP_PATH . '/Views/layouts/admin.php';
    }
    
    public function getLogs() {
        if (!$this->checkAccess()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $page = $this->getPage();
        $target = min(30, max(5, (int)($_GET['target'] ?? 12)));
        $search = $this->getSearch();
        
        $auditService = new AuditService();
        $total = $auditService->getTotalCount($search);
        $adaptive = $this->computeAdaptiveLimit($total, $target, 8, 20);
        $limit = $adaptive['limit'];
        $result = $auditService->getLogs($page, $limit, $search);
        
        return $this->json([
            'success' => true,
            'limit' => $limit,
            'pad' => $adaptive['pad'],
            ...$result
        ]);
    }
    
    public function getAuditLogDetail() {
        if (!$this->checkAccess()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            return $this->json(['success' => false, 'error' => 'ID required'], 422);
        }
        
        $pdo = Model::db();
        $stmt = $pdo->prepare("
            SELECT a.*, u.name as admin_name
            FROM audit_logs a
            LEFT JOIN users u ON a.admin_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $log = $stmt->fetch();
        
        if (!$log) {
            return $this->json(['success' => false, 'error' => 'Log not found'], 404);
        }
        
        $changes = [];
        $showChanges = true;
        
        if ($log['entity_type'] === 'users') {
            $showChanges = false;
        } elseif ($log['action'] === 'CLEAR') {
            $showChanges = false;
        } else {
            $oldValues = json_decode($log['old_values'], true);
            $newValues = json_decode($log['new_values'], true);
            
            $fields = [];
            if ($oldValues && is_array($oldValues)) {
                foreach ($oldValues as $field => $value) {
                    $fields[$field] = ['old' => $value, 'new' => null];
                }
            }
            if ($newValues && is_array($newValues)) {
                foreach ($newValues as $field => $value) {
                    if (isset($fields[$field])) {
                        $fields[$field]['new'] = $value;
                    } else {
                        $fields[$field] = ['old' => null, 'new' => $value];
                    }
                }
            }
            
            foreach ($fields as $field => $data) {
                $changes[] = [
                    'field' => $field,
                    'old_value' => $data['old'] ?? '-',
                    'new_value' => $data['new'] ?? '-'
                ];
            }
        }
        
        return $this->json([
            'success' => true,
            'log' => [
                'id' => (int)$log['id'],
                'admin_name' => $log['admin_name'] ?? 'Unknown',
                'action' => $log['action'],
                'entity_type' => $log['entity_type'],
                'description' => $log['description'] ?? '-',
                'ip_address' => $log['ip_address'] ?? '-',
                'created_at' => date('F j, Y g:i A', strtotime($log['created_at'])),
                'changes' => $changes,
                'show_changes' => $showChanges
            ]
        ]);
    }
    
public function clearLogs() {
    if (!$this->checkAccess()) {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    $pdo = Model::db();
    
    try {
        $pdo->beginTransaction();
        
        $count = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        
        $pdo->exec("DELETE FROM audit_logs");
        
        $adminData = SessionService::getAdminData();
        $adminId = $adminData['user_id'] ?? 0;
        $ip = \App\Services\SecurityService::getClientIP();
        
        $securityStmt = $pdo->prepare("
            INSERT INTO security_events (admin_id, event_type, description, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $securityStmt->execute([
            $adminId,
            'AUDIT_LOGS_CLEARED',
            "Cleared {$count} audit log entries",
            $ip
        ]);
        
        $pdo->commit();
        
        return $this->json(['success' => true, 'message' => "{$count} log entries cleared"]);
    } catch (\Exception $e) {
        $pdo->rollBack();
        return $this->json(['success' => false, 'error' => 'Failed to clear audit logs'], 500);
    }
}
    
public function exportCSV() {
    if (!$this->checkAccess()) {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    $auditService = new AuditService();
    
    $totalLogs = $auditService->getTotalCount();
    
    $exportDir = STORAGE_PATH . '/exports';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    $this->cleanupStaleExports($exportDir, 3600);
    
    $exportId = bin2hex(random_bytes(8));
    $filename = 'export_audit_logs_' . date('Ymd_His') . '_' . $exportId . '.csv';
    $filepath = $exportDir . '/' . $filename;
    
    $output = fopen($filepath, 'w');
    
    fputcsv($output, ['Date/Time', 'Admin', 'Action', 'Entity Type', 'Description', 'IP Address'], ',', '"', '\\');
    
    $offset = 0;
    $chunkSize = 100;
    $processed = 0;
    
    $progressFile = $exportDir . '/progress_' . $exportId . '.json';
    
    file_put_contents($progressFile, json_encode([
        'progress' => 0,
        'total' => $totalLogs,
        'processed' => 0,
        'status' => 'processing',
        'download_url' => null
    ]), LOCK_EX);
    
    while (true) {
        $result = $auditService->getLogsForExport($offset, $chunkSize);
        $logs = $result['data'];
        
        if (empty($logs)) {
            break;
        }
        
        foreach ($logs as $log) {
            fputcsv($output, [
                "'" . date('F j, Y g:i A', strtotime($log['created_at'])),
                $log['admin_name'] ?? 'Unknown',
                $log['action'],
                $log['entity_type'] ?? '-',
                $log['description'] ?? '-',
                $log['ip_address'] ?? '-'
            ], ',', '"', '\\');
            $processed++;
        }
        
        $offset += $chunkSize;
        
        file_put_contents($progressFile, json_encode([
            'progress' => $totalLogs > 0 ? round(($processed / $totalLogs) * 100) : 100,
            'total' => $totalLogs,
            'processed' => $processed,
            'status' => 'processing',
            'download_url' => null
        ]), LOCK_EX);
    }
    
    fclose($output);
    
    file_put_contents($progressFile, json_encode([
        'progress' => 100,
        'total' => $totalLogs,
        'processed' => $processed,
        'status' => 'complete',
        'download_url' => APP_URL . '/admin/api/audit-logs/download?file=' . $filename
    ]), LOCK_EX);
    
    return $this->json([
        'success' => true,
        'export_id' => $exportId,
        'message' => 'Export started'
    ]);
}
    
    public function downloadCSV() {
    if (!$this->checkAccess()) {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    $file = $_GET['file'] ?? '';
    
    if (!preg_match('/^export_audit_logs_\d{8}_\d{6}_[a-f0-9]{16}\.csv$/', $file)) {
        return $this->json(['success' => false, 'error' => 'Invalid file'], 422);
    }
    
    $filepath = STORAGE_PATH . '/exports/' . $file;
    
    if (!file_exists($filepath)) {
        return $this->json(['success' => false, 'error' => 'File not found'], 404);
    }
    
    $timestamp = date('Ymd_His');
    $downloadName = 'audit_logs_' . $timestamp . '.csv';
    
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
    if (!$this->checkAccess()) {
        return $this->json(['success' => false, 'error' => 'Access denied'], 403);
    }
    
    $exportDir = STORAGE_PATH . '/exports';
    $exportId = $_GET['export_id'] ?? '';
    
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
}