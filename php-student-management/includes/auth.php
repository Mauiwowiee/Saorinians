<?php
/**
 * Authentication Functions
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/db_operations.php';

/**
 * Start session if not already started
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Authenticate user
 */
function login($username, $password) {
    $user = getUserByUsername($username);
    
    if ($user && password_verify($password, $user['password'])) {
        initSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

/**
 * Logout user
 */
function logout() {
    initSession();
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    initSession();
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        logout();
        return false;
    }
    
    // Refresh session time on activity
    $_SESSION['login_time'] = time();
    return true;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    initSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    initSession();
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user's full name
 */
function getCurrentUserName() {
    initSession();
    return $_SESSION['full_name'] ?? null;
}

/**
 * Check if current user has specific role
 */
function hasRole($role) {
    return getCurrentUserRole() === $role;
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if current user is teacher
 */
function isTeacher() {
    return hasRole('teacher');
}

/**
 * Check if current user is student
 */
function isStudent() {
    return hasRole('student');
}

/**
 * Require login - redirects to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Require teacher role
 */
function requireTeacher() {
    requireRole('teacher');
}

/**
 * Require student role
 */
function requireStudent() {
    requireRole('student');
}

/**
 * Check if user can access resource
 */
function canAccess($allowedRoles) {
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    return in_array(getCurrentUserRole(), $allowedRoles);
}
