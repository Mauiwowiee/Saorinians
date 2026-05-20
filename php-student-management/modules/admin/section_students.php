<?php
/**
 * Section Students (Admin)
 * View and manage students in a section
 */

$pageTitle = 'Section Students';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$sectionId = $_GET['id'] ?? null;

if (!$sectionId) {
    setFlash('error', 'Section not specified.');
    redirect(BASE_URL . 'modules/admin/manage_sections.php');
}

$section = getSectionById($sectionId);
if (!$section) {
    setFlash('error', 'Section not found.');
    redirect(BASE_URL . 'modules/admin/manage_sections.php');
}

$students = getStudentsBySection($sectionId);
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/admin/manage_sections.php">Sections</a></li>
                <li class="breadcrumb-item active"><?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-people me-2"></i><?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?>
                </h2>
                <p class="text-muted"><?= sanitize($section['course_name']) ?></p>
            </div>
            <a href="<?= BASE_URL ?>modules/admin/enrollments.php?section_id=<?= $sectionId ?>" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i>Manage Enrollment
            </a>
        </div>
    </div>
</div>

<!-- Section Info -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-person-badge fs-3 text-primary"></i>
                <h6 class="mt-2 mb-0">Teacher</h6>
                <p class="mb-0"><?= sanitize($section['teacher_name'] ?: 'Not Assigned') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-geo-alt fs-3 text-primary"></i>
                <h6 class="mt-2 mb-0">Room</h6>
                <p class="mb-0"><?= sanitize($section['room_number'] ?: 'TBA') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-clock fs-3 text-primary"></i>
                <h6 class="mt-2 mb-0">Schedule</h6>
                <p class="mb-0"><?= sanitize($section['schedule_time'] ?: 'TBA') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-people fs-3 text-primary"></i>
                <h6 class="mt-2 mb-0">Enrolled</h6>
                <p class="mb-0"><?= count($students) ?> / <?= $section['max_students'] ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Students List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list me-2"></i>Enrolled Students</span>
        <input type="text" id="tableSearch" class="form-control form-control-sm w-auto" placeholder="Search...">
    </div>
    <div class="card-body">
        <?php if (empty($students)): ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>No students enrolled in this section.</p>
            <a href="<?= BASE_URL ?>modules/admin/enrollments.php?section_id=<?= $sectionId ?>" class="btn btn-primary">
                Enroll Students
            </a>
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
                        <th>Enrolled Date</th>
                        <th>Status</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
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
                        <td><?= sanitize($student['email'] ?: '-') ?></td>
                        <td><?= sanitize($student['phone'] ?: '-') ?></td>
                        <td><?= formatDate($student['enrollment_date']) ?></td>
                        <td>
                            <span class="badge bg-<?= $student['status'] === 'enrolled' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($student['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($student['grade']): ?>
                            <span class="badge bg-info"><?= sanitize($student['grade']) ?></span>
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
