USE student_management;

-- Creates two sections with 45 mixed-gender students each.
-- Section names: Rizal and Mabini.

INSERT INTO sections (section_name, course_id, room_number, schedule_time, max_students)
SELECT section_name, 1, room_number, schedule_time, 45
FROM (
    SELECT 'Rizal' AS section_name, 'Room 103' AS room_number, 'MWF 8:00-9:00 AM' AS schedule_time
    UNION ALL
    SELECT 'Mabini', 'Room 104', 'MWF 9:00-10:00 AM'
) AS new_sections
WHERE NOT EXISTS (
    SELECT 1 FROM sections existing
    WHERE existing.section_name = new_sections.section_name
      AND existing.course_id = 1
);

INSERT IGNORE INTO users (username, password, role, full_name)
WITH RECURSIVE numbers AS (
    SELECT 1 AS number
    UNION ALL
    SELECT number + 1 FROM numbers WHERE number < 45
), section_names AS (
    SELECT 'rizal' AS section_key, 'Rizal' AS section_label
    UNION ALL
    SELECT 'mabini', 'Mabini'
)
SELECT
    CONCAT(section_key, '_student_', LPAD(number, 2, '0')),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'student',
    CASE
        WHEN MOD(number, 2) = 1 THEN CONCAT(
            ELT(1 + MOD(number - 1, 10), 'Juan', 'Miguel', 'Carlos', 'Daniel', 'Marco', 'Gabriel', 'Angelo', 'Rafael', 'Paolo', 'Luis'),
            ' ',
            ELT(1 + MOD(number - 1, 10), 'Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Navarro', 'Castillo', 'Ramos')
        )
        ELSE CONCAT(
            ELT(1 + MOD(number - 2, 10), 'Maria', 'Sofia', 'Andrea', 'Patricia', 'Camille', 'Angela', 'Beatriz', 'Jasmine', 'Nicole', 'Isabella'),
            ' ',
            ELT(1 + MOD(number - 2, 10), 'Dela Cruz', 'Aquino', 'Bautista', 'Rivera', 'Villanueva', 'Gonzales', 'Fernandez', 'Manalo', 'Santiago', 'Magsaysay')
        )
    END
FROM numbers CROSS JOIN section_names;

INSERT IGNORE INTO user_profiles (user_id, email)
SELECT id, CONCAT(username, '@school.edu')
FROM users
WHERE username LIKE 'rizal_student_%' OR username LIKE 'mabini_student_%';

INSERT IGNORE INTO enrollments (student_id, section_id)
SELECT u.id, s.id
FROM users u
JOIN sections s
  ON s.section_name = CASE
      WHEN u.username LIKE 'rizal_student_%' THEN 'Rizal'
      WHEN u.username LIKE 'mabini_student_%' THEN 'Mabini'
     END
 AND s.course_id = 1
WHERE u.username LIKE 'rizal_student_%' OR u.username LIKE 'mabini_student_%';

-- Login for all generated students:
-- username: rizal_student_01 through rizal_student_45
--           mabini_student_01 through mabini_student_45
-- password: password123
