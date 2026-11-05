<?php
/**
 * Course Management (Admin Only)
 */

$pageTitle = 'Manage Courses';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$action = $_GET['action'] ?? 'list';
$courseId = $_GET['id'] ?? null;
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $courseCode = sanitize($_POST['course_code'] ?? '');
        $courseName = sanitize($_POST['course_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $credits = (int)($_POST['credits'] ?? 3);
        $status = $_POST['status'] ?? 'active';
        
        // Validation
        if (empty($courseCode) || empty($courseName)) {
            $errors[] = 'Course code and name are required.';
        }
        if ($credits < 1 || $credits > 12) {
            $errors[] = 'Credits must be between 1 and 12.';
        }
        
        if (empty($errors)) {
            try {
                if (isset($_POST['add_course'])) {
                    createCourse($courseCode, $courseName, $description, $credits);
                    setFlash('success', 'Course created successfully!');
                } elseif (isset($_POST['edit_course']) && $courseId) {
                    updateCourse($courseId, $courseCode, $courseName, $description, $credits, $status);
                    setFlash('success', 'Course updated successfully!');
                }
                redirect(BASE_URL . 'modules/admin/manage_courses.php');
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $courseId) {
    try {
        deleteCourse($courseId);
        setFlash('success', 'Course deleted successfully!');
    } catch (Exception $e) {
        setFlash('error', 'Cannot delete course: ' . $e->getMessage());
    }
    redirect(BASE_URL . 'modules/admin/manage_courses.php');
}

// Get course for editing
$course = null;
if ($action === 'edit' && $courseId) {
    $course = getCourseById($courseId);
    if (!$course) {
        setFlash('error', 'Course not found.');
        redirect(BASE_URL . 'modules/admin/manage_courses.php');
    }
}

// Get all courses
$courses = getAllCourses();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-journal-bookmark me-2"></i>Manage Courses
                </h2>
                <p class="text-muted">Create and manage courses</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="bi bi-journal-plus me-1"></i>Add Course
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>modules/admin/manage_courses.php" class="btn btn-outline-secondary">
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
        <i class="bi bi-<?= $action === 'add' ? 'journal-plus' : 'pencil-square' ?> me-2"></i>
        <?= $action === 'add' ? 'Add New Course' : 'Edit Course' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="course_code" name="course_code" 
                               value="<?= sanitize($course['course_code'] ?? $_POST['course_code'] ?? '') ?>"
                               placeholder="e.g., CS101" required>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="course_name" name="course_name" 
                               value="<?= sanitize($course['course_name'] ?? $_POST['course_name'] ?? '') ?>"
                               placeholder="e.g., Introduction to Programming" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" 
                          placeholder="Course description..."><?= sanitize($course['description'] ?? $_POST['description'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="credits" class="form-label">Credits</label>
                        <input type="number" class="form-control" id="credits" name="credits" 
                               value="<?= $course['credits'] ?? $_POST['credits'] ?? 3 ?>"
                               min="1" max="12">
                    </div>
                </div>
                <?php if ($action === 'edit'): ?>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= ($course['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($course['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="<?= $action === 'add' ? 'add_course' : 'edit_course' ?>" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $action === 'add' ? 'Create Course' : 'Update Course' ?>
                </button>
                <a href="<?= BASE_URL ?>modules/admin/manage_courses.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Courses List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list me-2"></i>All Courses (<?= count($courses) ?>)</span>
        <input type="text" id="tableSearch" class="form-control form-control-sm w-auto" placeholder="Search courses...">
    </div>
    <div class="card-body">
        <?php if (empty($courses)): ?>
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <p>No courses created yet.</p>
            <a href="?action=add" class="btn btn-primary">Create First Course</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Description</th>
                        <th class="text-center">Credits</th>
                        <th class="text-center">Sections</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                    <?php $courseSections = getSectionsByCourse($c['id']); ?>
                    <tr>
                        <td><strong><?= sanitize($c['course_code']) ?></strong></td>
                        <td><?= sanitize($c['course_name']) ?></td>
                        <td>
                            <small class="text-muted">
                                <?= sanitize(strlen($c['description']) > 50 ? substr($c['description'], 0, 50) . '...' : ($c['description'] ?: '-')) ?>
                            </small>
                        </td>
                        <td class="text-center"><?= $c['credits'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-info"><?= count($courseSections) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>modules/admin/manage_sections.php?action=add&course_id=<?= $c['id'] ?>" 
                                   class="btn btn-outline-success" title="Add Section">
                                    <i class="bi bi-plus"></i>
                                </a>
                                <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=1&id=<?= $c['id'] ?>" class="btn btn-outline-danger delete-confirm" title="Delete">
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
