<?php
/**
 * My Courses (Student)
 * View enrolled sections and classmates
 */

$pageTitle = 'My Courses';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireStudent();

$studentId = getCurrentUserId();
$enrolledSections = getStudentSections($studentId);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-book me-2"></i>My Courses
        </h2>
        <p class="text-muted">View your enrolled courses and sections</p>
    </div>
</div>

<?php if (empty($enrolledSections)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <i class="bi bi-book"></i>
            <p>You are not enrolled in any courses yet.</p>
            <p class="text-muted">Please contact the administrator for enrollment.</p>
        </div>
    </div>
</div>
<?php else: ?>

<div class="row">
    <?php foreach ($enrolledSections as $enrollment): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card section-card h-100">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><?= sanitize($enrollment['course_code']) ?></span>
                    <span class="badge bg-light text-dark"><?= $enrollment['credits'] ?> credits</span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= sanitize($enrollment['course_name']) ?></h5>
                <h6 class="card-subtitle mb-3 text-muted"><?= sanitize($enrollment['section_name']) ?></h6>
                
                <ul class="list-unstyled mb-3">
                    <li class="mb-2">
                        <i class="bi bi-person-badge text-primary me-2"></i>
                        <strong>Teacher:</strong> <?= sanitize($enrollment['teacher_name'] ?: 'TBA') ?>
                    </li>
                    <?php if ($enrollment['teacher_email']): ?>
                    <li class="mb-2">
                        <i class="bi bi-envelope text-primary me-2"></i>
                        <a href="mailto:<?= sanitize($enrollment['teacher_email']) ?>">
                            <?= sanitize($enrollment['teacher_email']) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="mb-2">
                        <i class="bi bi-geo-alt text-primary me-2"></i>
                        <strong>Room:</strong> <?= sanitize($enrollment['room_number'] ?: 'TBA') ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock text-primary me-2"></i>
                        <strong>Schedule:</strong> <?= sanitize($enrollment['schedule_time'] ?: 'TBA') ?>
                    </li>
                    <li>
                        <i class="bi bi-calendar text-primary me-2"></i>
                        <strong>Enrolled:</strong> <?= formatDate($enrollment['enrollment_date']) ?>
                    </li>
                </ul>
                
                <?php if ($enrollment['grade']): ?>
                <div class="alert alert-success py-2 mb-0">
                    <i class="bi bi-award me-1"></i>Current Grade: <strong><?= sanitize($enrollment['grade']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>modules/student/classmates.php?section_id=<?= $enrollment['section_id'] ?>" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-people me-1"></i>View Classmates
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
