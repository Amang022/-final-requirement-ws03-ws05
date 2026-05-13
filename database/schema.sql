-- =============================================================
-- Agri Inventory System - Database Schema
-- =============================================================

CREATE DATABASE IF NOT EXISTS agri_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agri_inventory;

-- -------------------------------------------------------------
-- Users table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('super_admin','admin','regular') NOT NULL DEFAULT 'regular',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Items table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category    VARCHAR(100) NOT NULL,
    quantity    DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit        VARCHAR(50)  NOT NULL DEFAULT 'pcs',
    description TEXT,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    added_by    INT UNSIGNED NOT NULL,
    approved_by INT UNSIGNED DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by)    REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Item approvals log
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS item_approvals (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id      INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    reviewed_by  INT UNSIGNED DEFAULT NULL,
    status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    note         TEXT,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at  DATETIME DEFAULT NULL,
    FOREIGN KEY (item_id)      REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Activity log
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED DEFAULT NULL,
    action     VARCHAR(100) NOT NULL,
    target     VARCHAR(200) DEFAULT NULL,
    ip_address VARCHAR(45)  DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Default Super Admin account
-- Password: Admin@1234  (bcrypt hash for the seeded account)
-- CHANGE THIS PASSWORD IMMEDIATELY after first login!
-- -------------------------------------------------------------
INSERT INTO users (name, email, password, role) VALUES (
    'Super Admin',
    'superadmin@agri.local',
    '$2b$12$ZxvXynbDRT5kQ/MkN5J0h.WLkHNo1UU11uqeJA/BwZSDCHxddXTDK',
    'super_admin'
);
