<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

initSession();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?= APP_NAME ?> - Student Management System">
    <meta name="theme-color" content="#0d6efd">
    <meta name="color-scheme" content="light dark">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= BASE_URL ?>assets/css/style.css" as="style">
    
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" integrity="sha384-4LISF5TTJX/fLmGSxO53rV4miRxdg84mF+5cokeSoJo36b6E22ZKSuHLzlqKyPU8" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <!-- Skip to main content link -->
    <style>
        .sr-only-focusable:focus {
            position: static;
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <a href="#main-content" class="sr-only sr-only-focusable btn btn-primary">Skip to main content</a>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" role="navigation" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>dashboard.php" aria-current="page">
                <i class="bi bi-mortarboard-fill me-2" aria-hidden="true"></i><span><?= APP_NAME ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" aria-hidden="true"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto" role="menubar">
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="<?= BASE_URL ?>dashboard.php" role="menuitem" aria-current="page">
                            <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <!-- Admin Menu -->
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="menuitem" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-people me-1" aria-hidden="true"></i>Users
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_users.php?role=student" role="menuitem">
                                <i class="bi bi-person me-2" aria-hidden="true"></i>Students
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_users.php?role=teacher" role="menuitem">
                                <i class="bi bi-person-badge me-2" aria-hidden="true"></i>Teachers
                            </a></li>
                            <li role="none"><hr class="dropdown-divider" role="separator"></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/teacher_workload.php" role="menuitem">
                                <i class="bi bi-list-check me-2" aria-hidden="true"></i>Teacher Workload
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/registrations.php" role="menuitem">
                                <i class="bi bi-person-plus me-2" aria-hidden="true"></i>Registration Requests
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="menuitem" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-book me-1" aria-hidden="true"></i>Academic
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_courses.php" role="menuitem">
                                <i class="bi bi-journal-bookmark me-2" aria-hidden="true"></i>Courses
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/manage_sections.php" role="menuitem">
                                <i class="bi bi-diagram-3 me-2" aria-hidden="true"></i>Sections
                            </a></li>
                            <li role="none"><hr class="dropdown-divider" role="separator"></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/admin/enrollments.php" role="menuitem">
                                <i class="bi bi-person-plus me-2" aria-hidden="true"></i>Enrollments
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/admin/announcements.php" role="menuitem">
                            <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Announcements
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isTeacher()): ?>
                    <!-- Teacher Menu -->
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/teacher/my_sections.php" role="menuitem">
                            <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>My Sections
                        </a>
                    </li>
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="menuitem" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-journal-text me-1" aria-hidden="true"></i>Teaching
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/attendance.php" role="menuitem">
                                <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Attendance
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/attendance_report.php" role="menuitem">
                                <i class="bi bi-graph-up me-2" aria-hidden="true"></i>Attendance Report
                            </a></li>
                            <li role="none"><hr class="dropdown-divider" role="separator"></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/manage_grades.php" role="menuitem">
                                <i class="bi bi-gear me-2" aria-hidden="true"></i>Configure Grades
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/enter_grades.php" role="menuitem">
                                <i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Enter Grades
                            </a></li>
                            <li role="none"><hr class="dropdown-divider" role="separator"></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/assignments.php" role="menuitem">
                                <i class="bi bi-file-earmark-text me-2" aria-hidden="true"></i>Assignments
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/teacher/resources.php" role="menuitem">
                                <i class="bi bi-folder me-2" aria-hidden="true"></i>Resources
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isStudent()): ?>
                    <!-- Student Menu -->
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/student/schedule.php" role="menuitem">
                            <i class="bi bi-calendar-week me-1" aria-hidden="true"></i>Schedule
                        </a>
                    </li>
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="menuitem" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-book me-1" aria-hidden="true"></i>Academic
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/my_courses.php" role="menuitem">
                                <i class="bi bi-journal-bookmark me-2" aria-hidden="true"></i>My Courses
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/grade_report.php" role="menuitem">
                                <i class="bi bi-graph-up me-2" aria-hidden="true"></i>My Grades
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/my_attendance.php" role="menuitem">
                                <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>My Attendance
                            </a></li>
                            <li role="none"><hr class="dropdown-divider" role="separator"></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/assignments.php" role="menuitem">
                                <i class="bi bi-file-earmark-text me-2" aria-hidden="true"></i>Assignments
                            </a></li>
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/student/resources.php" role="menuitem">
                                <i class="bi bi-folder me-2" aria-hidden="true"></i>Resources
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Messages for all users -->
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/common/messages.php" role="menuitem">
                            <i class="bi bi-envelope me-1" aria-hidden="true"></i>Messages
                            <?php 
                            $unreadCount = getUnreadMessageCount(getCurrentUserId());
                            if ($unreadCount > 0): 
                            ?>
                                <span class="badge bg-danger" aria-label="<?= $unreadCount ?> unread messages"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav" role="menubar">
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="menuitem" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i><span class="d-lg-inline d-none"><?= sanitize(getCurrentUserName()) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">
                            <li role="none"><a class="dropdown-item" href="<?= BASE_URL ?>modules/common/profile.php" role="menuitem">
                                <i class="bi bi-person me-2" aria-hidden="true"></i>My Profile
                            </a></li>
                            <li role="none"><hr class="dropdown-divider" role="separator"></li>
                            <li role="none"><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php" role="menuitem">
                                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main id="main-content" class="<?= isLoggedIn() ? 'py-4' : '' ?>" role="main">
        <div class="container-fluid">
            <?php displayFlash(); ?>
