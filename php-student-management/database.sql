-- Student Management System Database Schema
-- Run this file in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS student_management;
USE student_management;

-- Users table (for all roles: admin, teacher, student)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User profiles (extended user information)
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    profile_pic_path VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    credits INT DEFAULT 3,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sections table (links courses to teachers)
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    teacher_id INT,
    room_number VARCHAR(20),
    schedule_time VARCHAR(100),
    max_students INT DEFAULT 40,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_section_course (section_name, course_id)
);

-- Enrollments table (links students to sections)
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    section_id INT NOT NULL,
    enrollment_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
    grade VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, section_id)
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    remarks TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (section_id, student_id, attendance_date)
);

-- Announcements table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_role ENUM('all', 'admin', 'teacher', 'student') DEFAULT 'all',
    section_id INT,
    created_by INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Grading periods table (quarters of a school year, admin-managed)
CREATE TABLE IF NOT EXISTS grading_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(20) NOT NULL,
    period_number TINYINT NOT NULL,
    name VARCHAR(30) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_period (school_year, period_number)
);

-- Per-section grade component weights (teacher editable)
CREATE TABLE IF NOT EXISTS section_grade_weights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL UNIQUE,
    w_attendance DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    w_modules DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    w_quizzes DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    w_tests DECIMAL(5,2) NOT NULL DEFAULT 40.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
);

-- Grades/Assessments table
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    type ENUM('quiz', 'exam', 'assignment', 'project') NOT NULL,
    component ENUM('modules', 'quizzes', 'tests') NOT NULL DEFAULT 'quizzes',
    grading_period_id INT NULL,
    max_score DECIMAL(5,2) NOT NULL,
    weight DECIMAL(5,2) DEFAULT 1.00,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (grading_period_id) REFERENCES grading_periods(id) ON DELETE SET NULL,
    INDEX idx_assessment_grading (section_id, grading_period_id, component)
);

-- Student scores table
CREATE TABLE IF NOT EXISTS student_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    score DECIMAL(5,2),
    remarks TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_score (assessment_id, student_id)
);

-- Registration requests table (for pending user registrations)
CREATE TABLE IF NOT EXISTS registration_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    processed_by INT,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Messages table (for internal messaging)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Schedule/Timetable table
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
);

-- Resources/Materials table
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Assignments table (for student submissions)
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    max_points DECIMAL(5,2) DEFAULT 100,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Assignment submissions table
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    file_path VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2),
    feedback TEXT,
    graded_by INT,
    graded_at TIMESTAMP NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_submission (assignment_id, student_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role, full_name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator');

-- Insert sample teachers (password: password123)
INSERT INTO users (username, password, role, full_name) VALUES 
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'John Smith'),
('teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Jane Doe');

-- Insert sample students (password: password123)
INSERT INTO users (username, password, role, full_name) VALUES 
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Alice Johnson'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Bob Williams'),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Charlie Brown');

-- Create user profiles for all users
INSERT INTO user_profiles (user_id, email) 
SELECT id, CONCAT(username, '@school.edu') FROM users;

-- Insert sample courses
INSERT INTO courses (course_code, course_name, description, credits) VALUES 
('CS101', 'Introduction to Programming', 'Basic programming concepts using Python', 3),
('CS102', 'Data Structures', 'Arrays, linked lists, trees, and graphs', 4),
('MATH101', 'Calculus I', 'Limits, derivatives, and integrals', 4),
('ENG101', 'English Composition', 'Academic writing and critical thinking', 3);

-- Insert sample sections
INSERT INTO sections (section_name, course_id, teacher_id, room_number, schedule_time) VALUES 
('Section-A', 1, 2, 'Room 101', 'MWF 9:00-10:00 AM'),
('Section-B', 1, 3, 'Room 102', 'TTH 10:00-11:30 AM'),
('Section-A', 2, 2, 'Room 201', 'MWF 11:00-12:00 PM'),
('Section-A', 3, 3, 'Room 301', 'TTH 2:00-3:30 PM');

-- Insert sample enrollments
INSERT INTO enrollments (student_id, section_id) VALUES 
(4, 1), (5, 1), (6, 1),
(4, 3), (5, 2), (6, 4);

-- Insert sample announcement
INSERT INTO announcements (title, content, target_role, created_by) VALUES 
('Welcome to the New Semester', 'Welcome back everyone! Classes begin on Monday.', 'all', 1);

-- Seed the four grading periods for the current school year
-- School year starts in June: Jun-Dec => YYYY-(YYYY+1), Jan-May => (YYYY-1)-YYYY
SET @sy_start := IF(MONTH(CURDATE()) >= 6, YEAR(CURDATE()), YEAR(CURDATE()) - 1);
SET @sy := CONCAT(@sy_start, '-', @sy_start + 1);

INSERT INTO grading_periods (school_year, period_number, name, start_date, end_date, is_active) VALUES
(@sy, 1, '1st Grading', CONCAT(@sy_start, '-06-01'),     CONCAT(@sy_start, '-08-31'),     1),
(@sy, 2, '2nd Grading', CONCAT(@sy_start, '-09-01'),     CONCAT(@sy_start, '-11-30'),     0),
(@sy, 3, '3rd Grading', CONCAT(@sy_start, '-12-01'),     CONCAT(@sy_start + 1, '-02-28'), 0),
(@sy, 4, '4th Grading', CONCAT(@sy_start + 1, '-03-01'), CONCAT(@sy_start + 1, '-05-31'), 0);

-- Give every section the default grade weights (10/20/30/40)
INSERT INTO section_grade_weights (section_id) SELECT id FROM sections;
