<?php
/**
 * Teacher Sections (Admin)
 * View sections assigned to a specific teacher
 */

$pageTitle = 'Teacher Sections';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$teacherId = $_GET['id'] ?? null;

if (!$teacherId) {
    setFlash('error', 'Teacher not specified.');
    redirect(BASE_URL . 'modules/admin/teacher_workload.php');
}

$teacher = getUserById($teacherId);
if (!$teacher || $teacher['role'] !== 'teacher') {
    setFlash('error', 'Teacher not found.');
    redirect(BASE_URL . 'modules/admin/teacher_workload.php');
}

$sections = getSectionsByTeacher($teacherId);
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/admin/teacher_workload.php">Teacher Workload</a></li>
                <li class="breadcrumb-item active"><?= sanitize($teacher['full_name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-person-badge me-2"></i><?= sanitize($teacher['full_name']) ?>
                </h2>
                <p class="text-muted">Assigned Sections</p>
            </div>
            <a href="<?= BASE_URL ?>modules/admin/manage_sections.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Assign New Section
            </a>
        </div>
    </div>
</div>

<!-- Teacher Info -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <img src="<?= getProfilePicUrl($teacher['profile_pic_path']) ?>" 
                     class="avatar-circle-lg" alt="Profile">
            </div>
            <div class="col-md-5">
                <h5 class="mb-1"><?= sanitize($teacher['full_name']) ?></h5>
                <p class="mb-1"><i class="bi bi-envelope me-2"></i><?= sanitize($teacher['email'] ?: 'No email') ?></p>
                <p class="mb-0"><i class="bi bi-phone me-2"></i><?= sanitize($teacher['phone'] ?: 'No phone') ?></p>
            </div>
            <div class="col-md-5">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="mb-0 text-primary"><?= count($sections) ?></h4>
                        <small class="text-muted">Sections</small>
                    </div>
                    <div class="col-6">
                        <h4 class="mb-0 text-success">
                            <?= array_sum(array_column($sections, 'student_count')) ?>
                        </h4>
                        <small class="text-muted">Total Students</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sections List -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-diagram-3 me-2"></i>Assigned Sections
    </div>
    <div class="card-body">
        <?php if (empty($sections)): ?>
        <div class="empty-state">
            <i class="bi bi-folder-x"></i>
            <p>This teacher has no assigned sections.</p>
            <a href="<?= BASE_URL ?>modules/admin/manage_sections.php" class="btn btn-primary">
                Manage Sections
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Room</th>
                        <th>Schedule</th>
                        <th class="text-center">Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $index => $section): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= sanitize($section['course_code']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($section['course_name']) ?></small>
                        </td>
                        <td><?= sanitize($section['section_name']) ?></td>
                        <td><?= sanitize($section['room_number'] ?: 'TBA') ?></td>
                        <td><?= sanitize($section['schedule_time'] ?: 'TBA') ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary"><?= $section['student_count'] ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>modules/admin/section_students.php?id=<?= $section['id'] ?>" 
                                   class="btn btn-outline-primary" title="View Students">
                                    <i class="bi bi-people"></i>
                                </a>
                                <a href="<?= BASE_URL ?>modules/admin/manage_sections.php?action=edit&id=<?= $section['id'] ?>" 
                                   class="btn btn-outline-secondary" title="Edit Section">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
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
