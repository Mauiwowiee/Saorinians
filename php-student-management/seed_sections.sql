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
            ELT(1 + FLOOR((number - 1) / 2),
                'Liam', 'Enzo', 'Zion', 'Kean', 'Jiro', 'Calix', 'Elijah', 'Nathan', 'Lucas', 'Ethan',
                'Kobe', 'Jace', 'Axl', 'Noah', 'Caleb', 'Dylan', 'Miko', 'Joaquin', 'Theo', 'Rafael',
                'Matteo', 'Andre', 'Kiel', 'Xavier', 'Santino', 'Nico', 'Elian', 'Adrian', 'Kairo', 'Gael',
                'Leandro', 'Maverick', 'Ezekiel', 'Cian', 'Alonzo', 'Kian', 'Jaden', 'Zayn', 'Miguel', 'Marco',
                'Gabriel', 'Angelo', 'Paolo', 'Renz', 'Luis'),
            ' ',
            ELT(1 + FLOOR((number - 1) / 2) + IF(section_label = 'Mabini', 0, 45),
                'Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Navarro', 'Castillo', 'Ramos',
                'Aquino', 'Bautista', 'Rivera', 'Villanueva', 'Gonzales', 'Fernandez', 'Manalo', 'Santiago', 'Magsaysay', 'Dizon',
                'Salazar', 'Mercado', 'Valdez', 'Aguilar', 'Bernardo', 'Delos Santos', 'Pascual', 'Ocampo', 'Samson', 'Macapagal',
                'Tolentino', 'Montes', 'Soriano', 'Pineda', 'De Leon', 'Yap', 'Lim', 'Tan', 'Chua', 'Uy',
                'Co', 'Natividad', 'Lacson', 'Roxas', 'Marquez', 'Velasco', 'Domingo', 'Padilla', 'Estrada', 'David',
                'Luna', 'Manansala', 'Esguerra', 'Guevarra', 'Buan', 'Cabrera', 'Sison', 'Yulo', 'Abad', 'Quinto',
                'Pangilinan', 'Alcantara', 'Tuason', 'Concepcion', 'Banzon', 'Balmaceda', 'Araneta', 'Legaspi', 'Dela Pena', 'Mariano',
                'Enriquez', 'Fajardo', 'Ilagan', 'Javier', 'Kasilag', 'Lorenzo', 'Malvar', 'Nolasco', 'Ortega', 'Peralta',
                'Quezon', 'Regalado', 'Sevilla', 'Tejada', 'Umali', 'Villar', 'Ylagan', 'Zamora', 'Alvarez', 'Briones'),
            ' ', section_label
        )
        ELSE CONCAT(
            ELT(1 + FLOOR((number - 2) / 2),
                'Aira', 'Althea', 'Amara', 'Anika', 'Bianca', 'Celine', 'Danica', 'Eliana', 'Freya', 'Gianna',
                'Hannah', 'Ivy', 'Janelle', 'Kaira', 'Lara', 'Maya', 'Nadine', 'Naya', 'Olivia', 'Phoebe',
                'Quinn', 'Raina', 'Samara', 'Talia', 'Una', 'Valerie', 'Wynter', 'Yasmin', 'Zara', 'Alyssa',
                'Brielle', 'Cheska', 'Diana', 'Eloise', 'Fiona', 'Giselle', 'Isabelle', 'Jasmine', 'Kiara', 'Liana',
                'Mikaela', 'Nica', 'Odessa', 'Pia', 'Reese'),
            ' ',
            ELT(1 + FLOOR((number - 2) / 2) + IF(section_label = 'Mabini', 0, 45),
                'Dela Cruz', 'De Guzman', 'De Vera', 'Dela Rosa', 'Serrano', 'Mariano', 'Abad', 'Alvarez', 'Arceo', 'Bello',
                'Beltran', 'Cabrera', 'Calderon', 'Carpio', 'Castañeda', 'Castro', 'Chavez', 'Corpuz', 'David', 'Diaz',
                'Enriquez', 'Escobar', 'Espiritu', 'Ferrer', 'Francisco', 'Guevarra', 'Hernandez', 'Ibarra', 'Javier', 'Lazaro',
                'Lorenzo', 'Manalo', 'Marquez', 'Miranda', 'Montero', 'Natividad', 'Navarro', 'Ong', 'Panganiban', 'Paredes',
                'Quintos', 'Ramos', 'Rosales', 'Salvador', 'Sarmiento', 'Soliman', 'Suarez', 'Tiongson', 'Trinidad', 'Valencia',
                'Vergara', 'Vicente', 'Yap', 'Zamora', 'Zaragoza', 'Agustin', 'Baldonado', 'Basilio', 'Bonifacio', 'Buenaventura',
                'Cayanan', 'Dimaculangan', 'Gatchalian', 'Hilario', 'Imperial', 'Labrador', 'Macaraig', 'Mallari', 'Ocampo', 'Pascual',
                'Ramos', 'Sarmiento', 'Tanyag', 'Tupas', 'Velasco', 'Villareal', 'Yumul', 'Zabala', 'Zarate', 'Aguirre',
                'Arellano', 'Bautista', 'Cortez', 'Dumlao', 'Estrella', 'Flores', 'Gonzales', 'Limpin', 'Magsino', 'Manrique'),
            ' ', section_label
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
