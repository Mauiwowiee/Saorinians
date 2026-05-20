<?php
/**
 * Enrollment Management (Admin Only)
 * Enroll students in sections (including batch enrollment)
 */

$pageTitle = 'Enrollments';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$sectionId = $_GET['section_id'] ?? null;
$errors = [];

// Get all sections for dropdown
$sections = getAllSections();

// Handle batch enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_enroll'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $selectedSection = (int)($_POST['section_id'] ?? 0);
        $selectedStudents = $_POST['students'] ?? [];
        
        if ($selectedSection <= 0) {
            $errors[] = 'Please select a section.';
        }
        if (empty($selectedStudents)) {
            $errors[] = 'Please select at least one student.';
        }
        
        if (empty($errors)) {
            try {
                batchEnrollStudents($selectedStudents, $selectedSection);
                setFlash('success', count($selectedStudents) . ' student(s) enrolled successfully!');
                redirect(BASE_URL . 'modules/admin/enrollments.php?section_id=' . $selectedSection);
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle single enrollment drop
if (isset($_GET['drop']) && isset($_GET['student_id']) && $sectionId) {
    try {
        dropStudent($_GET['student_id'], $sectionId);
        setFlash('success', 'Student removed from section.');
    } catch (Exception $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
    }
    redirect(BASE_URL . 'modules/admin/enrollments.php?section_id=' . $sectionId);
}

// Get students for selected section
$enrolledStudents = [];
$availableStudents = [];
$selectedSection = null;

if ($sectionId) {
    $selectedSection = getSectionById($sectionId);
    if ($selectedSection) {
        $enrolledStudents = getStudentsBySection($sectionId);
        $availableStudents = getStudentsNotInSection($sectionId);
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-person-plus me-2"></i>Enrollment Management
        </h2>
        <p class="text-muted">Enroll students in course sections</p>
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
    <div class="col-lg-4 mb-4">
        <!-- Section Selection -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-diagram-3 me-2"></i>Select Section
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label for="section_id" class="form-label">Course Section</label>
                        <select class="form-select" id="section_id" name="section_id" onchange="this.form.submit()">
                            <option value="">-- Select a Section --</option>
                            <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['id'] ?>" <?= $sectionId == $section['id'] ? 'selected' : '' ?>>
                                <?= sanitize($section['course_code'] . ' - ' . $section['section_name']) ?>
                                (<?= sanitize($section['teacher_name'] ?: 'No Teacher') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                
                <?php if ($selectedSection): ?>
                <hr>
                <h6>Section Details</h6>
                <p class="mb-1"><strong>Course:</strong> <?= sanitize($selectedSection['course_code'] . ' - ' . $selectedSection['course_name']) ?></p>
                <p class="mb-1"><strong>Section:</strong> <?= sanitize($selectedSection['section_name']) ?></p>
                <p class="mb-1"><strong>Teacher:</strong> <?= sanitize($selectedSection['teacher_name'] ?: 'Not Assigned') ?></p>
                <p class="mb-1"><strong>Room:</strong> <?= sanitize($selectedSection['room_number'] ?: 'TBA') ?></p>
                <p class="mb-1"><strong>Schedule:</strong> <?= sanitize($selectedSection['schedule_time'] ?: 'TBA') ?></p>
                <p class="mb-0"><strong>Enrolled:</strong> <?= count($enrolledStudents) ?> / <?= $selectedSection['max_students'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($selectedSection && !empty($availableStudents)): ?>
        <!-- Batch Enrollment Form -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-people-fill me-2"></i>Batch Enrollment
            </div>
            <div class="card-body">
                <form method="POST" action="" id="batchEnrollForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Students</label>
                        <div class="border rounded p-2" style="max-height: 250px; overflow-y: auto;">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="selectAllStudents">
                                <label class="form-check-label fw-bold" for="selectAllStudents">
                                    Select All
                                </label>
                            </div>
                            <hr class="my-2">
                            <?php foreach ($availableStudents as $student): ?>
                            <div class="form-check">
                                <input class="form-check-input select-student" type="checkbox" 
                                       name="students[]" value="<?= $student['id'] ?>" id="student_<?= $student['id'] ?>">
                                <label class="form-check-label" for="student_<?= $student['id'] ?>">
                                    <?= sanitize($student['full_name']) ?>
                                    <small class="text-muted">(<?= sanitize($student['username']) ?>)</small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="batch_enroll" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus me-1"></i>Enroll Selected Students
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-8">
        <!-- Enrolled Students List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i>Enrolled Students</span>
                <?php if ($selectedSection): ?>
                <span class="badge bg-primary"><?= count($enrolledStudents) ?> students</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$sectionId): ?>
                <div class="empty-state">
                    <i class="bi bi-arrow-left-circle"></i>
                    <p>Please select a section from the left to view enrolled students.</p>
                </div>
                <?php elseif (empty($enrolledStudents)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>No students enrolled in this section yet.</p>
                    <?php if (!empty($availableStudents)): ?>
                    <p class="text-muted">Use the batch enrollment form to add students.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Enrolled Date</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolledStudents as $index => $student): ?>
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
                                <td><?= sanitize($student['email'] ?: '-') ?></td>
                                <td><?= formatDate($student['enrollment_date']) ?></td>
                                <td>
                                    <?php if ($student['grade']): ?>
                                    <span class="badge bg-success"><?= sanitize($student['grade']) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?section_id=<?= $sectionId ?>&drop=1&student_id=<?= $student['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger delete-confirm" title="Remove from section">
                                        <i class="bi bi-person-dash"></i>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all students checkbox
    var selectAll = document.getElementById('selectAllStudents');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.select-student');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }
    
    initBatchEnrollment();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
