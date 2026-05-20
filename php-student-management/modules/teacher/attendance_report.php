<?php
/**
 * Attendance Report for Teachers
 * Teachers can view attendance statistics for their sections
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireTeacher();

$pageTitle = 'Attendance Report';
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

// Get data
$sections = getSectionsByTeacher($teacherId);
$attendanceSummary = $sectionId ? getAttendanceSummary($sectionId) : [];

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Attendance Report</h1>
            <p class="text-muted mb-0">View attendance statistics for your sections</p>
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
    
    <?php if ($section && !empty($attendanceSummary)): ?>
        <!-- Summary Statistics -->
        <?php
        $totalStudents = count($attendanceSummary);
        $totalPresent = array_sum(array_column($attendanceSummary, 'present_count'));
        $totalAbsent = array_sum(array_column($attendanceSummary, 'absent_count'));
        $totalLate = array_sum(array_column($attendanceSummary, 'late_count'));
        $totalExcused = array_sum(array_column($attendanceSummary, 'excused_count'));
        $totalRecords = $totalPresent + $totalAbsent + $totalLate + $totalExcused;
        ?>
        
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $totalPresent; ?></h3>
                        <small>Total Present</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $totalAbsent; ?></h3>
                        <small>Total Absent</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $totalLate; ?></h3>
                        <small>Total Late</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo $totalExcused; ?></h3>
                        <small>Total Excused</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Report -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Student Attendance Summary - <?php echo sanitize($section['course_code'] . ' ' . $section['section_name']); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Student Name</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Excused</th>
                                <th class="text-center">Total Classes</th>
                                <th class="text-center">Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceSummary as $student): 
                                $totalClasses = $student['total_classes'] ?: 0;
                                $present = $student['present_count'] ?: 0;
                                $late = $student['late_count'] ?: 0;
                                $percentage = $totalClasses > 0 ? round((($present + $late) / $totalClasses) * 100, 1) : 0;
                                $badgeClass = $percentage >= 80 ? 'bg-success' : ($percentage >= 60 ? 'bg-warning text-dark' : 'bg-danger');
                            ?>
                                <tr>
                                    <td><?php echo sanitize($student['full_name']); ?></td>
                                    <td class="text-center"><span class="badge bg-success"><?php echo $present; ?></span></td>
                                    <td class="text-center"><span class="badge bg-danger"><?php echo $student['absent_count'] ?: 0; ?></span></td>
                                    <td class="text-center"><span class="badge bg-warning text-dark"><?php echo $late; ?></span></td>
                                    <td class="text-center"><span class="badge bg-info"><?php echo $student['excused_count'] ?: 0; ?></span></td>
                                    <td class="text-center"><?php echo $totalClasses; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $percentage; ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($section): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>No attendance records found for this section.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
