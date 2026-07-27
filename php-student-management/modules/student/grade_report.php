<?php
/**
 * Grade Report (Student)
 * View personal grades and performance
 */

$pageTitle = 'My Grades';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireStudent();

$studentId = getCurrentUserId();
$sectionId = $_GET['section_id'] ?? null;

// Get student's enrolled sections
$sections = getStudentSections($studentId);

// Get selected section and its grades
$selectedSection = null;
$periods = [];
$components = [];
$gradesByPeriod = [];

if ($sectionId) {
    foreach ($sections as $section) {
        if ($section['id'] == $sectionId) {
            $selectedSection = $section;
            break;
        }
    }
    
    if ($selectedSection) {
        $periods = getGradingPeriods($sectionId);
        $components = getGradeComponents($sectionId);
        
        // Get grades for each period
        foreach ($periods as $period) {
            $grades = getStudentGradesByPeriod($studentId, $sectionId, $period['id']);
            $quarterGrade = calculateQuarterGrade($sectionId, $studentId, $period['id']);
            $gradesByPeriod[$period['id']] = [
                'period' => $period,
                'grades' => $grades,
                'quarter_grade' => $quarterGrade
            ];
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-graph-up me-2"></i>My Grades
        </h2>
        <p class="text-muted">View your course grades and performance</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <!-- Course Selection -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-book me-2"></i>My Courses
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($sections as $section): ?>
                    <a href="<?= BASE_URL ?>modules/student/grade_report.php?section_id=<?= $section['id'] ?>"
                       class="list-group-item list-group-item-action <?= $sectionId == $section['id'] ? 'active' : '' ?>">
                        <strong><?= sanitize($section['course_code']) ?></strong><br>
                        <small><?= sanitize($section['section_name']) ?></small>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <?php if (!$selectedSection): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-left-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                <p class="mt-3 text-muted">Select a course from the left to view your grades.</p>
            </div>
        </div>
        <?php elseif (empty($periods)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-info-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                <p class="mt-3 text-muted">No grading periods configured for this course yet.</p>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Course Header -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-1"><?= sanitize($selectedSection['course_name']) ?></h5>
                <p class="text-muted mb-2">
                    <i class="bi bi-person me-2"></i><?= sanitize($selectedSection['teacher_name'] ?? 'Unknown Instructor') ?>
                </p>
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Course Code</small>
                        <strong><?= sanitize($selectedSection['course_code']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Credits</small>
                        <strong><?= $selectedSection['credits'] ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Room</small>
                        <strong><?= sanitize($selectedSection['room_number'] ?: 'TBA') ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grading Periods -->
        <?php foreach ($gradesByPeriod as $periodData): ?>
        <?php $period = $periodData['period']; $grades = $periodData['grades']; $quarterGrade = $periodData['quarter_grade']; ?>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-calendar-check me-2"></i><?= sanitize($period['period_name']) ?>
                    <small class="text-muted ms-2">(<?= formatDate($period['start_date']) ?> - <?= formatDate($period['end_date']) ?>)</small>
                </span>
                <div>
                    <span class="badge bg-primary" style="font-size: 0.95rem;">
                        Quarter Grade: <strong><?= $quarterGrade > 0 ? number_format($quarterGrade, 2) : 'N/A' ?></strong>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($components)): ?>
                <p class="text-muted mb-0">No grades recorded for this period.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th width="100">Weight</th>
                                <th width="100">Your Score</th>
                                <th width="150">Contribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($components as $component): ?>
                            <?php 
                            $studentGrade = null;
                            foreach ($grades as $g) {
                                if ($g['component_id'] == $component['id']) {
                                    $studentGrade = $g;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?= sanitize($component['component_name']) ?></strong><br>
                                    <small class="text-muted"><?= sanitize($component['description'] ?: '') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $component['weight'] ?>%</span>
                                </td>
                                <td>
                                    <?php if ($studentGrade): ?>
                                    <span class="badge bg-success"><?= number_format($studentGrade['score'], 2) ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-light text-muted">Not graded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($studentGrade): ?>
                                    <div class="progress" style="height: 20px;">
                                        <?php $contribution = ($studentGrade['score'] * $component['weight']) / 100; ?>
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= ($studentGrade['score'] / 100 * $component['weight']) ?>%;"
                                             aria-valuenow="<?= $contribution ?>" aria-valuemin="0" aria-valuemax="<?= $component['weight'] ?>">
                                            <?= number_format($contribution, 2) ?>
                                        </div>
                                    </div>
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
        <?php endforeach; ?>
        
        <!-- Grade Scale Reference -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Grading Scale
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr><td>90 - 100</td><td><strong>A</strong></td></tr>
                            <tr><td>80 - 89</td><td><strong>B</strong></td></tr>
                            <tr><td>70 - 79</td><td><strong>C</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr><td>60 - 69</td><td><strong>D</strong></td></tr>
                            <tr><td>Below 60</td><td><strong>F</strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
