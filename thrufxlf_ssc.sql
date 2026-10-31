-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 30, 2026 at 10:09 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thrufxlf_ssc`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint NOT NULL,
  `admin_id` int NOT NULL,
  `action` enum('CREATE','UPDATE','DELETE','CLEAR') COLLATE utf8mb4_general_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Table name: positions, candidates, partylists, students, users',
  `entity_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ID of the affected record',
  `old_values` longtext COLLATE utf8mb4_general_ci COMMENT 'JSON snapshot before change',
  `new_values` longtext COLLATE utf8mb4_general_ci COMMENT 'JSON snapshot after change',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Human-readable summary',
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int NOT NULL,
  `position_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `platform` text COLLATE utf8mb4_general_ci,
  `partylist_id` int DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_default_winner` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int NOT NULL,
  `student_id` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vote_data` longtext COLLATE utf8mb4_general_ci,
  `status` enum('pending','processing','sent','failed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `attempts` int DEFAULT '0',
  `next_retry_at` datetime DEFAULT NULL,
  `error_message` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partylists`
--

CREATE TABLE `partylists` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `badge_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '#1e40af',
  `badge_text_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

CREATE TABLE `security_events` (
  `id` bigint NOT NULL,
  `admin_id` int NOT NULL,
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(55) COLLATE utf8mb4_general_ci NOT NULL,
  `can_manage_audit_logs` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_accounts` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete_election` tinyint(1) NOT NULL DEFAULT '0',
  `can_export_csv` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit_info` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_vote` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_sessions` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `can_manage_audit_logs`, `can_manage_accounts`, `enabled`, `created_at`) VALUES
(1, 'samgoingco', '$2y$10$qbvRhXta.Vw/56BLHwMNuunXSNbJk6csqUnkZCQ4WwVT2ZN5o6uSe', 'Execquielle John Goingco', 'superadmin', 1, 1, 1, '2026-08-29 12:11:43');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int NOT NULL,
  `voter_identifier` varchar(16) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Unique voter identifier',
  `voter_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `voted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `section_id` int DEFAULT NULL COMMENT 'Section the voter picked (NULL for older votes)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vote_counts`
--

CREATE TABLE `vote_counts` (
  `position_id` int NOT NULL,
  `candidate_id` int NOT NULL,
  `vote_count` int DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vote_details`
--

CREATE TABLE `vote_details` (
  `vote_id` int NOT NULL,
  `position_id` int NOT NULL,
  `candidate_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `vote_details`
--
DELIMITER $$
CREATE TRIGGER `delete_vote_counts` AFTER DELETE ON `vote_details` FOR EACH ROW BEGIN
    UPDATE vote_counts SET vote_count = vote_count - 1, last_updated = NOW()
    WHERE position_id = OLD.position_id AND candidate_id = OLD.candidate_id;
    
    DELETE FROM vote_counts 
    WHERE position_id = OLD.position_id AND candidate_id = OLD.candidate_id AND vote_count <= 0;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_vote_counts` AFTER INSERT ON `vote_details` FOR EACH ROW INSERT INTO vote_counts (position_id, candidate_id, vote_count, last_updated)
VALUES (NEW.position_id, NEW.candidate_id, 1, NOW())
ON DUPLICATE KEY UPDATE vote_count = vote_count + 1, last_updated = NOW()
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `votingswitch`
--

CREATE TABLE `votingswitch` (
  `enable_vote` int NOT NULL,
  `enable_login` int NOT NULL,
  `enable_guest` int NOT NULL,
  `enable_site` int NOT NULL,
  `enable_test_vote` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votingswitch`
--

INSERT INTO `votingswitch` (`enable_vote`, `enable_login`, `enable_guest`, `enable_site`, `enable_test_vote`) VALUES
(1, 1, 1, 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_section_name` (`name`),
  ADD KEY `idx_sections_sort_order` (`sort_order`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_entity_type` (`entity_type`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_admin_created` (`admin_id`,`created_at`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `idx_candidates_position_name` (`position_id`,`name`),
  ADD KEY `idx_candidate_position` (`position_id`),
  ADD KEY `idx_candidate_name` (`name`),
  ADD KEY `idx_partylist_id` (`partylist_id`);
ALTER TABLE `candidates` ADD FULLTEXT KEY `name_2` (`name`,`platform`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_status_attempts` (`status`,`attempts`),
  ADD KEY `idx_sent_at_status` (`sent_at`,`status`),
  ADD KEY `idx_failed_at_status` (`failed_at`,`status`),
  ADD KEY `idx_created_at_status` (`created_at`,`status`),
  ADD KEY `idx_created_at_status_attempts` (`created_at`,`status`,`attempts`),
  ADD KEY `idx_next_retry` (`next_retry_at`);

--
-- Indexes for table `partylists`
--
ALTER TABLE `partylists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_position_sort_order` (`sort_order`),
  ADD KEY `idx_position_name` (`name`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_security_created_at` (`created_at`),
  ADD KEY `idx_security_event_type` (`event_type`),
  ADD KEY `idx_security_admin_id` (`admin_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_voter_created_lookup` (`voter_identifier`,`voted_at`),
  ADD KEY `idx_voter_identifier` (`voter_identifier`),
  ADD KEY `idx_voter_exists` (`voter_identifier`),
  ADD KEY `idx_voted_at_email` (`voted_at`,`email`),
  ADD KEY `idx_voted_at_identifier` (`voted_at`,`voter_identifier`),
  ADD KEY `idx_votes_section_id` (`section_id`);

--
-- Indexes for table `vote_counts`
--
ALTER TABLE `vote_counts`
  ADD PRIMARY KEY (`position_id`,`candidate_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `vote_details`
--
ALTER TABLE `vote_details`
  ADD PRIMARY KEY (`vote_id`,`position_id`),
  ADD KEY `idx_position_candidate` (`position_id`,`candidate_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partylists`
--
ALTER TABLE `partylists`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_events`
--
ALTER TABLE `security_events`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_candidate_partylist` FOREIGN KEY (`partylist_id`) REFERENCES `partylists` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_events`
--
ALTER TABLE `security_events`
  ADD CONSTRAINT `fk_security_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vote_counts`
--
ALTER TABLE `vote_counts`
  ADD CONSTRAINT `fk_vote_counts_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vote_counts_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vote_details`
--
ALTER TABLE `vote_details`
  ADD CONSTRAINT `vote_details_ibfk_1` FOREIGN KEY (`vote_id`) REFERENCES `votes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vote_details_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vote_details_ibfk_3` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;
ALTER TABLE `partylists` ADD COLUMN `badge_color` VARCHAR(20) COLLATE utf8mb4_general_ci DEFAULT '#1e40af';
ALTER TABLE `partylists` ADD COLUMN `badge_text_color` VARCHAR(20) COLLATE utf8mb4_general_ci DEFAULT '#ffffff';
ALTER TABLE `users` ADD COLUMN `can_delete_election` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `users` ADD COLUMN `can_export_csv` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `users` ADD COLUMN `can_edit_info` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `users` ADD COLUMN `can_manage_vote` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `users` ADD COLUMN `can_manage_sessions` TINYINT(1) NOT NULL DEFAULT '0';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;