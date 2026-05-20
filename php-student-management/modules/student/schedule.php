<?php
/**
 * Student Schedule View
 * Students can view their class schedule/timetable
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireStudent();

$pageTitle = 'My Schedule';
$studentId = getCurrentUserId();

// Get student's enrolled sections
$sections = getStudentSections($studentId);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Schedule</h1>
            <p class="text-muted mb-0">View your class timetable</p>
        </div>
    </div>
    
    <?php displayFlash(); ?>
    
    <?php if (empty($sections)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>You are not enrolled in any courses yet.
        </div>
    <?php else: ?>
        <!-- Schedule Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Weekly Schedule</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Schedule</th>
                                <th>Room</th>
                                <th>Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sections as $section): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo sanitize($section['course_code']); ?></strong><br>
                                        <small class="text-muted"><?php echo sanitize($section['course_name']); ?></small>
                                    </td>
                                    <td><?php echo sanitize($section['section_name']); ?></td>
                                    <td>
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo sanitize($section['schedule_time'] ?? 'TBA'); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?php echo sanitize($section['room_number'] ?? 'TBA'); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo sanitize($section['teacher_name'] ?? 'TBA'); ?>
                                        <?php if (!empty($section['teacher_email'])): ?>
                                            <br><small><a href="mailto:<?php echo sanitize($section['teacher_email']); ?>"><?php echo sanitize($section['teacher_email']); ?></a></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Course Cards -->
        <h4 class="mb-3">Enrolled Courses</h4>
        <div class="row">
            <?php foreach ($sections as $section): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><?php echo sanitize($section['course_code']); ?></h5>
                            <small><?php echo sanitize($section['section_name']); ?></small>
                        </div>
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted"><?php echo sanitize($section['course_name']); ?></h6>
                            
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>
                                    <strong>Teacher:</strong> <?php echo sanitize($section['teacher_name'] ?? 'TBA'); ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-clock me-2 text-primary"></i>
                                    <strong>Schedule:</strong> <?php echo sanitize($section['schedule_time'] ?? 'TBA'); ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                                    <strong>Room:</strong> <?php echo sanitize($section['room_number'] ?? 'TBA'); ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-mortarboard me-2 text-primary"></i>
                                    <strong>Credits:</strong> <?php echo $section['credits']; ?>
                                </li>
                                <li>
                                    <i class="bi bi-calendar-check me-2 text-primary"></i>
                                    <strong>Enrolled:</strong> <?php echo formatDate($section['enrollment_date']); ?>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <a href="<?php echo BASE_URL; ?>modules/student/resources.php?section_id=<?php echo $section['section_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-folder2-open"></i> Resources
                                </a>
                                <a href="<?php echo BASE_URL; ?>modules/student/assignments.php?section_id=<?php echo $section['section_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-text"></i> Assignments
                                </a>
                                <a href="<?php echo BASE_URL; ?>modules/student/classmates.php?section_id=<?php echo $section['section_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-people"></i> Classmates
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
