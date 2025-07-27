-- phpMyAdmin SQL Dump
-- version 5.2.2-1.fc42
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 27, 2025 at 11:39 PM
-- Server version: 10.11.11-MariaDB
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `blog`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `user_id` int(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) DEFAULT 0,
  `modified_at` timestamp NULL DEFAULT NULL,
  `last_editor_id` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `user_id`, `title`, `created_at`, `approved`, `modified_at`, `last_editor_id`, `approval_date`) VALUES
(7, 22000, 'testing', '2025-07-25 00:13:04', 1, '2025-07-27 01:32:59', NULL, '2025-07-27 01:32:59'),
(8, 22000, 'sdasdasdasdasdasd', '2025-07-25 09:59:50', 1, '2025-07-27 02:16:48', NULL, '2025-07-27 02:16:48'),
(9, 22000, 'dadasda', '2025-07-26 00:09:06', 0, '2025-07-27 02:48:19', NULL, '2025-07-27 02:16:46'),
(12, 23000, 'lllllll', '2025-07-27 00:15:41', 1, '2025-07-27 02:41:30', 23000, '2025-07-27 02:40:48');

-- --------------------------------------------------------

--
-- Table structure for table `article_blocks`
--

CREATE TABLE `article_blocks` (
  `id` int(11) NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `block_type` enum('text','image') NOT NULL,
  `content` text NOT NULL,
  `sort_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_blocks`
--

INSERT INTO `article_blocks` (`id`, `article_id`, `block_type`, `content`, `sort_order`, `created_at`) VALUES
(12, 7, 'image', 'uploads/img_6882cc10a96019.56501101.png', 0, '2025-07-25 00:13:04'),
(13, 7, 'image', 'uploads/img_6882cc10aba329.13061175.png', 1, '2025-07-25 00:13:04'),
(14, 7, 'text', 'sdasjkdhsahdjashdhasdhsadhsahjdashdasjd mncn,mcncnmzxnc djashdaskjdas  ueywiqueyiquwyeqw opooppo nsdgjashdghsad', 2, '2025-07-25 00:13:04'),
(15, 8, 'image', 'uploads/img_688355968b0ce2.67222476.png', 0, '2025-07-25 09:59:50'),
(16, 8, 'image', 'uploads/img_68835596a3f607.60415808.png', 1, '2025-07-25 09:59:50'),
(17, 9, 'image', 'uploads/img_68841ca20a4635.57947279.png', 0, '2025-07-26 00:09:06'),
(18, 9, 'text', 'dasdasdasdasda', 1, '2025-07-26 00:09:06'),
(19, 9, 'image', 'uploads/img_68841ca20beda5.10536157.png', 2, '2025-07-26 00:09:06'),
(36, 12, 'image', 'http://localhost:3000/public/uploads/img_68856fad9324a4.00289418.png', 0, '2025-07-27 02:41:30'),
(37, 12, 'text', 'wewqeqwewqewqewqe2', 1, '2025-07-27 02:41:30'),
(38, 12, 'text', 'qweqwewqe', 2, '2025-07-27 02:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `article_comments`
--

CREATE TABLE `article_comments` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `article_comments`
--

INSERT INTO `article_comments` (`id`, `article_id`, `user_id`, `content`, `created_at`, `modified_at`) VALUES
(5, 8, 22000, 'adasdasdasdsadasdas', '2025-07-26 00:05:54', NULL),
(6, 8, 22000, 'adadasd', '2025-07-26 00:06:07', NULL),
(7, 8, 22000, 'asdasda', '2025-07-26 00:07:50', NULL),
(8, 8, 22000, 'asdadad', '2025-07-26 01:36:20', NULL),
(11, 9, 22000, 'wedsdasd', '2025-07-26 23:12:27', NULL),
(12, 9, 23000, 'adas', '2025-07-26 23:13:00', NULL),
(15, 12, 22000, 'dhasdjkhasdjshadhksa', '2025-07-27 01:29:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `article_drafts`
--

CREATE TABLE `article_drafts` (
  `id` int(11) NOT NULL,
  `original_article_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `article_draft_blocks`
--

CREATE TABLE `article_draft_blocks` (
  `id` int(11) NOT NULL,
  `draft_id` int(11) NOT NULL,
  `block_type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `sort_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `article_likes`
--

CREATE TABLE `article_likes` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `article_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `article_likes`
--

INSERT INTO `article_likes` (`id`, `user_id`, `article_id`) VALUES
(55, '22000', 7),
(62, '22000', 8),
(57, '22000', 9),
(64, '22000', 12),
(65, '23000', 12);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `usn` int(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `privilege` int(11) NOT NULL DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`usn`, `pass`, `role`, `name`, `privilege`) VALUES
(22000, '22000', 'admin', 'admiatest', 1),
(23000, '23000', 'teacher', 'teacherist', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_editor_id` (`last_editor_id`);

--
-- Indexes for table `article_blocks`
--
ALTER TABLE `article_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);

--
-- Indexes for table `article_comments`
--
ALTER TABLE `article_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_drafts`
--
ALTER TABLE `article_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `original_article_id` (`original_article_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `article_draft_blocks`
--
ALTER TABLE `article_draft_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `draft_id` (`draft_id`);

--
-- Indexes for table `article_likes`
--
ALTER TABLE `article_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`article_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD KEY `usn` (`usn`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `article_blocks`
--
ALTER TABLE `article_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `article_comments`
--
ALTER TABLE `article_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `article_drafts`
--
ALTER TABLE `article_drafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `article_draft_blocks`
--
ALTER TABLE `article_draft_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `article_likes`
--
ALTER TABLE `article_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `article_creator` FOREIGN KEY (`user_id`) REFERENCES `user` (`usn`),
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`last_editor_id`) REFERENCES `user` (`usn`);

--
-- Constraints for table `article_blocks`
--
ALTER TABLE `article_blocks`
  ADD CONSTRAINT `article_blocks_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_drafts`
--
ALTER TABLE `article_drafts`
  ADD CONSTRAINT `article_drafts_ibfk_1` FOREIGN KEY (`original_article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `article_drafts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`usn`);

--
-- Constraints for table `article_draft_blocks`
--
ALTER TABLE `article_draft_blocks`
  ADD CONSTRAINT `article_draft_blocks_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `article_drafts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
