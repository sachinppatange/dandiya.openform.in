-- Saved guest profile so the next event form can be prefilled.
-- Safe to run more than once.
--
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root dandiyanew < migrations/2026_10_09_student_profile.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` varchar(120) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'first_name') = 0,
  'ALTER TABLE `students` ADD COLUMN `first_name` varchar(100) DEFAULT NULL',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'middle_name') = 0,
  'ALTER TABLE `students` ADD COLUMN `middle_name` varchar(100) DEFAULT NULL',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'last_name') = 0,
  'ALTER TABLE `students` ADD COLUMN `last_name` varchar(100) DEFAULT NULL',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'college_id') = 0,
  'ALTER TABLE `students` ADD COLUMN `college_id` int(11) DEFAULT NULL',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'school_name') = 0,
  'ALTER TABLE `students` ADD COLUMN `school_name` varchar(255) DEFAULT NULL',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

INSERT IGNORE INTO `schema_migrations` (`id`) VALUES ('2026_10_09_student_profile');
