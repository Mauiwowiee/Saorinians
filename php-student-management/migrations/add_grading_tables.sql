-- ========================================
-- Grading System Tables
-- ========================================

-- Grading periods (quarters, midterm, final, etc.)
CREATE TABLE IF NOT EXISTS grading_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    period_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_section_period (section_id, period_name)
);

-- Grade components (attendance, modules, quizzes, tests, etc.)
CREATE TABLE IF NOT EXISTS grade_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    weight DECIMAL(5, 2) NOT NULL COMMENT 'Weight percentage (must sum to 100)',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_section_component (section_id, component_name)
);

-- Student grades
CREATE TABLE IF NOT EXISTS student_grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    student_id INT NOT NULL,
    period_id INT NOT NULL,
    component_id INT NOT NULL,
    score DECIMAL(5, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES grading_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES grade_components(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_grade (section_id, student_id, period_id, component_id)
);

-- ========================================
-- Enhanced Attendance Tables
-- ========================================

-- Attendance undo history for tracking changes
CREATE TABLE IF NOT EXISTS attendance_undo_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    previous_status VARCHAR(20),
    new_status VARCHAR(20) NOT NULL,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_section_date (section_id, attendance_date)
);

-- Add updated_at to attendance table if not exists
ALTER TABLE attendance ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
