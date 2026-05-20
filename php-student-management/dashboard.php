<?php
/**
 * Dashboard - Role-based dashboard view
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db_operations.php';

requireLogin();

$userId = getCurrentUserId();
$role = getCurrentUserRole();
$user = getUserById($userId);

// Get role-specific data
if (isAdmin()) {
    $stats = getAdminStats();
    $announcements = getAnnouncements('all', null, 5);
} elseif (isTeacher()) {
    $stats = getTeacherStats($userId);
    $sections = getSectionsByTeacher($userId);
    $announcements = getAnnouncements('teacher', null, 5);
} else {
    $stats = getStudentStats($userId);
    $enrolledSections = getStudentSections($userId);
    $announcements = getAnnouncements('student', null, 5);
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </h2>
        <p class="text-muted">Welcome back, <?= sanitize($user['full_name']) ?>!</p>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- Admin Dashboard -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stats-card students">
            <div class="card-body d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= $stats['total_students'] ?></h3>
                    <small class="text-muted">Total Students</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stats-card teachers">
            <div class="card-body d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= $stats['total_teachers'] ?></h3>
                    <small class="text-muted">Total Teachers</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stats-card courses">
            <div class="card-body d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="bi bi-book"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= $stats['total_courses'] ?></h3>
                    <small class="text-muted">Active Courses</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card stats-card sections">
            <div class="card-body d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= $stats['total_sections'] ?></h3>
                    <small class="text-muted">Active Sections</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= BASE_URL ?>modules/admin/manage_users.php?action=add&role=student" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-person-plus text-primary"></i>
                            <span>Add Student</span>
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= BASE_URL ?>modules/admin/manage_users.php?action=add&role=teacher" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-person-badge text-success"></i>
                            <span>Add Teacher</span>
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= BASE_URL ?>modules/admin/manage_courses.php?action=add" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-journal-plus text-warning"></i>
                            <span>Add Course</span>
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= BASE_URL ?>modules/admin/announcements.php?action=add" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-megaphone text-info"></i>
                            <span>New Announcement</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif (isTeacher()): ?>
<!-- Teacher Dashboard -->
<div class="row mb-4">
    <div class="col-md-6 col-sm-6 mb-3">
        <div class="card stats-card sections">
            <div class="card-body d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= $stats['sections'] ?></h3>
                    <small class="text-muted">Assigned Sections</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-sm-6 mb-3">
        <div class="card stats-card students">
            <div class="card-body d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= $stats['students'] ?></h3>
                    <small class="text-muted">Total Students</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Teacher Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/teacher/attendance.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-calendar-check text-primary"></i>
                            <span>Attendance</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/teacher/grades.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-card-checklist text-success"></i>
                            <span>Grades</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/teacher/assignments.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-file-earmark-text text-warning"></i>
                            <span>Assignments</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/teacher/resources.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-folder text-info"></i>
                            <span>Resources</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/teacher/attendance_report.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-graph-up text-danger"></i>
                            <span>Reports</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/common/messages.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-envelope text-secondary"></i>
                            <span>Messages</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Sections -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-diagram-3 me-2"></i>My Sections</span>
                <a href="<?= BASE_URL ?>modules/teacher/my_sections.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($sections)): ?>
                <div class="empty-state">
                    <i class="bi bi-folder-x"></i>
                    <p>No sections assigned yet.</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach (array_slice($sections, 0, 4) as $section): ?>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card section-card">
                            <div class="card-header">
                                <?= sanitize($section['course_code']) ?>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?= sanitize($section['section_name']) ?></h6>
                                <p class="card-text small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i><?= sanitize($section['room_number'] ?: 'TBA') ?><br>
                                    <i class="bi bi-clock me-1"></i><?= sanitize($section['schedule_time'] ?: 'TBA') ?><br>
                                    <i class="bi bi-people me-1"></i><?= $section['student_count'] ?> students
                                </p>
                                <a href="<?= BASE_URL ?>modules/teacher/section_details.php?id=<?= $section['id'] ?>" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Student Dashboard -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stats-card courses">
            <div class="card-body d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="bi bi-book"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= $stats['enrolled_courses'] ?></h3>
                    <small class="text-muted">Enrolled Courses</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/student/schedule.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-calendar-week text-primary"></i>
                            <span>Schedule</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/student/my_grades.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-card-checklist text-success"></i>
                            <span>Grades</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/student/my_attendance.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-calendar-check text-warning"></i>
                            <span>Attendance</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/student/assignments.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-file-earmark-text text-info"></i>
                            <span>Assignments</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/student/resources.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-folder text-danger"></i>
                            <span>Resources</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-4 mb-3">
                        <a href="<?= BASE_URL ?>modules/common/messages.php" class="card quick-action-btn text-decoration-none">
                            <i class="bi bi-envelope text-secondary"></i>
                            <span>Messages</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enrolled Courses -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-book me-2"></i>My Courses</span>
                <a href="<?= BASE_URL ?>modules/student/my_courses.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($enrolledSections)): ?>
                <div class="empty-state">
                    <i class="bi bi-folder-x"></i>
                    <p>You are not enrolled in any courses yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Teacher</th>
                                <th>Schedule</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolledSections as $enrollment): ?>
                            <tr>
                                <td>
                                    <strong><?= sanitize($enrollment['course_code']) ?></strong><br>
                                    <small class="text-muted"><?= sanitize($enrollment['course_name']) ?></small>
                                </td>
                                <td><?= sanitize($enrollment['section_name']) ?></td>
                                <td><?= sanitize($enrollment['teacher_name'] ?: 'TBA') ?></td>
                                <td><?= sanitize($enrollment['schedule_time'] ?: 'TBA') ?></td>
                                <td><?= sanitize($enrollment['room_number'] ?: 'TBA') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Announcements Section (All Roles) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-megaphone me-2"></i>Recent Announcements
            </div>
            <div class="card-body">
                <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <i class="bi bi-megaphone"></i>
                    <p>No announcements at this time.</p>
                </div>
                <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                <div class="card announcement-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= sanitize($announcement['title']) ?></strong>
                        <span class="announcement-meta"><?= timeAgo($announcement['created_at']) ?></span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><?= nl2br(sanitize($announcement['content'])) ?></p>
                        <small class="text-muted">
                            <i class="bi bi-person me-1"></i>Posted by <?= sanitize($announcement['author_name'] ?: 'System') ?>
                            <?php if ($announcement['section_id']): ?>
                            | <i class="bi bi-diagram-3 me-1"></i><?= sanitize($announcement['course_code'] . ' - ' . $announcement['section_name']) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
