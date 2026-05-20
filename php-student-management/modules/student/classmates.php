<?php
/**
 * Classmates List (Student)
 * View other students in the same section
 */

$pageTitle = 'Classmates';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireStudent();

$studentId = getCurrentUserId();
$sectionId = $_GET['section_id'] ?? null;

if (!$sectionId) {
    setFlash('error', 'Section not specified.');
    redirect(BASE_URL . 'modules/student/my_courses.php');
}

// Verify student is enrolled in this section
$enrolledSections = getStudentSections($studentId);
$isEnrolled = false;
$currentSection = null;

foreach ($enrolledSections as $enrollment) {
    if ($enrollment['section_id'] == $sectionId) {
        $isEnrolled = true;
        $currentSection = $enrollment;
        break;
    }
}

if (!$isEnrolled) {
    setFlash('error', 'You are not enrolled in this section.');
    redirect(BASE_URL . 'modules/student/my_courses.php');
}

// Get all students in this section (including current student)
$classmates = getStudentsBySection($sectionId);
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/student/my_courses.php">My Courses</a></li>
                <li class="breadcrumb-item active"><?= sanitize($currentSection['course_code'] . ' - ' . $currentSection['section_name']) ?></li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-people me-2"></i>Classmates
        </h2>
        <p class="text-muted"><?= sanitize($currentSection['course_code'] . ' - ' . $currentSection['course_name']) ?></p>
    </div>
</div>

<!-- Section & Teacher Info -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Section Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Course:</strong> <?= sanitize($currentSection['course_code'] . ' - ' . $currentSection['course_name']) ?></p>
                        <p class="mb-2"><strong>Section:</strong> <?= sanitize($currentSection['section_name']) ?></p>
                        <p class="mb-2"><strong>Credits:</strong> <?= $currentSection['credits'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Room:</strong> <?= sanitize($currentSection['room_number'] ?: 'TBA') ?></p>
                        <p class="mb-2"><strong>Schedule:</strong> <?= sanitize($currentSection['schedule_time'] ?: 'TBA') ?></p>
                        <p class="mb-0"><strong>Class Size:</strong> <?= count($classmates) ?> students</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-badge me-2"></i>Teacher
            </div>
            <div class="card-body text-center">
                <?php if ($currentSection['teacher_name']): ?>
                <h5 class="mb-2"><?= sanitize($currentSection['teacher_name']) ?></h5>
                <?php if ($currentSection['teacher_email']): ?>
                <p class="mb-0">
                    <a href="mailto:<?= sanitize($currentSection['teacher_email']) ?>">
                        <i class="bi bi-envelope me-1"></i><?= sanitize($currentSection['teacher_email']) ?>
                    </a>
                </p>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-muted mb-0">Teacher not assigned</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Classmates List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i>Class Roster (<?= count($classmates) ?> students)</span>
        <input type="text" id="tableSearch" class="form-control form-control-sm w-auto" placeholder="Search classmates...">
    </div>
    <div class="card-body">
        <?php if (empty($classmates)): ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>No students enrolled in this section.</p>
        </div>
        <?php else: ?>
        <div class="row" id="dataTable">
            <?php foreach ($classmates as $classmate): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 <?= $classmate['id'] == $studentId ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="<?= getProfilePicUrl($classmate['profile_pic_path']) ?>" 
                                 class="avatar-circle me-3" alt="Profile">
                            <div>
                                <h6 class="mb-0">
                                    <?= sanitize($classmate['full_name']) ?>
                                    <?php if ($classmate['id'] == $studentId): ?>
                                    <span class="badge bg-primary">You</span>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted">@<?= sanitize($classmate['username']) ?></small>
                                <?php if ($classmate['email'] && $classmate['id'] != $studentId): ?>
                                <br>
                                <a href="mailto:<?= sanitize($classmate['email']) ?>" class="small">
                                    <i class="bi bi-envelope"></i> Contact
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
