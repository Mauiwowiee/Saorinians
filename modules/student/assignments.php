<?php
/**
 * Student Assignments View
 * Students can view and submit assignments
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireStudent();

$pageTitle = 'My Assignments';
$studentId = getCurrentUserId();

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            if ($action === 'submit') {
                $assignmentId = intval($_POST['assignment_id'] ?? 0);
                $submissionText = sanitize($_POST['submission_text'] ?? '');
                
                // Verify student is enrolled in the assignment's section
                $assignment = getAssignmentById($assignmentId);
                $isEnrolled = false;
                foreach (getStudentSections($studentId) as $enrolledSection) {
                    if ((int)$enrolledSection['section_id'] === (int)($assignment['section_id'] ?? 0)) {
                        $isEnrolled = true;
                        break;
                    }
                }
                if ($assignment && $isEnrolled && trim($submissionText) !== '') {
                    submitAssignment($assignmentId, $studentId, $submissionText);
                    setFlash('success', 'Assignment submitted successfully.');
                } else {
                    setFlash('error', 'Invalid assignment.');
                }
            }
        } catch (Exception $e) {
            setFlash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get student's enrolled sections
$sections = getStudentSections($studentId);
$sectionId = intval($_GET['section_id'] ?? 0);

// Verify student is enrolled in selected section
$selectedSection = null;
if ($sectionId) {
    foreach ($sections as $sec) {
        if ($sec['section_id'] == $sectionId) {
            $selectedSection = $sec;
            break;
        }
    }
    if (!$selectedSection) {
        $sectionId = 0;
    }
}

// Get assignments for selected section
$assignments = $sectionId ? getStudentAssignments($studentId, $sectionId) : [];

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Assignments</h1>
            <p class="text-muted mb-0">View and submit assignments</p>
        </div>
    </div>
    
    <?php displayFlash(); ?>
    
    <!-- Section Selector -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Select Course</label>
                    <select class="form-select" name="section_id" onchange="this.form.submit()">
                        <option value="">-- Select a Course --</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec['section_id']; ?>" <?php echo $sectionId == $sec['section_id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($sec['course_code'] . ' - ' . $sec['course_name'] . ' (' . $sec['section_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($selectedSection): ?>
        <?php if (empty($assignments)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No assignments for this course yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($assignments as $assignment): 
                    $isSubmitted = !empty($assignment['submitted_at']);
                    $isPastDue = strtotime($assignment['due_date']) < time();
                    $isGraded = $assignment['grade'] !== null;
                ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100 <?php echo $isPastDue && !$isSubmitted ? 'border-danger' : ''; ?>">
                            <div class="card-header d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo sanitize($assignment['title']); ?></h5>
                                    <small class="text-muted">Max Points: <?php echo $assignment['max_points']; ?></small>
                                </div>
                                <div>
                                    <?php if ($isGraded): ?>
                                        <span class="badge bg-success">Graded</span>
                                    <?php elseif ($isSubmitted): ?>
                                        <span class="badge bg-primary">Submitted</span>
                                    <?php elseif ($isPastDue): ?>
                                        <span class="badge bg-danger">Past Due</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(sanitize($assignment['description'])); ?></p>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>Due: <?php echo formatDateTime($assignment['due_date']); ?>
                                    </small>
                                </div>
                                
                                <?php if ($isSubmitted): ?>
                                    <div class="bg-light p-3 rounded mb-3">
                                        <strong>Your Submission:</strong>
                                        <p class="mb-0 mt-2"><?php echo nl2br(sanitize($assignment['submission_text'])); ?></p>
                                        <small class="text-muted">Submitted: <?php echo formatDateTime($assignment['submitted_at']); ?></small>
                                    </div>
                                    
                                    <?php if ($isGraded): ?>
                                        <div class="alert alert-success mb-0">
                                            <strong>Grade: <?php echo $assignment['grade']; ?>/<?php echo $assignment['max_points']; ?></strong>
                                            <?php if ($assignment['feedback']): ?>
                                                <hr>
                                                <p class="mb-0"><strong>Feedback:</strong> <?php echo nl2br(sanitize($assignment['feedback'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-primary w-100" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#submitModal<?php echo $assignment['id']; ?>"
                                            <?php echo $isPastDue ? 'disabled' : ''; ?>>
                                        <i class="bi bi-upload me-2"></i>Submit Assignment
                                    </button>
                                    
                                    <!-- Submit Modal -->
                                    <div class="modal fade" id="submitModal<?php echo $assignment['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="submit">
                                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Submit: <?php echo sanitize($assignment['title']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Your Submission <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" name="submission_text" rows="6" required 
                                                                      placeholder="Enter your answer or response here..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Submit</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php elseif (empty($sections)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>You are not enrolled in any courses yet.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
