<?php
/**
 * User Management (Admin Only)
 * Manage students and teachers
 */

$role = $_GET['role'] ?? 'student';
$pageTitle = 'Manage ' . ucfirst($role) . 's';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireAdmin();

$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;
$errors = [];

// Validate role
if (!in_array($role, ['student', 'teacher'])) {
    redirect(BASE_URL . 'modules/admin/manage_users.php?role=student');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $fullName = sanitize($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = sanitize($_POST['email'] ?? '');
        
        // Validation
        if (empty($username) || empty($fullName)) {
            $errors[] = 'Username and full name are required.';
        }
        
        if (isset($_POST['add_user']) && empty($password)) {
            $errors[] = 'Password is required for new users.';
        }
        
        if (!empty($email) && !isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Check if username exists (for new users)
        if (isset($_POST['add_user']) && getUserByUsername($username)) {
            $errors[] = 'Username already exists.';
        }
        
        if (empty($errors)) {
            try {
                if (isset($_POST['add_user'])) {
                    $newUserId = createUser($username, $password, $role, $fullName);
                    if ($email) {
                        updateUserProfile($newUserId, $email, null, null);
                    }
                    setFlash('success', ucfirst($role) . ' added successfully!');
                } elseif (isset($_POST['edit_user']) && $userId) {
                    updateUser($userId, $fullName);
                    if ($email) {
                        $existingProfile = getUserById($userId);
                        updateUserProfile($userId, $email, $existingProfile['phone'], $existingProfile['address']);
                    }
                    
                    // Update password if provided
                    if (!empty($password)) {
                        changePassword($userId, $password);
                    }
                    
                    setFlash('success', ucfirst($role) . ' updated successfully!');
                }
                redirect(BASE_URL . 'modules/admin/manage_users.php?role=' . $role);
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $userId) {
    try {
        deleteUser($userId);
        setFlash('success', ucfirst($role) . ' deleted successfully!');
    } catch (Exception $e) {
        setFlash('error', 'Cannot delete user: ' . $e->getMessage());
    }
    redirect(BASE_URL . 'modules/admin/manage_users.php?role=' . $role);
}

// Get user for editing
$user = null;
if ($action === 'edit' && $userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== $role) {
        setFlash('error', 'User not found.');
        redirect(BASE_URL . 'modules/admin/manage_users.php?role=' . $role);
    }
}

// Get all users
$users = getUsersByRole($role);
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-<?= $role === 'teacher' ? 'person-badge' : 'people' ?> me-2"></i>
                    Manage <?= ucfirst($role) ?>s
                </h2>
                <p class="text-muted">Add, edit, and remove <?= $role ?>s</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?role=<?= $role ?>&action=add" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i>Add <?= ucfirst($role) ?>
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>modules/admin/manage_users.php?role=<?= $role ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Role Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $role === 'student' ? 'active' : '' ?>" href="?role=student">
            <i class="bi bi-people me-1"></i>Students
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $role === 'teacher' ? 'active' : '' ?>" href="?role=teacher">
            <i class="bi bi-person-badge me-1"></i>Teachers
        </a>
    </li>
</ul>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?= sanitize($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-<?= $action === 'add' ? 'person-plus' : 'pencil-square' ?> me-2"></i>
        <?= $action === 'add' ? 'Add New ' . ucfirst($role) : 'Edit ' . ucfirst($role) ?>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= sanitize($user['username'] ?? $_POST['username'] ?? '') ?>"
                               <?= $action === 'edit' ? 'readonly' : 'required' ?>
                               placeholder="Enter username">
                        <?php if ($action === 'add'): ?>
                        <div class="form-text">Username will be used for login. Cannot be changed later.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?= sanitize($user['full_name'] ?? $_POST['full_name'] ?? '') ?>"
                               required placeholder="Enter full name">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= sanitize($user['email'] ?? $_POST['email'] ?? '') ?>"
                               placeholder="Enter email address">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password <?= $action === 'add' ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   <?= $action === 'add' ? 'required' : '' ?>
                                   placeholder="<?= $action === 'edit' ? 'Leave blank to keep current' : 'Enter password' ?>">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if ($action === 'add'): ?>
                        <div class="form-text">Minimum 8 characters recommended.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="<?= $action === 'add' ? 'add_user' : 'edit_user' ?>" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $action === 'add' ? 'Add ' . ucfirst($role) : 'Update ' . ucfirst($role) ?>
                </button>
                <a href="<?= BASE_URL ?>modules/admin/manage_users.php?role=<?= $role ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Users List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list me-2"></i>All <?= ucfirst($role) ?>s (<?= count($users) ?>)</span>
        <div>
            <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search...">
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>No <?= $role ?>s found.</p>
            <a href="?role=<?= $role ?>&action=add" class="btn btn-primary">Add First <?= ucfirst($role) ?></a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Created</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $u): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?= getProfilePicUrl($u['profile_pic_path']) ?>" 
                                     class="avatar-circle-sm me-2" alt="">
                                <?= sanitize($u['full_name']) ?>
                            </div>
                        </td>
                        <td><code><?= sanitize($u['username']) ?></code></td>
                        <td><?= sanitize($u['email'] ?: '-') ?></td>
                        <td><?= sanitize($u['phone'] ?: '-') ?></td>
                        <td><?= formatDate($u['created_at']) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?role=<?= $role ?>&action=edit&id=<?= $u['id'] ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?role=<?= $role ?>&delete=1&id=<?= $u['id'] ?>" 
                                   class="btn btn-outline-danger delete-confirm" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
