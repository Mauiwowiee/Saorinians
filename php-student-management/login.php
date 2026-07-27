<?php
/**
 * Login Page
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

initSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (login($username, $password)) {
            redirect(BASE_URL . 'dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Login to <?= APP_NAME ?> - Student Management System">
    <meta name="theme-color" content="#0d6efd">
    <meta name="color-scheme" content="light dark">
    <title>Login - <?= APP_NAME ?></title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= BASE_URL ?>assets/css/style.css" as="style">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" integrity="sha384-4LISF5TTJX/fLmGSxO53rV4miRxdg84mF+5cokeSoJo36b6E22ZKSuHLzlqKyPU8" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <main class="login-container" role="main">
        <div class="login-card">
            <div class="text-center mb-5">
                <div class="login-logo" aria-label="<?= APP_NAME ?> logo">
                    <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
                </div>
                <h1 class="h3 fw-bold mt-3"><?= APP_NAME ?></h1>
                <p class="text-muted mb-0">Student Management System</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" aria-live="polite" aria-atomic="true">
                <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i>
                <strong>Error:</strong> <?= sanitize($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="needs-validation" novalidate>
                <fieldset>
                    <legend class="visually-hidden">Login Form</legend>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person me-2" aria-hidden="true"></i>Username
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="username" 
                               name="username" 
                               value="<?= isset($_POST['username']) ? sanitize($_POST['username']) : '' ?>" 
                               placeholder="Enter your username"
                               autocomplete="username"
                               required 
                               autofocus
                               aria-describedby="usernameHelp">
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Please enter your username.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-2" aria-hidden="true"></i>Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control form-control-lg" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   required
                                   aria-describedby="passwordHelp">
                            <button class="btn btn-outline-secondary toggle-password" 
                                    type="button" 
                                    data-target="#password"
                                    aria-label="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Please enter your password.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" aria-label="Sign in to your account">
                        <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Sign In
                    </button>
                </fieldset>
            </form>
            
            <hr class="my-4" aria-hidden="true">
            
            <div class="text-center mb-4">
                <p class="mb-0" id="registerLink">Don't have an account? 
                    <a href="<?= BASE_URL ?>register.php" class="text-decoration-none fw-bold">Register here</a>
                </p>
            </div>
            
            <details class="mt-4 pt-3 border-top">
                <summary class="cursor-pointer fw-bold text-muted">Demo Credentials</summary>
                <div class="mt-3 small">
                    <div class="mb-2">
                        <strong>Admin:</strong>
                        <div class="font-monospace bg-light p-2 rounded">admin / admin123</div>
                    </div>
                    <div class="mb-2">
                        <strong>Teacher:</strong>
                        <div class="font-monospace bg-light p-2 rounded">teacher1 / password123</div>
                    </div>
                    <div>
                        <strong>Student:</strong>
                        <div class="font-monospace bg-light p-2 rounded">student1 / password123</div>
                    </div>
                </div>
            </details>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9yk0LslrbIlK12OSVAOS2FtqLoJihYYdHvT0q3+8duLvFixucQzRn0zIx" crossorigin="anonymous"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
