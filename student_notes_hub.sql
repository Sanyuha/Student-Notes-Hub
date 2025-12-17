-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 13, 2025 at 10:13 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_notes_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','superadmin') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', '2025-12-02 17:22:08', '2025-12-13 14:08:37');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'file',
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_cat_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `slug`, `name`, `icon`, `description`) VALUES
(1, 'engineering', 'Engineering', 'cogs', NULL),
(2, 'software', 'Software Engineering', 'code', NULL),
(3, 'networking', 'Networking', 'network-wired', NULL),
(4, 'mathematics', 'Mathematics', 'calculator', NULL),
(5, 'science', 'Science', 'flask', NULL),
(6, 'business', 'Business', 'briefcase', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_groups`
--

DROP TABLE IF EXISTS `chat_groups`;
CREATE TABLE IF NOT EXISTS `chat_groups` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creator_id` int UNSIGNED NOT NULL,
  `is_private` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_group_creator` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `note_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_com_note` (`note_id`,`created_at`),
  KEY `idx_com_user` (`user_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_comment_thread` (`note_id`,`parent_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user1_id` int UNSIGNED NOT NULL,
  `user2_id` int UNSIGNED NOT NULL,
  `last_message_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversation` (`user1_id`,`user2_id`),
  KEY `idx_user1` (`user1_id`),
  KEY `idx_user2` (`user2_id`),
  KEY `idx_last_message` (`last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

DROP TABLE IF EXISTS `downloads`;
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `note_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_down_note` (`note_id`,`created_at`),
  KEY `idx_down_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `downloads`
--

INSERT INTO `downloads` (`id`, `note_id`, `user_id`, `ip_address`, `created_at`) VALUES
(1, 6, 2, '::1', '2025-12-02 17:40:51'),
(5, 3, NULL, '::1', '2025-12-06 10:08:49'),
(7, 1, NULL, '::1', '2025-12-06 10:09:33'),
(14, 1, 2, '::1', '2025-12-06 10:53:11'),
(38, 7, NULL, '::1', '2025-12-06 11:58:21'),
(39, 7, NULL, '::1', '2025-12-06 11:58:21'),
(40, 7, NULL, '::1', '2025-12-06 11:58:25'),
(41, 7, NULL, '::1', '2025-12-06 11:58:25'),
(48, 10, 2, '::1', '2025-12-13 05:59:44'),
(49, 10, 2, '::1', '2025-12-13 05:59:52'),
(50, 10, 2, '::1', '2025-12-13 05:59:52'),
(51, 10, 2, '::1', '2025-12-13 06:00:20'),
(52, 10, 2, '::1', '2025-12-13 06:00:20'),
(53, 10, 2, '::1', '2025-12-13 06:00:24'),
(54, 10, 2, '::1', '2025-12-13 06:00:24'),
(55, 10, 2, '::1', '2025-12-13 06:21:00'),
(56, 10, 2, '::1', '2025-12-13 06:21:00'),
(57, 10, 2, '::1', '2025-12-13 06:21:22'),
(58, 10, 2, '::1', '2025-12-13 06:21:22'),
(59, 10, 2, '::1', '2025-12-13 06:45:01'),
(60, 10, 2, '::1', '2025-12-13 06:45:01'),
(61, 11, 2, '::1', '2025-12-13 07:35:34'),
(62, 11, 2, '::1', '2025-12-13 08:04:31'),
(63, 11, 2, '::1', '2025-12-13 08:04:31');

--
-- Triggers `downloads`
--
DROP TRIGGER IF EXISTS `trg_after_download`;
DELIMITER $$
CREATE TRIGGER `trg_after_download` AFTER INSERT ON `downloads` FOR EACH ROW BEGIN
    UPDATE notes SET downloads = downloads + 1 WHERE id = NEW.note_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `follower_id` int UNSIGNED NOT NULL,
  `following_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`follower_id`,`following_id`),
  KEY `idx_following` (`following_id`),
  KEY `idx_follow_active` (`follower_id`,`following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

DROP TABLE IF EXISTS `group_members`;
CREATE TABLE IF NOT EXISTS `group_members` (
  `group_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `role` enum('admin','moderator','member') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'member',
  `last_read_at` timestamp NULL DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `idx_member_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_messages`
--

DROP TABLE IF EXISTS `group_messages`;
CREATE TABLE IF NOT EXISTS `group_messages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int UNSIGNED NOT NULL,
  `sender_id` int UNSIGNED NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_url` VARCHAR(500) NULL,
  `file_name` VARCHAR(255) NULL,
  `file_type` VARCHAR(100) NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gmsg_group` (`group_id`,`created_at`),
  KEY `idx_gmsg_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
CREATE TABLE IF NOT EXISTS `likes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `note_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_like` (`note_id`,`user_id`),
  KEY `idx_like_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `note_id`, `user_id`, `created_at`) VALUES
(12, 1, 3, '2025-11-06 22:41:29'),
(27, 5, 3, '2025-11-06 22:53:36');

--
-- Triggers `likes`
--
DROP TRIGGER IF EXISTS `trg_after_like`;
DELIMITER $$
CREATE TRIGGER `trg_after_like` AFTER INSERT ON `likes` FOR EACH ROW BEGIN
    UPDATE notes SET likes = likes + 1 WHERE id = NEW.note_id;
    INSERT INTO notifications(user_id, type, from_id, note_id)
    SELECT user_id, 'like', NEW.user_id, NEW.note_id
    FROM notes WHERE id = NEW.note_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_after_unlike`;
DELIMITER $$
CREATE TRIGGER `trg_after_unlike` AFTER DELETE ON `likes` FOR EACH ROW BEGIN
    UPDATE notes SET likes = likes - 1 WHERE id = OLD.note_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int UNSIGNED NOT NULL,
  `sender_id` int UNSIGNED NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_url` VARCHAR(500) NULL,
  `file_name` VARCHAR(255) NULL,
  `file_type` VARCHAR(100) NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_conv` (`conversation_id`,`created_at`),
  KEY `idx_msg_sender` (`sender_id`),
  KEY `idx_msg_read` (`conversation_id`,`is_read`,`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `category_id` tinyint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint UNSIGNED DEFAULT NULL,
  `file_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('published','draft','private') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `downloads` int UNSIGNED DEFAULT '0',
  `likes` int UNSIGNED DEFAULT '0',
  `views` int UNSIGNED DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notes_user` (`user_id`),
  KEY `idx_notes_cat` (`category_id`),
  KEY `idx_notes_status` (`status`),
  KEY `idx_notes_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `category_id`, `title`, `description`, `file_url`, `file_size`, `file_type`, `status`, `downloads`, `likes`, `views`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'Advanced Calculus Notes', 'Limits, derivatives, integrals', 'uploads/calc.pdf', 2500000, 'pdf', 'published', 238, 1, 575, '2025-11-06 21:30:47', '2025-12-06 10:53:37'),
(3, 1, 3, 'Network Security Basics', 'Encryption, protocols', 'uploads/netsec.pdf', 1800000, 'pdf', 'published', 125, 34, 355, '2025-11-06 21:30:47', '2025-12-13 10:09:55'),
(4, 3, 6, 'enter', '', 'uploads/1af9e6815033b1d1.pdf', 3631, 'pdf', 'draft', 0, 0, 0, '2025-11-06 22:23:45', '2025-11-06 22:23:45'),
(5, 3, 6, 'asaiment', 'chapter 5', 'uploads/10de104fa627526e.pdf', 3631, 'pdf', 'published', 0, 1, 98, '2025-11-06 22:42:47', '2025-12-13 10:11:26'),
(6, 3, 2, 'software', 'chapter 5', 'uploads/64fd9e8fc58f4064.pdf', 979900, 'pdf', 'published', 2, 0, 48, '2025-11-08 10:32:44', '2025-12-13 09:40:36'),
(7, 3, 3, 'sdajhdjs', 'sadas', 'uploads/48c428008b1b8e24.docx', 388090, 'docx', 'published', 6, 0, 6, '2025-11-08 14:31:53', '2025-12-13 06:32:51'),
(10, 2, 1, 'thsi is it the endssss', '', 'uploads/693d05dfbc005_Linear search.pdf', NULL, 'pdf', 'published', 20, 0, 38, '2025-12-13 05:52:48', '2025-12-13 10:11:22'),
(11, 2, 6, 'ye', 'WYES', 'uploads/41ee45d204e2a9cd.pdf', 206865, 'pdf', 'published', 4, 0, 20, '2025-12-13 06:01:47', '2025-12-13 10:11:13');

-- --------------------------------------------------------

--
-- Table structure for table `note_tags`
--

DROP TABLE IF EXISTS `note_tags`;
CREATE TABLE IF NOT EXISTS `note_tags` (
  `note_id` int UNSIGNED NOT NULL,
  `tag_id` smallint UNSIGNED NOT NULL,
  PRIMARY KEY (`note_id`,`tag_id`),
  KEY `idx_nt_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `note_tags`
--

INSERT INTO `note_tags` (`note_id`, `tag_id`) VALUES
(1, 1),
(3, 3),
(3, 5);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_id` int UNSIGNED DEFAULT NULL,
  `note_id` int UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`),
  KEY `idx_notif_from` (`from_id`),
  KEY `idx_notif_note` (`note_id`),
  KEY `idx_notif_unread` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `from_id`, `note_id`, `is_read`, `created_at`) VALUES
(2, 1, 'like', 3, 1, 0, '2025-12-02 17:36:59'),
(3, 3, 'like', 3, 5, 0, '2025-12-02 17:36:59'),
(5, 1, 'like', 2, 1, 0, '2025-12-06 10:53:36'),
(7, 2, 'like', 2, 11, 0, '2025-12-13 07:35:38'),
(8, 2, 'like', 2, 11, 0, '2025-12-13 07:35:40'),
(9, 2, 'like', 2, 11, 0, '2025-12-13 07:35:41'),
(10, 2, 'like', 2, 11, 0, '2025-12-13 07:35:43'),
(11, 2, 'like', 2, 11, 0, '2025-12-13 07:40:45'),
(12, 2, 'like', 2, 11, 0, '2025-12-13 08:04:34'),
(13, 3, 'like', 2, 5, 0, '2025-12-13 09:44:48');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
  `id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`) VALUES
(4, 'algorithms'),
(1, 'calculus'),
(2, 'dsa'),
(5, 'networking'),
(3, 'security');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher','professional') COLLATE utf8mb4_unicode_ci DEFAULT 'student',
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'https://via.placeholder.com/150',
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `university` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `major` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `study_year` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_activity` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_email` (`email`),
  KEY `idx_user_name` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `avatar_url`, `cover_url`, `bio`, `university`, `major`, `study_year`, `member_since`, `created_at`, `updated_at`, `last_activity`) VALUES
(1, 'johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'https://via.placeholder.com/150', NULL, NULL, 'MIT', 'Computer Science', '3rd Year', '2025-11-07', '2025-11-06 21:30:47', '2025-11-06 21:30:47', NULL),
(2, 'Sarah Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'uploads/avatar_2_69341eb35d26b.jpg', NULL, '', '', '', '', '2025-11-07', '2025-11-06 21:30:47', '2025-12-13 10:11:09', '2025-12-13 14:11:09'),
(3, '', 'alhbsyy38@gmail.com', '$2y$10$5jVmxiuxbReVkHhkydLhD.GaRBYHfJ9tJhQ1ll6vEupJlH2h.HKCy', 'student', 'https://via.placeholder.com/150', NULL, NULL, '', '', '', '2025-11-07', '2025-11-06 21:32:14', '2025-11-08 10:31:53', NULL),
(4, 'said', 'alhbsyye38@gmail.com', '$2y$10$ub.//LNgppe4mhsZ6cIU6umzveMt9B/I6kyeLG5LcAvtr/cCE3UdS', 'student', 'https://via.placeholder.com/150', NULL, NULL, '', '', '', '2025-11-07', '2025-11-06 21:42:51', '2025-11-06 21:42:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `views`
--

DROP TABLE IF EXISTS `views`;
CREATE TABLE IF NOT EXISTS `views` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `note_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_view_note` (`note_id`,`created_at`),
  KEY `idx_view_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `views`
--

INSERT INTO `views` (`id`, `note_id`, `user_id`, `ip_address`, `created_at`) VALUES
(2, 6, NULL, '::1', '2025-12-02 17:40:41'),
(4, 6, NULL, '::1', '2025-12-02 17:40:47'),
(5, 7, NULL, '::1', '2025-12-02 17:41:03'),
(17, 6, NULL, '::1', '2025-12-02 18:24:45'),
(18, 6, NULL, '::1', '2025-12-02 18:25:10'),
(19, 6, NULL, '::1', '2025-12-02 18:25:13'),
(20, 6, NULL, '::1', '2025-12-03 18:54:46'),
(22, 6, NULL, '::1', '2025-12-03 18:57:36'),
(23, 5, NULL, '::1', '2025-12-03 19:00:08'),
(25, 3, NULL, '::1', '2025-12-06 10:08:46'),
(27, 1, NULL, '::1', '2025-12-06 10:09:29'),
(43, 7, NULL, '::1', '2025-12-06 10:52:56'),
(44, 1, NULL, '::1', '2025-12-06 10:53:10'),
(62, 7, NULL, '::1', '2025-12-06 11:58:20'),
(69, 10, NULL, '::1', '2025-12-13 05:53:23'),
(70, 10, NULL, '::1', '2025-12-13 05:53:50'),
(71, 10, NULL, '::1', '2025-12-13 05:54:14'),
(72, 10, NULL, '::1', '2025-12-13 05:56:36'),
(73, 10, NULL, '::1', '2025-12-13 05:59:48'),
(74, 10, NULL, '::1', '2025-12-13 06:00:06'),
(75, 10, NULL, '::1', '2025-12-13 06:00:22'),
(76, 10, NULL, '::1', '2025-12-13 06:00:23'),
(77, 10, NULL, '::1', '2025-12-13 06:00:27'),
(78, 11, NULL, '::1', '2025-12-13 06:02:23'),
(79, 10, NULL, '::1', '2025-12-13 06:02:39'),
(80, 10, NULL, '::1', '2025-12-13 06:02:53'),
(81, 10, NULL, '::1', '2025-12-13 06:13:22'),
(82, 10, NULL, '::1', '2025-12-13 06:13:28'),
(83, 10, NULL, '::1', '2025-12-13 06:20:18'),
(84, 10, NULL, '::1', '2025-12-13 06:20:55'),
(85, 10, NULL, '::1', '2025-12-13 06:20:58'),
(86, 10, NULL, '::1', '2025-12-13 06:21:21'),
(87, 10, NULL, '::1', '2025-12-13 06:21:29'),
(88, 11, NULL, '::1', '2025-12-13 06:22:00'),
(89, 11, NULL, '::1', '2025-12-13 06:22:29'),
(90, 5, NULL, '::1', '2025-12-13 06:26:25'),
(91, 11, NULL, '::1', '2025-12-13 06:26:27'),
(92, 5, NULL, '::1', '2025-12-13 06:26:30'),
(93, 11, NULL, '::1', '2025-12-13 06:26:34'),
(94, 3, NULL, '::1', '2025-12-13 06:31:27'),
(95, 3, NULL, '::1', '2025-12-13 06:32:01'),
(96, 3, NULL, '::1', '2025-12-13 06:32:08'),
(97, 3, NULL, '::1', '2025-12-13 06:32:12'),
(98, 3, NULL, '::1', '2025-12-13 06:32:27'),
(99, 3, NULL, '::1', '2025-12-13 06:32:31'),
(100, 7, NULL, '::1', '2025-12-13 06:32:51'),
(101, 10, NULL, '::1', '2025-12-13 06:45:36'),
(102, 10, NULL, '::1', '2025-12-13 06:45:55'),
(103, 10, NULL, '::1', '2025-12-13 06:51:56'),
(104, 10, NULL, '::1', '2025-12-13 06:52:06'),
(105, 10, NULL, '::1', '2025-12-13 06:52:09'),
(106, 10, NULL, '::1', '2025-12-13 06:52:14'),
(107, 10, NULL, '::1', '2025-12-13 06:52:17'),
(108, 10, NULL, '::1', '2025-12-13 06:59:46'),
(109, 10, NULL, '::1', '2025-12-13 06:59:49'),
(110, 10, NULL, '::1', '2025-12-13 06:59:53'),
(111, 10, NULL, '::1', '2025-12-13 06:59:56'),
(112, 10, NULL, '::1', '2025-12-13 07:00:20'),
(113, 10, NULL, '::1', '2025-12-13 07:00:23'),
(114, 10, NULL, '::1', '2025-12-13 07:00:39'),
(115, 10, NULL, '::1', '2025-12-13 07:05:46'),
(116, 10, NULL, '::1', '2025-12-13 07:06:44'),
(117, 10, NULL, '::1', '2025-12-13 07:10:20'),
(118, 10, NULL, '::1', '2025-12-13 07:10:53'),
(119, 11, NULL, '::1', '2025-12-13 07:35:23'),
(120, 11, NULL, '::1', '2025-12-13 07:35:33'),
(121, 5, NULL, '::1', '2025-12-13 07:35:48'),
(122, 11, NULL, '::1', '2025-12-13 07:35:51'),
(123, 11, NULL, '::1', '2025-12-13 07:40:42'),
(124, 11, NULL, '::1', '2025-12-13 07:40:43'),
(125, 11, NULL, '::1', '2025-12-13 07:40:44'),
(126, 5, NULL, '::1', '2025-12-13 07:43:05'),
(127, 5, NULL, '::1', '2025-12-13 07:43:27'),
(128, 5, NULL, '::1', '2025-12-13 07:53:18'),
(129, 5, NULL, '::1', '2025-12-13 07:55:17'),
(130, 5, NULL, '::1', '2025-12-13 07:56:48'),
(131, 5, NULL, '::1', '2025-12-13 07:56:58'),
(132, 11, NULL, '::1', '2025-12-13 07:59:19'),
(133, 11, NULL, '::1', '2025-12-13 08:01:12'),
(134, 11, NULL, '::1', '2025-12-13 08:04:26'),
(135, 11, NULL, '::1', '2025-12-13 08:04:30'),
(136, 11, NULL, '::1', '2025-12-13 08:04:49'),
(137, 6, NULL, '::1', '2025-12-13 09:40:09'),
(138, 6, NULL, '::1', '2025-12-13 09:40:36'),
(139, 10, NULL, '::1', '2025-12-13 09:44:05'),
(140, 5, NULL, '::1', '2025-12-13 09:44:14'),
(141, 5, NULL, '::1', '2025-12-13 09:44:22'),
(142, 11, NULL, '::1', '2025-12-13 09:44:54'),
(143, 11, NULL, '::1', '2025-12-13 09:54:05'),
(144, 11, NULL, '::1', '2025-12-13 10:10:46'),
(145, 11, NULL, '::1', '2025-12-13 10:11:13'),
(146, 10, NULL, '::1', '2025-12-13 10:11:22'),
(147, 5, NULL, '::1', '2025-12-13 10:11:26');

--
-- Triggers `views`
--
DROP TRIGGER IF EXISTS `trg_after_view`;
DELIMITER $$
CREATE TRIGGER `trg_after_view` AFTER INSERT ON `views` FOR EACH ROW BEGIN
    UPDATE notes SET views = views + 1 WHERE id = NEW.note_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_note_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `vw_note_summary`;
CREATE TABLE IF NOT EXISTS `vw_note_summary` (
`author_name` varchar(40)
,`avatar_url` varchar(255)
,`category_name` varchar(60)
,`category_slug` varchar(30)
,`created_at` timestamp
,`description` text
,`downloads` int unsigned
,`id` int unsigned
,`likes` int unsigned
,`status` enum('published','draft','private')
,`title` varchar(255)
,`views` int unsigned
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_dashboard`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `vw_user_dashboard`;
CREATE TABLE IF NOT EXISTS `vw_user_dashboard` (
`avatar_url` varchar(255)
,`followers_count` bigint
,`following_count` bigint
,`id` int unsigned
,`notes_count` bigint
,`username` varchar(40)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_note_summary`
--
DROP TABLE IF EXISTS `vw_note_summary`;

DROP VIEW IF EXISTS `vw_note_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_note_summary`  AS SELECT `n`.`id` AS `id`, `n`.`title` AS `title`, `n`.`description` AS `description`, `n`.`downloads` AS `downloads`, `n`.`likes` AS `likes`, `n`.`views` AS `views`, `n`.`status` AS `status`, `n`.`created_at` AS `created_at`, `c`.`slug` AS `category_slug`, `c`.`name` AS `category_name`, `u`.`username` AS `author_name`, `u`.`avatar_url` AS `avatar_url` FROM ((`notes` `n` join `categories` `c` on((`c`.`id` = `n`.`category_id`))) join `users` `u` on((`u`.`id` = `n`.`user_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_dashboard`
--
DROP TABLE IF EXISTS `vw_user_dashboard`;

DROP VIEW IF EXISTS `vw_user_dashboard`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_dashboard`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`avatar_url` AS `avatar_url`, (select count(0) from `notes` where (`notes`.`user_id` = `u`.`id`)) AS `notes_count`, (select count(0) from `follows` where (`follows`.`follower_id` = `u`.`id`)) AS `following_count`, (select count(0) from `follows` where (`follows`.`following_id` = `u`.`id`)) AS `followers_count` FROM `users` AS `u` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD CONSTRAINT `fk_group_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_com_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_com_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_user1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_user2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `downloads`
--
ALTER TABLE `downloads`
  ADD CONSTRAINT `fk_down_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_down_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `fk_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_messages`
--
ALTER TABLE `group_messages`
  ADD CONSTRAINT `fk_gmsg_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gmsg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `fk_like_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `fk_notes_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `note_tags`
--
ALTER TABLE `note_tags`
  ADD CONSTRAINT `fk_nt_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_nt_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_from` FOREIGN KEY (`from_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `views`
--
ALTER TABLE `views`
  ADD CONSTRAINT `fk_view_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_view_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
