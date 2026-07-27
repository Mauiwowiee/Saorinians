<?php
/**
 * Grade Management (Teacher)
 * Configure grading periods and components
 */

$pageTitle = 'Manage Grading';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireTeacher();

$teacherId = getCurrentUserId();
$sectionId = $_GET['section_id'] ?? null;
$errors = [];
$success = [];

// Get teacher's sections
$teacherSections = getSectionsByTeacher($teacherId);

// Handle adding grading period
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_period'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $sectionId = (int)$_POST['section_id'];
        $periodName = trim($_POST['period_name'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        
        // Verify section belongs to teacher
        $section = getSectionById($sectionId);
        if (!$section || $section['teacher_id'] != $teacherId) {
            $errors[] = 'You do not have access to this section.';
        } elseif (empty($periodName)) {
            $errors[] = 'Period name is required.';
        } elseif (empty($startDate) || empty($endDate)) {
            $errors[] = 'Start and end dates are required.';
        } elseif (strtotime($endDate) <= strtotime($startDate)) {
            $errors[] = 'End date must be after start date.';
        } else {
            try {
                createGradingPeriod($sectionId, $periodName, $startDate, $endDate);
                $success[] = 'Grading period created successfully!';
            } catch (Exception $e) {
                $errors[] = 'Error creating grading period: ' . $e->getMessage();
            }
        }
    }
}

// Handle adding grade component
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_component'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $sectionId = (int)$_POST['section_id'];
        $componentName = trim($_POST['component_name'] ?? '');
        $weight = (float)($_POST['weight'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        
        // Verify section belongs to teacher
        $section = getSectionById($sectionId);
        if (!$section || $section['teacher_id'] != $teacherId) {
            $errors[] = 'You do not have access to this section.';
        } elseif (empty($componentName)) {
            $errors[] = 'Component name is required.';
        } elseif ($weight <= 0 || $weight > 100) {
            $errors[] = 'Weight must be between 0 and 100.';
        } else {
            try {
                // Check if adding this component would exceed 100%
                $components = getGradeComponents($sectionId);
                $totalWeight = 0;
                foreach ($components as $comp) {
                    $totalWeight += $comp['weight'];
                }
                
                if ($totalWeight + $weight > 100) {
                    $errors[] = 'Total weight cannot exceed 100%. Current total: ' . $totalWeight . '%';
                } else {
                    createGradeComponent($sectionId, $componentName, $weight, $description ?: null);
                    $success[] = 'Grade component added successfully!';
                }
            } catch (Exception $e) {
                $errors[] = 'Error adding component: ' . $e->getMessage();
            }
        }
    }
}

// Get selected section details
$section = null;
$periods = [];
$components = [];

if ($sectionId) {
    $section = getSectionById($sectionId);
    if ($section && $section['teacher_id'] == $teacherId) {
        $periods = getGradingPeriods($sectionId);
        $components = getGradeComponents($sectionId);
    } else {
        $sectionId = null;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-calculator me-2"></i>Manage Grading
        </h2>
        <p class="text-muted">Configure grading periods and components</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?= sanitize($error) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($success as $msg): ?>
        <li><?= sanitize($msg) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-3 mb-4">
        <!-- Section Selection -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-sliders me-2"></i>Select Section
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label for="section_id" class="form-label">Section</label>
                        <select class="form-select" id="section_id" name="section_id" onchange="this.form.submit()">
                            <option value="">-- Select Section --</option>
                            <?php foreach ($teacherSections as $sec): ?>
                            <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>>
                                <?= sanitize($sec['course_code'] . ' - ' . $sec['section_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                
                <?php if ($section): ?>
                <hr>
                <h6>Section Info</h6>
                <p class="mb-1 small"><strong>Course:</strong> <?= sanitize($section['course_name']) ?></p>
                <p class="mb-1 small"><strong>Room:</strong> <?= sanitize($section['room_number'] ?: 'TBA') ?></p>
                <p class="mb-0 small"><strong>Schedule:</strong> <?= sanitize($section['schedule_time'] ?: 'TBA') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <?php if (!$sectionId): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-left-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                <p class="mt-3 text-muted">Please select a section to configure grading.</p>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Grading Components -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2"></i>Grade Components</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addComponentModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Component
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($components)): ?>
                <p class="text-muted mb-0">No components configured yet. Add components to create a grading scale.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th>Weight</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalWeight = 0;
                            foreach ($components as $component): 
                                $totalWeight += $component['weight'];
                            ?>
                            <tr>
                                <td><strong><?= sanitize($component['component_name']) ?></strong></td>
                                <td>
                                    <span class="badge bg-primary"><?= $component['weight'] ?>%</span>
                                </td>
                                <td><?= sanitize($component['description'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-light">
                                <td><strong>Total Weight</strong></td>
                                <td>
                                    <span class="badge <?= $totalWeight == 100 ? 'bg-success' : 'bg-warning' ?>">
                                        <?= $totalWeight ?>%
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalWeight != 100): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Weight must total 100% to calculate grades properly. Current: <?= $totalWeight ?>%
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Grading Periods -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-check me-2"></i>Grading Periods</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Period
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($periods)): ?>
                <p class="text-muted mb-0">No grading periods configured. Create periods to track grades.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($periods as $period): ?>
                            <tr>
                                <td><strong><?= sanitize($period['period_name']) ?></strong></td>
                                <td><?= formatDate($period['start_date']) ?></td>
                                <td><?= formatDate($period['end_date']) ?></td>
                                <td>
                                    <span class="badge <?= $period['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($period['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>modules/teacher/enter_grades.php?section_id=<?= $sectionId ?>&period_id=<?= $period['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil me-1"></i>Enter Grades
                                    </a>
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
    </div>
</div>

<!-- Add Component Modal -->
<div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addComponentLabel">Add Grade Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="component_name" class="form-label">Component Name *</label>
                        <input type="text" class="form-control" id="component_name" name="component_name" 
                               placeholder="e.g., Attendance, Quizzes, Tests" required>
                    </div>
                    <div class="mb-3">
                        <label for="weight" class="form-label">Weight (%) *</label>
                        <input type="number" class="form-control" id="weight" name="weight" 
                               min="0" max="100" step="0.01" placeholder="0" required>
                        <small class="form-text text-muted">All weights must sum to 100%</small>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_component" class="btn btn-primary">Add Component</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Period Modal -->
<div class="modal fade" id="addPeriodModal" tabindex="-1" aria-labelledby="addPeriodLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPeriodLabel">Add Grading Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="period_name" class="form-label">Period Name *</label>
                        <input type="text" class="form-control" id="period_name" name="period_name" 
                               placeholder="e.g., Q1, Midterm, Final" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date *</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date *</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_period" class="btn btn-primary">Add Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
