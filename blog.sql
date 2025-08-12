  -- phpMyAdmin SQL Dump
  -- version 5.2.2
  -- https://www.phpmyadmin.net/
  --
  -- Host: localhost
  -- Generation Time: Aug 12, 2025 at 08:54 PM
  -- Server version: 11.8.3-MariaDB
  -- PHP Version: 8.4.11

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
    `approval_date` timestamp NULL DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

  -- --------------------------------------------------------

  --
  -- Table structure for table `article_categories`
  --

  CREATE TABLE `article_categories` (
    `article_id` int(11) NOT NULL,
    `category_id` int(11) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

  -- --------------------------------------------------------

  --
  -- Table structure for table `article_tags`
  --

  CREATE TABLE `article_tags` (
    `article_id` int(11) NOT NULL,
    `tag_id` int(11) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  -- --------------------------------------------------------

  --
  -- Table structure for table `categories`
  --

  CREATE TABLE `categories` (
    `id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  -- --------------------------------------------------------

  --
  -- Table structure for table `tags`
  --

  CREATE TABLE `tags` (
    `id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  -- Indexes for dumped tables
  --

  --
  -- Indexes for table `articles`
  --
  ALTER TABLE `articles`
    ADD PRIMARY KEY (`id`),
    ADD KEY `user_id` (`user_id`),
    ADD KEY `last_editor_id` (`last_editor_id`),
    ADD KEY `fk_category` (`category_id`);

  --
  -- Indexes for table `article_blocks`
  --
  ALTER TABLE `article_blocks`
    ADD PRIMARY KEY (`id`),
    ADD KEY `article_id` (`article_id`);

  --
  -- Indexes for table `article_categories`
  --
  ALTER TABLE `article_categories`
    ADD PRIMARY KEY (`article_id`,`category_id`),
    ADD KEY `category_id` (`category_id`);

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
  -- Indexes for table `article_tags`
  --
  ALTER TABLE `article_tags`
    ADD PRIMARY KEY (`article_id`,`tag_id`),
    ADD KEY `tag_id` (`tag_id`);

  --
  -- Indexes for table `categories`
  --
  ALTER TABLE `categories`
    ADD PRIMARY KEY (`id`);

  --
  -- Indexes for table `tags`
  --
  ALTER TABLE `tags`
    ADD PRIMARY KEY (`id`);

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
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `article_blocks`
  --
  ALTER TABLE `article_blocks`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `article_comments`
  --
  ALTER TABLE `article_comments`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `article_drafts`
  --
  ALTER TABLE `article_drafts`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `article_draft_blocks`
  --
  ALTER TABLE `article_draft_blocks`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `article_likes`
  --
  ALTER TABLE `article_likes`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `categories`
  --
  ALTER TABLE `categories`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `tags`
  --
  ALTER TABLE `tags`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- Constraints for dumped tables
  --

  --
  -- Constraints for table `articles`
  --
  ALTER TABLE `articles`
    ADD CONSTRAINT `article_creator` FOREIGN KEY (`user_id`) REFERENCES `user` (`usn`),
    ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`last_editor_id`) REFERENCES `user` (`usn`),
    ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

  --
  -- Constraints for table `article_blocks`
  --
  ALTER TABLE `article_blocks`
    ADD CONSTRAINT `article_blocks_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

  --
  -- Constraints for table `article_categories`
  --
  ALTER TABLE `article_categories`
    ADD CONSTRAINT `article_categories_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `article_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

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

  --
  -- Constraints for table `article_tags`
  --
  ALTER TABLE `article_tags`
    ADD CONSTRAINT `article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
  COMMIT;

  /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
  /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
  /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
