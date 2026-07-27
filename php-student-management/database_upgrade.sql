-- ============================================================
-- Student Management System - Grading Upgrade Migration
-- Adds: grading periods (quarters), per-section grade weights,
--       and component/period tagging on assessments.
--
-- SAFE TO RE-RUN. Existing data is preserved and auto-mapped.
-- Run this in phpMyAdmin (SQL tab) or:  mysql -u root -p < database_upgrade.sql
-- ============================================================

USE student_management;

-- ------------------------------------------------------------
-- 1. Grading periods (admin-managed school year)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 2. Per-section grade weights (teacher editable)
--    Defaults: Attendance 10, Modules 20, Quizzes 30, Tests 40
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 3. Add `component` + `grading_period_id` to assessments
--    Wrapped in a procedure so re-running never errors.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sms_upgrade_assessments;
DELIMITER $$
CREATE PROCEDURE sms_upgrade_assessments()
BEGIN
    -- component column
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'assessments'
          AND COLUMN_NAME = 'component'
    ) THEN
        ALTER TABLE assessments
            ADD COLUMN component ENUM('modules','quizzes','tests')
            NOT NULL DEFAULT 'quizzes' AFTER type;
    END IF;

    -- grading_period_id column
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'assessments'
          AND COLUMN_NAME = 'grading_period_id'
    ) THEN
        ALTER TABLE assessments
            ADD COLUMN grading_period_id INT NULL AFTER component;
        ALTER TABLE assessments
            ADD CONSTRAINT fk_assessment_period
            FOREIGN KEY (grading_period_id)
            REFERENCES grading_periods(id) ON DELETE SET NULL;
    END IF;

    -- lookup index
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'assessments'
          AND INDEX_NAME = 'idx_assessment_grading'
    ) THEN
        ALTER TABLE assessments
            ADD INDEX idx_assessment_grading (section_id, grading_period_id, component);
    END IF;
END$$
DELIMITER ;

CALL sms_upgrade_assessments();
DROP PROCEDURE IF EXISTS sms_upgrade_assessments;

-- ------------------------------------------------------------
-- 4. Seed four quarters for the current school year
--    School year starts in June (Philippine calendar):
--    Jun-Dec => YYYY-(YYYY+1), Jan-May => (YYYY-1)-YYYY
-- ------------------------------------------------------------
SET @sy_start := IF(MONTH(CURDATE()) >= 6, YEAR(CURDATE()), YEAR(CURDATE()) - 1);
SET @sy := CONCAT(@sy_start, '-', @sy_start + 1);

INSERT INTO grading_periods (school_year, period_number, name, start_date, end_date, is_active)
VALUES
    (@sy, 1, '1st Grading', CONCAT(@sy_start, '-06-01'),     CONCAT(@sy_start, '-08-31'),     1),
    (@sy, 2, '2nd Grading', CONCAT(@sy_start, '-09-01'),     CONCAT(@sy_start, '-11-30'),     0),
    (@sy, 3, '3rd Grading', CONCAT(@sy_start, '-12-01'),     CONCAT(@sy_start + 1, '-02-28'), 0),
    (@sy, 4, '4th Grading', CONCAT(@sy_start + 1, '-03-01'), CONCAT(@sy_start + 1, '-05-31'), 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ------------------------------------------------------------
-- 5. Backfill existing assessments (no data loss)
--    quiz -> quizzes | exam -> tests | assignment/project -> modules
-- ------------------------------------------------------------
UPDATE assessments SET component = 'quizzes' WHERE type = 'quiz';
UPDATE assessments SET component = 'tests'   WHERE type = 'exam';
UPDATE assessments SET component = 'modules' WHERE type IN ('assignment', 'project');

-- Attach untagged assessments to the 1st Grading period of the current school year
SET @first_period := (
    SELECT id FROM grading_periods
    WHERE school_year = @sy AND period_number = 1
    LIMIT 1
);

UPDATE assessments
SET grading_period_id = @first_period
WHERE grading_period_id IS NULL AND @first_period IS NOT NULL;

-- ------------------------------------------------------------
-- 6. Give every existing section the default weight row
-- ------------------------------------------------------------
INSERT INTO section_grade_weights (section_id, w_attendance, w_modules, w_quizzes, w_tests)
SELECT s.id, 10.00, 20.00, 30.00, 40.00
FROM sections s
LEFT JOIN section_grade_weights w ON w.section_id = s.id
WHERE w.section_id IS NULL;
