<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\SessionService;
use App\Services\AuditService;

class ApiController extends Controller {
    
    private function getInput($key, $default = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $input = file_get_contents('php://input');
            parse_str($input, $data);
            return $data[$key] ?? $default;
        }
        return $_POST[$key] ?? $default;
    }

    private function audit($entityType, $action, $entityId, $oldValues = null, $newValues = null, $description = '') {
        (new AuditService())->log($entityType, $action, $entityId, $oldValues, $newValues, $description);
    }

    private function canEditInfo() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        if (!$adminId) return false;
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->prepare("SELECT can_edit_info FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        return $row && $row['can_edit_info'] == 1;
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

    public function getStats() {
        $pdo = \App\Core\Model::db();
        return $this->json([
            'success' => true,
            'positions' => (int)$pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn(),
            'candidates' => (int)$pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn(),
            'partylists' => (int)$pdo->query("SELECT COUNT(*) FROM partylists")->fetchColumn(),
        ]);
    }

    public function getVoteToggle() {
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->query("SELECT enable_vote FROM votingswitch LIMIT 1");
        $result = $stmt->fetch();
        return $this->json([
            'success' => true,
            'enabled' => $result && $result['enable_vote'] == 1
        ]);
    }

    public function toggleVote() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        $canManageVote = false;
        if ($adminId) {
            $stmt = \App\Core\Model::db()->prepare("SELECT can_manage_vote FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $row = $stmt->fetch();
            $canManageVote = $row && $row['can_manage_vote'] == 1;
        }
        if (!$canManageVote) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->query("SELECT enable_vote FROM votingswitch LIMIT 1");
        $current = $stmt->fetch();
        $newStatus = ($current && $current['enable_vote'] == 1) ? 0 : 1;
        
        $pdo->prepare("UPDATE votingswitch SET enable_vote = ?")->execute([$newStatus]);
        
        $statusText = $newStatus ? 'enabled' : 'disabled';
        $this->audit('votingswitch', 'UPDATE', 1, ['enable_vote' => !$newStatus], ['enable_vote' => $newStatus], "Voting {$statusText}");
        
        return $this->json([
            'success' => true,
            'enabled' => $newStatus == 1,
            'message' => 'Voting ' . $statusText
        ]);
    }

    public function getPositions() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $target = min(30, max(5, (int)($_GET['target'] ?? 12)));
        $search = trim($_GET['search'] ?? '');
        $total = (int)\App\Core\Model::db()->query("SELECT COUNT(*) FROM positions" . ($search ? " WHERE name LIKE " . \App\Core\Model::db()->quote('%' . $search . '%') : ""))->fetchColumn();
        $adaptive = $this->computeAdaptiveLimit($total, $target, 8, 20);
        $limit = $adaptive['limit'];
        $result = (new \App\Models\Position())->getAll($page, $limit, $search);
        return $this->json(['success' => true, 'limit' => $limit, 'pad' => $adaptive['pad'], ...$result]);
    }

    public function savePosition() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        $model = new \App\Models\Position();
        
        if ($id) {
            $existing = $model->find($id);
            if (!$existing) {
                return $this->json(['success' => false, 'error' => 'Position not found'], 404);
            }
            if (empty($name)) {
                $name = $existing['name'];
            }
            if (empty($description) && $description !== '') {
                $description = $existing['description'];
            }
            if (!isset($_POST['sort_order']) || $_POST['sort_order'] === '') {
                $sortOrder = (int)$existing['sort_order'];
            }
        }
        
        if (empty($name)) return $this->json(['success' => false, 'error' => 'Position name is required'], 422);
        
        try {
            if ($id) {
                $changed = ($existing['name'] !== $name || $existing['description'] !== $description);
                
                if ($changed) {
                    $oldValues = [
                        'Name' => $existing['name'],
                        'Description' => $existing['description'] ?: '-'
                    ];
                    $newValues = [
                        'Name' => $name,
                        'Description' => $description ?: '-'
                    ];
                    $model->update($id, ['name' => $name, 'description' => $description, 'sort_order' => $sortOrder]);
                    $this->audit('positions', 'UPDATE', $id, $oldValues, $newValues, "Updated position: {$name}");
                } else {
                    $model->update($id, ['name' => $name, 'description' => $description, 'sort_order' => $sortOrder]);
                }
            } else {
                $model->create(['name' => $name, 'description' => $description, 'sort_order' => $sortOrder]);
                $newValues = [
                    'Name' => $name,
                    'Description' => $description ?: '-'
                ];
                $this->audit('positions', 'CREATE', null, null, $newValues, "Created position: {$name}");
            }
            return $this->json(['success' => true, 'message' => $id ? 'Position updated' : 'Position created']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Failed to save position'], 500);
        }
    }

    public function deletePosition() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);
        $id = $_POST['id'] ?? null;
        if (!$id) return $this->json(['success' => false, 'error' => 'ID required'], 422);
        
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE position_id = ?");
        $stmt->execute([(int)$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return $this->json(['success' => false, 'error' => 'Cannot delete position with assigned candidates'], 409);
        }
        
        $model = new \App\Models\Position();
        $existing = $model->find($id);
        $oldValues = [
            'Name' => $existing['name'],
            'Description' => $existing['description'] ?: '-',
            'Badge Color' => $existing['badge_color'] ?? '#1e40af',
            'Text Color' => $existing['badge_text_color'] ?? '#ffffff'
        ];
        $model->delete((int)$id);
        $this->audit('positions', 'DELETE', $id, $oldValues, null, "Deleted position: " . ($existing['name'] ?? 'Unknown'));
        return $this->json(['success' => true, 'message' => 'Position deleted']);
    }

    public function bulkDeletePositions() {
        $ids = $_POST['ids'] ?? '[]';
        if (is_string($ids)) $ids = json_decode($ids, true);
        
        if (!is_array($ids) || empty($ids)) {
            return $this->json(['success' => false, 'error' => 'No items selected'], 422);
        }
        
        if (count($ids) > 100) {
            return $this->json(['success' => false, 'error' => 'Maximum 100 records per request'], 422);
        }
        
        $pdo = \App\Core\Model::db();
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT p.name, COUNT(c.id) as candidate_count FROM positions p LEFT JOIN candidates c ON p.id = c.position_id WHERE p.id IN ($placeholders) GROUP BY p.id HAVING candidate_count > 0");
        $stmt->execute(array_map('intval', $ids));
        $blocked = $stmt->fetchAll();
        
        if (!empty($blocked)) {
            return $this->json(['success' => false, 'error' => 'Candidates still assigned to the position'], 409);
        }
        
        $model = new \App\Models\Position();
        $deleted = 0;
        foreach ($ids as $id) {
            $model->delete((int)$id);
            $deleted++;
        }
        
        $this->audit('positions', 'DELETE', 'bulk', null, null, "Bulk deleted {$deleted} positions");
        return $this->json(['success' => true, 'message' => "{$deleted} deleted"]);
    }

    public function checkPositionDelete() {
        $id = $_POST['id'] ?? null;
        if (!$id) return $this->json(['success' => false, 'error' => 'ID required'], 422);
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE position_id = ?");
        $stmt->execute([(int)$id]);
        $count = (int)$stmt->fetchColumn();
        return $this->json(['success' => true, 'can_delete' => $count === 0, 'candidate_count' => $count, 'message' => $count > 0 ? "Cannot be deleted, {$count} candidate(s) assigned to this position" : 'Safe to delete']);
    }

    public function getCandidates() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $target = min(30, max(5, (int)($_GET['target'] ?? 12)));
        $search = trim($_GET['search'] ?? '');
        $pdo = \App\Core\Model::db();
        if ($search) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE name LIKE ?");
            $stmt->execute(['%' . $search . '%']);
            $total = (int)$stmt->fetchColumn();
        } else {
            $total = (int)$pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
        }
        $adaptive = $this->computeAdaptiveLimit($total, $target, 8, 20);
        $limit = $adaptive['limit'];
        $result = (new \App\Models\Candidate())->getAll($page, $limit, $search);
        return $this->json(['success' => true, 'limit' => $limit, 'pad' => $adaptive['pad'], ...$result]);
    }

    public function getCandidateOptions() {
        $positions = (new \App\Models\Position())->getAllOptions();
        $partylists = (new \App\Models\Partylist())->getAllOptions();
        return $this->json(['success' => true, 'positions' => $positions, 'partylists' => $partylists]);
    }

    public function saveCandidate() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $positionId = (int)($_POST['position_id'] ?? 0);
        $partylistId = $_POST['partylist_id'] ? (int)$_POST['partylist_id'] : null;
        $platform = trim($_POST['platform'] ?? '');
        $imagePath = $_POST['image_path'] ?? null;
        $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
        
        if (empty($name) || !$positionId) return $this->json(['success' => false, 'error' => 'Name and position are required'], 422);
        
        $model = new \App\Models\Candidate();
        $pdo = \App\Core\Model::db();
        
        $positionModel = new \App\Models\Position();
        $positionData = $positionModel->find($positionId);
        $positionName = $positionData['name'] ?? 'Unknown';
        
        $partylistName = '-';
        if ($partylistId) {
            $partylistModel = new \App\Models\Partylist();
            $partylistData = $partylistModel->find($partylistId);
            $partylistName = $partylistData['name'] ?? '-';
        }
        
        $data = ['name' => $name, 'position_id' => $positionId, 'partylist_id' => $partylistId, 'platform' => $platform];
        
        if ($id && $removeImage) {
            $existing = $model->find($id);
            if ($existing && !empty($existing['image_path'])) {
                $oldFile = PUBLIC_PATH . '/candidate_images/' . $existing['image_path'];
                if (file_exists($oldFile)) unlink($oldFile);
            }
            $data['image_path'] = null;
        }
        
        if ($imagePath) {
            $data['image_path'] = $imagePath;
            if ($id) {
                $existing = $existing ?? $model->find($id);
                if ($existing && !empty($existing['image_path'])) {
                    $oldFile = PUBLIC_PATH . '/candidate_images/' . $existing['image_path'];
                    if (file_exists($oldFile)) unlink($oldFile);
                }
            }
        }
        
        try {
            if ($id) {
                $existing = $existing ?? $model->find($id);
                
                $oldPositionName = '-';
                if ($existing['position_id']) {
                    $oldPosData = $positionModel->find($existing['position_id']);
                    $oldPositionName = $oldPosData['name'] ?? '-';
                }
                
                $oldPartylistName = '-';
                if (!empty($existing['partylist_id'])) {
                    $partylistModel = new \App\Models\Partylist();
                    $oldPlData = $partylistModel->find($existing['partylist_id']);
                    $oldPartylistName = $oldPlData['name'] ?? '-';
                }
                
                $oldValues = [
                    'Name' => $existing['name'],
                    'Position' => $oldPositionName,
                    'Partylist' => $oldPartylistName,
                    'Platform' => $existing['platform'] ?: '-',
                    'Image' => $existing['image_path'] ? 'UPDATE' : '-'
                ];
                
                $newValues = [
                    'Name' => $name,
                    'Position' => $positionName,
                    'Partylist' => $partylistName,
                    'Platform' => $platform ?: '-',
                    'Image' => $imagePath ? 'UPDATE' : ($removeImage ? '-' : ($existing['image_path'] ? 'UPDATE' : '-'))
                ];
                
                $changed = (
                    $existing['name'] !== $name || 
                    (int)$existing['position_id'] !== $positionId || 
                    $existing['partylist_id'] != $partylistId || 
                    $existing['platform'] !== $platform || 
                    ($imagePath && $existing['image_path'] !== $imagePath) ||
                    ($removeImage && !empty($existing['image_path']))
                );
                
                $model->update($id, $data);
                
                if ($changed) {
                    $this->audit('candidates', 'UPDATE', $id, $oldValues, $newValues, "Updated candidate: {$name}");
                }
            } else {
                $model->create($data);
                $newValues = [
                    'Name' => $name,
                    'Position' => $positionName,
                    'Partylist' => $partylistName,
                    'Platform' => $platform ?: '-',
                    'Image' => $imagePath ? 'UPDATE' : '-'
                ];
                $this->audit('candidates', 'CREATE', null, null, $newValues, "Created candidate: {$name}");
            }
            return $this->json(['success' => true, 'message' => $id ? 'Candidate updated' : 'Candidate created']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Failed to save candidate'], 500);
        }
    }

    public function deleteCandidate() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);
        $id = $_POST['id'] ?? null;
        if (!$id) return $this->json(['success' => false, 'error' => 'ID required'], 422);
        $model = new \App\Models\Candidate();
        $existing = $model->find($id);
        
        if ($existing && !empty($existing['image_path'])) {
            $imageFile = PUBLIC_PATH . '/candidate_images/' . $existing['image_path'];
            if (file_exists($imageFile)) {
                unlink($imageFile);
            }
        }
        
        $oldValues = [
            'Name' => $existing['name'] ?? '-',
            'Position' => '-',
            'Partylist' => '-',
            'Platform' => $existing['platform'] ?: '-',
            'Image' => !empty($existing['image_path']) ? 'UPDATE' : '-'
        ];
        
        if (!empty($existing['position_id'])) {
            $positionModel = new \App\Models\Position();
            $posData = $positionModel->find($existing['position_id']);
            $oldValues['Position'] = $posData['name'] ?? '-';
        }
        
        if (!empty($existing['partylist_id'])) {
            $partylistModel = new \App\Models\Partylist();
            $plData = $partylistModel->find($existing['partylist_id']);
            $oldValues['Partylist'] = $plData['name'] ?? '-';
        }
        
        $model->delete((int)$id);
        $this->audit('candidates', 'DELETE', $id, $oldValues, null, "Deleted candidate: " . ($existing['name'] ?? 'Unknown'));
        return $this->json(['success' => true, 'message' => 'Candidate deleted']);
    }

    public function bulkDeleteCandidates() {
        $ids = $_POST['ids'] ?? '[]';
        if (is_string($ids)) $ids = json_decode($ids, true);
        
        if (!is_array($ids) || empty($ids)) {
            return $this->json(['success' => false, 'error' => 'No items selected'], 422);
        }
        
        if (count($ids) > 100) {
            return $this->json(['success' => false, 'error' => 'Maximum 100 records per request'], 422);
        }
        
        $model = new \App\Models\Candidate();
        $pdo = \App\Core\Model::db();
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, image_path FROM candidates WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $ids));
        $candidates = $stmt->fetchAll();
        
        $imagePaths = [];
        foreach ($candidates as $candidate) {
            if (!empty($candidate['image_path'])) {
                $imagePaths[] = $candidate['image_path'];
            }
            $model->delete((int)$candidate['id']);
        }
        
        foreach ($imagePaths as $imagePath) {
            $imageFile = PUBLIC_PATH . '/candidate_images/' . $imagePath;
            if (file_exists($imageFile)) {
                unlink($imageFile);
            }
        }
        
        $this->audit('candidates', 'DELETE', 'bulk', null, null, "Bulk deleted " . count($ids) . " candidates");
        return $this->json(['success' => true, 'message' => count($ids) . ' candidates deleted']);
    }

    public function uploadCandidateImage() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return $this->json(['success' => false, 'error' => 'No image uploaded or upload error'], 422);
    }
    
    $file = $_FILES['image'];
    
    if ($file['size'] > 2 * 1024 * 1024) {
        return $this->json(['success' => false, 'error' => 'Image must be less than 2MB'], 422);
    }
    
    if (!is_uploaded_file($file['tmp_name'])) {
        return $this->json(['success' => false, 'error' => 'Invalid upload'], 422);
    }
    
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $actualMimeType = $finfo->file($file['tmp_name']);
    
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    
    if (!isset($allowedMimeTypes[$actualMimeType])) {
        return $this->json(['success' => false, 'error' => 'Only JPG, PNG, and WebP images are allowed'], 422);
    }
    
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return $this->json(['success' => false, 'error' => 'Invalid image file'], 422);
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $maxDimension = 4096;
    
    if ($width > $maxDimension || $height > $maxDimension) {
        return $this->json([
            'success' => false,
            'error' => "Image dimensions are too large. Maximum {$maxDimension}x{$maxDimension} pixels."
        ], 422);
    }
    
    $expectedMime = image_type_to_mime_type($imageInfo[2]);
    if ($expectedMime !== $actualMimeType) {
        return $this->json(['success' => false, 'error' => 'File content does not match image type'], 422);
    }
    
    $ext = $allowedMimeTypes[$actualMimeType];
    
    $filename = 'candidate_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = PUBLIC_PATH . '/candidate_images/' . $filename;
    
    $uploadDir = PUBLIC_PATH . '/candidate_images';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (extension_loaded('gd')) {
        try {
            $compressed = $this->compressImageFileSize($file['tmp_name'], $destPath, $actualMimeType);
            
            if ($compressed) {
                return $this->json([
                    'success' => true, 
                    'image_path' => $filename, 
                    'message' => 'Image uploaded and compressed',
                    'original_size' => $file['size'],
                    'compressed_size' => filesize($destPath)
                ]);
            }
        } catch (\Exception $e) {
        }
    }
    
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return $this->json(['success' => true, 'image_path' => $filename, 'message' => 'Image uploaded']);
    }
    
    return $this->json(['success' => false, 'error' => 'Failed to save image'], 500);
}

    private function compressImageFileSize($sourcePath, $destPath, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        $success = false;
        
        if ($mimeType == 'image/jpeg') {
            $success = imagejpeg($sourceImage, $destPath, 70);
        } elseif ($mimeType == 'image/png') {
            imagealphablending($sourceImage, false);
            imagesavealpha($sourceImage, true);
            $success = imagepng($sourceImage, $destPath, 6);
        } elseif ($mimeType == 'image/webp') {
            $success = imagewebp($sourceImage, $destPath, 70);
        }
        
        imagedestroy($sourceImage);
        
        return $success;
    }

    public function getPartylists() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $target = min(30, max(5, (int)($_GET['target'] ?? 12)));
        $search = trim($_GET['search'] ?? '');
        $pdo = \App\Core\Model::db();
        if ($search) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM partylists WHERE name LIKE ?");
            $stmt->execute(['%' . $search . '%']);
            $total = (int)$stmt->fetchColumn();
        } else {
            $total = (int)$pdo->query("SELECT COUNT(*) FROM partylists")->fetchColumn();
        }
        $adaptive = $this->computeAdaptiveLimit($total, $target, 8, 20);
        $limit = $adaptive['limit'];
        $result = (new \App\Models\Partylist())->getAll($page, $limit, $search);
        return $this->json(['success' => true, 'limit' => $limit, 'pad' => $adaptive['pad'], ...$result]);
    }

    public function savePartylist() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $badgeColor = trim($_POST['badge_color'] ?? '#1e40af');
        $badgeTextColor = trim($_POST['badge_text_color'] ?? '#ffffff');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        $model = new \App\Models\Partylist();
        
        if ($id) {
            $existing = $model->find($id);
            if (!$existing) {
                return $this->json(['success' => false, 'error' => 'Partylist not found'], 404);
            }
            if (empty($name)) {
                $name = $existing['name'];
            }
            if (empty($description) && $description !== '') {
                $description = $existing['description'];
            }
            if (!isset($_POST['badge_color']) || $_POST['badge_color'] === '') {
                $badgeColor = $existing['badge_color'] ?? '#1e40af';
            }
            if (!isset($_POST['badge_text_color']) || $_POST['badge_text_color'] === '') {
                $badgeTextColor = $existing['badge_text_color'] ?? '#ffffff';
            }
            if (!isset($_POST['sort_order']) || $_POST['sort_order'] === '') {
                $sortOrder = (int)$existing['sort_order'];
            }
        } else {
            if (!isset($_POST['badge_color']) || $_POST['badge_color'] === '') {
                $badgeColor = '#1e40af';
            }
            if (!isset($_POST['badge_text_color']) || $_POST['badge_text_color'] === '') {
                $badgeTextColor = '#ffffff';
            }
        }
        
        if (empty($name)) return $this->json(['success' => false, 'error' => 'Partylist name is required'], 422);
        
        try {
            if ($id) {
                $changed = ($existing['name'] !== $name || $existing['description'] !== $description || ($existing['badge_color'] ?? '#1e40af') !== $badgeColor || ($existing['badge_text_color'] ?? '#ffffff') !== $badgeTextColor);
                
                if ($changed) {
                    $oldValues = [
                        'Name' => $existing['name'],
                        'Description' => $existing['description'] ?: '-',
                        'Badge Color' => $existing['badge_color'] ?? '#1e40af',
                        'Text Color' => $existing['badge_text_color'] ?? '#ffffff'
                    ];
                    $newValues = [
                        'Name' => $name,
                        'Description' => $description ?: '-',
                        'Badge Color' => $badgeColor,
                        'Text Color' => $badgeTextColor
                    ];
                    $model->update($id, ['name' => $name, 'description' => $description, 'badge_color' => $badgeColor, 'badge_text_color' => $badgeTextColor, 'sort_order' => $sortOrder]);
                    $this->audit('partylists', 'UPDATE', $id, $oldValues, $newValues, "Updated partylist: {$name}");
                } else {
                    $model->update($id, ['name' => $name, 'description' => $description, 'badge_color' => $badgeColor, 'badge_text_color' => $badgeTextColor, 'sort_order' => $sortOrder]);
                }
            } else {
                $model->create(['name' => $name, 'description' => $description, 'badge_color' => $badgeColor, 'badge_text_color' => $badgeTextColor, 'sort_order' => $sortOrder]);
                $newValues = [
                    'Name' => $name,
                    'Description' => $description ?: '-',
                    'Badge Color' => $badgeColor,
                    'Text Color' => $badgeTextColor
                ];
                $this->audit('partylists', 'CREATE', null, null, $newValues, "Created partylist: {$name}");
            }
            return $this->json(['success' => true, 'message' => $id ? 'Partylist updated' : 'Partylist created']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Failed to save partylist'], 500);
        }
    }

    public function deletePartylist() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);
        $id = $_POST['id'] ?? null;
        if (!$id) return $this->json(['success' => false, 'error' => 'ID required'], 422);
        
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE partylist_id = ?");
        $stmt->execute([(int)$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return $this->json(['success' => false, 'error' => 'Cannot delete partylist with assigned candidates'], 409);
        }
        
        $model = new \App\Models\Partylist();
        $existing = $model->find($id);
        $oldValues = [
            'Name' => $existing['name'],
            'Description' => $existing['description'] ?: '-',
            'Badge Color' => $existing['badge_color'] ?? '#1e40af',
            'Text Color' => $existing['badge_text_color'] ?? '#ffffff'
        ];
        $model->delete((int)$id);
        $this->audit('partylists', 'DELETE', $id, $oldValues, null, "Deleted partylist: " . ($existing['name'] ?? 'Unknown'));
        return $this->json(['success' => true, 'message' => 'Partylist deleted']);
    }

    public function bulkDeletePartylists() {
        $ids = $_POST['ids'] ?? '[]';
        if (is_string($ids)) $ids = json_decode($ids, true);
        
        if (!is_array($ids) || empty($ids)) {
            return $this->json(['success' => false, 'error' => 'No items selected'], 422);
        }
        
        if (count($ids) > 100) {
            return $this->json(['success' => false, 'error' => 'Maximum 100 records per request'], 422);
        }
        
        $pdo = \App\Core\Model::db();
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT pl.name, COUNT(c.id) as candidate_count FROM partylists pl LEFT JOIN candidates c ON pl.id = c.partylist_id WHERE pl.id IN ($placeholders) GROUP BY pl.id HAVING candidate_count > 0");
        $stmt->execute(array_map('intval', $ids));
        $blocked = $stmt->fetchAll();
        
        if (!empty($blocked)) {
            return $this->json(['success' => false, 'error' => 'Candidates still assigned to the partylist'], 409);
        }
        
        $model = new \App\Models\Partylist();
        $deleted = 0;
        foreach ($ids as $id) {
            $model->delete((int)$id);
            $deleted++;
        }
        
        $this->audit('partylists', 'DELETE', 'bulk', null, null, "Bulk deleted {$deleted} partylists");
        return $this->json(['success' => true, 'message' => "{$deleted} deleted"]);
    }

    public function checkPartylistDelete() {
        $id = $_POST['id'] ?? null;
        if (!$id) return $this->json(['success' => false, 'error' => 'ID required'], 422);
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE partylist_id = ?");
        $stmt->execute([(int)$id]);
        $count = (int)$stmt->fetchColumn();
        return $this->json(['success' => true, 'can_delete' => $count === 0, 'candidate_count' => $count, 'message' => $count > 0 ? "Cannot be deleted, {$count} candidate(s) assigned to this partylist" : 'Safe to delete']);
    }

    public function getLoginToggle() {
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->query("SELECT enable_login FROM votingswitch LIMIT 1");
        $result = $stmt->fetch();
        return $this->json([
            'success' => true,
            'enabled' => $result && $result['enable_login'] == 1
        ]);
    }

    public function toggleLogin() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        $canManageVote = false;
        if ($adminId) {
            $stmt = \App\Core\Model::db()->prepare("SELECT can_manage_vote FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $row = $stmt->fetch();
            $canManageVote = $row && $row['can_manage_vote'] == 1;
        }
        if (!$canManageVote) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->query("SELECT enable_login FROM votingswitch LIMIT 1");
        $current = $stmt->fetch();
        $newStatus = ($current && $current['enable_login'] == 1) ? 0 : 1;
        
        $pdo->prepare("UPDATE votingswitch SET enable_login = ?")->execute([$newStatus]);
        
        $statusText = $newStatus ? 'enabled' : 'disabled';
        $this->audit('votingswitch', 'UPDATE', 1, ['enable_login' => !$newStatus], ['enable_login' => $newStatus], "Voter login {$statusText}");
        
        return $this->json([
            'success' => true,
            'enabled' => $newStatus == 1,
            'message' => 'Voter login ' . $statusText
        ]);
    }

    public function getToggleStates() {
        $pdo = \App\Core\Model::db();
        $stmt = $pdo->query("SELECT enable_vote, enable_login, enable_guest, enable_test_vote FROM votingswitch LIMIT 1");
        $result = $stmt->fetch();
        return $this->json([
            'success' => true,
            'vote_enabled' => $result && $result['enable_vote'] == 1,
            'login_enabled' => $result && $result['enable_login'] == 1,
            'guest_enabled' => $result && $result['enable_guest'] == 1,
            'test_enabled' => $result && $result['enable_test_vote'] == 1
        ]);
    }

    public function toggleState() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        $canManageVote = false;
        if ($adminId) {
            $stmt = \App\Core\Model::db()->prepare("SELECT can_manage_vote FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $row = $stmt->fetch();
            $canManageVote = $row && $row['can_manage_vote'] == 1;
        }
        if (!$canManageVote) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $type = $_POST['type'] ?? '';
        $enabled = ($_POST['enabled'] ?? '0') === '1' ? 1 : 0;
        
        $allowedTypes = [
            'login' => 'enable_login',
            'vote' => 'enable_vote',
            'guest' => 'enable_guest',
            'test' => 'enable_test_vote'
        ];
        
        if (!isset($allowedTypes[$type])) {
            return $this->json(['success' => false, 'error' => 'Invalid toggle type'], 422);
        }
        
        $column = $allowedTypes[$type];
        $pdo = \App\Core\Model::db();
        $pdo->prepare("UPDATE votingswitch SET $column = ?")->execute([$enabled]);
        
        $labelMap = [
            'login' => 'Login',
            'vote' => 'Voting',
            'guest' => 'Guest',
            'test' => 'Test Vote'
        ];
        $label = $labelMap[$type] ?? ucfirst($type);
        $statusText = $enabled ? 'enabled' : 'disabled';
        $this->audit('votingswitch', 'UPDATE', 1, [$column => !$enabled], [$column => $enabled], "{$label} {$statusText}");
        
        return $this->json([
            'success' => true,
            'enabled' => $enabled == 1,
            'message' => "{$label} {$statusText}"
        ]);
    }
}