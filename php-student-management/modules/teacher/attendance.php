<?php
/**
 * Section-Based Attendance (Teacher)
 * Take attendance for assigned sections
 */

$pageTitle = 'Take Attendance';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireTeacher();

$teacherId = getCurrentUserId();
$sectionId = $_GET['section_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');
$errors = [];

// Get teacher's sections
$teacherSections = getSectionsByTeacher($teacherId);

// Handle AJAX attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_mark_attendance'])) {
    header('Content-Type: application/json');
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    try {
        $sectionId = (int)$_POST['section_id'];
        $studentId = (int)$_POST['student_id'];
        $date = $_POST['attendance_date'];
        $status = $_POST['status'];
        
        // Verify section belongs to teacher
        $section = getSectionById($sectionId);
        if (!$section || $section['teacher_id'] != $teacherId) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        markAttendanceWithUndo($sectionId, $studentId, $date, $status, $teacherId);
        echo json_encode(['success' => true, 'message' => 'Attendance marked']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle undo request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['undo_attendance'])) {
    header('Content-Type: application/json');
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    try {
        $sectionId = (int)$_POST['section_id'];
        $studentId = (int)$_POST['student_id'];
        $date = $_POST['attendance_date'];
        
        $section = getSectionById($sectionId);
        if (!$section || $section['teacher_id'] != $teacherId) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        undoLastAttendanceChange($sectionId, $studentId, $date, $teacherId);
        echo json_encode(['success' => true, 'message' => 'Attendance undone']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle batch submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $sectionId = (int)$_POST['section_id'];
        $date = $_POST['attendance_date'];
        $attendanceData = [];
        
        foreach ($_POST['attendance'] as $studentId => $status) {
            $attendanceData[$studentId] = [
                'status' => $status,
                'remarks' => $_POST['remarks'][$studentId] ?? null
            ];
        }
        
        // Verify section belongs to teacher
        $section = getSectionById($sectionId);
        if (!$section || $section['teacher_id'] != $teacherId) {
            $errors[] = 'You do not have access to this section.';
        }
        
        if (empty($errors)) {
            try {
                batchMarkAttendance($sectionId, $date, $attendanceData, $teacherId);
                setFlash('success', 'Attendance saved successfully!');
                redirect(BASE_URL . 'modules/teacher/attendance.php?section_id=' . $sectionId . '&date=' . $date);
            } catch (Exception $e) {
                $errors[] = 'Error saving attendance: ' . $e->getMessage();
            }
        }
    }
}

// Get selected section details and students
$section = null;
$students = [];
$existingAttendance = [];
$attendanceSummary = [];

if ($sectionId) {
    $section = getSectionById($sectionId);
    
    // Verify section belongs to teacher
    if ($section && $section['teacher_id'] == $teacherId) {
        $students = getStudentsBySection($sectionId);
        
        // Get existing attendance for this date
        $attendanceRecords = getAttendanceByDate($sectionId, $date);
        foreach ($attendanceRecords as $record) {
            $existingAttendance[$record['student_id']] = $record;
        }
        
        // Get overall attendance summary
        $attendanceSummary = getAttendanceSummaryWithStats($sectionId);
    } else {
        setFlash('error', 'You do not have access to this section.');
        $sectionId = null;
        $section = null;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-calendar-check me-2"></i>Take Attendance
        </h2>
        <p class="text-muted">Mark attendance for your sections</p>
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

<div class="row">
    <div class="col-lg-3 mb-4">
        <!-- Section & Date Selection -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-sliders me-2"></i>Select Section & Date
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label for="section_id" class="form-label">Section</label>
                        <select class="form-select" id="section_id" name="section_id" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach ($teacherSections as $sec): ?>
                            <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>>
                                <?= sanitize($sec['course_code'] . ' - ' . $sec['section_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?= sanitize($date) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Load Students
                    </button>
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
        <!-- Attendance Summary Cards -->
        <?php if ($section && !empty($attendanceSummary)): ?>
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card students">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon students">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="ms-3">
                                <small class="text-muted d-block">Present Today</small>
                                <h5 class="mb-0" id="present-count">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card teachers">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon teachers">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div class="ms-3">
                                <small class="text-muted d-block">Absent Today</small>
                                <h5 class="mb-0" id="absent-count">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card courses">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon courses">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="ms-3">
                                <small class="text-muted d-block">Late Today</small>
                                <h5 class="mb-0" id="late-count">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card sections">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon sections">
                                <i class="bi bi-hand-thumbs-up"></i>
                            </div>
                            <div class="ms-3">
                                <small class="text-muted d-block">Excused Today</small>
                                <h5 class="mb-0" id="excused-count">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Attendance Form -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-list-check me-2"></i>
                    <?php if ($section): ?>
                    Attendance for <?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?>
                    (<?= formatDate($date) ?>)
                    <?php else: ?>
                    Attendance Sheet
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (!$sectionId): ?>
                <div class="empty-state">
                    <i class="bi bi-arrow-left-circle"></i>
                    <p>Please select a section and date from the left panel.</p>
                </div>
                <?php elseif (empty($students)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>No students enrolled in this section.</p>
                </div>
                <?php else: ?>
                <form id="attendanceForm" method="POST" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                    <input type="hidden" name="attendance_date" value="<?= sanitize($date) ?>">
                    
                    <div class="mb-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" id="defaultPresent" title="Mark all as present by default">
                            <i class="bi bi-check-all me-1"></i>Default Present
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="defaultAbsent" title="Mark all as absent by default">
                            <i class="bi bi-x-lg me-1"></i>Default Absent
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Student</th>
                                    <th width="300">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                <?php $existing = $existingAttendance[$student['id']] ?? null; ?>
                                <tr data-student-id="<?= $student['id'] ?>">
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
                                    <td>
                                        <div class="attendance-toggle-group">
                                            <input type="hidden" name="attendance[<?= $student['id'] ?>]" value="<?= $existing['status'] ?? 'present' ?>" class="attendance-value">
                                            <button type="button" class="btn attendance-btn present" data-status="present" 
                                                    <?= ($existing['status'] ?? 'present') === 'present' ? 'active' : '' ?> title="Mark present">
                                                <i class="bi bi-check-circle"></i> Present
                                            </button>
                                            <button type="button" class="btn attendance-btn absent" data-status="absent"
                                                    <?= ($existing['status'] ?? '') === 'absent' ? 'active' : '' ?> title="Mark absent">
                                                <i class="bi bi-x-circle"></i> Absent
                                            </button>
                                            <button type="button" class="btn attendance-btn late" data-status="late"
                                                    <?= ($existing['status'] ?? '') === 'late' ? 'active' : '' ?> title="Mark late">
                                                <i class="bi bi-clock"></i> Late
                                            </button>
                                            <button type="button" class="btn attendance-btn excused" data-status="excused"
                                                    <?= ($existing['status'] ?? '') === 'excused' ? 'active' : '' ?> title="Mark excused">
                                                <i class="bi bi-hand-thumbs-up"></i> Excused
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= BASE_URL ?>modules/teacher/my_sections.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" name="save_attendance" class="btn btn-success btn-lg">
                            <i class="bi bi-check-lg me-1"></i>Save Attendance
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Undo Toast Notification -->
<div id="undoToast" class="toast-notification" style="display: none;">
    <div class="d-flex align-items-center">
        <span>Attendance updated</span>
        <button type="button" class="btn btn-sm btn-link ms-2" onclick="undoLastChange()">Undo</button>
        <button type="button" class="btn-close btn-sm ms-2" onclick="document.getElementById('undoToast').style.display='none'"></button>
    </div>
</div>

<script>
const sectionId = <?= $sectionId ?? 'null' ?>;
const attendanceDate = '<?= sanitize($date) ?>';
const csrfToken = '<?= generateCSRFToken() ?>';

document.addEventListener('DOMContentLoaded', function() {
    if (sectionId) {
        initAttendanceToggles();
        updateSummary();
    }
});

function initAttendanceToggles() {
    document.querySelectorAll('.attendance-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const status = this.dataset.status;
            const row = this.closest('tr');
            const studentId = row.dataset.studentId;
            const valueInput = row.querySelector('.attendance-value');
            
            // Update toggle UI
            row.querySelectorAll('.attendance-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            valueInput.value = status;
            
            // Update summary in real-time
            updateSummary();
            
            // Show undo toast
            showUndoToast();
        });
    });
    
    // Default present/absent buttons
    document.getElementById('defaultPresent')?.addEventListener('click', function() {
        document.querySelectorAll('.attendance-btn.present').forEach(btn => {
            if (!btn.classList.contains('active')) btn.click();
        });
    });
    
    document.getElementById('defaultAbsent')?.addEventListener('click', function() {
        document.querySelectorAll('.attendance-btn.absent').forEach(btn => {
            if (!btn.classList.contains('active')) btn.click();
        });
    });
}

function updateSummary() {
    let counts = { present: 0, absent: 0, late: 0, excused: 0 };
    document.querySelectorAll('.attendance-value').forEach(input => {
        const status = input.value;
        if (counts.hasOwnProperty(status)) counts[status]++;
    });
    
    document.getElementById('present-count').textContent = counts.present;
    document.getElementById('absent-count').textContent = counts.absent;
    document.getElementById('late-count').textContent = counts.late;
    document.getElementById('excused-count').textContent = counts.excused;
}

function showUndoToast() {
    const toast = document.getElementById('undoToast');
    if (toast) {
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 5000);
    }
}

function undoLastChange() {
    // This would connect to the AJAX undo endpoint
    alert('Undo functionality can track the last change');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
