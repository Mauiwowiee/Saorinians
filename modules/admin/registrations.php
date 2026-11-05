<?php
/**
 * Registration Requests Management
 * Admin can approve or reject user registration requests
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

$pageTitle = 'Registration Requests';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $action = $_POST['action'] ?? '';
        $requestId = intval($_POST['request_id'] ?? 0);
        $notes = sanitize($_POST['notes'] ?? '');
        
        try {
            if ($action === 'approve') {
                approveRegistration($requestId, getCurrentUserId(), $notes);
                setFlash('success', 'Registration approved successfully. User account has been created.');
            } elseif ($action === 'reject') {
                rejectRegistration($requestId, getCurrentUserId(), $notes);
                setFlash('success', 'Registration rejected.');
            }
        } catch (Exception $e) {
            setFlash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get registration requests
$filter = $_GET['filter'] ?? 'pending';
if ($filter === 'all') {
    $registrations = getAllRegistrations();
} else {
    $registrations = getPendingRegistrations();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Registration Requests</h1>
            <p class="text-muted mb-0">Manage user registration requests</p>
        </div>
    </div>
    
    <?php displayFlash(); ?>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" href="?filter=pending">
                Pending <span class="badge bg-warning text-dark"><?php echo count(getPendingRegistrations()); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">
                All Requests
            </a>
        </li>
    </ul>
    
    <?php if (empty($registrations)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>No registration requests found.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td>
                                    <strong><?php echo sanitize($reg['full_name']); ?></strong>
                                    <?php if ($reg['phone']): ?>
                                        <br><small class="text-muted"><?php echo sanitize($reg['phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo sanitize($reg['username']); ?></td>
                                <td><?php echo sanitize($reg['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $reg['role'] === 'teacher' ? 'primary' : 'success'; ?>">
                                        <?php echo ucfirst($reg['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDateTime($reg['created_at']); ?></td>
                                <td>
                                    <?php if ($reg['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($reg['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($reg['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $reg['id']; ?>">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $reg['id']; ?>">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                        
                                        <!-- Approve Modal -->
                                        <div class="modal fade" id="approveModal<?php echo $reg['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="request_id" value="<?php echo $reg['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Approve Registration</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Approve registration for <strong><?php echo sanitize($reg['full_name']); ?></strong> as <strong><?php echo ucfirst($reg['role']); ?></strong>?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Notes (optional)</label>
                                                                <textarea class="form-control" name="notes" rows="2"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Approve</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal<?php echo $reg['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="request_id" value="<?php echo $reg['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject Registration</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Reject registration for <strong><?php echo sanitize($reg['full_name']); ?></strong>?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason (optional)</label>
                                                                <textarea class="form-control" name="notes" rows="2"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">
                                            <?php echo $reg['processed_by_name'] ? 'By ' . sanitize($reg['processed_by_name']) : ''; ?>
                                            <?php if ($reg['admin_notes']): ?>
                                                <br><em><?php echo sanitize($reg['admin_notes']); ?></em>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
