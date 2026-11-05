<?php
/**
 * My Attendance (Student)
 * View attendance history
 */

$pageTitle = 'My Attendance';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireStudent();

$studentId = getCurrentUserId();
$sectionId = $_GET['section_id'] ?? null;

// Get student's enrolled sections
$enrolledSections = getStudentSections($studentId);

// Get attendance records
$attendanceRecords = getStudentAttendance($studentId, $sectionId);

// Calculate summary stats
$summary = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'excused' => 0,
    'total' => count($attendanceRecords)
];

foreach ($attendanceRecords as $record) {
    $summary[$record['status']]++;
}

$attendanceRate = $summary['total'] > 0 
    ? round(($summary['present'] + $summary['late']) / $summary['total'] * 100, 1) 
    : 0;
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-calendar-check me-2"></i>My Attendance
        </h2>
        <p class="text-muted">View your attendance history</p>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="section_id" class="form-label">Filter by Course</label>
                <select class="form-select" id="section_id" name="section_id" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php foreach ($enrolledSections as $section): ?>
                    <option value="<?= $section['section_id'] ?>" <?= $sectionId == $section['section_id'] ? 'selected' : '' ?>>
                        <?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <?php if ($sectionId): ?>
                <a href="<?= BASE_URL ?>modules/student/my_attendance.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Clear Filter
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-0"><?= $summary['present'] ?></h3>
                <small>Present</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3 class="mb-0"><?= $summary['absent'] ?></h3>
                <small>Absent</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h3 class="mb-0"><?= $summary['late'] ?></h3>
                <small>Late</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-0"><?= $attendanceRate ?>%</h3>
                <small>Attendance Rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Attendance History -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i>Attendance History
    </div>
    <div class="card-body">
        <?php if (empty($attendanceRecords)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <p>No attendance records found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th class="text-center">Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceRecords as $record): ?>
                    <tr>
                        <td><?= formatDate($record['attendance_date']) ?></td>
                        <td>
                            <strong><?= sanitize($record['course_code']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($record['course_name']) ?></small>
                        </td>
                        <td><?= sanitize($record['section_name']) ?></td>
                        <td class="text-center">
                            <?php
                            $badgeClass = 'bg-secondary';
                            switch ($record['status']) {
                                case 'present': $badgeClass = 'bg-success'; break;
                                case 'absent': $badgeClass = 'bg-danger'; break;
                                case 'late': $badgeClass = 'bg-warning text-dark'; break;
                                case 'excused': $badgeClass = 'bg-info'; break;
                            }
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($record['status']) ?></span>
                        </td>
                        <td><?= sanitize($record['remarks'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
