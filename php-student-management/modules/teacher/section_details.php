<?php
/**
 * Section Details (Teacher)
 * View students in a section
 */

$pageTitle = 'Section Details';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireTeacher();

$sectionId = $_GET['id'] ?? null;
$teacherId = getCurrentUserId();

if (!$sectionId) {
    setFlash('error', 'Section not specified.');
    redirect(BASE_URL . 'modules/teacher/my_sections.php');
}

$section = getSectionById($sectionId);

// Verify this section belongs to the teacher
if (!$section || $section['teacher_id'] != $teacherId) {
    setFlash('error', 'You do not have access to this section.');
    redirect(BASE_URL . 'modules/teacher/my_sections.php');
}

$students = getStudentsBySection($sectionId);
$attendanceSummary = getAttendanceSummary($sectionId);

// Create attendance lookup
$attendanceLookup = [];
foreach ($attendanceSummary as $record) {
    $attendanceLookup[$record['id']] = $record;
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/teacher/my_sections.php">My Sections</a></li>
                <li class="breadcrumb-item active"><?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?></li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-diagram-3 me-2"></i><?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?>
        </h2>
        <p class="text-muted"><?= sanitize($section['course_name']) ?></p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-geo-alt fs-3 text-primary"></i>
                <h6 class="mt-2 mb-0">Room</h6>
                <p class="mb-0"><?= sanitize($section['room_number'] ?: 'TBA') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-clock fs-3 text-primary"></i>
                <h6 class="mt-2 mb-0">Schedule</h6>
                <p class="mb-0"><?= sanitize($section['schedule_time'] ?: 'TBA') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-people fs-3 text-primary"></i>
                <h6 class="mt-2 mb-0">Enrolled Students</h6>
                <p class="mb-0"><?= count($students) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i>Student Roster</span>
        <a href="<?= BASE_URL ?>modules/teacher/attendance.php?section_id=<?= $sectionId ?>" class="btn btn-sm btn-success">
            <i class="bi bi-calendar-check me-1"></i>Take Attendance
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($students)): ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>No students enrolled in this section yet.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th class="text-center">Attendance</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                    <?php 
                    $attendance = $attendanceLookup[$student['id']] ?? null;
                    $totalClasses = $attendance['total_classes'] ?? 0;
                    $presentCount = $attendance['present_count'] ?? 0;
                    $attendanceRate = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100) : 0;
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?= getProfilePicUrl($student['profile_pic_path']) ?>" 
                                     class="avatar-circle-sm me-2" alt="">
                                <div>
                                    <strong><?= sanitize($student['full_name']) ?></strong><br>
                                    <small class="text-muted">@<?= sanitize($student['username']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($student['email']): ?>
                            <a href="mailto:<?= sanitize($student['email']) ?>"><?= sanitize($student['email']) ?></a>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($student['phone'] ?: '-') ?></td>
                        <td class="text-center">
                            <?php if ($totalClasses > 0): ?>
                            <div class="progress" style="height: 20px;" title="<?= $presentCount ?>/<?= $totalClasses ?> classes">
                                <div class="progress-bar bg-<?= $attendanceRate >= 75 ? 'success' : ($attendanceRate >= 50 ? 'warning' : 'danger') ?>" 
                                     style="width: <?= $attendanceRate ?>%">
                                    <?= $attendanceRate ?>%
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">No records</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($student['grade']): ?>
                            <span class="badge bg-success fs-6"><?= sanitize($student['grade']) ?></span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
