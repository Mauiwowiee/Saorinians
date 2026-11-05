<?php
/**
 * Teacher Workload View (Admin Only)
 * View all teachers and their assigned sections
 */

$pageTitle = 'Teacher Workload';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$teachers = getTeacherWorkload();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-list-check me-2"></i>Teacher Workload
        </h2>
        <p class="text-muted">View all teachers and their section assignments</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-badge me-2"></i>Teachers Overview</span>
        <input type="text" id="tableSearch" class="form-control form-control-sm w-auto" placeholder="Search teachers...">
    </div>
    <div class="card-body">
        <?php if (empty($teachers)): ?>
        <div class="empty-state">
            <i class="bi bi-person-badge"></i>
            <p>No teachers found in the system.</p>
            <a href="<?= BASE_URL ?>modules/admin/manage_users.php?role=teacher&action=add" class="btn btn-primary">
                Add First Teacher
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Teacher Name</th>
                        <th>Email</th>
                        <th class="text-center">Sections Assigned</th>
                        <th class="text-center">Workload Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $index => $teacher): ?>
                    <?php
                    // Determine workload status
                    $sectionCount = $teacher['section_count'];
                    if ($sectionCount == 0) {
                        $statusClass = 'bg-secondary';
                        $statusText = 'No Assignment';
                    } elseif ($sectionCount <= 2) {
                        $statusClass = 'bg-success';
                        $statusText = 'Light';
                    } elseif ($sectionCount <= 4) {
                        $statusClass = 'bg-warning text-dark';
                        $statusText = 'Moderate';
                    } else {
                        $statusClass = 'bg-danger';
                        $statusText = 'Heavy';
                    }
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= sanitize($teacher['full_name']) ?></strong>
                        </td>
                        <td><?= sanitize($teacher['email'] ?: '-') ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6"><?= $sectionCount ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>modules/admin/teacher_sections.php?id=<?= $teacher['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View Sections
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h4 class="mb-0"><?= count($teachers) ?></h4>
                        <small class="text-muted">Total Teachers</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h4 class="mb-0"><?= array_sum(array_column($teachers, 'section_count')) ?></h4>
                        <small class="text-muted">Total Assignments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h4 class="mb-0">
                            <?= count(array_filter($teachers, fn($t) => $t['section_count'] == 0)) ?>
                        </h4>
                        <small class="text-muted">Unassigned Teachers</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h4 class="mb-0">
                            <?= count($teachers) > 0 ? number_format(array_sum(array_column($teachers, 'section_count')) / count($teachers), 1) : 0 ?>
                        </h4>
                        <small class="text-muted">Avg. Sections/Teacher</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
