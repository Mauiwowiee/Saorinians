<?php
/**
 * Student Resources View
 * Students can view and download learning materials
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireStudent();

$pageTitle = 'Learning Resources';
$studentId = getCurrentUserId();

// Get student's enrolled sections
$sections = getStudentSections($studentId);
$sectionId = intval($_GET['section_id'] ?? 0);

// Verify student is enrolled in selected section
$selectedSection = null;
if ($sectionId) {
    foreach ($sections as $sec) {
        if ($sec['section_id'] == $sectionId) {
            $selectedSection = $sec;
            break;
        }
    }
    if (!$selectedSection) {
        $sectionId = 0;
    }
}

// Get resources for selected section
$resources = $sectionId ? getResourcesBySection($sectionId) : [];

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Learning Resources</h1>
            <p class="text-muted mb-0">Access course materials and documents</p>
        </div>
    </div>
    
    <?php displayFlash(); ?>
    
    <!-- Section Selector -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Select Course</label>
                    <select class="form-select" name="section_id" onchange="this.form.submit()">
                        <option value="">-- Select a Course --</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec['section_id']; ?>" <?php echo $sectionId == $sec['section_id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($sec['course_code'] . ' - ' . $sec['course_name'] . ' (' . $sec['section_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($selectedSection): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo sanitize($selectedSection['course_code'] . ' - ' . $selectedSection['course_name']); ?></h5>
                <small class="text-muted">Teacher: <?php echo sanitize($selectedSection['teacher_name'] ?? 'TBA'); ?></small>
            </div>
        </div>
        
        <?php if (empty($resources)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No resources have been uploaded for this course yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($resources as $resource): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <?php
                                        $iconClass = 'bi-file-earmark';
                                        $iconColor = 'text-secondary';
                                        switch ($resource['file_type']) {
                                            case 'pdf':
                                                $iconClass = 'bi-file-earmark-pdf';
                                                $iconColor = 'text-danger';
                                                break;
                                            case 'doc':
                                            case 'docx':
                                                $iconClass = 'bi-file-earmark-word';
                                                $iconColor = 'text-primary';
                                                break;
                                            case 'ppt':
                                            case 'pptx':
                                                $iconClass = 'bi-file-earmark-ppt';
                                                $iconColor = 'text-warning';
                                                break;
                                            case 'xls':
                                            case 'xlsx':
                                                $iconClass = 'bi-file-earmark-excel';
                                                $iconColor = 'text-success';
                                                break;
                                            case 'zip':
                                                $iconClass = 'bi-file-earmark-zip';
                                                $iconColor = 'text-info';
                                                break;
                                        }
                                        ?>
                                        <i class="bi <?php echo $iconClass; ?> <?php echo $iconColor; ?> display-6"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1"><?php echo sanitize($resource['title']); ?></h5>
                                        <p class="card-text small text-muted mb-2"><?php echo nl2br(sanitize($resource['description'])); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i><?php echo sanitize($resource['uploaded_by_name']); ?>
                                            <span class="mx-2">|</span>
                                            <i class="bi bi-clock me-1"></i><?php echo timeAgo($resource['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php if ($resource['file_path']): ?>
                                <div class="card-footer bg-transparent">
                                    <a href="<?php echo BASE_URL; ?>uploads/resources/<?php echo $resource['file_path']; ?>" 
                                       class="btn btn-primary btn-sm w-100" download>
                                        <i class="bi bi-download me-2"></i>Download
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php elseif (empty($sections)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>You are not enrolled in any courses yet.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
