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
function createAssessment($sectionId, $title, $type, $maxScore, $weight, $dueDate = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO assessments (section_id, title, type, max_score, weight, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sectionId, $title, $type, $maxScore, $weight, $dueDate]);
    return $db->lastInsertId();
}

/**
 * Get assessments by section
 */
function getAssessmentsBySection($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM assessments WHERE section_id = ? ORDER BY due_date DESC, created_at DESC");
    $stmt->execute([$sectionId]);
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
function updateAssessment($id, $title, $type, $maxScore, $weight, $dueDate) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE assessments SET title = ?, type = ?, max_score = ?, weight = ?, due_date = ? WHERE id = ?");
    return $stmt->execute([$title, $type, $maxScore, $weight, $dueDate, $id]);
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
function getStudentScoresBySection($studentId, $sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, ss.score, ss.remarks, ss.submitted_at 
                          FROM assessments a 
                          LEFT JOIN student_scores ss ON a.id = ss.assessment_id AND ss.student_id = ? 
                          WHERE a.section_id = ? 
                          ORDER BY a.due_date DESC");
    $stmt->execute([$studentId, $sectionId]);
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
 * Get unread message count for user
 */
function getUnreadMessageCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// ==================== GRADING OPERATIONS ====================

/**
 * Create grading period
 */
function createGradingPeriod($sectionId, $periodName, $startDate, $endDate, $status = 'active') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO grading_periods (section_id, period_name, start_date, end_date, status) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$sectionId, $periodName, $startDate, $endDate, $status]);
    return $db->lastInsertId();
}

/**
 * Get grading periods by section
 */
function getGradingPeriods($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM grading_periods WHERE section_id = ? ORDER BY period_name");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

/**
 * Create grade component (e.g., attendance, quizzes, tests)
 */
function createGradeComponent($sectionId, $componentName, $weight, $description = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO grade_components (section_id, component_name, weight, description) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([$sectionId, $componentName, $weight, $description]);
    return $db->lastInsertId();
}

/**
 * Get grade components for section
 */
function getGradeComponents($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM grade_components WHERE section_id = ? ORDER BY component_name");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

/**
 * Validate component weights sum to 100
 */
function validateComponentWeights($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COALESCE(SUM(weight), 0) as total FROM grade_components WHERE section_id = ?");
    $stmt->execute([$sectionId]);
    $result = $stmt->fetch();
    return $result['total'] == 100;
}

/**
 * Record grade for student
 */
function recordGrade($sectionId, $studentId, $periodId, $componentId, $score) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO student_grades (section_id, student_id, period_id, component_id, score) 
                          VALUES (?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE score = ?, updated_at = NOW()");
    $stmt->execute([$sectionId, $studentId, $periodId, $componentId, $score, $score]);
    return $db->lastInsertId();
}

/**
 * Get student grades for period
 */
function getStudentGradesByPeriod($studentId, $sectionId, $periodId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT sg.*, gc.component_name, gc.weight 
                          FROM student_grades sg
                          JOIN grade_components gc ON sg.component_id = gc.id
                          WHERE sg.student_id = ? AND sg.section_id = ? AND sg.period_id = ?
                          ORDER BY gc.component_name");
    $stmt->execute([$studentId, $sectionId, $periodId]);
    return $stmt->fetchAll();
}

/**
 * Calculate weighted quarter grade
 */
function calculateQuarterGrade($sectionId, $studentId, $periodId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(sg.score * gc.weight / 100) as weighted_score
                          FROM student_grades sg
                          JOIN grade_components gc ON sg.component_id = gc.id
                          WHERE sg.section_id = ? AND sg.student_id = ? AND sg.period_id = ?");
    $stmt->execute([$sectionId, $studentId, $periodId]);
    $result = $stmt->fetch();
    return round($result['weighted_score'] ?? 0, 2);
}

/**
 * Get all grades for a section period
 */
function getGradesForPeriod($sectionId, $periodId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT DISTINCT u.id, u.full_name
                          FROM enrollments e
                          JOIN users u ON e.student_id = u.id
                          WHERE e.section_id = ? AND e.status = 'enrolled'
                          ORDER BY u.full_name");
    $stmt->execute([$sectionId]);
    $students = $stmt->fetchAll();
    
    $grades = [];
    foreach ($students as $student) {
        $grades[$student['id']] = [
            'name' => $student['full_name'],
            'components' => getStudentGradesByPeriod($student['id'], $sectionId, $periodId),
            'quarter_grade' => calculateQuarterGrade($sectionId, $student['id'], $periodId)
        ];
    }
    return $grades;
}

// ==================== ENHANCED ATTENDANCE OPERATIONS ====================

/**
 * Mark attendance with undo history
 */
function markAttendanceWithUndo($sectionId, $studentId, $date, $status, $markedBy, $remarks = null) {
    $db = getDB();
    try {
        $db->beginTransaction();
        
        // Save previous status for undo
        $stmt = $db->prepare("SELECT status FROM attendance WHERE section_id = ? AND student_id = ? AND attendance_date = ?");
        $stmt->execute([$sectionId, $studentId, $date]);
        $previous = $stmt->fetch();
        $previousStatus = $previous ? $previous['status'] : null;
        
        // Mark attendance
        $stmt = $db->prepare("INSERT INTO attendance (section_id, student_id, attendance_date, status, marked_by, remarks) 
                              VALUES (?, ?, ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE status = ?, remarks = ?, marked_by = ?, updated_at = NOW()");
        $stmt->execute([$sectionId, $studentId, $date, $status, $markedBy, $remarks, $status, $remarks, $markedBy]);
        
        // Record in undo history
        $stmt = $db->prepare("INSERT INTO attendance_undo_history (section_id, student_id, attendance_date, previous_status, new_status, changed_by) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sectionId, $studentId, $date, $previousStatus, $status, $markedBy]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Undo last attendance change
 */
function undoLastAttendanceChange($sectionId, $studentId, $date, $changedBy) {
    $db = getDB();
    try {
        $db->beginTransaction();
        
        // Get last change
        $stmt = $db->prepare("SELECT * FROM attendance_undo_history 
                              WHERE section_id = ? AND student_id = ? AND attendance_date = ?
                              ORDER BY changed_at DESC LIMIT 1");
        $stmt->execute([$sectionId, $studentId, $date]);
        $lastChange = $stmt->fetch();
        
        if (!$lastChange) {
            throw new Exception("No undo history found");
        }
        
        // Revert to previous status
        if ($lastChange['previous_status']) {
            $stmt = $db->prepare("UPDATE attendance SET status = ? WHERE section_id = ? AND student_id = ? AND attendance_date = ?");
            $stmt->execute([$lastChange['previous_status'], $sectionId, $studentId, $date]);
        }
        
        // Record the undo action
        $stmt = $db->prepare("INSERT INTO attendance_undo_history (section_id, student_id, attendance_date, previous_status, new_status, changed_by) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sectionId, $studentId, $date, $lastChange['new_status'], $lastChange['previous_status'], $changedBy]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Get attendance summary with statistics
 */
function getAttendanceSummaryWithStats($sectionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.full_name,
                          SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                          SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                          SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                          SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                          COUNT(a.id) as total_classes,
                          ROUND(SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(a.id) * 100, 2) as attendance_rate
                          FROM enrollments e
                          JOIN users u ON e.student_id = u.id
                          LEFT JOIN attendance a ON a.student_id = u.id AND a.section_id = e.section_id
                          WHERE e.section_id = ? AND e.status = 'enrolled'
                          GROUP BY u.id, u.full_name
                          ORDER BY u.full_name");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
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
