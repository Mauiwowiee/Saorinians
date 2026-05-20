<?php
/**
 * Assignment Management for Teachers
 * Teachers can create assignments and grade submissions
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireTeacher();

$pageTitle = 'Assignments';
$teacherId = getCurrentUserId();
$sectionId = intval($_GET['section_id'] ?? 0);

// Verify teacher owns this section
$section = null;
if ($sectionId) {
    $section = getSectionById($sectionId);
    if (!$section || $section['teacher_id'] != $teacherId) {
        setFlash('error', 'Access denied.');
        header('Location: ' . BASE_URL . 'modules/teacher/my_sections.php');
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            if ($action === 'create') {
                $title = sanitize($_POST['title'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                $dueDate = $_POST['due_date'] ?? '';
                $maxPoints = floatval($_POST['max_points'] ?? 100);
                
                createAssignment($sectionId, $title, $description, $dueDate, $maxPoints, $teacherId);
                setFlash('success', 'Assignment created successfully.');
            } elseif ($action === 'grade') {
                $submissionId = intval($_POST['submission_id'] ?? 0);
                $grade = floatval($_POST['grade'] ?? 0);
                $feedback = sanitize($_POST['feedback'] ?? '');
                
                gradeSubmission($submissionId, $grade, $feedback, $teacherId);
                setFlash('success', 'Submission graded successfully.');
            } elseif ($action === 'delete') {
                $assignmentId = intval($_POST['assignment_id'] ?? 0);
                deleteAssignment($assignmentId);
                setFlash('success', 'Assignment deleted.');
            }
        } catch (Exception $e) {
            setFlash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get data
$sections = getSectionsByTeacher($teacherId);
$assignments = $sectionId ? getAssignmentsBySection($sectionId) : [];

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Assignments</h1>
            <p class="text-muted mb-0">Create and manage assignments for your sections</p>
        </div>
    </div>
    
    <?php displayFlash(); ?>
    
    <!-- Section Selector -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Select Section</label>
                    <select class="form-select" name="section_id" onchange="this.form.submit()">
                        <option value="">-- Select a Section --</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec['id']; ?>" <?php echo $sectionId == $sec['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($sec['course_code'] . ' - ' . $sec['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($section): ?>
                    <div class="col-md-6 text-md-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                            <i class="bi bi-plus-lg me-2"></i>New Assignment
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if ($section): ?>
        <?php if (empty($assignments)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No assignments created for this section yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0"><?php echo sanitize($assignment['title']); ?></h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-dark" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="?section_id=<?php echo $sectionId; ?>&view=<?php echo $assignment['id']; ?>">
                                                <i class="bi bi-eye me-2"></i>View Submissions
                                            </a>
                                        </li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Delete this assignment?');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text text-muted small"><?php echo nl2br(sanitize($assignment['description'])); ?></p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>Due: <?php echo formatDateTime($assignment['due_date']); ?>
                                    </small>
                                    <span class="badge bg-primary"><?php echo $assignment['max_points']; ?> pts</span>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-file-earmark-text me-1"></i><?php echo $assignment['submission_count']; ?> submissions
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- View Submissions Modal -->
        <?php if (isset($_GET['view'])): 
            $viewAssignment = getAssignmentById(intval($_GET['view']));
            $submissions = $viewAssignment ? getAssignmentSubmissions($viewAssignment['id']) : [];
        ?>
            <div class="modal fade show" id="viewSubmissionsModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Submissions: <?php echo sanitize($viewAssignment['title']); ?></h5>
                            <a href="?section_id=<?php echo $sectionId; ?>" class="btn-close"></a>
                        </div>
                        <div class="modal-body">
                            <?php if (empty($submissions)): ?>
                                <div class="alert alert-info mb-0">No submissions yet.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Submitted</th>
                                                <th>Grade</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($submissions as $sub): ?>
                                                <tr>
                                                    <td><?php echo sanitize($sub['student_name']); ?></td>
                                                    <td><?php echo formatDateTime($sub['submitted_at']); ?></td>
                                                    <td>
                                                        <?php if ($sub['grade'] !== null): ?>
                                                            <span class="badge bg-success"><?php echo $sub['grade']; ?>/<?php echo $viewAssignment['max_points']; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Not Graded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#gradeModal<?php echo $sub['id']; ?>">
                                                            <i class="bi bi-pencil"></i> Grade
                                                        </button>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Grade Modal -->
                                                <div class="modal fade" id="gradeModal<?php echo $sub['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <?php echo csrfField(); ?>
                                                                <input type="hidden" name="action" value="grade">
                                                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Grade Submission</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p><strong>Student:</strong> <?php echo sanitize($sub['student_name']); ?></p>
                                                                    <p><strong>Submission:</strong></p>
                                                                    <div class="bg-light p-3 rounded mb-3">
                                                                        <?php echo nl2br(sanitize($sub['submission_text'])); ?>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Grade (max: <?php echo $viewAssignment['max_points']; ?>)</label>
                                                                        <input type="number" class="form-control" name="grade" 
                                                                               value="<?php echo $sub['grade']; ?>"
                                                                               min="0" max="<?php echo $viewAssignment['max_points']; ?>" step="0.01" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Feedback</label>
                                                                        <textarea class="form-control" name="feedback" rows="3"><?php echo sanitize($sub['feedback']); ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">Save Grade</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
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
        
        <!-- Create Assignment Modal -->
        <div class="modal fade" id="createAssignmentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="create">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="due_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Max Points</label>
                                    <input type="number" class="form-control" name="max_points" value="100" min="1">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
