-- ==========================================
-- Uni.Book MySQL Database Schema (3NF Normalized)
-- Compatibility Target: localhost / XAMPP / phpMyAdmin
-- ==========================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `unibook_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unibook_db`;

-- Set connection collation explicitly
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

-- Disable foreign key checks temporarily to drop tables in any order
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS `bookings`;
DROP TABLE IF EXISTS `bookings_data`;
DROP TABLE IF EXISTS `resources`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- 1. Table `roles` (Lookup Table)
-- --------------------------------------------------------
CREATE TABLE `roles` (
    `role_id` INT AUTO_INCREMENT,
    `role_name` VARCHAR(50) NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table `users` (Normalized)
-- --------------------------------------------------------
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci,
    `phone` VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `email` VARCHAR(255) NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci,
    `password` VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci,
    `role_id` INT NOT NULL,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table `categories` (Lookup Table)
-- --------------------------------------------------------
CREATE TABLE `categories` (
    `category_id` INT AUTO_INCREMENT,
    `category_name` VARCHAR(100) NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table `resources` (Normalized)
-- --------------------------------------------------------
CREATE TABLE `resources` (
    `resource_id` INT AUTO_INCREMENT,
    `category_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci,
    `description` TEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `image` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `status` ENUM('Available', 'Unavailable') NOT NULL DEFAULT 'Available',
    `pickup_address` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`resource_id`),
    CONSTRAINT `fk_resources_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table `bookings_data` (Normalized Transactions)
-- --------------------------------------------------------
CREATE TABLE `bookings_data` (
    `booking_id` INT AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `resource_id` INT NOT NULL,
    `booking_date` DATE NOT NULL,
    `booking_slot` VARCHAR(100) NOT NULL COLLATE utf8mb4_unicode_ci,
    `booking_purpose` TEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `amount` DECIMAL(10,2) NOT NULL,
    `booking_status` ENUM('Pending', 'Confirmed', 'Completed', 'Rejected', 'Declined', 'Pending Review', 'Approved', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `payment_method` VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `payment_bank` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `transaction_reference` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `payment_status` ENUM('Unpaid', 'Pending Cash Verification', 'Paid', 'Refund Requested', 'Refunded') NOT NULL DEFAULT 'Unpaid',
    `payment_date` DATETIME DEFAULT NULL,
    `receipt_id` VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`booking_id`),
    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Indexes for performance tuning
-- --------------------------------------------------------
CREATE INDEX `idx_bookings_user` ON `bookings_data` (`user_id`);
CREATE INDEX `idx_bookings_resource` ON `bookings_data` (`resource_id`);
CREATE INDEX `idx_bookings_date_slot` ON `bookings_data` (`booking_date`, `booking_slot`);
CREATE INDEX `idx_bookings_status` ON `bookings_data` (`booking_status`);
CREATE INDEX `idx_bookings_payment_status` ON `bookings_data` (`payment_status`);

-- --------------------------------------------------------
-- 6. Compatibility View `bookings`
-- Resolves queries from the legacy PHP dashboard directly
-- --------------------------------------------------------
CREATE OR REPLACE VIEW `bookings` AS
SELECT 
    CONCAT('UB', b.`booking_id`) AS `booking_id`,
    CONCAT('U', b.`user_id`) AS `user_id`,
    u.`email` AS `user_email`,
    u.`name` AS `user_name`,
    CONCAT('res_', b.`resource_id`) AS `resource_id`,
    r.`name` AS `resource_name`,
    c.`category_name` AS `category`,
    b.`booking_date` AS `date`,
    b.`booking_slot` AS `slot`,
    b.`booking_purpose` AS `booking_purpose`,
    b.`amount` AS `amount`,
    b.`booking_status` AS `booking_status`,
    b.`payment_method` AS `payment_method`,
    b.`payment_bank` AS `payment_bank`,
    b.`transaction_reference` AS `transaction_reference`,
    b.`payment_status` AS `payment_status`,
    r.`pickup_address` AS `pickup_address`,
    b.`payment_date` AS `payment_date`,
    b.`receipt_id` AS `receipt_id`
FROM `bookings_data` b
INNER JOIN `users` u ON b.`user_id` = u.`user_id`
INNER JOIN `resources` r ON b.`resource_id` = r.`resource_id`
INNER JOIN `categories` c ON r.`category_id` = c.`category_id`;


-- ========================================================
-- Seed Initial Data
-- ========================================================

-- Seed Roles
INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'user'),
(3, 'manager');

-- Seed Users (with bcrypt hashes matched from JSON files)
INSERT INTO `users` (`user_id`, `name`, `phone`, `email`, `password`, `role_id`) VALUES
(1001, 'John Doe', '+1234567890', 'student@unibook.edu', '$2y$10$mu9gnImhvAuF/kLdJymDp.wrqitJ03mdUTMYDor1L1pp4YhVtGCNm', 2),
(1002, 'Jane Smith', '+1987654321', 'staff@unibook.edu', '$2y$10$vIg/YjNAkntw5GfHEk7Bzu4SO6RGu6OVKGm4NG0SgCngT9oR0ONMO', 2),
(1003, 'ALIAS BIN SAID', NULL, 'aliahnadhirah08@gmail.com', '$2y$10$mu9gnImhvAuF/kLdJymDp.wrqitJ03mdUTMYDor1L1pp4YhVtGCNm', 2),
(1004, 'Eyna LLLL', NULL, 'aliah@gmail.com', '$2y$10$mu9gnImhvAuF/kLdJymDp.wrqitJ03mdUTMYDor1L1pp4YhVtGCNm', 2),
(1005, 'Facilities Manager', NULL, 'facilitiesmanager@unibook.com', '$2y$10$a/9zcDMVSGYeJn7ER0xZou9mDW.9/8cO5laiHL7NkJOWWp8mXRs2S', 3),
(1006, 'Transport Manager', NULL, 'transportmanager@unibook.com', '$2y$10$a/9zcDMVSGYeJn7ER0xZou9mDW.9/8cO5laiHL7NkJOWWp8mXRs2S', 3),
(1007, 'ICT Manager', NULL, 'ictmanager@unibook.com', '$2y$10$a/9zcDMVSGYeJn7ER0xZou9mDW.9/8cO5laiHL7NkJOWWp8mXRs2S', 3),
(1008, 'HR Manager', NULL, 'hrmanager@unibook.com', '$2y$10$a/9zcDMVSGYeJn7ER0xZou9mDW.9/8cO5laiHL7NkJOWWp8mXRs2S', 3);

-- Seed Categories
INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Facilities'),
(2, 'Vehicles'),
(3, 'Personnel'),
(4, 'Equipment');

-- Seed Resources (res_1 to res_12 mapped 1-to-1)
INSERT INTO `resources` (`resource_id`, `category_id`, `name`, `description`, `price`, `image`, `status`) VALUES
(1, 1, 'Seminar Hall A', 'Large air-conditioned hall with dual projectors, professional sound system, and seating capacity of 150.', 100.00, 'seminar_hall_a.jpg', 'Available'),
(2, 1, 'Seminar Hall B', 'Modern seminar hall equipped with video conferencing systems and seating capacity of 100.', 120.00, 'seminar_hall_b.jpg', 'Available'),
(3, 1, 'Discussion Room', 'Cozy meeting space with interactive smart-board and LED screen, ideal for group study of up to 8.', 30.00, 'discussion_room.jpg', 'Available'),
(4, 1, 'Sports Hall', 'Multi-purpose indoor sports hall suitable for badminton, basketball, volleyball, and campus-wide events.', 80.00, 'sports_hall.jpg', 'Available'),
(5, 2, 'University Bus', '44-seater luxury coach with reclining seats and air conditioning, perfect for long-distance study trips.', 150.00, 'university_bus.jpg', 'Available'),
(6, 2, 'University Van', '12-seater passenger van for local department transport and campus transfers.', 80.00, 'university_van.jpg', 'Available'),
(7, 3, 'Photographer', 'Professional event photography services, including raw image selection and post-production processing.', 50.00, 'photographer.jpg', 'Available'),
(8, 3, 'Event Crew', 'Logistical support crew of 4 student marshals to assist in venue setup, ushering, and coordination.', 40.00, 'event_crew.jpg', 'Available'),
(9, 3, 'Technical Support', 'Dedicated IT/AV technician for setup, live management, and on-site troubleshooting.', 60.00, 'technical_support.jpg', 'Available'),
(10, 4, 'Projector', 'High-brightness laser projector with HDMI inputs and screen casting capabilities.', 20.00, 'projector.jpg', 'Available'),
(11, 4, 'Camera', 'DSLR camera kit (Canon/Sony) with variable standard zoom lens, tripod, and external microphone.', 40.00, 'camera.jpg', 'Available'),
(12, 4, 'Audio System', 'Active speaker system with 2 wireless microphones, audio mixer, and Bluetooth connectivity.', 50.00, 'audio_system.jpg', 'Available');

-- Seed Bookings (UB1001 to UB1006 mapped 1-to-1)
INSERT INTO `bookings_data` (`booking_id`, `user_id`, `resource_id`, `booking_date`, `booking_slot`, `amount`, `booking_status`, `payment_status`, `payment_date`, `receipt_id`) VALUES
(1001, 1001, 1, '2026-06-18', '10:00 AM – 12:00 PM', 100.00, 'Declined', 'Unpaid', NULL, NULL),
(1002, 1001, 6, '2026-06-20', '2:00 PM – 4:00 PM', 80.00, 'Confirmed', 'Paid', '2026-06-16 15:30:00', 'RC98471'),
(1003, 1002, 7, '2026-06-18', '8:00 AM – 10:00 AM', 50.00, 'Completed', 'Paid', '2026-06-15 09:15:00', 'RC98122'),
(1004, 1003, 1, '2026-06-21', '4:00 PM – 6:00 PM', 100.00, 'Pending Review', 'Unpaid', NULL, NULL),
(1005, 1004, 2, '2026-09-16', '4:00 PM – 6:00 PM', 120.00, 'Pending Review', 'Unpaid', NULL, NULL),
(1006, 1004, 2, '2026-06-30', '10:00 AM – 12:00 PM', 120.00, 'Pending Review', 'Unpaid', NULL, NULL);
