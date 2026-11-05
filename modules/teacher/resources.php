<?php
/**
 * Resource/Material Management for Teachers
 * Teachers can upload learning materials for their sections
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireTeacher();

$pageTitle = 'Learning Resources';
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            if ($action === 'upload') {
                $title = sanitize($_POST['title'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                
                $filePath = null;
                $fileType = null;
                
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../uploads/resources/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip'];
                    $filePath = uploadFile($_FILES['file'], $uploadDir, $allowedTypes);
                    $fileType = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                }
                
                uploadResource($sectionId, $title, $description, $filePath, $fileType, $teacherId);
                setFlash('success', 'Resource uploaded successfully.');
            } elseif ($action === 'delete') {
                $resourceId = intval($_POST['resource_id'] ?? 0);
                deleteResource($resourceId);
                setFlash('success', 'Resource deleted.');
            }
        } catch (Exception $e) {
            setFlash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get data
$sections = getSectionsByTeacher($teacherId);
$resources = $sectionId ? getResourcesBySection($sectionId) : [];

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Learning Resources</h1>
            <p class="text-muted mb-0">Upload and manage learning materials for your sections</p>
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
                <?php if ($section): ?>
                    <div class="col-md-6 text-md-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-upload me-2"></i>Upload Resource
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if ($section): ?>
        <?php if (empty($resources)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No resources uploaded for this section yet.
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
                                            <i class="bi bi-clock me-1"></i><?php echo timeAgo($resource['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <?php if ($resource['file_path']): ?>
                                        <a href="<?php echo BASE_URL; ?>uploads/resources/<?php echo $resource['file_path']; ?>" 
                                           class="btn btn-sm btn-outline-primary" download>
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this resource?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="upload">
                        <div class="modal-header">
                            <h5 class="modal-title">Upload Resource</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File</label>
                                <input type="file" class="form-control" name="file" 
                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip">
                                <div class="form-text">Allowed: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
