<?php
/**
 * Application Configuration
 */

// Base URL - Update this according to your server
define('BASE_URL', 'http://localhost/php-student-management/');

// Application Settings
define('APP_NAME', 'Student Management System');
define('APP_VERSION', '1.0.0');

// File Upload Settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PROFILE_PIC_PATH', UPLOAD_PATH . 'profiles/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Session Settings
define('SESSION_TIMEOUT', 3600); // 1 hour

// Pagination
define('ITEMS_PER_PAGE', 10);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_DATETIME_FORMAT', 'M d, Y h:i A');

// ============================================================
// Grading System Settings
// ============================================================

// Default component weights (must total 100)
define('DEFAULT_WEIGHT_ATTENDANCE', 10);
define('DEFAULT_WEIGHT_MODULES', 20);
define('DEFAULT_WEIGHT_QUIZZES', 30);
define('DEFAULT_WEIGHT_TESTS', 40);

// Attendance point values used for the Attendance component.
// 'excused' is excluded from the denominator instead of being scored.
define('ATT_POINTS_PRESENT', 1.0);
define('ATT_POINTS_LATE', 0.5);
define('ATT_POINTS_ABSENT', 0.0);

// Human-readable labels for the grade components
define('GRADE_COMPONENTS', [
    'attendance' => 'Attendance',
    'modules'    => 'Modules',
    'quizzes'    => 'Quizzes',
    'tests'      => 'Tests',
]);

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(PROFILE_PIC_PATH)) {
    mkdir(PROFILE_PIC_PATH, 0755, true);
}
