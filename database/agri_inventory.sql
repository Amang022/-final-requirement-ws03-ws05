-- =============================================================
-- Agri Inventory System — Complete Database Setup
-- Single file: schema + indexes + constraints + seed data
-- =============================================================
-- Usage:
--   1. Open phpMyAdmin or MySQL CLI
--   2. Import this file — it will create the database and all tables
--   3. Default super admin: superadmin@agri.local / Admin@1234
--      CHANGE THIS PASSWORD immediately after first login!
-- =============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =============================================================
-- Create database
-- =============================================================
CREATE DATABASE IF NOT EXISTS `agri_inventory`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `agri_inventory`;

-- =============================================================
-- Table: users
-- =============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100)  NOT NULL,
    `email`       VARCHAR(150)  NOT NULL UNIQUE,
    `password`    VARCHAR(255)  NOT NULL,
    `role`        ENUM('super_admin','admin','regular') NOT NULL DEFAULT 'regular',
    `is_archived` TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_users_email`      (`email`),
    INDEX `idx_users_role`       (`role`),
    INDEX `idx_users_archived`   (`is_archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: items
-- =============================================================
CREATE TABLE IF NOT EXISTS `items` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(150)  NOT NULL,
    `category`    VARCHAR(100)  NOT NULL,
    `quantity`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `unit`        VARCHAR(50)   NOT NULL DEFAULT 'pcs',
    `description` TEXT          DEFAULT NULL,
    `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `is_archived` TINYINT(1)    NOT NULL DEFAULT 0,
    `added_by`    INT UNSIGNED  NOT NULL,
    `approved_by` INT UNSIGNED  DEFAULT NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_items_status`   (`status`),
    INDEX `idx_items_archived` (`is_archived`),
    INDEX `idx_items_added_by` (`added_by`),

    CONSTRAINT `fk_items_added_by`    FOREIGN KEY (`added_by`)    REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_items_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: item_approvals
-- =============================================================
CREATE TABLE IF NOT EXISTS `item_approvals` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id`      INT UNSIGNED NOT NULL,
    `requested_by` INT UNSIGNED NOT NULL,
    `reviewed_by`  INT UNSIGNED DEFAULT NULL,
    `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `note`         TEXT         DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`  DATETIME     DEFAULT NULL,

    INDEX `idx_approvals_status` (`status`),

    CONSTRAINT `fk_approvals_item`      FOREIGN KEY (`item_id`)      REFERENCES `items`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_approvals_requester` FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_approvals_reviewer`  FOREIGN KEY (`reviewed_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: activity_log
-- =============================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `target`     VARCHAR(200) DEFAULT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_log_user`    (`user_id`),
    INDEX `idx_log_action`  (`action`),
    INDEX `idx_log_created` (`created_at`),

    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Seed: Default Super Admin account
-- Email:    superadmin@agri.local
-- Password: Admin@1234  (bcrypt hash)
-- CHANGE THIS PASSWORD IMMEDIATELY after first login!
-- =============================================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Super Admin', 'superadmin@agri.local',
    '$2b$12$ZxvXynbDRT5kQ/MkN5J0h.WLkHNo1UU11uqeJA/BwZSDCHxddXTDK',
    'super_admin');

-- =============================================================
-- Seed: 2 Regular User accounts
-- Both passwords: Password1  (bcrypt cost 12)
-- =============================================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(2, 'Olympio Corpuz Jr.', 'jrpogi200318@gmail.com',
    '$2y$12$fycjByXlym3dWt7o9RBZCOkJfeXkvbgtcQ36t4Plz17XjHKbvLMLO',
    'regular'),
(3, 'Mark Christian Mendoza', 'markchristianmendoza04@gmail.com',
    '$2y$12$UY5If/ILPFrIvf4cWGaAxOqMBEdaxjDGUzu9OFhQ/gYxLmYfHUQQa',
    'regular');

-- =============================================================
-- Seed: Products added by Olympio Corpuz Jr. (user_id = 2)
-- =============================================================
INSERT INTO `items` (`id`, `name`, `category`, `quantity`, `unit`, `description`, `status`, `added_by`, `approved_by`) VALUES
(1, 'Premium Rice Sack (50kg)', 'Grains', 120.00, 'sack',
    'Locally harvested premium white rice, 50kg per sack. Stored in climate-controlled warehouse.',
    'approved', 2, 1),
(2, 'Yellow Corn Kernels', 'Grains', 85.00, 'kg',
    'Dried yellow corn kernels suitable for animal feed and cornmeal production.',
    'approved', 2, 1),
(3, 'Organic Fertilizer (Vermicast)', 'Supplies', 200.00, 'bag',
    'Premium vermicast organic fertilizer. 25kg bags, ideal for vegetable and rice farming.',
    'approved', 2, 1);

-- =============================================================
-- Seed: Products added by Mark Christian Mendoza (user_id = 3)
-- =============================================================
INSERT INTO `items` (`id`, `name`, `category`, `quantity`, `unit`, `description`, `status`, `added_by`, `approved_by`) VALUES
(4, 'Fresh Eggplant (Talong)', 'Vegetables', 45.00, 'kg',
    'Freshly harvested eggplants from local farms. Grade A quality for market distribution.',
    'approved', 3, 1),
(5, 'Calamansi Fruits', 'Fruits', 60.00, 'kg',
    'Ripe calamansi fruits, hand-picked and sorted. Suitable for juice production and retail.',
    'approved', 3, 1),
(6, 'Seedling Trays (200-cell)', 'Supplies', 8.00, 'pcs',
    'Reusable 200-cell seedling trays for germinating vegetable and crop seeds.',
    'pending', 3, NULL);

-- =============================================================
-- Seed: Approval record for the pending item
-- =============================================================
INSERT INTO `item_approvals` (`id`, `item_id`, `requested_by`, `status`) VALUES
(1, 6, 3, 'pending');

-- =============================================================
-- Seed: Activity log entries
-- =============================================================
INSERT INTO `activity_log` (`user_id`, `action`, `target`, `ip_address`) VALUES
(1, 'login', 'superadmin@agri.local', '::1'),
(2, 'login', 'jrpogi200318@gmail.com', '::1'),
(2, 'add_item', 'Premium Rice Sack (50kg)', '::1'),
(2, 'add_item', 'Yellow Corn Kernels', '::1'),
(2, 'add_item', 'Organic Fertilizer (Vermicast)', '::1'),
(3, 'login', 'markchristianmendoza04@gmail.com', '::1'),
(3, 'add_item', 'Fresh Eggplant (Talong)', '::1'),
(3, 'add_item', 'Calamansi Fruits', '::1'),
(3, 'add_item', 'Seedling Trays (200-cell)', '::1'),
(1, 'review_item_approved', '1', '::1'),
(1, 'review_item_approved', '2', '::1'),
(1, 'review_item_approved', '3', '::1'),
(1, 'review_item_approved', '4', '::1'),
(1, 'review_item_approved', '5', '::1');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
