-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 09:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agri_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `target`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'superadmin@agri.local', '::1', '2026-05-13 15:13:36'),
(2, 1, 'logout', 'Super Admin', '::1', '2026-05-13 15:13:55'),
(3, 1, 'login', 'superadmin@agri.local', '::1', '2026-05-13 15:14:34'),
(4, 1, 'add_user', 'jrpogi200318@gmail.com', '::1', '2026-05-13 15:15:29'),
(5, 1, 'add_user', 'markchristianmendoza04@gmail.com', '::1', '2026-05-13 15:16:19'),
(6, 1, 'archive_user', '3', '::1', '2026-05-13 15:16:36'),
(7, 1, 'restore_user', '3', '::1', '2026-05-13 15:17:19'),
(8, 1, 'archive_user', '3', '::1', '2026-05-13 15:17:44'),
(9, 1, 'restore_user', '3', '::1', '2026-05-13 15:17:57'),
(10, 1, 'add_user', 'chesterwesleyyuzon36@gmail.com', '::1', '2026-05-13 15:18:53'),
(11, 1, 'logout', 'Super Admin', '::1', '2026-05-13 15:19:09'),
(12, 4, 'login', 'chesterwesleyyuzon36@gmail.com', '::1', '2026-05-13 15:19:15'),
(13, 4, 'logout', 'Chester Wesley Yuzon', '::1', '2026-05-13 15:19:31'),
(14, 4, 'login', 'chesterwesleyyuzon36@gmail.com', '::1', '2026-05-13 15:20:24'),
(15, 4, 'reset_password', '3', '::1', '2026-05-13 15:20:47'),
(16, 4, 'logout', 'Chester Wesley Yuzon', '::1', '2026-05-13 15:20:51'),
(17, 3, 'login', 'markchristianmendoza04@gmail.com', '::1', '2026-05-13 15:21:14'),
(18, 3, 'logout', 'Mark Christian Mendoza', '::1', '2026-05-13 15:21:34');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `added_by` int(10) UNSIGNED NOT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_approvals`
--

CREATE TABLE `item_approvals` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `requested_by` int(10) UNSIGNED NOT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','regular') NOT NULL DEFAULT 'regular',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_archived`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'superadmin@agri.local', '$2b$12$ZxvXynbDRT5kQ/MkN5J0h.WLkHNo1UU11uqeJA/BwZSDCHxddXTDK', 'super_admin', 0, '2026-05-13 15:02:26', '2026-05-13 15:12:20'),
(2, 'Olympio Corpuz Jr.', 'jrpogi200318@gmail.com', '$2y$12$fycjByXlym3dWt7o9RBZCOkJfeXkvbgtcQ36t4Plz17XjHKbvLMLO', 'regular', 0, '2026-05-13 15:15:29', '2026-05-13 15:15:29'),
(3, 'Mark Christian Mendoza', 'markchristianmendoza04@gmail.com', '$2y$12$UY5If/ILPFrIvf4cWGaAxOqMBEdaxjDGUzu9OFhQ/gYxLmYfHUQQa', 'regular', 0, '2026-05-13 15:16:19', '2026-05-13 15:20:47'),
(4, 'Chester Wesley Yuzon', 'chesterwesleyyuzon36@gmail.com', '$2y$12$f8YjgXkS66TTDpoHYTyY6ukjqkxOtr9cVWWtGHOphamvWW/3EoDwW', 'admin', 0, '2026-05-13 15:18:53', '2026-05-13 15:18:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `item_approvals`
--
ALTER TABLE `item_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_approvals`
--
ALTER TABLE `item_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `item_approvals`
--
ALTER TABLE `item_approvals`
  ADD CONSTRAINT `item_approvals_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_approvals_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `item_approvals_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
