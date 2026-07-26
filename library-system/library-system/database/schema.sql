-- Smart Library Management System
-- Database schema

CREATE DATABASE IF NOT EXISTS library_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_system;

-- ---------------------------------------------------------------
-- Users (both admin and student accounts live here, role separates them)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student') NOT NULL DEFAULT 'student',
    phone VARCHAR(30) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Books (cover_image stores the filename saved in uploads/covers/)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(50) DEFAULT NULL,
    category VARCHAR(80) DEFAULT NULL,
    description TEXT,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    cover_image VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Reservations
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('pending','approved','rejected','returned','cancelled') NOT NULL DEFAULT 'pending',
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Notifications
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Demo user accounts (admin@library.com / student@library.com) are created
-- by running `php database/seed_users.php` after this schema is imported.
-- That script uses PHP's own password_hash() so the hash always matches
-- what login.php verifies with password_verify() -- no hard-coded hashes here.
-- ---------------------------------------------------------------

-- created_by is left NULL here because seed_users.php runs AFTER this file,
-- so no user rows exist yet -- inserting created_by = 1 at this point would
-- violate the books.created_by foreign key (no matching users.id). If you
-- want these demo books attributed to the admin account, run an UPDATE
-- after seed_users.php has created it, e.g.:
--   UPDATE books SET created_by = (SELECT id FROM users WHERE email = 'admin@library.com') WHERE created_by IS NULL;
INSERT INTO books (title, author, isbn, category, description, total_copies, available_copies, cover_image, created_by) VALUES
('Clean Code', 'Robert C. Martin', '9780132350884', 'Programming', 'A handbook of agile software craftsmanship.', 3, 3, 'Clean_Code_cover.jpg', NULL),
('Harry Potter', 'J.K. Rowling', '9780201616224', 'Story', 'Harry Potter by Rowling.', 2, 2, 'Harry_Potter_Cover.jpg', NULL),
('Introduction to Algorithms', 'Thomas H. Cormen', '9780262033848', 'Computer Science', 'Comprehensive algorithms textbook.', 2, 2, 'Algorithms_cover.jpg', NULL),
('Artificial Intelligence', 'Stuart Russel', '9780062316097', 'AI', 'AI by Stuart Russel.', 4, 4, 'Artificial_Intel_Cover.jpg', NULL),
('Atomic Habits', 'James Clear', '9780735211292', 'Self-Help', 'An easy and proven way to build good habits.', 5, 5, 'Atomic_Habits_cover.jpg', NULL);
