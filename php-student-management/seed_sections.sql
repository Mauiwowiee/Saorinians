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
    SELECT 'Rizal' AS section_label, 0 AS number_offset
    UNION ALL
    SELECT 'Mabini', 45
)
SELECT
    CONCAT('04-2324-', LPAD(number + number_offset, 5, '0')),
    '$2b$10$tzRlFWwoRyMIiuLUwSqZsuU4m/0zne5dHxy8Z7DtQNNtIYmmSUnnS',
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
WHERE username LIKE '04-2324-%';

INSERT IGNORE INTO enrollments (student_id, section_id)
SELECT u.id, s.id
FROM users u
JOIN sections s
  ON s.section_name = CASE
      WHEN CAST(SUBSTRING(u.username, 9) AS UNSIGNED) BETWEEN 1 AND 45 THEN 'Rizal'
      WHEN CAST(SUBSTRING(u.username, 9) AS UNSIGNED) BETWEEN 46 AND 90 THEN 'Mabini'
     END
 AND s.course_id = 1
WHERE u.username LIKE '04-2324-%';

-- Login for all generated students:
-- usernames: 04-2324-00001 through 04-2324-00090
-- password: student1
