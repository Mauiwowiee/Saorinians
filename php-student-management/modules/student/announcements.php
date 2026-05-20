<?php
/**
 * Student Announcements View
 * Students can view announcements relevant to them
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireStudent();

$pageTitle = 'Announcements';
$studentId = getCurrentUserId();

// Get student's enrolled section IDs
$sections = getStudentSections($studentId);
$sectionIds = array_column($sections, 'section_id');

// Get announcements (general + section-specific)
$announcements = getAnnouncements('student', null, 50);

// Filter to show only relevant announcements
$filteredAnnouncements = [];
foreach ($announcements as $ann) {
    if ($ann['section_id'] === null || in_array($ann['section_id'], $sectionIds)) {
        $filteredAnnouncements[] = $ann;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Announcements</h1>
            <p class="text-muted mb-0">Stay updated with the latest announcements</p>
        </div>
    </div>
    
    <?php displayFlash(); ?>
    
    <?php if (empty($filteredAnnouncements)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>No announcements at this time.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($filteredAnnouncements as $announcement): ?>
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="bi bi-megaphone me-2 text-primary"></i>
                                    <?php echo sanitize($announcement['title']); ?>
                                </h5>
                                <?php if ($announcement['section_id']): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-book me-1"></i>
                                        <?php echo sanitize($announcement['course_code'] . ' - ' . $announcement['section_name']); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">
                                        <i class="bi bi-globe me-1"></i>General Announcement
                                    </small>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo timeAgo($announcement['created_at']); ?></small>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(sanitize($announcement['content'])); ?></p>
                        </div>
                        <div class="card-footer bg-transparent text-muted">
                            <small>
                                <i class="bi bi-person me-1"></i>Posted by: <?php echo sanitize($announcement['author_name'] ?? 'Admin'); ?>
                                <span class="mx-2">|</span>
                                <i class="bi bi-calendar me-1"></i><?php echo formatDateTime($announcement['created_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
