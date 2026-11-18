CREATE DATABASE IF NOT EXISTS `invitation_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `invitation_db`;

-- Table 1: admins
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'superadmin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB;

-- Table 2: invitations
CREATE TABLE IF NOT EXISTS `invitations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `date_time` DATETIME NOT NULL,
  `venue` TEXT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `theme` VARCHAR(30) NOT NULL,
  `dress_code` VARCHAR(100) DEFAULT NULL,
  `registry` VARCHAR(255) DEFAULT NULL,
  `special_notes` TEXT DEFAULT NULL,
  `share_token` VARCHAR(8) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 3: rsvps
CREATE TABLE IF NOT EXISTS `rsvps` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invitation_id` INT NOT NULL,
  `guest_name` VARCHAR(100) NOT NULL,
  `status` ENUM('Attending', 'Declined') NOT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invitation_id`) REFERENCES `invitations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Default Super Admin (Username: admin | Password: password)
INSERT INTO `admins` (`id`, `username`, `password`, `role`) 
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin')
ON DUPLICATE KEY UPDATE `role` = 'superadmin';