<?php
/**
 * Profile Management Page (All Roles)
 * View and edit profile with tabbed interface
 */

$pageTitle = 'My Profile';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/db_operations.php';

requireLogin();

$userId = getCurrentUserId();
$user = getUserById($userId);
$errors = [];
$success = '';

// Handle profile picture upload via AJAX
if (isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        try {
            $filename = uploadFile($_FILES['profile_picture'], PROFILE_PIC_PATH);
            
            // Delete old picture if exists and not default
            $oldPic = $user['profile_pic_path'];
            if ($oldPic && $oldPic !== 'assets/images/default-avatar.png' && file_exists(PROFILE_PIC_PATH . $oldPic)) {
                unlink(PROFILE_PIC_PATH . $oldPic);
            }
            
            updateProfilePicture($userId, $filename);
            
            if (isset($_POST['ajax'])) {
                jsonResponse(['success' => true, 'filename' => $filename]);
            }
            
            setFlash('success', 'Profile picture updated successfully!');
            redirect(BASE_URL . 'modules/common/profile.php');
        } catch (Exception $e) {
            if (isset($_POST['ajax'])) {
                jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
            $errors[] = $e->getMessage();
        }
    }
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $dob = $_POST['date_of_birth'] ?? '';
        
        // Validation
        if (!empty($email) && !isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($errors)) {
            updateUserProfile($userId, $email, $phone, $address, $dob ?: null);
            setFlash('success', 'Profile updated successfully!');
            redirect(BASE_URL . 'modules/common/profile.php');
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'All password fields are required.';
        } elseif (!verifyPassword($userId, $currentPassword)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
        
        if (empty($errors)) {
            changePassword($userId, $newPassword);
            setFlash('success', 'Password changed successfully!');
            redirect(BASE_URL . 'modules/common/profile.php');
        }
    }
}

// Refresh user data
$user = getUserById($userId);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0">
            <i class="bi bi-person-circle me-2"></i>My Profile
        </h2>
        <p class="text-muted">View and manage your profile information</p>
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
        <!-- Profile Card -->
        <div class="card">
            <div class="card-body text-center">
                <div class="profile-picture-container mb-3">
                    <img src="<?= getProfilePicUrl($user['profile_pic_path']) ?>" 
                         alt="Profile Picture" 
                         class="profile-picture"
                         id="profilePicturePreview">
                    <label for="profilePictureInput" class="profile-picture-overlay">
                        <i class="bi bi-camera"></i>
                    </label>
                </div>
                
                <form id="profilePictureForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="file" id="profilePictureInput" name="profile_picture" 
                           accept="image/jpeg,image/png,image/gif" style="display: none;">
                    <input type="hidden" name="upload_picture" value="1">
                    <?= csrfField() ?>
                </form>
                
                <h4 class="mb-1"><?= sanitize($user['full_name']) ?></h4>
                <p class="text-muted mb-2">@<?= sanitize($user['username']) ?></p>
                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'teacher' ? 'success' : 'primary') ?> text-capitalize">
                    <?= sanitize($user['role']) ?>
                </span>
                
                <hr>
                
                <div class="text-start">
                    <p class="mb-2">
                        <i class="bi bi-envelope me-2 text-muted"></i>
                        <?= sanitize($user['email'] ?: 'Not set') ?>
                    </p>
                    <p class="mb-2">
                        <i class="bi bi-phone me-2 text-muted"></i>
                        <?= sanitize($user['phone'] ?: 'Not set') ?>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-calendar me-2 text-muted"></i>
                        Member since <?= formatDate($user['created_at']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Profile Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#viewProfile">
                            <i class="bi bi-eye me-1"></i>View Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#editProfile">
                            <i class="bi bi-pencil me-1"></i>Edit Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#changePassword">
                            <i class="bi bi-shield-lock me-1"></i>Security
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- View Profile Tab -->
                    <div class="tab-pane fade show active" id="viewProfile">
                        <h5 class="mb-4">Profile Information</h5>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Full Name</strong>
                            </div>
                            <div class="col-sm-8">
                                <?= sanitize($user['full_name']) ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Username</strong>
                            </div>
                            <div class="col-sm-8">
                                <?= sanitize($user['username']) ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Role</strong>
                            </div>
                            <div class="col-sm-8 text-capitalize">
                                <?= sanitize($user['role']) ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Email</strong>
                            </div>
                            <div class="col-sm-8">
                                <?= sanitize($user['email'] ?: 'Not set') ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Phone</strong>
                            </div>
                            <div class="col-sm-8">
                                <?= sanitize($user['phone'] ?: 'Not set') ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Address</strong>
                            </div>
                            <div class="col-sm-8">
                                <?= sanitize($user['address'] ?: 'Not set') ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Date of Birth</strong>
                            </div>
                            <div class="col-sm-8">
                                <?= $user['date_of_birth'] ? formatDate($user['date_of_birth']) : 'Not set' ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Profile Tab -->
                    <div class="tab-pane fade" id="editProfile">
                        <h5 class="mb-4">Edit Profile</h5>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <?= csrfField() ?>
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= sanitize($user['email'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= sanitize($user['phone'] ?? '') ?>"
                                       placeholder="+1 (555) 123-4567">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= sanitize($user['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?= $user['date_of_birth'] ?? '' ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Save Changes
                            </button>
                        </form>
                    </div>
                    
                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="changePassword">
                        <h5 class="mb-4">Change Password</h5>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <?= csrfField() ?>
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" 
                                            data-target="#current_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" minlength="8" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" 
                                            data-target="#new_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Password must be at least 8 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" 
                                            data-target="#confirm_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-shield-check me-1"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initProfilePictureUpload();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
