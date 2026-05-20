<?php
/**
 * Student Grades View
 * Students can view their grades for all enrolled courses
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireStudent();

$pageTitle = 'My Grades';
$studentId = getCurrentUserId();

// Get student's enrolled sections
$sections = getStudentSections($studentId);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Grades</h1>
            <p class="text-muted mb-0">View your grades and academic performance</p>
        </div>
    </div>
    
    <?php displayFlash(); ?>
    
    <?php if (empty($sections)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>You are not enrolled in any courses yet.
        </div>
    <?php else: ?>
        <!-- Grade Summary Cards -->
        <div class="row mb-4">
            <?php foreach ($sections as $section): 
                $percentage = calculateStudentGrade($studentId, $section['section_id']);
                $letterGrade = $percentage !== null ? getGradeLetter($percentage) : '-';
                $gradeColor = 'secondary';
                if ($percentage !== null) {
                    if ($percentage >= 90) $gradeColor = 'success';
                    elseif ($percentage >= 80) $gradeColor = 'primary';
                    elseif ($percentage >= 70) $gradeColor = 'info';
                    elseif ($percentage >= 60) $gradeColor = 'warning';
                    else $gradeColor = 'danger';
                }
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo sanitize($section['course_code']); ?></h5>
                            <small class="text-muted"><?php echo sanitize($section['course_name']); ?></small>
                        </div>
                        <div class="card-body text-center">
                            <div class="display-4 text-<?php echo $gradeColor; ?> mb-2"><?php echo $letterGrade; ?></div>
                            <?php if ($percentage !== null): ?>
                                <p class="text-muted mb-0"><?php echo $percentage; ?>%</p>
                            <?php else: ?>
                                <p class="text-muted mb-0">No grades yet</p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <button class="btn btn-sm btn-outline-primary w-100" 
                                    data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $section['section_id']; ?>">
                                <i class="bi bi-eye me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Detail Modal -->
                <div class="modal fade" id="detailModal<?php echo $section['section_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo sanitize($section['course_code'] . ' - ' . $section['course_name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php 
                                $assessments = getStudentScoresBySection($studentId, $section['section_id']);
                                if (empty($assessments)):
                                ?>
                                    <div class="alert alert-info mb-0">No assessments have been recorded yet.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Assessment</th>
                                                    <th>Type</th>
                                                    <th>Due Date</th>
                                                    <th>Score</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assessments as $assessment): 
                                                    $scorePercent = null;
                                                    if ($assessment['score'] !== null && $assessment['max_score'] > 0) {
                                                        $scorePercent = round(($assessment['score'] / $assessment['max_score']) * 100, 1);
                                                    }
                                                ?>
                                                    <tr>
                                                        <td><?php echo sanitize($assessment['title']); ?></td>
                                                        <td><span class="badge bg-secondary"><?php echo ucfirst($assessment['type']); ?></span></td>
                                                        <td><?php echo $assessment['due_date'] ? formatDate($assessment['due_date']) : '-'; ?></td>
                                                        <td>
                                                            <?php if ($assessment['score'] !== null): ?>
                                                                <?php echo $assessment['score']; ?>/<?php echo $assessment['max_score']; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($scorePercent !== null): ?>
                                                                <span class="badge bg-<?php echo $scorePercent >= 60 ? 'success' : 'danger'; ?>">
                                                                    <?php echo $scorePercent; ?>%
                                                                </span>
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
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Overall Summary -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Grade Summary</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Teacher</th>
                                <th>Credits</th>
                                <th>Current Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sections as $section): 
                                $percentage = calculateStudentGrade($studentId, $section['section_id']);
                                $letterGrade = $percentage !== null ? getGradeLetter($percentage) : '-';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo sanitize($section['course_code']); ?></strong><br>
                                        <small class="text-muted"><?php echo sanitize($section['course_name']); ?></small>
                                    </td>
                                    <td><?php echo sanitize($section['section_name']); ?></td>
                                    <td><?php echo sanitize($section['teacher_name'] ?? 'TBA'); ?></td>
                                    <td><?php echo $section['credits']; ?></td>
                                    <td>
                                        <?php if ($percentage !== null): ?>
                                            <span class="badge bg-<?php echo $letterGrade === 'F' ? 'danger' : 'primary'; ?> fs-6">
                                                <?php echo $letterGrade; ?> (<?php echo $percentage; ?>%)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No grades yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
