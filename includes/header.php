<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

initSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>dashboard.php">
                <i class="bi bi-mortarboard-fill me-2"></i><?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <!-- Admin Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-people me-1"></i>Users
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_users.php?role=student">
                                <i class="bi bi-person me-2"></i>Students
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_users.php?role=teacher">
                                <i class="bi bi-person-badge me-2"></i>Teachers
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/teacher_workload.php">
                                <i class="bi bi-list-check me-2"></i>Teacher Workload
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/registrations.php">
                                <i class="bi bi-person-plus me-2"></i>Registration Requests
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-book me-1"></i>Academic
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_courses.php">
                                <i class="bi bi-journal-bookmark me-2"></i>Courses
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_sections.php">
                                <i class="bi bi-diagram-3 me-2"></i>Sections
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/enrollments.php">
                                <i class="bi bi-person-plus me-2"></i>Enrollments
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/admin/announcements.php">
                            <i class="bi bi-megaphone me-1"></i>Announcements
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isTeacher()): ?>
                    <!-- Teacher Menu -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/teacher/my_sections.php">
                            <i class="bi bi-diagram-3 me-1"></i>My Sections
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-journal-text me-1"></i>Teaching
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/attendance.php">
                                <i class="bi bi-calendar-check me-2"></i>Attendance
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/attendance_report.php">
                                <i class="bi bi-graph-up me-2"></i>Attendance Report
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/grades.php">
                                <i class="bi bi-card-checklist me-2"></i>Grades
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/assignments.php">
                                <i class="bi bi-file-earmark-text me-2"></i>Assignments
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/resources.php">
                                <i class="bi bi-folder me-2"></i>Resources
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isStudent()): ?>
                    <!-- Student Menu -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/student/schedule.php">
                            <i class="bi bi-calendar-week me-1"></i>Schedule
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-book me-1"></i>Academic
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/my_courses.php">
                                <i class="bi bi-journal-bookmark me-2"></i>My Courses
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/my_grades.php">
                                <i class="bi bi-card-checklist me-2"></i>My Grades
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/my_attendance.php">
                                <i class="bi bi-calendar-check me-2"></i>My Attendance
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/assignments.php">
                                <i class="bi bi-file-earmark-text me-2"></i>Assignments
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/resources.php">
                                <i class="bi bi-folder me-2"></i>Resources
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Messages for all users -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/common/messages.php">
                            <i class="bi bi-envelope me-1"></i>Messages
                            <?php 
                            $unreadCount = getUnreadMessageCount(getCurrentUserId());
                            if ($unreadCount > 0): 
                            ?>
                                <span class="badge bg-danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= sanitize(getCurrentUserName()) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/common/profile.php">
                                <i class="bi bi-person me-2"></i>My Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="<?= isLoggedIn() ? 'py-4' : '' ?>">
        <div class="container-fluid">
            <?php displayFlash(); ?>
