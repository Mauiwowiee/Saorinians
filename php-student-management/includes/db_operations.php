<?php
/**
 * Database Operations Helper Functions
 * All database queries use PDO with prepared statements
 */

require_once __DIR__ . '/../config/database.php';

// ==================== USER OPERATIONS ====================

/**
 * Get user by ID
 */
function getUserById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, up.email, up.phone, up.address, up.profile_pic_path, up.date_of_birth 
                          FROM users u 
                          LEFT JOIN user_profiles up ON u.id = up.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get user by username
 */
function getUserByUsername($username) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Get all users by role
 */
function getUsersByRole($role) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, up.email, up.phone, up.profile_pic_path 
                          FROM users u 
                          LEFT JOIN user_profiles up ON u.id = up.user_id 
                          WHERE u.role = ? 
                          ORDER BY u.full_name");
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}

/**
 * Create new user
 */
function createUser($username, $password, $role, $fullName) {
    $db = getDB();
    try {
        $db->beginTransaction();
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $role, $fullName]);
        $userId = $db->lastInsertId();
        
        // Create user profile
        $stmt = $db->prepare("INSERT INTO user_profiles (user_id, email) VALUES (?, ?)");
        $stmt->execute([$userId, $username . '@school.edu']);
        
        $db->commit();
        return $userId;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Update user
 */
function updateUser($id, $fullName, $role = null) {
    $db = getDB();
    if ($role) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, role = ? WHERE id = ?");
        $stmt->execute([$fullName, $role, $id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->execute([$fullName, $id]);
    }
    return $stmt->rowCount();
}

/**
 * Update user profile
 */
function updateUserProfile($userId, $email, $phone, $address, $dateOfBirth = null) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE user_profiles SET email = ?, phone = ?, address = ?, date_of_birth = ? WHERE user_id = ?");
    return $stmt->execute([$email, $phone, $address, $dateOfBirth, $userId]);
}

/**
 * Update profile picture
 */
function updateProfilePicture($userId, $picPath) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE user_profiles SET profile_pic_path = ? WHERE user_id = ?");
    return $stmt->execute([$picPath, $userId]);
}

/**
 * Change user password
 */
function changePassword($userId, $newPassword) {
    $db = getDB();
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashedPassword, $userId]);
}

/**
 * Verify user password
 */
function verifyPassword($userId, $password) {
    $user = getUserById($userId);
    if ($user) {
        return password_verify($password, $user['password']);
    }
    return false;
}

/**
 * Delete user
 */
function deleteUser($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== COURSE OPERATIONS ====================

/**
 * Get all courses
 */
function getAllCourses() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM courses ORDER BY course_code");
    return $stmt->fetchAll();
}

/**
 * Get active courses
 */
function getActiveCourses() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM courses WHERE status = 'active' ORDER BY course_code");
    return $stmt->fetchAll();
}

/**
 * Get course by ID
 */
function getCourseById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Create course
 */
function createCourse($code, $name, $description, $credits) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO courses (course_code, course_name, description, credits) VALUES (?, ?, ?, ?)");
    $stmt->execute([$code, $name, $description, $credits]);
    return $db->lastInsertId();
}

/**
 * Update course
 */
function updateCourse($id, $code, $name, $description, $credits, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE courses SET course_code = ?, course_name = ?, description = ?, credits = ?, status = ? WHERE id = ?");
    return $stmt->execute([$code, $name, $description, $credits, $status, $id]);
}

/**
 * Delete course
 */
function deleteCourse($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== SECTION OPERATIONS ====================

/**
 * Get all sections with course and teacher info
 */
function getAllSections() {
    $db = getDB();
    $stmt = $db->query("SELECT s.*, c.course_code, c.course_name, u.full_name as teacher_name 
                        FROM sections s 
                        JOIN courses c ON s.course_id = c.id 
                        LEFT JOIN users u ON s.teacher_id = u.id 
                        ORDER BY c.course_code, s.section_name");
    return $stmt->fetchAll();
}

/**
 * Get section by ID
 */
function getSectionById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, c.course_code, c.course_name, c.credits, u.full_name as teacher_name, up.email as teacher_email 
                          FROM sections s 
                          JOIN courses c ON s.course_id = c.id 
                          LEFT JOIN users u ON s.teacher_id = u.id 
                          LEFT JOIN user_profiles up ON u.id = up.user_id
                          WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get sections by course
 */
function getSectionsByCourse($courseId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, u.full_name as teacher_name 
                          FROM sections s 
                          LEFT JOIN users u ON s.teacher_id = u.id 
                          WHERE s.course_id = ? 
                          ORDER BY s.section_name");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

/**
 * Get sections by teacher
 */
function getSectionsByTeacher($teacherId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, c.course_code, c.course_name, c.credits,
                          (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = s.id AND e.status = 'enrolled') as student_count
                          FROM sections s 
                          JOIN courses c ON s.course_id = c.id 
                          WHERE s.teacher_id = ? AND s.status = 'active'
                          ORDER BY c.course_code, s.section_name");
    $stmt->execute([$teacherId]);
    return $stmt->fetchAll();
}

/**
 * Create section
 */
function createSection($sectionName, $courseId, $teacherId, $roomNumber, $scheduleTime, $maxStudents) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO sections (section_name, course_id, teacher_id, room_number, schedule_time, max_students) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sectionName, $courseId, $teacherId ?: null, $roomNumber, $scheduleTime, $maxStudents]);
    return $db->lastInsertId();
}

/**
 * Update section
 */
function updateSection($id, $sectionName, $teacherId, $roomNumber, $scheduleTime, $maxStudents, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE sections SET section_name = ?, teacher_id = ?, room_number = ?, schedule_time = ?, max_students = ?, status = ? WHERE id = ?");
    return $stmt->execute([$sectionName, $teacherId ?: null, $roomNumber, $scheduleTime, $maxStudents, $status, $id]);
}

/**
 * Assign teacher to section
 */
function assignTeacherToSection($sectionId, $teacherId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE sections SET teacher_id = ? WHERE id = ?");
    return $stmt->execute([$teacherId ?: null, $sectionId]);
}

/**
 * Delete section
 */
function deleteSection($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM sections WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get teacher workload (number of sections)
 */
function getTeacherWorkload() {
    $db = getDB();
    $stmt = $db->query("SELECT u.id, u.full_name, up.email,
                        (SELECT COUNT(*) FROM sections s WHERE s.teacher_id = u.id AND s.status = 'active') as section_count
                        FROM users u
                        LEFT JOIN user_profiles up ON u.id = up.user_id
                        WHERE u.role = 'teacher'
                        ORDER BY u.full_name");
    return $stmt->fetchAll();
}

// ==================== ENROLLMENT OPERATIONS ====================

/**
 * Get students by section
 */
function getStudentsBySection($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.username, u.full_name, up.email, up.phone, up.profile_pic_path, e.enrollment_date, e.status, e.grade
                          FROM enrollments e
                          JOIN users u ON e.student_id = u.id
                          LEFT JOIN user_profiles up ON u.id = up.user_id
                          WHERE e.section_id = ? AND e.status = 'enrolled'
                          ORDER BY u.full_name");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

/**
 * Get student's enrolled sections
 */
function getStudentSections($studentId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT e.*, s.section_name, s.room_number, s.schedule_time,
                          c.course_code, c.course_name, c.credits,
                          u.full_name as teacher_name, up.email as teacher_email
                          FROM enrollments e
                          JOIN sections s ON e.section_id = s.id
                          JOIN courses c ON s.course_id = c.id
                          LEFT JOIN users u ON s.teacher_id = u.id
                          LEFT JOIN user_profiles up ON u.id = up.user_id
                          WHERE e.student_id = ? AND e.status = 'enrolled'
                          ORDER BY c.course_code");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

/**
 * Get students not in section
 */
function getStudentsNotInSection($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.username, u.full_name 
                          FROM users u 
                          WHERE u.role = 'student' 
                          AND u.id NOT IN (SELECT student_id FROM enrollments WHERE section_id = ?)
                          ORDER BY u.full_name");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

/**
 * Enroll student in section
 */
function enrollStudent($studentId, $sectionId) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO enrollments (student_id, section_id) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE status = 'enrolled'");
    return $stmt->execute([$studentId, $sectionId]);
}

/**
 * Batch enroll students
 */
function batchEnrollStudents($studentIds, $sectionId) {
    $db = getDB();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO enrollments (student_id, section_id) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE status = 'enrolled'");
        foreach ($studentIds as $studentId) {
            $stmt->execute([$studentId, $sectionId]);
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Drop student from section
 */
function dropStudent($studentId, $sectionId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE enrollments SET status = 'dropped' WHERE student_id = ? AND section_id = ?");
    return $stmt->execute([$studentId, $sectionId]);
}

/**
 * Update student grade
 */
function updateStudentGrade($studentId, $sectionId, $grade) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE enrollments SET grade = ? WHERE student_id = ? AND section_id = ?");
    return $stmt->execute([$grade, $studentId, $sectionId]);
}

// ==================== ATTENDANCE OPERATIONS ====================

/**
 * Get attendance for section on date
 */
function getAttendanceByDate($sectionId, $date) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, u.full_name as student_name 
                          FROM attendance a
                          JOIN users u ON a.student_id = u.id
                          WHERE a.section_id = ? AND a.attendance_date = ?
                          ORDER BY u.full_name");
    $stmt->execute([$sectionId, $date]);
    return $stmt->fetchAll();
}

/**
 * Get student attendance history
 */
function getStudentAttendance($studentId, $sectionId = null) {
    $db = getDB();
    if ($sectionId) {
        $stmt = $db->prepare("SELECT a.*, s.section_name, c.course_code, c.course_name
                              FROM attendance a
                              JOIN sections s ON a.section_id = s.id
                              JOIN courses c ON s.course_id = c.id
                              WHERE a.student_id = ? AND a.section_id = ?
                              ORDER BY a.attendance_date DESC");
        $stmt->execute([$studentId, $sectionId]);
    } else {
        $stmt = $db->prepare("SELECT a.*, s.section_name, c.course_code, c.course_name
                              FROM attendance a
                              JOIN sections s ON a.section_id = s.id
                              JOIN courses c ON s.course_id = c.id
                              WHERE a.student_id = ?
                              ORDER BY a.attendance_date DESC");
        $stmt->execute([$studentId]);
    }
    return $stmt->fetchAll();
}

/**
 * Mark attendance
 */
function markAttendance($sectionId, $studentId, $date, $status, $markedBy, $remarks = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO attendance (section_id, student_id, attendance_date, status, marked_by, remarks) 
                          VALUES (?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE status = ?, remarks = ?");
    return $stmt->execute([$sectionId, $studentId, $date, $status, $markedBy, $remarks, $status, $remarks]);
}

/**
 * Batch mark attendance
 */
function batchMarkAttendance($sectionId, $date, $attendanceData, $markedBy) {
    $db = getDB();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO attendance (section_id, student_id, attendance_date, status, marked_by, remarks) 
                              VALUES (?, ?, ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)");
        foreach ($attendanceData as $studentId => $data) {
            $stmt->execute([$sectionId, $studentId, $date, $data['status'], $markedBy, $data['remarks'] ?? null]);
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Get attendance summary for section
 */
function getAttendanceSummary($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.full_name,
                          SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                          SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                          SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                          SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                          COUNT(a.id) as total_classes
                          FROM enrollments e
                          JOIN users u ON e.student_id = u.id
                          LEFT JOIN attendance a ON a.student_id = u.id AND a.section_id = e.section_id
                          WHERE e.section_id = ? AND e.status = 'enrolled'
                          GROUP BY u.id, u.full_name
                          ORDER BY u.full_name");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

// ==================== ANNOUNCEMENT OPERATIONS ====================

/**
 * Get announcements
 */
function getAnnouncements($role = 'all', $sectionId = null, $limit = 10) {
    $db = getDB();
    $sql = "SELECT a.*, u.full_name as author_name, s.section_name, c.course_code
            FROM announcements a
            LEFT JOIN users u ON a.created_by = u.id
            LEFT JOIN sections s ON a.section_id = s.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE a.is_active = 1 AND (a.target_role = 'all' OR a.target_role = ?)";
    
    if ($sectionId) {
        $sql .= " AND (a.section_id IS NULL OR a.section_id = ?)";
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT ?";
    
    $stmt = $db->prepare($sql);
    if ($sectionId) {
        $stmt->execute([$role, $sectionId, $limit]);
    } else {
        $stmt->execute([$role, $limit]);
    }
    return $stmt->fetchAll();
}

/**
 * Get announcement by ID
 */
function getAnnouncementById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, u.full_name as author_name 
                          FROM announcements a
                          LEFT JOIN users u ON a.created_by = u.id
                          WHERE a.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Create announcement
 */
function createAnnouncement($title, $content, $targetRole, $sectionId, $createdBy) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO announcements (title, content, target_role, section_id, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $targetRole, $sectionId ?: null, $createdBy]);
    return $db->lastInsertId();
}

/**
 * Update announcement
 */
function updateAnnouncement($id, $title, $content, $targetRole, $sectionId, $isActive) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE announcements SET title = ?, content = ?, target_role = ?, section_id = ?, is_active = ? WHERE id = ?");
    return $stmt->execute([$title, $content, $targetRole, $sectionId ?: null, $isActive, $id]);
}

/**
 * Delete announcement
 */
function deleteAnnouncement($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== DASHBOARD STATISTICS ====================

/**
 * Get admin dashboard stats
 */
function getAdminStats() {
    $db = getDB();
    return [
        'total_students' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
        'total_teachers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn(),
        'total_courses' => $db->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn(),
        'total_sections' => $db->query("SELECT COUNT(*) FROM sections WHERE status = 'active'")->fetchColumn(),
        'total_enrollments' => $db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'enrolled'")->fetchColumn(),
    ];
}

/**
 * Get teacher dashboard stats
 */
function getTeacherStats($teacherId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM sections WHERE teacher_id = ? AND status = 'active'");
    $stmt->execute([$teacherId]);
    $sectionCount = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(DISTINCT e.student_id) 
                          FROM enrollments e 
                          JOIN sections s ON e.section_id = s.id 
                          WHERE s.teacher_id = ? AND e.status = 'enrolled'");
    $stmt->execute([$teacherId]);
    $studentCount = $stmt->fetchColumn();
    
    return [
        'sections' => $sectionCount,
        'students' => $studentCount,
    ];
}

/**
 * Get student dashboard stats
 */
function getStudentStats($studentId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'enrolled'");
    $stmt->execute([$studentId]);
    
    return [
        'enrolled_courses' => $stmt->fetchColumn(),
    ];
}

// ==================== REGISTRATION OPERATIONS ====================

/**
 * Get registration request by username
 */
function getRegistrationByUsername($username) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM registration_requests WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Create registration request
 */
function createRegistrationRequest($username, $password, $role, $fullName, $email, $phone = null) {
    $db = getDB();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO registration_requests (username, password, role, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $role, $fullName, $email, $phone]);
    return $db->lastInsertId();
}

/**
 * Get all pending registration requests
 */
function getPendingRegistrations() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM registration_requests WHERE status = 'pending' ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Get all registration requests
 */
function getAllRegistrations() {
    $db = getDB();
    $stmt = $db->query("SELECT r.*, u.full_name as processed_by_name 
                        FROM registration_requests r 
                        LEFT JOIN users u ON r.processed_by = u.id 
                        ORDER BY r.created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Approve registration request
 */
function approveRegistration($requestId, $adminId, $notes = null) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // Get registration request
        $stmt = $db->prepare("SELECT * FROM registration_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Registration request not found or already processed');
        }
        
        // Create user
        $stmt = $db->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$request['username'], $request['password'], $request['role'], $request['full_name']]);
        $userId = $db->lastInsertId();
        
        // Create user profile
        $stmt = $db->prepare("INSERT INTO user_profiles (user_id, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $request['email'], $request['phone']]);
        
        // Update registration request
        $stmt = $db->prepare("UPDATE registration_requests SET status = 'approved', processed_by = ?, processed_at = NOW(), admin_notes = ? WHERE id = ?");
        $stmt->execute([$adminId, $notes, $requestId]);
        
        $db->commit();
        return $userId;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Reject registration request
 */
function rejectRegistration($requestId, $adminId, $notes = null) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE registration_requests SET status = 'rejected', processed_by = ?, processed_at = NOW(), admin_notes = ? WHERE id = ?");
    return $stmt->execute([$adminId, $notes, $requestId]);
}

// ==================== ASSESSMENT OPERATIONS ====================

/**
 * Create assessment
 */
function createAssessment($sectionId, $title, $type, $maxScore, $weight, $dueDate = null, $component = null, $gradingPeriodId = null) {
    $db = getDB();
    // Derive the component from the legacy type when not supplied
    if ($component === null) {
        $component = mapTypeToComponent($type);
    }
    $stmt = $db->prepare("INSERT INTO assessments (section_id, title, type, component, grading_period_id, max_score, weight, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sectionId, $title, $type, $component, $gradingPeriodId ?: null, $maxScore, $weight, $dueDate]);
    return $db->lastInsertId();
}

/**
 * Map a legacy assessment type to a grade component
 */
function mapTypeToComponent($type) {
    switch ($type) {
        case 'exam':
            return 'tests';
        case 'assignment':
        case 'project':
            return 'modules';
        case 'quiz':
        default:
            return 'quizzes';
    }
}

/**
 * Get assessments by section, optionally filtered by grading period
 */
function getAssessmentsBySection($sectionId, $gradingPeriodId = null) {
    $db = getDB();
    if ($gradingPeriodId) {
        $stmt = $db->prepare("SELECT * FROM assessments WHERE section_id = ? AND grading_period_id = ? ORDER BY due_date DESC, created_at DESC");
        $stmt->execute([$sectionId, $gradingPeriodId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM assessments WHERE section_id = ? ORDER BY due_date DESC, created_at DESC");
        $stmt->execute([$sectionId]);
    }
    return $stmt->fetchAll();
}

/**
 * Get assessment by ID
 */
function getAssessmentById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, s.section_name, c.course_code, c.course_name 
                          FROM assessments a 
                          JOIN sections s ON a.section_id = s.id 
                          JOIN courses c ON s.course_id = c.id 
                          WHERE a.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Update assessment
 */
function updateAssessment($id, $title, $type, $maxScore, $weight, $dueDate, $component = null, $gradingPeriodId = null) {
    $db = getDB();
    if ($component === null) {
        $component = mapTypeToComponent($type);
    }
    $stmt = $db->prepare("UPDATE assessments SET title = ?, type = ?, component = ?, grading_period_id = ?, max_score = ?, weight = ?, due_date = ? WHERE id = ?");
    return $stmt->execute([$title, $type, $component, $gradingPeriodId ?: null, $maxScore, $weight, $dueDate, $id]);
}

/**
 * Delete assessment
 */
function deleteAssessment($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM assessments WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Record student score
 */
function recordStudentScore($assessmentId, $studentId, $score, $remarks = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO student_scores (assessment_id, student_id, score, remarks) VALUES (?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE score = ?, remarks = ?");
    return $stmt->execute([$assessmentId, $studentId, $score, $remarks, $score, $remarks]);
}

/**
 * Get student scores for assessment
 */
function getScoresForAssessment($assessmentId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT ss.*, u.full_name as student_name 
                          FROM student_scores ss 
                          JOIN users u ON ss.student_id = u.id 
                          WHERE ss.assessment_id = ? 
                          ORDER BY u.full_name");
    $stmt->execute([$assessmentId]);
    return $stmt->fetchAll();
}

/**
 * Get student's scores for a section
 */
function getStudentScoresBySection($studentId, $sectionId, $gradingPeriodId = null) {
    $db = getDB();
    if ($gradingPeriodId) {
        $stmt = $db->prepare("SELECT a.*, ss.score, ss.remarks, ss.submitted_at 
                              FROM assessments a 
                              LEFT JOIN student_scores ss ON a.id = ss.assessment_id AND ss.student_id = ? 
                              WHERE a.section_id = ? AND a.grading_period_id = ? 
                              ORDER BY a.component, a.due_date DESC");
        $stmt->execute([$studentId, $sectionId, $gradingPeriodId]);
    } else {
        $stmt = $db->prepare("SELECT a.*, ss.score, ss.remarks, ss.submitted_at 
                              FROM assessments a 
                              LEFT JOIN student_scores ss ON a.id = ss.assessment_id AND ss.student_id = ? 
                              WHERE a.section_id = ? 
                              ORDER BY a.due_date DESC");
        $stmt->execute([$studentId, $sectionId]);
    }
    return $stmt->fetchAll();
}

/**
 * Calculate student's grade for section
 */
function calculateStudentGrade($studentId, $sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT 
                            COALESCE(SUM(ss.score * a.weight), 0) as weighted_score,
                            COALESCE(SUM(a.max_score * a.weight), 0) as weighted_max
                          FROM assessments a
                          LEFT JOIN student_scores ss ON a.id = ss.assessment_id AND ss.student_id = ?
                          WHERE a.section_id = ?");
    $stmt->execute([$studentId, $sectionId]);
    $result = $stmt->fetch();
    
    if ($result['weighted_max'] > 0) {
        return round(($result['weighted_score'] / $result['weighted_max']) * 100, 2);
    }
    return null;
}

// ==================== GRADING PERIOD & WEIGHTED GRADE OPERATIONS ====================

/**
 * Get all grading periods, optionally for one school year
 */
function getGradingPeriods($schoolYear = null) {
    $db = getDB();
    if ($schoolYear) {
        $stmt = $db->prepare("SELECT * FROM grading_periods WHERE school_year = ? ORDER BY period_number");
        $stmt->execute([$schoolYear]);
    } else {
        $stmt = $db->query("SELECT * FROM grading_periods ORDER BY school_year DESC, period_number");
    }
    return $stmt->fetchAll();
}

/**
 * Get the distinct school years that have grading periods
 */
function getSchoolYears() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT school_year FROM grading_periods ORDER BY school_year DESC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get a single grading period
 */
function getGradingPeriodById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM grading_periods WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get the period flagged active by the admin
 */
function getActiveGradingPeriod() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM grading_periods WHERE is_active = 1 ORDER BY school_year DESC, period_number LIMIT 1");
    return $stmt->fetch();
}

/**
 * Get the period whose date range contains today.
 * Falls back to the admin-flagged active period, then the most recent one.
 */
function getCurrentGradingPeriod() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM grading_periods WHERE CURDATE() BETWEEN start_date AND end_date ORDER BY school_year DESC, period_number LIMIT 1");
    $period = $stmt->fetch();
    if ($period) {
        return $period;
    }
    $active = getActiveGradingPeriod();
    if ($active) {
        return $active;
    }
    $stmt = $db->query("SELECT * FROM grading_periods ORDER BY school_year DESC, period_number LIMIT 1");
    return $stmt->fetch();
}

/**
 * Find which grading period a given date falls into
 */
function getGradingPeriodForDate($date) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM grading_periods WHERE ? BETWEEN start_date AND end_date ORDER BY school_year DESC, period_number LIMIT 1");
    $stmt->execute([$date]);
    return $stmt->fetch();
}

/**
 * Create or update a grading period (keyed on school_year + period_number)
 */
function saveGradingPeriod($schoolYear, $periodNumber, $name, $startDate, $endDate) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO grading_periods (school_year, period_number, name, start_date, end_date)
                          VALUES (?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE name = ?, start_date = ?, end_date = ?");
    return $stmt->execute([
        $schoolYear, $periodNumber, $name, $startDate, $endDate,
        $name, $startDate, $endDate
    ]);
}

/**
 * Delete a grading period
 */
function deleteGradingPeriod($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM grading_periods WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Mark one period active (clears the flag on all others)
 */
function setActiveGradingPeriod($id) {
    $db = getDB();
    $db->beginTransaction();
    try {
        $db->exec("UPDATE grading_periods SET is_active = 0");
        $stmt = $db->prepare("UPDATE grading_periods SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

/**
 * Get a section's component weights, falling back to the config defaults
 */
function getSectionWeights($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT w_attendance, w_modules, w_quizzes, w_tests FROM section_grade_weights WHERE section_id = ?");
    $stmt->execute([$sectionId]);
    $row = $stmt->fetch();

    if ($row) {
        return [
            'attendance' => (float) $row['w_attendance'],
            'modules'    => (float) $row['w_modules'],
            'quizzes'    => (float) $row['w_quizzes'],
            'tests'      => (float) $row['w_tests'],
        ];
    }

    return [
        'attendance' => (float) DEFAULT_WEIGHT_ATTENDANCE,
        'modules'    => (float) DEFAULT_WEIGHT_MODULES,
        'quizzes'    => (float) DEFAULT_WEIGHT_QUIZZES,
        'tests'      => (float) DEFAULT_WEIGHT_TESTS,
    ];
}

/**
 * Save a section's component weights. Expects keys:
 * attendance, modules, quizzes, tests
 */
function saveSectionWeights($sectionId, $weights) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO section_grade_weights (section_id, w_attendance, w_modules, w_quizzes, w_tests)
                          VALUES (?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE w_attendance = ?, w_modules = ?, w_quizzes = ?, w_tests = ?");
    return $stmt->execute([
        $sectionId,
        $weights['attendance'], $weights['modules'], $weights['quizzes'], $weights['tests'],
        $weights['attendance'], $weights['modules'], $weights['quizzes'], $weights['tests']
    ]);
}

/**
 * Attendance-based component score for one student in one grading period.
 *
 * present = 1.0, late = 0.5, absent = 0.0, excused is excluded entirely.
 * Returns null when no attendance sessions were held (so the component
 * can be dropped from the weighting instead of scoring zero).
 */
function getAttendanceComponentScore($studentId, $sectionId, $periodId) {
    $period = getGradingPeriodById($periodId);
    if (!$period) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT status, COUNT(*) as total
                          FROM attendance
                          WHERE student_id = ? AND section_id = ?
                            AND attendance_date BETWEEN ? AND ?
                          GROUP BY status");
    $stmt->execute([$studentId, $sectionId, $period['start_date'], $period['end_date']]);

    $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['total'];
    }

    // Excused sessions do not count for or against the student
    $graded = $counts['present'] + $counts['absent'] + $counts['late'];
    if ($graded === 0) {
        return null;
    }

    $earned = ($counts['present'] * ATT_POINTS_PRESENT)
            + ($counts['late'] * ATT_POINTS_LATE)
            + ($counts['absent'] * ATT_POINTS_ABSENT);

    return [
        'percentage' => round(($earned / $graded) * 100, 2),
        'counts'     => $counts,
        'sessions'   => $graded,
    ];
}

/**
 * Score for one assessment component (modules/quizzes/tests) in a period.
 * Only assessments the student actually has a score for are counted.
 * Returns null when there is nothing to grade yet.
 */
function getComponentScore($studentId, $sectionId, $periodId, $component) {
    $db = getDB();
    $stmt = $db->prepare("SELECT
                            COALESCE(SUM(ss.score), 0) as earned,
                            COALESCE(SUM(a.max_score), 0) as possible,
                            COUNT(ss.id) as scored_count,
                            (SELECT COUNT(*) FROM assessments a2
                               WHERE a2.section_id = a.section_id
                                 AND a2.grading_period_id = ?
                                 AND a2.component = ?) as total_count
                          FROM assessments a
                          INNER JOIN student_scores ss
                              ON a.id = ss.assessment_id
                             AND ss.student_id = ?
                             AND ss.score IS NOT NULL
                          WHERE a.section_id = ?
                            AND a.grading_period_id = ?
                            AND a.component = ?");
    $stmt->execute([$periodId, $component, $studentId, $sectionId, $periodId, $component]);
    $row = $stmt->fetch();

    if (!$row || $row['possible'] <= 0) {
        return null;
    }

    return [
        'percentage'   => round(($row['earned'] / $row['possible']) * 100, 2),
        'earned'       => (float) $row['earned'],
        'possible'     => (float) $row['possible'],
        'scored_count' => (int) $row['scored_count'],
        'total_count'  => (int) $row['total_count'],
    ];
}

/**
 * Calculate a student's weighted grade for one grading period.
 *
 * Final = (Attendance x Wa) + (Modules x Wm) + (Quizzes x Wq) + (Tests x Wt)
 *
 * Components with no data are dropped and the remaining weights are
 * re-normalized to 100% so an early-quarter grade is not unfairly low.
 * The return array flags this via 'normalized'.
 */
function calculateQuarterGrade($studentId, $sectionId, $periodId) {
    $weights = getSectionWeights($sectionId);

    $attendance = getAttendanceComponentScore($studentId, $sectionId, $periodId);
    $components = [
        'attendance' => [
            'percentage' => $attendance ? $attendance['percentage'] : null,
            'weight'     => $weights['attendance'],
            'detail'     => $attendance,
        ],
    ];

    foreach (['modules', 'quizzes', 'tests'] as $key) {
        $score = getComponentScore($studentId, $sectionId, $periodId, $key);
        $components[$key] = [
            'percentage' => $score ? $score['percentage'] : null,
            'weight'     => $weights[$key],
            'detail'     => $score,
        ];
    }

    // Total weight of the components that actually have data
    $activeWeight = 0.0;
    foreach ($components as $c) {
        if ($c['percentage'] !== null) {
            $activeWeight += $c['weight'];
        }
    }

    if ($activeWeight <= 0) {
        return [
            'components'    => $components,
            'final'         => null,
            'letter'        => null,
            'normalized'    => false,
            'active_weight' => 0.0,
            'weights'       => $weights,
        ];
    }

    $final = 0.0;
    foreach ($components as $key => $c) {
        if ($c['percentage'] === null) {
            $components[$key]['effective_weight'] = 0.0;
            $components[$key]['contribution'] = null;
            continue;
        }
        // Re-normalize so the active weights total 100
        $effective = ($c['weight'] / $activeWeight) * 100;
        $contribution = $c['percentage'] * ($effective / 100);
        $components[$key]['effective_weight'] = round($effective, 2);
        $components[$key]['contribution'] = round($contribution, 2);
        $final += $contribution;
    }

    $final = round($final, 2);

    return [
        'components'    => $components,
        'final'         => $final,
        'letter'        => getGradeLetter($final),
        'normalized'    => abs($activeWeight - 100) > 0.01,
        'active_weight' => $activeWeight,
        'weights'       => $weights,
    ];
}

/**
 * Average of every quarter that has data, for one student in one section
 */
function calculateSectionFinalGrade($studentId, $sectionId, $schoolYear = null) {
    $periods = getGradingPeriods($schoolYear);
    $totals = [];

    foreach ($periods as $period) {
        $result = calculateQuarterGrade($studentId, $sectionId, $period['id']);
        if ($result['final'] !== null) {
            $totals[$period['period_number']] = $result['final'];
        }
    }

    if (empty($totals)) {
        return null;
    }

    return [
        'quarters' => $totals,
        'final'    => round(array_sum($totals) / count($totals), 2),
    ];
}

/**
 * Quarter grades for every student in a section - drives the gradebook table
 */
function getSectionQuarterGrades($sectionId, $periodId) {
    $students = getStudentsBySection($sectionId);
    $rows = [];

    foreach ($students as $student) {
        $rows[] = [
            'student_id'   => $student['id'],
            'student_name' => $student['full_name'],
            'username'     => $student['username'],
            'grade'        => calculateQuarterGrade($student['id'], $sectionId, $periodId),
        ];
    }

    return $rows;
}

// ==================== ASSIGNMENT OPERATIONS ====================

/**
 * Create assignment
 */
function createAssignment($sectionId, $title, $description, $dueDate, $maxPoints, $createdBy) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO assignments (section_id, title, description, due_date, max_points, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sectionId, $title, $description, $dueDate, $maxPoints, $createdBy]);
    return $db->lastInsertId();
}

/**
 * Get assignments by section
 */
function getAssignmentsBySection($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, u.full_name as created_by_name,
                          (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
                          FROM assignments a 
                          LEFT JOIN users u ON a.created_by = u.id 
                          WHERE a.section_id = ? 
                          ORDER BY a.due_date DESC");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

/**
 * Get assignment by ID
 */
function getAssignmentById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, s.section_name, c.course_code, c.course_name, u.full_name as created_by_name
                          FROM assignments a 
                          JOIN sections s ON a.section_id = s.id 
                          JOIN courses c ON s.course_id = c.id 
                          LEFT JOIN users u ON a.created_by = u.id 
                          WHERE a.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Update assignment
 */
function updateAssignment($id, $title, $description, $dueDate, $maxPoints) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, max_points = ? WHERE id = ?");
    return $stmt->execute([$title, $description, $dueDate, $maxPoints, $id]);
}

/**
 * Delete assignment
 */
function deleteAssignment($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Submit assignment
 */
function submitAssignment($assignmentId, $studentId, $submissionText, $filePath = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_path) 
                          VALUES (?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE submission_text = ?, file_path = ?, submitted_at = NOW()");
    return $stmt->execute([$assignmentId, $studentId, $submissionText, $filePath, $submissionText, $filePath]);
}

/**
 * Get student submission
 */
function getStudentSubmission($assignmentId, $studentId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $stmt->execute([$assignmentId, $studentId]);
    return $stmt->fetch();
}

/**
 * Get all submissions for assignment
 */
function getAssignmentSubmissions($assignmentId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT asub.*, u.full_name as student_name, up.email as student_email
                          FROM assignment_submissions asub
                          JOIN users u ON asub.student_id = u.id
                          LEFT JOIN user_profiles up ON u.id = up.user_id
                          WHERE asub.assignment_id = ?
                          ORDER BY asub.submitted_at DESC");
    $stmt->execute([$assignmentId]);
    return $stmt->fetchAll();
}

/**
 * Grade submission
 */
function gradeSubmission($submissionId, $grade, $feedback, $gradedBy) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE assignment_submissions SET grade = ?, feedback = ?, graded_by = ?, graded_at = NOW() WHERE id = ?");
    return $stmt->execute([$grade, $feedback, $gradedBy, $submissionId]);
}

/**
 * Get student assignments for section
 */
function getStudentAssignments($studentId, $sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, asub.submission_text, asub.file_path, asub.submitted_at, asub.grade, asub.feedback
                          FROM assignments a
                          LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                          WHERE a.section_id = ?
                          ORDER BY a.due_date DESC");
    $stmt->execute([$studentId, $sectionId]);
    return $stmt->fetchAll();
}

// ==================== MESSAGE OPERATIONS ====================

/**
 * Send message
 */
function sendMessage($senderId, $receiverId, $subject, $content) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$senderId, $receiverId, $subject, $content]);
    return $db->lastInsertId();
}

/**
 * Get received messages
 */
function getReceivedMessages($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT m.*, u.full_name as sender_name 
                          FROM messages m 
                          JOIN users u ON m.sender_id = u.id 
                          WHERE m.receiver_id = ? 
                          ORDER BY m.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get sent messages
 */
function getSentMessages($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT m.*, u.full_name as receiver_name 
                          FROM messages m 
                          JOIN users u ON m.receiver_id = u.id 
                          WHERE m.sender_id = ? 
                          ORDER BY m.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get message by ID
 */
function getMessageById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT m.*, s.full_name as sender_name, r.full_name as receiver_name 
                          FROM messages m 
                          JOIN users s ON m.sender_id = s.id 
                          JOIN users r ON m.receiver_id = r.id 
                          WHERE m.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Mark message as read
 */
function markMessageAsRead($id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get unread message count
 */
function getUnreadMessageCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

/**
 * Delete message
 */
function deleteMessage($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== RESOURCE OPERATIONS ====================

/**
 * Upload resource
 */
function uploadResource($sectionId, $title, $description, $filePath, $fileType, $uploadedBy) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO resources (section_id, title, description, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sectionId, $title, $description, $filePath, $fileType, $uploadedBy]);
    return $db->lastInsertId();
}

/**
 * Get resources by section
 */
function getResourcesBySection($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT r.*, u.full_name as uploaded_by_name 
                          FROM resources r 
                          LEFT JOIN users u ON r.uploaded_by = u.id 
                          WHERE r.section_id = ? 
                          ORDER BY r.created_at DESC");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

/**
 * Delete resource
 */
function deleteResource($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get all students for messaging
 */
function getAllStudents() {
    return getUsersByRole('student');
}

/**
 * Get all teachers for messaging
 */
function getAllTeachers() {
    return getUsersByRole('teacher');
}
