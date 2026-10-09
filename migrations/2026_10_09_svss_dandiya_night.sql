-- SVSS Dandiya Night
-- Run on the existing registration database (local: dandiyanew).
-- Safe to run more than once. Does not delete registrations or payment keys.
--
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root dandiyanew < migrations/2026_10_09_svss_dandiya_night.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` varchar(120) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- helpers: each block checks information_schema, then prepares one statement
-- ---------------------------------------------------------------------------

-- app_settings primary key (collapse duplicate keys if the import had none)
SET @settings_pk = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'app_settings' AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql = IF(@settings_pk = 0,
  'CREATE TABLE `app_settings_mig` (
     `setting_key` varchar(80) NOT NULL,
     `setting_value` longtext DEFAULT NULL,
     `updated_by` varchar(20) DEFAULT NULL,
     `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`setting_key`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF(@settings_pk = 0,
  'INSERT INTO `app_settings_mig` (`setting_key`, `setting_value`, `updated_by`, `updated_at`)
   SELECT `setting_key`, `setting_value`, `updated_by`, `updated_at`
     FROM (
       SELECT `setting_key`, `setting_value`, `updated_by`, `updated_at`,
              ROW_NUMBER() OVER (PARTITION BY `setting_key` ORDER BY `updated_at` DESC) AS rn
         FROM `app_settings`
     ) ranked
    WHERE rn = 1',
  'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF(@settings_pk = 0, 'DROP TABLE `app_settings`', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @sql = IF(@settings_pk = 0, 'RENAME TABLE `app_settings_mig` TO `app_settings`', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- admins: photo / password already in the dump; primary key and auto increment were not
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'photo');
SET @sql = IF(@col = 0, 'ALTER TABLE `admins` ADD COLUMN `photo` varchar(255) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'password_hash');
SET @sql = IF(@col = 0, 'ALTER TABLE `admins` ADD COLUMN `password_hash` varchar(255) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'last_login');
SET @sql = IF(@col = 0, 'ALTER TABLE `admins` ADD COLUMN `last_login` datetime DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'login_count');
SET @sql = IF(@col = 0, 'ALTER TABLE `admins` ADD COLUMN `login_count` int(11) DEFAULT 0', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `admins` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND INDEX_NAME = 'phone');
SET @sql = IF(@idx = 0, 'ALTER TABLE `admins` ADD UNIQUE KEY `phone` (`phone`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- id 0 cannot stay once AUTO_INCREMENT is enabled; move that admin to the next free id
SET @zero_admin = (SELECT COUNT(*) FROM `admins` WHERE `id` = 0);
SET @next_admin = (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `admins`);
SET @sql = IF(@zero_admin > 0, CONCAT('UPDATE `admins` SET `id` = ', @next_admin, ' WHERE `id` = 0'), 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `admins` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- coupons
SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'coupons' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `coupons` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'coupons' AND INDEX_NAME = 'code');
SET @sql = IF(@idx = 0, 'ALTER TABLE `coupons` ADD UNIQUE KEY `code` (`code`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'coupons' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `coupons` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- event_checkins
SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_checkins' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `event_checkins` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_checkins' AND INDEX_NAME = 'app_station');
SET @sql = IF(@idx = 0, 'ALTER TABLE `event_checkins` ADD UNIQUE KEY `app_station` (`application_id`, `station`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_checkins' AND INDEX_NAME = 'station');
SET @sql = IF(@idx = 0, 'ALTER TABLE `event_checkins` ADD KEY `station` (`station`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_checkins' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `event_checkins` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- notification_logs
SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notification_logs' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `notification_logs` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notification_logs' AND INDEX_NAME = 'idx_channel_created');
SET @sql = IF(@idx = 0, 'ALTER TABLE `notification_logs` ADD KEY `idx_channel_created` (`channel`, `created_at`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notification_logs' AND INDEX_NAME = 'idx_status_created');
SET @sql = IF(@idx = 0, 'ALTER TABLE `notification_logs` ADD KEY `idx_status_created` (`status`, `created_at`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notification_logs' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `notification_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- razorpay_webhook_logs
SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'razorpay_webhook_logs' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `razorpay_webhook_logs` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'razorpay_webhook_logs' AND INDEX_NAME = 'order_id');
SET @sql = IF(@idx = 0, 'ALTER TABLE `razorpay_webhook_logs` ADD KEY `order_id` (`order_id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'razorpay_webhook_logs' AND INDEX_NAME = 'created_at');
SET @sql = IF(@idx = 0, 'ALTER TABLE `razorpay_webhook_logs` ADD KEY `created_at` (`created_at`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'razorpay_webhook_logs' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `razorpay_webhook_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- staff
SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `staff` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND INDEX_NAME = 'phone');
SET @sql = IF(@idx = 0, 'ALTER TABLE `staff` ADD UNIQUE KEY `phone` (`phone`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'referral_code');
SET @sql = IF(@col = 0, 'ALTER TABLE `staff` ADD COLUMN `referral_code` varchar(16) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND INDEX_NAME = 'referral_code');
SET @sql = IF(@idx = 0, 'ALTER TABLE `staff` ADD UNIQUE KEY `referral_code` (`referral_code`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `staff` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- students
SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `students` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND INDEX_NAME = 'phone');
SET @sql = IF(@idx = 0, 'ALTER TABLE `students` ADD UNIQUE KEY `phone` (`phone`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `students` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

-- scholarship_applications columns used by the Dandiya form
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'submitted_by_staff_id');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `submitted_by_staff_id` int(11) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'submitted_by_student_id');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `submitted_by_student_id` int(11) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'submitted_by_phone');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `submitted_by_phone` varchar(20) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'institution_type');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `institution_type` varchar(32) DEFAULT ''academia''', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'fee_base');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `fee_base` decimal(10,2) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'fee_platform');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `fee_platform` decimal(10,2) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'photo');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `photo` varchar(255) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'coupon_id');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `coupon_id` int(11) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'coupon_code');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `coupon_code` varchar(24) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'receipt_token');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `receipt_token` varchar(64) DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'payment_date');
SET @sql = IF(@col = 0, 'ALTER TABLE `scholarship_applications` ADD COLUMN `payment_date` datetime DEFAULT NULL', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

SET @pk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@pk = 0, 'ALTER TABLE `scholarship_applications` ADD PRIMARY KEY (`id`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND INDEX_NAME = 'receipt_token');
SET @sql = IF(@idx = 0, 'ALTER TABLE `scholarship_applications` ADD UNIQUE KEY `receipt_token` (`receipt_token`)', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;
SET @extra = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'id');
SET @sql = IF(@extra NOT LIKE '%auto_increment%', 'ALTER TABLE `scholarship_applications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'SET @dandiya_noop = 1');
PREPARE dandiya_stmt FROM @sql; EXECUTE dandiya_stmt; DEALLOCATE PREPARE dandiya_stmt;

ALTER TABLE `scholarship_applications` MODIFY `class` varchar(40) NOT NULL;

-- SVSS Dandiya Night copy and entry fee. Razorpay and SMS keys are left as they are.
INSERT INTO `app_settings` (`setting_key`, `setting_value`, `updated_by`) VALUES
('brand_name', 'SVSS Dandiya Night', 'migration'),
('zepto_from_name', 'SVSS Dandiya Night', 'migration'),
('exam_fee_1_4', '300', 'migration'),
('exam_fee_5_10', '300', 'migration'),
('exam_fee_flat', '300', 'migration'),
('landing_enabled', '1', 'migration'),
('landing_title', 'SVSS Dandiya Night', 'migration'),
('landing_subtitle', 'Entry registration · Shri Vetaleshwar Shikshan Sanstha', 'migration'),
('landing_about', 'SVSS Dandiya Night is organised by Shri Vetaleshwar Shikshan Sanstha at Latur College of Pharmacy, Hasegaon.\nRegister with your name, college and ticket type, then pay online. A seat is confirmed only after successful payment.\nPaid guests receive a pass number and a digital entry ticket with QR. Show that ticket at the gate.', 'migration'),
('landing_who', 'Students\nFaculty and staff\nAlumni\nGuests\nOne registration and one entry ticket per person', 'migration'),
('landing_dates', '17 Oct 2026, 6:00 pm\nEntry only with a paid digital ticket\nPass number is issued after payment', 'migration'),
('landing_how', 'Log in with your mobile number and SMS OTP\nFill your name, college and ticket type\nPay the entry fee through official Razorpay on this website\nOpen your pass number and ticket, and show the QR at the gate', 'migration'),
('landing_need', 'Full name of the guest\nCollege or organisation name\nTicket type (student, faculty / staff, alumni, or guest)', 'migration'),
('landing_helpline', 'Phone / WhatsApp: +91 99750 40405\nLatur College of Pharmacy, Hasegaon, Gurunathappa Bawage Knowledge City, Tq. Ausa, Dist. Latur 413512', 'migration'),
('landing_cta', 'Register for Dandiya Night', 'migration'),
('landing_venue', 'Latur College of Pharmacy, Hasegaon\nLatur, Maharashtra', 'migration'),
('landing_highlights', 'SVSS Dandiya Night\nDigital entry ticket\nPass number after payment\nEntry fee ₹300', 'migration'),
('landing_show_fees', '1', 'migration'),
('content_pack', 'svss_dandiya_night_v1', 'migration'),
('legal_checkbox_text', 'I have read the event details, declaration and rules. I confirm that the information is true, and I voluntarily accept these terms.', 'migration'),
('legal_declaration_html', '<h4 id="declaration">Guest Declaration</h4>\n<p>I register as a guest for SVSS Dandiya Night, organised by Shri Vetaleshwar Shikshan Sanstha at Latur College of Pharmacy, Hasegaon, through this official website.</p>\n<p>I understand that entry is confirmed only after successful payment of the published fee, and that a pass number and digital ticket are issued only for a paid registration.</p>\n<p>I will follow the organiser''s entry, safety and conduct rules. I confirm that the information in this form is true. I accept that the organiser may change the schedule or venue when reasonably necessary and will communicate through this website, registered mobile, or email.</p>\n<p>By selecting the acceptance checkbox and submitting this form, I give my informed consent and treat this electronic acceptance as my formal declaration.</p>', 'migration'),
('legal_terms_html', '<h4 id="terms">Rules — Terms and Conditions</h4>\n<p>These terms apply to guests registering for SVSS Dandiya Night organised by Shri Vetaleshwar Shikshan Sanstha (“the Organiser”).</p>\n<ol>\n<li><b>Voluntary registration.</b> Registration is voluntary. You confirm that you are authorised to submit this form.</li>\n<li><b>Accuracy.</b> Provide complete and correct information. The Organiser may cancel a pass if details are false or misleading.</li>\n<li><b>Fees.</b> The published entry fee and any gateway fee shown on this website apply at the time of payment. A pass is confirmed only after successful payment.</li>\n<li><b>Payment.</b> Pay only through the official Razorpay facility on this website. Keep your receipt and ticket. The Organiser is not responsible for payments to unofficial accounts or links.</li>\n<li><b>Pass and ticket.</b> After payment, this website issues a pass number and a digital entry ticket with a QR code. One paid registration is one entry. The ticket is not transferable unless the Organiser says otherwise.</li>\n<li><b>Entry.</b> Carry the digital ticket or its QR at the gate. Entry can be refused without a valid paid ticket, or if the same ticket has already been used.</li>\n<li><b>Venue and schedule.</b> The event is at the venue and time published on this website, unless officially changed. The Organiser may change venue or timings when reasonably necessary.</li>\n<li><b>Conduct.</b> Follow staff instructions and event rules. Misconduct may lead to removal without refund.</li>\n<li><b>Communication.</b> Keep your mobile number correct and check official messages.</li>\n<li><b>Postponement.</b> The Organiser may postpone, relocate, or cancel due to circumstances beyond reasonable control. Any change will be communicated through this website, SMS, WhatsApp, or email.</li>\n<li><b>Refunds.</b> Fees are handled according to the Organiser''s published refund policy. No refund is ordinarily payable for absence, false information, or rule violation.</li>\n<li><b>Governing law.</b> These terms are governed by the laws of India. Disputes are subject to courts of competent jurisdiction in Maharashtra.</li>\n<li><b>Electronic acceptance.</b> Selecting the checkbox and submitting the form is your valid consent and declaration.</li>\n</ol>', 'migration')
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`),
  `updated_by` = 'migration';

INSERT IGNORE INTO `schema_migrations` (`id`) VALUES ('2026_10_09_svss_dandiya_night');
