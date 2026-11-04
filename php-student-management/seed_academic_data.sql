USE student_management;

-- Student fees table for dashboard/account balances.
CREATE TABLE IF NOT EXISTS student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    section_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    due_date DATE NOT NULL,
    status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_fee (student_id, section_id, fee_type)
);

-- Detailed class timetable for Rizal and Mabini.
INSERT IGNORE INTO schedules (section_id, day_of_week, start_time, end_time)
SELECT s.id, days.day_of_week, days.start_time, days.end_time
FROM sections s
CROSS JOIN (
    SELECT 'monday' AS day_of_week, '08:00:00' AS start_time, '09:00:00' AS end_time
    UNION ALL SELECT 'wednesday', '08:00:00', '09:00:00'
    UNION ALL SELECT 'friday', '08:00:00', '09:00:00'
) days
WHERE s.section_name = 'Rizal' AND s.course_id = 1;

INSERT IGNORE INTO schedules (section_id, day_of_week, start_time, end_time)
SELECT s.id, days.day_of_week, days.start_time, days.end_time
FROM sections s
CROSS JOIN (
    SELECT 'tuesday' AS day_of_week, '09:00:00' AS start_time, '10:00:00' AS end_time
    UNION ALL SELECT 'thursday', '09:00:00', '10:00:00'
    UNION ALL SELECT 'saturday', '09:00:00', '10:00:00'
) days
WHERE s.section_name = 'Mabini' AND s.course_id = 1;

-- Assignments visible on each student's dashboard.
INSERT INTO assignments (section_id, title, description, due_date, max_points, created_by)
SELECT s.id, a.title, a.description, a.due_date, a.max_points,
       (SELECT id FROM users WHERE username = 'teacher1' LIMIT 1)
FROM sections s
CROSS JOIN (
    SELECT 'Personal Learning Profile' AS title,
           'Write a one-page reflection about your learning goals and study habits.' AS description,
           '2026-11-14 23:59:00' AS due_date, 50 AS max_points
    UNION ALL SELECT 'Community Issues Research',
           'Research one local community issue and submit a short report with three sources.',
           '2026-11-21 23:59:00', 100
    UNION ALL SELECT 'Final Presentation',
           'Prepare a five-minute presentation connecting the course lessons to everyday life.',
           '2026-12-05 23:59:00', 100
) a
WHERE s.section_name IN ('Rizal', 'Mabini') AND s.course_id = 1
  AND NOT EXISTS (
      SELECT 1 FROM assignments old
      WHERE old.section_id = s.id AND old.title = a.title
  );

-- Sample tuition fee records for all 90 generated students.
INSERT INTO student_fees (student_id, section_id, fee_type, amount, paid_amount, due_date, status)
SELECT u.id, e.section_id, 'Tuition Fee', 12500.00, 5000.00, '2026-11-30', 'partial'
FROM users u
JOIN enrollments e ON e.student_id = u.id AND e.status = 'enrolled'
JOIN sections s ON s.id = e.section_id AND s.section_name IN ('Rizal', 'Mabini') AND s.course_id = 1
WHERE u.username LIKE '04-2324-%'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), paid_amount = VALUES(paid_amount), due_date = VALUES(due_date), status = VALUES(status);

INSERT INTO student_fees (student_id, section_id, fee_type, amount, paid_amount, due_date, status)
SELECT u.id, e.section_id, 'Laboratory Fee', 1500.00, 0.00, '2026-12-05', 'unpaid'
FROM users u
JOIN enrollments e ON e.student_id = u.id AND e.status = 'enrolled'
JOIN sections s ON s.id = e.section_id AND s.section_name IN ('Rizal', 'Mabini') AND s.course_id = 1
WHERE u.username LIKE '04-2324-%'
ON DUPLICATE KEY UPDATE amount = VALUES(amount), paid_amount = VALUES(paid_amount), due_date = VALUES(due_date), status = VALUES(status);
