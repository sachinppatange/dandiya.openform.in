-- Stable participant numbers for 3×3 inch judging cards.
-- Safe to run more than once.
--
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root dandiyanew < migrations/2026_10_09_participant_numbers.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` varchar(120) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'participant_no') = 0,
  'ALTER TABLE `scholarship_applications` ADD COLUMN `participant_no` int(11) DEFAULT NULL',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND INDEX_NAME = 'uniq_participant_no') = 0,
  'ALTER TABLE `scholarship_applications` ADD UNIQUE KEY `uniq_participant_no` (`participant_no`)',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

INSERT IGNORE INTO `schema_migrations` (`id`) VALUES ('2026_10_09_participant_numbers');
