<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Model;
use App\Models\Section;
use App\Services\SessionService;
use App\Services\AuditService;

class SectionController extends Controller {

    private function canEditInfo() {
        $adminData = SessionService::getAdminData();
        $adminId = isset($adminData['user_id']) ? (int)$adminData['user_id'] : 0;
        if (!$adminId) return false;
        $stmt = Model::db()->prepare("SELECT can_edit_info FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        return $row && $row['can_edit_info'] == 1;
    }

    private function audit($action, $entityId, $oldValues = null, $newValues = null, $description = '') {
        (new AuditService())->log('sections', $action, $entityId, $oldValues, $newValues, $description);
    }

    // List all sections (used by the dashboard table and the voting page)
    public function getSections() {
        $search = trim($_GET['search'] ?? '');
        $rows = (new Section())->getAll($search);
        return $this->json(['success' => true, 'data' => $rows]);
    }

    public function saveSection() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);

        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($name === '') return $this->json(['success' => false, 'error' => 'Section name is required'], 422);
        if (mb_strlen($name) > 100) return $this->json(['success' => false, 'error' => 'Section name is too long'], 422);

        $model = new Section();
        if ($id) {
            $existing = $model->find($id);
            if (!$existing) return $this->json(['success' => false, 'error' => 'Section not found'], 404);
        }
        if ($model->nameExists($name, $id ?: null)) {
            return $this->json(['success' => false, 'error' => 'That section already exists'], 409);
        }

        try {
            if ($id) {
                $model->updateSection($id, $name, $sortOrder);
                if ($existing['name'] !== $name) {
                    $this->audit('UPDATE', (int)$id, ['Name' => $existing['name']], ['Name' => $name], "Updated section: {$name}");
                }
                return $this->json(['success' => true, 'message' => 'Section updated']);
            }
            $newId = $model->createSection($name, $sortOrder);
            $this->audit('CREATE', $newId, null, ['Name' => $name], "Created section: {$name}");
            return $this->json(['success' => true, 'message' => 'Section created']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Failed to save section'], 500);
        }
    }

    public function deleteSection() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return $this->json(['success' => false, 'error' => 'ID required'], 422);

        $model = new Section();
        $existing = $model->find($id);
        if (!$existing) return $this->json(['success' => false, 'error' => 'Section not found'], 404);

        // Keep old votes linked to a section: block deletes that already have votes
        $stmt = Model::db()->prepare("SELECT COUNT(*) FROM votes WHERE section_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return $this->json(['success' => false, 'error' => 'Cannot delete a section that already has votes'], 409);
        }

        $model->deleteSection($id);
        $this->audit('DELETE', $id, ['Name' => $existing['name']], null, "Deleted section: {$existing['name']}");
        return $this->json(['success' => true, 'message' => 'Section deleted']);
    }

    // Delete many sections at once. Sections that have votes or no longer exist are skipped.
    public function bulkDeleteSections() {
        if (!$this->canEditInfo()) return $this->json(['success' => false, 'error' => 'Edit info access denied'], 403);

        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return $this->json(['success' => false, 'error' => 'No sections selected'], 422);

        $model = new Section();
        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $existing = $model->find($id);
            if (!$existing) { $skipped++; continue; }

            $stmt = Model::db()->prepare("SELECT COUNT(*) FROM votes WHERE section_id = ?");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) { $skipped++; continue; }

            $model->deleteSection($id);
            $this->audit('DELETE', $id, ['Name' => $existing['name']], null, "Deleted section: {$existing['name']}");
            $deleted++;
        }

        $message = "Deleted {$deleted} section(s)";
        if ($skipped) $message .= ", skipped {$skipped} with votes or not found";
        return $this->json(['success' => true, 'message' => $message]);
    }
}
