<?php
/**
 * Grade Management for Teachers
 * Teachers can create assessments and record grades
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireTeacher();

$pageTitle = 'Grade Management';
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
            if ($action === 'create_assessment') {
                $title = sanitize($_POST['title'] ?? '');
                $type = sanitize($_POST['type'] ?? 'quiz');
                $maxScore = floatval($_POST['max_score'] ?? 100);
                $weight = floatval($_POST['weight'] ?? 1);
                $dueDate = $_POST['due_date'] ?? null;
                
                createAssessment($sectionId, $title, $type, $maxScore, $weight, $dueDate ?: null);
                setFlash('success', 'Assessment created successfully.');
            } elseif ($action === 'record_scores') {
                $assessmentId = intval($_POST['assessment_id'] ?? 0);
                $scores = $_POST['scores'] ?? [];
                $remarks = $_POST['remarks'] ?? [];
                
                foreach ($scores as $studentId => $score) {
                    if ($score !== '') {
                        recordStudentScore($assessmentId, $studentId, floatval($score), $remarks[$studentId] ?? null);
                    }
                }
                setFlash('success', 'Scores recorded successfully.');
            } elseif ($action === 'delete_assessment') {
                $assessmentId = intval($_POST['assessment_id'] ?? 0);
                deleteAssessment($assessmentId);
                setFlash('success', 'Assessment deleted.');
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
$assessments = $sectionId ? getAssessmentsBySection($sectionId) : [];
$students = $sectionId ? getStudentsBySection($sectionId) : [];

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Grade Management</h1>
            <p class="text-muted mb-0">Create assessments and record student grades</p>
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
            </form>
        </div>
    </div>
    
    <?php if ($section): ?>
        <div class="row">
            <!-- Assessments List -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Assessments</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createAssessmentModal">
                            <i class="bi bi-plus-lg"></i> New
                        </button>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($assessments)): ?>
                            <div class="list-group-item text-muted">No assessments created yet.</div>
                        <?php else: ?>
                            <?php foreach ($assessments as $assessment): ?>
                                <a href="?section_id=<?php echo $sectionId; ?>&assessment_id=<?php echo $assessment['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo (isset($_GET['assessment_id']) && $_GET['assessment_id'] == $assessment['id']) ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo sanitize($assessment['title']); ?></h6>
                                        <small><?php echo $assessment['max_score']; ?> pts</small>
                                    </div>
                                    <small>
                                        <span class="badge bg-secondary"><?php echo ucfirst($assessment['type']); ?></span>
                                        <?php if ($assessment['due_date']): ?>
                                            Due: <?php echo formatDate($assessment['due_date']); ?>
                                        <?php endif; ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Grade Entry -->
            <div class="col-lg-8">
                <?php 
                $selectedAssessment = null;
                if (isset($_GET['assessment_id'])) {
                    $selectedAssessment = getAssessmentById(intval($_GET['assessment_id']));
                    $existingScores = getScoresForAssessment($selectedAssessment['id']);
                    $scoresMap = [];
                    foreach ($existingScores as $score) {
                        $scoresMap[$score['student_id']] = $score;
                    }
                }
                ?>
                
                <?php if ($selectedAssessment): ?>
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><?php echo sanitize($selectedAssessment['title']); ?></h5>
                                <small class="text-muted">Max Score: <?php echo $selectedAssessment['max_score']; ?> | Weight: <?php echo $selectedAssessment['weight']; ?></small>
                            </div>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this assessment?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete_assessment">
                                <input type="hidden" name="assessment_id" value="<?php echo $selectedAssessment['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="record_scores">
                                <input type="hidden" name="assessment_id" value="<?php echo $selectedAssessment['id']; ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th width="150">Score</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo sanitize($student['full_name']); ?></td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="scores[<?php echo $student['id']; ?>]" 
                                                               value="<?php echo isset($scoresMap[$student['id']]) ? $scoresMap[$student['id']]['score'] : ''; ?>"
                                                               min="0" max="<?php echo $selectedAssessment['max_score']; ?>" step="0.01">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="remarks[<?php echo $student['id']; ?>]"
                                                               value="<?php echo isset($scoresMap[$student['id']]) ? sanitize($scoresMap[$student['id']]['remarks']) : ''; ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>Save Scores
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-journal-bookmark display-4 text-muted"></i>
                            <p class="mt-3 text-muted">Select an assessment to record grades</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Create Assessment Modal -->
        <div class="modal fade" id="createAssessmentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="create_assessment">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Assessment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type">
                                        <option value="quiz">Quiz</option>
                                        <option value="exam">Exam</option>
                                        <option value="assignment">Assignment</option>
                                        <option value="project">Project</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Max Score</label>
                                    <input type="number" class="form-control" name="max_score" value="100" min="1" step="0.01">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Weight</label>
                                    <input type="number" class="form-control" name="weight" value="1" min="0.01" step="0.01">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" class="form-control" name="due_date">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
