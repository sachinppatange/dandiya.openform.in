-- College dropdown for SVSS Dandiya Night registration.
-- Safe to run more than once. Does not delete registrations.
--
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root dandiyanew < migrations/2026_10_09_colleges.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` varchar(120) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `colleges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_other` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `colleges` (`name`, `sort_order`, `is_other`, `status`) VALUES
('Latur College of Pharmacy, Hasegaon', 10, 0, 'active'),
('Latur College of Pharmacy, Latur', 20, 0, 'active'),
('SVSS Latur College of Nursing, Latur', 30, 0, 'active'),
('SVSS Latur College of Physiotherapy, Latur', 40, 0, 'active'),
('Rajiv Gandhi Institute of Polytechnic, Latur', 50, 0, 'active'),
('Latur College of Pvt. ITI, Hasegaon', 60, 0, 'active'),
('Latur College of Science', 70, 0, 'active'),
('Other', 1000, 1, 'active')
ON DUPLICATE KEY UPDATE `name` = `name`;

SET @col = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'scholarship_applications'
     AND COLUMN_NAME = 'college_id'
);
SET @sql = IF(@col = 0,
  'ALTER TABLE `scholarship_applications` ADD COLUMN `college_id` int(11) DEFAULT NULL',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql;
EXECUTE dandiya_stmt;
DEALLOCATE PREPARE dandiya_stmt;

SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'scholarship_applications'
     AND INDEX_NAME = 'idx_college_id'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE `scholarship_applications` ADD KEY `idx_college_id` (`college_id`)',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql;
EXECUTE dandiya_stmt;
DEALLOCATE PREPARE dandiya_stmt;

INSERT IGNORE INTO `schema_migrations` (`id`) VALUES ('2026_10_09_colleges');
