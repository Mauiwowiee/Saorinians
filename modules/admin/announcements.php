<?php
/**
 * Announcements Management (Admin Only)
 */

$pageTitle = 'Announcements';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$action = $_GET['action'] ?? 'list';
$announcementId = $_GET['id'] ?? null;
$errors = [];

// Get sections for dropdown
$sections = getAllSections();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        $targetRole = $_POST['target_role'] ?? 'all';
        $sectionId = $_POST['section_id'] ? (int)$_POST['section_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (empty($title) || empty($content)) {
            $errors[] = 'Title and content are required.';
        }
        
        if (empty($errors)) {
            try {
                if (isset($_POST['add_announcement'])) {
                    createAnnouncement($title, $content, $targetRole, $sectionId, getCurrentUserId());
                    setFlash('success', 'Announcement created successfully!');
                } elseif (isset($_POST['edit_announcement']) && $announcementId) {
                    updateAnnouncement($announcementId, $title, $content, $targetRole, $sectionId, $isActive);
                    setFlash('success', 'Announcement updated successfully!');
                }
                redirect(BASE_URL . 'modules/admin/announcements.php');
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $announcementId) {
    try {
        deleteAnnouncement($announcementId);
        setFlash('success', 'Announcement deleted successfully!');
    } catch (Exception $e) {
        setFlash('error', 'Cannot delete announcement: ' . $e->getMessage());
    }
    redirect(BASE_URL . 'modules/admin/announcements.php');
}

// Get announcement for editing
$announcement = null;
if ($action === 'edit' && $announcementId) {
    $announcement = getAnnouncementById($announcementId);
    if (!$announcement) {
        setFlash('error', 'Announcement not found.');
        redirect(BASE_URL . 'modules/admin/announcements.php');
    }
}

// Get all announcements
$db = getDB();
$announcements = $db->query("SELECT a.*, u.full_name as author_name, s.section_name, c.course_code 
                             FROM announcements a 
                             LEFT JOIN users u ON a.created_by = u.id 
                             LEFT JOIN sections s ON a.section_id = s.id 
                             LEFT JOIN courses c ON s.course_id = c.id 
                             ORDER BY a.created_at DESC")->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-megaphone me-2"></i>Announcements
                </h2>
                <p class="text-muted">Create and manage announcements</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>New Announcement
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>modules/admin/announcements.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?= sanitize($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-<?= $action === 'add' ? 'megaphone' : 'pencil-square' ?> me-2"></i>
        <?= $action === 'add' ? 'Create New Announcement' : 'Edit Announcement' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?= sanitize($announcement['title'] ?? $_POST['title'] ?? '') ?>"
                       placeholder="Announcement title" required>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                <textarea class="form-control" id="content" name="content" rows="5" 
                          placeholder="Write your announcement here..." required><?= sanitize($announcement['content'] ?? $_POST['content'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="target_role" class="form-label">Target Audience</label>
                        <select class="form-select" id="target_role" name="target_role">
                            <option value="all" <?= ($announcement['target_role'] ?? '') === 'all' ? 'selected' : '' ?>>Everyone</option>
                            <option value="student" <?= ($announcement['target_role'] ?? '') === 'student' ? 'selected' : '' ?>>Students Only</option>
                            <option value="teacher" <?= ($announcement['target_role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teachers Only</option>
                            <option value="admin" <?= ($announcement['target_role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admins Only</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="section_id" class="form-label">Specific Section (Optional)</label>
                        <select class="form-select" id="section_id" name="section_id">
                            <option value="">Global (All Sections)</option>
                            <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['id'] ?>" <?= ($announcement['section_id'] ?? '') == $section['id'] ? 'selected' : '' ?>>
                                <?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <?php if ($action === 'edit'): ?>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           <?= ($announcement['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">
                        Active (visible to users)
                    </label>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="d-flex gap-2">
                <button type="submit" name="<?= $action === 'add' ? 'add_announcement' : 'edit_announcement' ?>" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $action === 'add' ? 'Post Announcement' : 'Update Announcement' ?>
                </button>
                <a href="<?= BASE_URL ?>modules/admin/announcements.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Announcements List -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i>All Announcements (<?= count($announcements) ?>)
    </div>
    <div class="card-body">
        <?php if (empty($announcements)): ?>
        <div class="empty-state">
            <i class="bi bi-megaphone"></i>
            <p>No announcements yet.</p>
            <a href="?action=add" class="btn btn-primary">Create First Announcement</a>
        </div>
        <?php else: ?>
        <?php foreach ($announcements as $ann): ?>
        <div class="card announcement-card <?= !$ann['is_active'] ? 'opacity-50' : '' ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= sanitize($ann['title']) ?></strong>
                    <?php if (!$ann['is_active']): ?>
                    <span class="badge bg-secondary ms-2">Inactive</span>
                    <?php endif; ?>
                </div>
                <div class="btn-group btn-group-sm">
                    <a href="?action=edit&id=<?= $ann['id'] ?>" class="btn btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="?delete=1&id=<?= $ann['id'] ?>" class="btn btn-outline-danger delete-confirm" title="Delete">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-2"><?= nl2br(sanitize($ann['content'])) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-<?= $ann['target_role'] === 'all' ? 'primary' : 'secondary' ?>">
                        <?= ucfirst($ann['target_role']) ?>
                    </span>
                    <?php if ($ann['section_id']): ?>
                    <span class="badge bg-info">
                        <?= sanitize($ann['course_code'] . ' - ' . $ann['section_name']) ?>
                    </span>
                    <?php endif; ?>
                    <small class="text-muted">
                        <i class="bi bi-person me-1"></i><?= sanitize($ann['author_name'] ?: 'System') ?>
                        <i class="bi bi-clock ms-2 me-1"></i><?= timeAgo($ann['created_at']) ?>
                    </small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
