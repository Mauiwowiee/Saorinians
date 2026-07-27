<?php
/**
 * Enter Grades (Teacher)
 * Input and manage student grades
 */

$pageTitle = 'Enter Grades';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireTeacher();

$teacherId = getCurrentUserId();
$sectionId = $_GET['section_id'] ?? null;
$periodId = $_GET['period_id'] ?? null;
$errors = [];
$success = [];

// Handle grade submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save_grade'])) {
    header('Content-Type: application/json');
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    try {
        $sectionId = (int)$_POST['section_id'];
        $studentId = (int)$_POST['student_id'];
        $periodId = (int)$_POST['period_id'];
        $componentId = (int)$_POST['component_id'];
        $score = (float)$_POST['score'];
        
        // Verify section and score
        $section = getSectionById($sectionId);
        if (!$section || $section['teacher_id'] != $teacherId) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if ($score < 0 || $score > 100) {
            echo json_encode(['success' => false, 'message' => 'Score must be between 0 and 100']);
            exit;
        }
        
        recordGrade($sectionId, $studentId, $periodId, $componentId, $score);
        $quarterGrade = calculateQuarterGrade($sectionId, $studentId, $periodId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Grade saved',
            'quarter_grade' => $quarterGrade
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get teacher's sections
$teacherSections = getSectionsByTeacher($teacherId);

// Get selected section, period, components, and students
$section = null;
$period = null;
$components = [];
$students = [];
$gradeData = [];

if ($sectionId && $periodId) {
    $section = getSectionById($sectionId);
    if ($section && $section['teacher_id'] == $teacherId) {
        $periods = getGradingPeriods($sectionId);
        foreach ($periods as $p) {
            if ($p['id'] == $periodId) {
                $period = $p;
                break;
            }
        }
        
        if ($period) {
            $components = getGradeComponents($sectionId);
            $students = getStudentsBySection($sectionId);
            $gradeData = getGradesForPeriod($sectionId, $periodId);
        }
    } else {
        $sectionId = null;
        $periodId = null;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-pencil-square me-2"></i>Enter Grades
        </h2>
        <p class="text-muted">Input student grades for grading periods</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <!-- Section & Period Selection -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-sliders me-2"></i>Select Period
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label for="section_id" class="form-label">Section</label>
                        <select class="form-select" id="section_id" name="section_id">
                            <option value="">-- Select Section --</option>
                            <?php foreach ($teacherSections as $sec): ?>
                            <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>>
                                <?= sanitize($sec['course_code'] . ' - ' . $sec['section_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($sectionId): ?>
                    <div class="mb-3">
                        <label for="period_id" class="form-label">Grading Period</label>
                        <select class="form-select" id="period_id" name="period_id">
                            <option value="">-- Select Period --</option>
                            <?php 
                            $periods = $sectionId ? getGradingPeriods($sectionId) : [];
                            foreach ($periods as $p): 
                            ?>
                            <option value="<?= $p['id'] ?>" <?= $periodId == $p['id'] ? 'selected' : '' ?>>
                                <?= sanitize($p['period_name'] . ' (' . formatDate($p['start_date']) . ' - ' . formatDate($p['end_date']) . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Load Period
                    </button>
                </form>
                
                <?php if ($section): ?>
                <hr>
                <h6>Section Info</h6>
                <p class="mb-1 small"><strong>Course:</strong> <?= sanitize($section['course_name']) ?></p>
                <p class="mb-1 small"><strong>Room:</strong> <?= sanitize($section['room_number'] ?: 'TBA') ?></p>
                <?php endif; ?>
                
                <?php if ($period): ?>
                <hr>
                <h6>Period Info</h6>
                <p class="mb-1 small"><strong>Name:</strong> <?= sanitize($period['period_name']) ?></p>
                <p class="mb-0 small"><strong>Range:</strong> <?= formatDate($period['start_date']) ?> - <?= formatDate($period['end_date']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <?php if (!$period): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-left-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                <p class="mt-3 text-muted">Please select a section and grading period.</p>
            </div>
        </div>
        <?php elseif (empty($components)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-exclamation-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                <p class="mt-3 text-muted">No grade components configured for this section.</p>
                <a href="<?= BASE_URL ?>modules/teacher/manage_grades.php?section_id=<?= $sectionId ?>" 
                   class="btn btn-primary mt-2">Configure Components</a>
            </div>
        </div>
        <?php elseif (empty($students)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-people" style="font-size: 3rem; opacity: 0.5;"></i>
                <p class="mt-3 text-muted">No students enrolled in this section.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-table me-2"></i>Grade Entry for <?= sanitize($period['period_name']) ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="gradeTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Student</th>
                                <?php foreach ($components as $comp): ?>
                                <th class="text-center" title="<?= sanitize($comp['component_name']) ?> (<?= $comp['weight'] ?>%)">
                                    <?= sanitize(substr($comp['component_name'], 0, 12)) ?>
                                    <small class="d-block">(<strong><?= $comp['weight'] ?>%</strong>)</small>
                                </th>
                                <?php endforeach; ?>
                                <th class="text-center">Quarter Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <?php $studentGrades = $gradeData[$student['id']] ?? ['name' => $student['full_name'], 'components' => [], 'quarter_grade' => 0]; ?>
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
                                <?php foreach ($components as $comp): ?>
                                <?php 
                                $gradeValue = '';
                                foreach ($studentGrades['components'] as $g) {
                                    if ($g['component_id'] == $comp['id']) {
                                        $gradeValue = $g['score'];
                                        break;
                                    }
                                }
                                ?>
                                <td class="text-center">
                                    <input type="number" class="form-control form-control-sm grade-input" 
                                           data-student="<?= $student['id'] ?>"
                                           data-component="<?= $comp['id'] ?>"
                                           data-period="<?= $periodId ?>"
                                           min="0" max="100" step="0.01"
                                           value="<?= $gradeValue ?>"
                                           placeholder="0">
                                </td>
                                <?php endforeach; ?>
                                <td class="text-center">
                                    <strong class="quarter-grade" data-student="<?= $student['id'] ?>">
                                        <?= $studentGrades['quarter_grade'] > 0 ? number_format($studentGrades['quarter_grade'], 2) : '-' ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 p-3 bg-light rounded">
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Grades are automatically saved as you enter them. Quarter grades are calculated based on weighted components.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const sectionId = <?= $sectionId ?? 'null' ?>;
const periodId = <?= $periodId ?? 'null' ?>;
const csrfToken = '<?= generateCSRFToken() ?>';

document.addEventListener('DOMContentLoaded', function() {
    initGradeInput();
    updateSectionPeriods();
});

// Update periods when section changes
document.getElementById('section_id')?.addEventListener('change', function() {
    updateSectionPeriods();
});

function updateSectionPeriods() {
    const sectionId = document.getElementById('section_id').value;
    if (!sectionId) return;
    
    // Reload to get new periods
    window.location.href = window.location.pathname + '?section_id=' + sectionId;
}

function initGradeInput() {
    document.querySelectorAll('.grade-input').forEach(input => {
        input.addEventListener('blur', function() {
            saveGrade(this);
        });
        
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                saveGrade(this);
            }
        });
    });
}

function saveGrade(input) {
    const score = parseFloat(input.value);
    if (isNaN(score) || score === '') {
        return; // Don't save empty values
    }
    
    if (score < 0 || score > 100) {
        showAlert('error', 'Score must be between 0 and 100');
        input.value = input.dataset.originalValue || '';
        return;
    }
    
    const studentId = input.dataset.student;
    const componentId = input.dataset.component;
    const periodId = input.dataset.period;
    
    const formData = new FormData();
    formData.append('ajax_save_grade', '1');
    formData.append('section_id', sectionId);
    formData.append('student_id', studentId);
    formData.append('period_id', periodId);
    formData.append('component_id', componentId);
    formData.append('score', score);
    formData.append('csrf_token', csrfToken);
    
    fetch(window.location.pathname, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.dataset.originalValue = score;
            input.classList.add('bg-light');
            setTimeout(() => input.classList.remove('bg-light'), 300);
            
            // Update quarter grade
            const quarterGradeEl = document.querySelector(`.quarter-grade[data-student="${studentId}"]`);
            if (quarterGradeEl) {
                quarterGradeEl.textContent = data.quarter_grade > 0 ? 
                    data.quarter_grade.toFixed(2) : '-';
            }
        } else {
            showAlert('error', data.message || 'Error saving grade');
        }
    })
    .catch(error => {
        console.error('[v0] Grade save error:', error);
        showAlert('error', 'Network error while saving grade');
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
