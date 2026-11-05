<?php
/**
 * Section Management (Admin Only)
 * CRUD for sections + Teacher assignment
 */

$pageTitle = 'Manage Sections';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$action = $_GET['action'] ?? 'list';
$sectionId = $_GET['id'] ?? null;
$errors = [];

// Get data for forms
$courses = getActiveCourses();
$teachers = getUsersByRole('teacher');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $sectionName = sanitize($_POST['section_name'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? 0);
        $teacherId = $_POST['teacher_id'] ? (int)$_POST['teacher_id'] : null;
        $roomNumber = sanitize($_POST['room_number'] ?? '');
        $scheduleTime = sanitize($_POST['schedule_time'] ?? '');
        $maxStudents = (int)($_POST['max_students'] ?? 40);
        $status = $_POST['status'] ?? 'active';
        
        // Validation
        if (empty($sectionName)) {
            $errors[] = 'Section name is required.';
        }
        if ($courseId <= 0) {
            $errors[] = 'Please select a course.';
        }
        if ($maxStudents <= 0) {
            $errors[] = 'Maximum students must be greater than 0.';
        }
        
        if (empty($errors)) {
            try {
                if (isset($_POST['add_section'])) {
                    createSection($sectionName, $courseId, $teacherId, $roomNumber, $scheduleTime, $maxStudents);
                    setFlash('success', 'Section created successfully!');
                } elseif (isset($_POST['edit_section']) && $sectionId) {
                    updateSection($sectionId, $sectionName, $teacherId, $roomNumber, $scheduleTime, $maxStudents, $status);
                    setFlash('success', 'Section updated successfully!');
                }
                redirect(BASE_URL . 'modules/admin/manage_sections.php');
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $sectionId) {
    try {
        deleteSection($sectionId);
        setFlash('success', 'Section deleted successfully!');
    } catch (Exception $e) {
        setFlash('error', 'Cannot delete section: ' . $e->getMessage());
    }
    redirect(BASE_URL . 'modules/admin/manage_sections.php');
}

// Get section for editing
$section = null;
if ($action === 'edit' && $sectionId) {
    $section = getSectionById($sectionId);
    if (!$section) {
        setFlash('error', 'Section not found.');
        redirect(BASE_URL . 'modules/admin/manage_sections.php');
    }
}

// Get all sections for listing
$sections = getAllSections();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>Manage Sections
                </h2>
                <p class="text-muted">Create and manage course sections</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Section
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>modules/admin/manage_sections.php" class="btn btn-outline-secondary">
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
        <i class="bi bi-<?= $action === 'add' ? 'plus' : 'pencil' ?>-square me-2"></i>
        <?= $action === 'add' ? 'Add New Section' : 'Edit Section' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                        <select class="form-select" id="course_id" name="course_id" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" 
                                    <?= ($section && $section['course_id'] == $course['id']) || (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : '' ?>>
                                <?= sanitize($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="course_id" value="<?= $section['course_id'] ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="section_name" class="form-label">Section Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="section_name" name="section_name" 
                               value="<?= sanitize($section['section_name'] ?? $_POST['section_name'] ?? '') ?>"
                               placeholder="e.g., Section-A" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label">Assigned Teacher</label>
                        <select class="form-select" id="teacher_id" name="teacher_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>" 
                                    <?= ($section && $section['teacher_id'] == $teacher['id']) || (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher['id']) ? 'selected' : '' ?>>
                                <?= sanitize($teacher['full_name']) ?>
                                <?php if ($teacher['email']): ?>(<?= sanitize($teacher['email']) ?>)<?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="max_students" class="form-label">Maximum Students</label>
                        <input type="number" class="form-control" id="max_students" name="max_students" 
                               value="<?= $section['max_students'] ?? $_POST['max_students'] ?? 40 ?>"
                               min="1" max="200">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="room_number" class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" 
                               value="<?= sanitize($section['room_number'] ?? $_POST['room_number'] ?? '') ?>"
                               placeholder="e.g., Room 101">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="schedule_time" class="form-label">Schedule</label>
                        <input type="text" class="form-control" id="schedule_time" name="schedule_time" 
                               value="<?= sanitize($section['schedule_time'] ?? $_POST['schedule_time'] ?? '') ?>"
                               placeholder="e.g., MWF 9:00-10:00 AM">
                    </div>
                </div>
            </div>
            
            <?php if ($action === 'edit'): ?>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= $section['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $section['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="d-flex gap-2">
                <button type="submit" name="<?= $action === 'add' ? 'add_section' : 'edit_section' ?>" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $action === 'add' ? 'Create Section' : 'Update Section' ?>
                </button>
                <a href="<?= BASE_URL ?>modules/admin/manage_sections.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Sections List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list me-2"></i>All Sections</span>
        <div>
            <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search sections...">
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($sections)): ?>
        <div class="empty-state">
            <i class="bi bi-diagram-3"></i>
            <p>No sections created yet.</p>
            <a href="?action=add" class="btn btn-primary">Create First Section</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Room</th>
                        <th>Schedule</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $sec): ?>
                    <tr>
                        <td>
                            <strong><?= sanitize($sec['course_code']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($sec['course_name']) ?></small>
                        </td>
                        <td><?= sanitize($sec['section_name']) ?></td>
                        <td>
                            <?php if ($sec['teacher_name']): ?>
                            <span class="text-success">
                                <i class="bi bi-person-check me-1"></i><?= sanitize($sec['teacher_name']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-warning">
                                <i class="bi bi-person-dash me-1"></i>Unassigned
                            </span>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($sec['room_number'] ?: '-') ?></td>
                        <td><?= sanitize($sec['schedule_time'] ?: '-') ?></td>
                        <td>
                            <?php
                            $students = getStudentsBySection($sec['id']);
                            echo count($students) . '/' . $sec['max_students'];
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $sec['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($sec['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>modules/admin/section_students.php?id=<?= $sec['id'] ?>" 
                                   class="btn btn-outline-info" title="View Students">
                                    <i class="bi bi-people"></i>
                                </a>
                                <a href="?action=edit&id=<?= $sec['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=1&id=<?= $sec['id'] ?>" class="btn btn-outline-danger delete-confirm" title="Delete">
                                    <i class="bi bi-trash"></i>
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
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
