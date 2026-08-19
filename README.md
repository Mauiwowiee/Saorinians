# Saorinians: Development of a Role-Based Student Management System

## Project Overview

Saorinians is a web-based student management system developed for Santa Clarita International School. It improves the organization and monitoring of student academic, attendance, behavioral, scheduling, and profile records.

The system provides a centralized platform for administrators, teachers, and students, replacing manual or scattered recordkeeping with a more organized and accessible digital process.

## Purpose of the Project

The purpose of Saorinians is to develop a centralized and secure platform that helps educational staff manage student records efficiently. It aims to improve record organization, information accessibility, academic monitoring, communication, and data security while reducing errors and delays caused by manual processes.

## Project Objectives

- Manage student, teacher, subject, class, and schedule records in one database
- Record and monitor student attendance
- Manage and view academic grades
- Monitor student behavioral records
- Publish announcements for authorized users
- Implement role-based access control
- Improve security through authentication, password hashing, sessions, and input validation
- Reduce manual recordkeeping errors

## Key Features

- Centralized student information management
- Teacher and subject management
- Class and schedule management
- Attendance recording and monitoring
- Grade recording and viewing
- Behavioral record management
- Announcements
- Role-based dashboards and permissions
- Secure login and registration
- Database-backed record storage

## Security Features

Saorinians handles sensitive student and academic information. The system uses:

- Role-Based Access Control (RBAC)
- User authentication and session management
- Password hashing using PHP password functions
- Prepared SQL statements through PDO
- Input validation and sanitization
- User-specific access restrictions
- Protected role-based pages
- Upload validation where applicable

These mechanisms help maintain the confidentiality, integrity, and availability of student records.

## User Roles

- **Administrator** – manages users, students, teachers, subjects, classes, schedules, announcements, and system records
- **Teacher** – views assigned classes, records attendance, manages grades, and monitors authorized student information
- **Student** – views personal grades, attendance, schedules, announcements, and available behavioral records

The system uses RBAC so each user accesses only the functions and information relevant to their role.

## Technologies Used

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Backend:** PHP
- **Database:** MySQL
- **Database Access:** PHP Data Objects (PDO)
- **Authentication:** PHP sessions and password hashing
- **Development Environment:** XAMPP, WAMP, or Laragon
- **Code Editor:** Visual Studio Code
- **Version Control:** GitHub
- **Documentation:** Canva, Google Docs, and PDF documentation

## Main Project Structure

```text
php-student-management/
├── admin/          # Administrator pages and management modules
├── teacher/        # Teacher pages and academic functions
├── student/        # Student pages and personal records
├── config/         # Application and database configuration
├── includes/       # Authentication and shared database operations
├── assets/         # CSS, JavaScript, images, and interface resources
├── uploads/        # Uploaded files, when applicable
├── database.sql    # MySQL database structure and sample records
├── index.php       # System landing page
├── login.php       # User login page
├── register.php    # User registration page
└── dashboard.php   # Role-based dashboard redirect
```

## Installation and Setup

1. Install XAMPP, WAMP, or Laragon.
2. Copy the `php-student-management` folder into the server directory.
3. Start Apache and MySQL.
4. Open phpMyAdmin.
5. Create a MySQL database for the system.
6. Import `database.sql` into the database.
7. Update the database credentials in `config/database.php`.
8. Open the project in a browser:

```text
http://localhost/php-student-management/
```

9. Register or use an existing account to access the appropriate dashboard.

## System Workflow

1. A user opens the system landing page.
2. The user logs in with an authorized account.
3. The authentication system validates the credentials.
4. The system checks the user role.
5. The user is redirected to the appropriate dashboard.
6. The user accesses only permitted functions.
7. Records are saved in the MySQL database.

## Project Documentation

The project includes PDF documents for the mock-up presentation:

- `saorinians-code-explanation-presentation.pdf` – explains the system architecture, code organization, database, security, and modules
- `saorinians-step-by-step-user-guide.pdf` – explains installation, login, and administrator, teacher, and student workflows

## Project Contributors

Developed by the BSIT / Cybersecurity Researchers of:

**PHINMA University of Iloilo**

## Project Purpose Statement

Saorinians centralizes student records and academic monitoring in one organized system. Through role-based access, secure authentication, and database-backed management, it supports more efficient coordination between administrators, teachers, and students.

## License

This project is developed for academic and educational purposes.
