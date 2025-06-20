CREATE DATABASE IF NOT EXISTS `db-election`;
USE `db-election`;

-- Admins table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
);

-- Classes table
CREATE TABLE IF NOT EXISTS `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty` VARCHAR(100) NOT NULL,
  `batch` VARCHAR(50) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  UNIQUE KEY `class_identifier` (`faculty`, `batch`)
);

-- Students table
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `crn` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `voted` BOOLEAN DEFAULT FALSE,
  `image` VARCHAR(255),
  `class_id` INT NOT NULL,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
);

-- Candidates table
CREATE TABLE IF NOT EXISTS `candidates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `crn` VARCHAR(20) NOT NULL,
  `votes` INT DEFAULT 0,
  `supporter_1` VARCHAR(20) NOT NULL,
  `supporter_2` VARCHAR(20) NOT NULL,
  FOREIGN KEY (`crn`) REFERENCES `students`(`crn`) ON DELETE CASCADE,
  FOREIGN KEY (`supporter_1`) REFERENCES `students`(`crn`) ON DELETE CASCADE,
  FOREIGN KEY (`supporter_2`) REFERENCES `students`(`crn`) ON DELETE CASCADE
);

-- Elections table
CREATE TABLE IF NOT EXISTS `elections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `election_date` DATE NOT NULL,
  `winner` INT,
  `showresult` BOOLEAN DEFAULT FALSE,
  `voting` BOOLEAN DEFAULT FALSE,
  `status` ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`winner`) REFERENCES `candidates`(`id`) ON DELETE SET NULL
);