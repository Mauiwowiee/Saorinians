<?php
/**
 * My Sections (Teacher)
 * View assigned sections and schedules
 */

$pageTitle = 'My Sections';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireTeacher();

$teacherId = getCurrentUserId();
$sections = getSectionsByTeacher($teacherId);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-diagram-3 me-2"></i>My Sections
        </h2>
        <p class="text-muted">View your assigned course sections</p>
    </div>
</div>

<?php if (empty($sections)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <i class="bi bi-folder-x"></i>
            <p>You are not assigned to any sections yet.</p>
            <p class="text-muted">Please contact the administrator for section assignments.</p>
        </div>
    </div>
</div>
<?php else: ?>

<!-- Weekly Schedule Overview -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-calendar-week me-2"></i>Weekly Schedule
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $section): ?>
                    <tr>
                        <td>
                            <strong><?= sanitize($section['course_code']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($section['course_name']) ?></small>
                        </td>
                        <td><?= sanitize($section['section_name']) ?></td>
                        <td><?= sanitize($section['schedule_time'] ?: 'TBA') ?></td>
                        <td><?= sanitize($section['room_number'] ?: 'TBA') ?></td>
                        <td><span class="badge bg-primary"><?= $section['student_count'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section Cards -->
<div class="row">
    <?php foreach ($sections as $section): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card section-card h-100">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><?= sanitize($section['course_code']) ?></span>
                    <span class="badge bg-light text-dark"><?= $section['credits'] ?> credits</span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= sanitize($section['course_name']) ?></h5>
                <h6 class="card-subtitle mb-3 text-muted"><?= sanitize($section['section_name']) ?></h6>
                
                <ul class="list-unstyled mb-3">
                    <li class="mb-2">
                        <i class="bi bi-geo-alt text-primary me-2"></i>
                        <strong>Room:</strong> <?= sanitize($section['room_number'] ?: 'TBA') ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock text-primary me-2"></i>
                        <strong>Schedule:</strong> <?= sanitize($section['schedule_time'] ?: 'TBA') ?>
                    </li>
                    <li>
                        <i class="bi bi-people text-primary me-2"></i>
                        <strong>Students:</strong> <?= $section['student_count'] ?>
                    </li>
                </ul>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>modules/teacher/section_details.php?id=<?= $section['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View Students
                    </a>
                    <a href="<?= BASE_URL ?>modules/teacher/attendance.php?section_id=<?= $section['id'] ?>" class="btn btn-outline-success">
                        <i class="bi bi-calendar-check me-1"></i>Take Attendance
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
