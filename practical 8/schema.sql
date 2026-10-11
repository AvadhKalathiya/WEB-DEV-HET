-- =====================================================================
-- Practical 8: MySQL Schema Design, ER Model, and Database Connectivity
-- Course: Web Development Frameworks (ITUE203) - CHARUSAT FTE
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `charusat_portal`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `charusat_portal`;

-- 1. Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Users Table (Core entity for Authentication & RBAC)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'faculty', 'student') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_role` (`role`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB;

-- 3. Students Table (1:1 with Users or standalone profile)
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(20) NOT NULL UNIQUE,
    `user_id` INT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(15) NOT NULL,
    `department_id` INT NOT NULL,
    `semester` INT NOT NULL DEFAULT 1,
    `cgpa` DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_student_dept` FOREIGN KEY (`department_id`)
        REFERENCES `departments`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_dept` (`department_id`)
) ENGINE=InnoDB;

-- 4. Events Table (For Practical 12 Poster Upload & Event Management)
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `event_date` DATE NOT NULL,
    `venue` VARCHAR(100) NOT NULL,
    `poster_path` VARCHAR(255) NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_event_creator` FOREIGN KEY (`created_by`)
        REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 5. Seed Initial Baseline Data
INSERT INTO `departments` (`code`, `name`) VALUES
('IT', 'Information Technology'),
('CE', 'Computer Engineering'),
('CSE', 'Computer Science & Engineering'),
('EC', 'Electronics & Communication'),
('ME', 'Mechanical Engineering')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Default Admin User: username=admin, password=Admin@123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'admin@charusat.edu.in', '$2y$10$wN9QnK0rC1wG4ZgXG9z0x.HhQ0j6Ue6d0a7JbXo3oG2i8YgO3x3lG', 'Portal Administrator', 'admin'),
('faculty1', 'faculty@charusat.edu.in', '$2y$10$wN9QnK0rC1wG4ZgXG9z0x.HhQ0j6Ue6d0a7JbXo3oG2i8YgO3x3lG', 'Prof. Sharma', 'faculty')
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- Seed sample students
INSERT INTO `students` (`student_id`, `full_name`, `email`, `phone`, `department_id`, `semester`, `cgpa`) VALUES
('23IT001', 'Het Patel', 'het.patel@charusat.edu.in', '9876543210', 1, 4, 9.45),
('23IT012', 'Aarav Sharma', 'aarav.sharma@charusat.edu.in', '9822334455', 1, 4, 8.85),
('23CE045', 'Diya Joshi', 'diya.joshi@charusat.edu.in', '9876112233', 2, 6, 9.15)
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);
