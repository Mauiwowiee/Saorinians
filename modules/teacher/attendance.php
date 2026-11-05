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

// Handle attendance submission
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
                <?php if ($section && !empty($students)): ?>
                <div>
                    <button type="button" id="markAllPresent" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-check-all me-1"></i>All Present
                    </button>
                    <button type="button" id="markAllAbsent" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-x-lg me-1"></i>All Absent
                    </button>
                </div>
                <?php endif; ?>
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
                <form method="POST" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                    <input type="hidden" name="attendance_date" value="<?= sanitize($date) ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Student</th>
                                    <th width="200">Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                <?php $existing = $existingAttendance[$student['id']] ?? null; ?>
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
                                    <td>
                                        <select class="form-select form-select-sm attendance-status" 
                                                name="attendance[<?= $student['id'] ?>]" required>
                                            <option value="present" <?= ($existing['status'] ?? '') === 'present' ? 'selected' : '' ?>>
                                                Present
                                            </option>
                                            <option value="absent" <?= ($existing['status'] ?? '') === 'absent' ? 'selected' : '' ?>>
                                                Absent
                                            </option>
                                            <option value="late" <?= ($existing['status'] ?? '') === 'late' ? 'selected' : '' ?>>
                                                Late
                                            </option>
                                            <option value="excused" <?= ($existing['status'] ?? '') === 'excused' ? 'selected' : '' ?>>
                                                Excused
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="remarks[<?= $student['id'] ?>]"
                                               value="<?= sanitize($existing['remarks'] ?? '') ?>"
                                               placeholder="Optional remarks">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="<?= BASE_URL ?>modules/teacher/my_sections.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" name="save_attendance" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>Save Attendance
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initAttendance();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
