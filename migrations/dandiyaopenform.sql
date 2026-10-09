-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 09, 2026 at 03:30 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u750208840_hplcdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'Admin',
  `email` varchar(150) DEFAULT NULL,
  `role` enum('admin','superadmin') NOT NULL DEFAULT 'admin',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `phone`, `name`, `email`, `role`, `status`, `created_at`, `updated_at`, `last_login`, `login_count`, `photo`, `password_hash`) VALUES
(1, '919096463943', 'Admin', 'openform.info@gmail.com', 'superadmin', 'active', '2026-02-12 16:49:10', '2026-10-07 13:38:09', '2026-10-07 13:38:09', 95, 'storage/branding/admin_1.png', '$2y$10$yFgbiV2Yj5Vo6X6bwBTNs.FvXb/4Q5augrLKCH7nFecL3EeXXb062'),
(0, '919130831517', 'Sachin Patange', 'admin@agnipankh.in', '', 'active', '2026-08-31 17:47:42', '2026-08-31 17:53:20', NULL, 0, NULL, '$2y$10$jn/ez1YrEbtgiCzTtaCk8.s13hJEFzJcqQ9yOsK/vR9m40k3WQBxS');

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('brand_logo_file', 'storage/branding/logo.png', '919096463943', '2026-10-07 13:38:52'),
('brand_logo_url', '', '919096463943', '2026-09-04 18:10:29'),
('brand_name', 'Latur College of Pharmacy, Hasegaon', '919096463943', '2026-10-07 13:38:52'),
('brand_tagline_admin', 'Admin panel', '919096463943', '2026-09-04 18:10:29'),
('brand_tagline_staff', 'Staff panel', '919096463943', '2026-09-04 18:10:29'),
('content_pack', 'hplc_masterclass_2026_v5', '919096463943', '2026-10-07 13:38:52'),
('coupons_on_form', '0', '919096463943', '2026-10-07 13:38:52'),
('event_feedback_url', '', '919096463943', '2026-09-04 18:10:29'),
('event_group_url', '', '919096463943', '2026-09-04 18:10:29'),
('event_photos_url', '', '919096463943', '2026-09-04 18:10:29'),
('exam_fee_1_4', '5000', '919096463943', '2026-10-07 13:38:52'),
('exam_fee_5_10', '5000', '919096463943', '2026-10-07 13:38:52'),
('exam_fee_flat', '10000', '919096463943', '2026-10-07 13:38:52'),
('icard_photo_on_form', '0', '919096463943', '2026-10-07 13:38:52'),
('landing_about', 'Organised by the Professional Training Division, Latur College of Pharmacy, Hasegaon (SVSS Shri Vetaleshwar Shikshan Sanstha; NAAC B++).\r\nTrainer: Dr D. N. Wasmate (7+ years R&D, 6+ years academic experience).\r\nMorning theory (9:00 am–12:30 pm) and afternoon practical (1:30 pm–5:00 pm): instrumentation, QbD, gradient optimisation, stability-indicating methods, validation and troubleshooting.\r\nFee ₹5,000 includes expert instruction, course materials, certificate, case studies, lunch and refreshments. A seat is confirmed only after online payment — OTP login alone is not registration.', '919096463943', '2026-10-07 13:41:01'),
('landing_cta', 'Register for the workshop', '919096463943', '2026-10-07 13:38:52'),
('landing_dates', 'Dates: 28 September 2026 to 3 October 2026\r\nTheory: 9:00 am – 12:30 pm\r\nPractical: 1:30 pm – 5:00 pm\r\nDay 1 Instrumentation & theory · Day 2 Method scouting & QbD · Day 3 Gradient optimisation · Day 4 Stability-indicating methods · Day 5 Validation & troubleshooting', '919096463943', '2026-10-07 13:41:01'),
('landing_enabled', '1', '919096463943', '2026-10-07 13:38:52'),
('landing_helpline', 'Email: register@laturpharmacyworkshops.com\r\nPhone / WhatsApp: +91 99750 40405\r\nLatur College of Pharmacy, Hasegaon, Gurunathappa Bawage Knowledge City, Tq. Ausa, Dist. Latur 413512', '919096463943', '2026-10-07 13:41:01'),
('landing_highlights', '5-day masterclass\r\nHands-on HPLC\r\nCertificate included\r\nFee ₹5,000', '919096463943', '2026-10-07 13:41:01'),
('landing_how', 'Log in with your mobile number and SMS OTP\r\nFill name, organisation and role\r\nPay ₹5,000 through official Razorpay on this website and save the receipt\r\nCarry the receipt / pass on workshop days at Latur College of Pharmacy, Hasegaon', '919096463943', '2026-10-07 13:41:01'),
('landing_need', 'Full name of the participant\r\nCollege / organisation name\r\nRole (M.Pharm, B.Pharm, faculty, researcher, scholar, or other)', '919096463943', '2026-10-07 13:41:01'),
('landing_poster_file', '', '919096463943', '2026-10-07 13:38:52'),
('landing_poster_url', '', '919096463943', '2026-10-07 13:38:52'),
('landing_show_fees', '1', '919096463943', '2026-10-07 13:38:52'),
('landing_subtitle', 'Method Development & Validation', '919096463943', '2026-10-07 13:41:38'),
('landing_title', 'HPLC Project Enrollment', '919096463943', '2026-10-07 13:41:38'),
('landing_venue', 'Latur College of Pharmacy, Hasegaon\r\nLatur, Maharashtra', '919096463943', '2026-10-07 13:41:01'),
('landing_whatsapp', '9975040405', '919096463943', '2026-10-07 13:38:52'),
('landing_who', 'Analytical chemists\r\nQC / QA analysts\r\nR&D scientists\r\nLab managers\r\nPharmacy faculty and PG / research students\r\nOne registration per participant', '919096463943', '2026-10-07 13:41:01'),
('legal_checkbox_text', 'I have read the workshop details, declaration and rules. I confirm that the information is true, and I voluntarily accept these terms.', '919096463943', '2026-10-07 13:38:52'),
('legal_declaration_html', '<h4 id=\"declaration\">Participant Declaration</h4>\r\n<p>I register as a participant in the Academia &amp; Industry Workshop on Advanced HPLC Method Development &amp; Validation, organised by Latur College of Pharmacy, Hasegaon, through this official website.</p>\r\n<p>I understand that the programme includes theory and practical laboratory sessions from 28 September to 3 October 2026, and that a seat is confirmed only after successful payment of the published fee.</p>\r\n<p>I will follow laboratory safety instructions and the organiser’s rules. I confirm that the information in this form is true. I accept that the organiser may change schedule or venue when reasonably necessary and will communicate through this website, registered mobile, or email.</p>\r\n<p>By selecting the acceptance checkbox and submitting this form, I give my informed consent and treat this electronic acceptance as my formal declaration.</p>', '919096463943', '2026-10-07 13:41:01'),
('legal_declaration_url', '', '919096463943', '2026-09-04 18:10:29'),
('legal_rules_url', '', '919096463943', '2026-09-04 18:10:29'),
('legal_terms_html', '<h4 id=\"terms\">Rules — Terms and Conditions</h4>\r\n<p>These terms apply to participants in the HPLC workshop organised by Latur College of Pharmacy, Hasegaon (“the Organiser”).</p>\r\n<ol>\r\n    <li><b>Voluntary participation.</b> Registration is voluntary. You confirm that you are authorised to submit this application.</li>\r\n    <li><b>Accuracy.</b> Provide complete and correct information. The Organiser may cancel registration or a certificate if details are false or misleading.</li>\r\n    <li><b>Fees.</b> The published registration fee and any gateway fee shown on this website apply at the time of payment. A seat is confirmed only after successful payment.</li>\r\n    <li><b>Payment.</b> Pay only through the official Razorpay facility on this website. Keep your receipt. The Organiser is not responsible for payments to unofficial accounts or links.</li>\r\n    <li><b>Fee inclusions.</b> The fee includes instruction, course materials, certificate, case studies, and lunch as published on this website. Travel and stay are the participant’s responsibility unless stated otherwise.</li>\r\n    <li><b>Venue and schedule.</b> Sessions run at Latur College of Pharmacy, Hasegaon, from 28 September to 3 October 2026, unless officially changed. The Organiser may change venue or timings when reasonably necessary.</li>\r\n    <li><b>Laboratory conduct.</b> Follow trainer and staff instructions, safety rules, and dress requirements for practical sessions. Misconduct may lead to removal without refund.</li>\r\n    <li><b>Communication.</b> Keep your mobile and email correct and check official messages. Missing a message is not ordinarily a reason for a special session or refund.</li>\r\n    <li><b>Postponement.</b> The Organiser may postpone, relocate, or cancel due to circumstances beyond reasonable control. Any change will be communicated through this website, SMS, WhatsApp, or email.</li>\r\n    <li><b>Refunds.</b> Fees are handled according to the Organiser’s published refund policy. No refund is ordinarily payable for absence, false information, or rule violation.</li>\r\n    <li><b>Certificates.</b> Certificates are issued for eligible participants who complete the workshop as announced by the Organiser.</li>\r\n    <li><b>Governing law.</b> These terms are governed by the laws of India. Disputes are subject to courts of competent jurisdiction in Maharashtra.</li>\r\n    <li><b>Electronic acceptance.</b> Selecting the checkbox and submitting the form is your valid consent and declaration.</li>\r\n</ol>', '919096463943', '2026-10-07 13:41:01'),
('msg91_auth_key', '510004AvZZf08aEY6a36a548P1', '919096463943', '2026-09-04 18:10:29'),
('msg91_dlt_content', 'Your OTP for account verification is ##var1##. Valid for ##var2## minutes. Do not share it. Atharv Media\r\n', '919096463943', '2026-09-04 18:10:29'),
('msg91_dlt_template_id', '1007133380550115309', '919096463943', '2026-09-04 18:10:29'),
('msg91_sender_id', 'ATHMDA', '919096463943', '2026-09-04 18:10:29'),
('msg91_template_id', '6a3107710151eb0bf500a2a3', '919096463943', '2026-09-04 18:10:29'),
('msg91_template_name', 'athmdaotp', '919096463943', '2026-09-04 18:10:29'),
('otp_email_html', '<div style=\"font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#f4f6f9;\">\r\n  <div style=\"background:#ffffff;border-radius:12px;padding:28px 24px;border-top:4px solid #0d3b8c;\">\r\n    <p style=\"margin:0 0 8px;font-size:12px;letter-spacing:.08em;color:#f15a22;font-weight:700;\">AGNIPANKH</p>\r\n    <h2 style=\"margin:0 0 16px;color:#0d3b8c;font-size:22px;\">Your login OTP</h2>\r\n    <p style=\"margin:0 0 12px;color:#334155;font-size:15px;line-height:1.5;\">Use this one-time password to log in. Do not share it with anyone.</p>\r\n    <p style=\"margin:16px 0;font-size:32px;letter-spacing:8px;font-weight:800;color:#0d3b8c;text-align:center;\">{{otp}}</p>\r\n    <p style=\"margin:0;color:#64748b;font-size:13px;\">Valid for {{minutes}} minutes.</p>\r\n  </div>\r\n</div>', '919096463943', '2026-09-04 18:10:29'),
('otp_email_subject', 'Your HPLC workshop login OTP', '919096463943', '2026-09-04 18:10:29'),
('otp_validity_minutes', '5', '919096463943', '2026-09-04 18:10:29'),
('payment_test_mode', '0', '919096463943', '2026-09-10 06:42:01'),
('platform_fee_payer', 'admin', '919096463943', '2026-10-07 13:38:52'),
('platform_fee_percent', '4', '919096463943', '2026-09-04 18:10:29'),
('razorpay_key_id', 'rzp_live_D53J9UWwYtGimn', '919096463943', '2026-09-04 18:10:29'),
('razorpay_key_secret', 'w0SnqzH2SOOIc0gnUR7cYO3r', '919096463943', '2026-09-04 18:10:29'),
('razorpay_mode', 'live', '919096463943', '2026-09-04 18:10:29'),
('razorpay_webhook_secret', 'w0SnqzH2SOOIc0gnUR7cYO3r', '919096463943', '2026-09-04 18:10:29'),
('staff_ask_on_form', '0', '919096463943', '2026-10-07 13:38:52'),
('zepto_bounce_address', 'noreply@openform.in', '919096463943', '2026-09-04 18:10:29'),
('zepto_data_center', 'in', '919096463943', '2026-09-04 18:10:29'),
('zepto_from_email', 'noreply@openform.in', '919096463943', '2026-09-04 18:10:29'),
('zepto_from_name', 'HPLC Workshop', '919096463943', '2026-10-07 13:38:52'),
('zepto_reply_to_email', 'noreply@openform.in', '919096463943', '2026-09-04 18:10:29'),
('zepto_send_mail_token', 'Zoho-enczapikey PHtE6r0PEO253mAq8EAFsae/H5PxNI0r9O9mfVJPsIlGCPIDG01Qqtt4lTK+/kp4VvcQQP+awIlvubnJs+OELDm5MT4ZDmqyqK3sx/VYSPOZsbq6x00bsVUed0DcV4DtdNVr1CHTudvZNA==', '919096463943', '2026-09-04 18:10:29'),
('zepto_send_method', 'api', '919096463943', '2026-09-04 18:10:29');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(24) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 1.00,
  `max_uses` int(11) NOT NULL DEFAULT 1,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `note` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Coupon codes that set the registration fee';

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `fee_amount`, `max_uses`, `used_count`, `status`, `note`, `created_at`) VALUES
(1, 'LCOPS', 1000.00, 50, 24, 'active', NULL, '2026-09-28 05:00:56'),
(2, 'STU1000', 4000.00, 10, 2, 'active', NULL, '2026-09-28 05:01:45');

-- --------------------------------------------------------

--
-- Table structure for table `event_checkins`
--

CREATE TABLE `event_checkins` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `station` varchar(40) NOT NULL,
  `checked_in_at` datetime NOT NULL DEFAULT current_timestamp(),
  `checked_in_by` varchar(120) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_checkins`
--

INSERT INTO `event_checkins` (`id`, `application_id`, `station`, `checked_in_at`, `checked_in_by`, `note`) VALUES
(1, 3, 'icard', '2026-09-05 13:57:58', 'admin:Admin', NULL),
(2, 3, 'kit', '2026-09-05 13:58:12', 'admin:Admin', NULL),
(3, 3, 'qr', '2026-09-05 13:58:15', 'admin:Admin', NULL),
(4, 3, 'attendance', '2026-09-05 13:58:27', 'admin:Admin', NULL),
(5, 3, 'feedback', '2026-09-05 13:58:37', 'admin:Admin', NULL),
(6, 5, 'icard', '2026-09-09 03:54:52', 'admin:Admin', NULL),
(7, 5, 'kit', '2026-09-09 03:55:06', 'admin:Admin', NULL),
(8, 5, 'ecert', '2026-09-09 03:55:19', 'admin:Admin', NULL),
(9, 5, 'attendance', '2026-09-09 03:55:28', 'admin:Admin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `channel` enum('sms','email') NOT NULL,
  `status` enum('ok','error') NOT NULL DEFAULT 'error',
  `recipient` varchar(180) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `http_code` int(11) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_logs`
--

INSERT INTO `notification_logs` (`id`, `channel`, `status`, `recipient`, `message`, `http_code`, `response`, `created_at`) VALUES
(1, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669656d7344494b6676724d\",\"type\":\"success\"}', '2026-09-05 07:49:30'),
(2, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669656d49486b5952495378\",\"type\":\"success\"}', '2026-09-05 08:05:34'),
(3, 'sms', 'ok', '919860777105', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669656d566d6570766c3866\",\"type\":\"success\"}', '2026-09-05 08:18:14'),
(4, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669656d5955773763474d35\",\"type\":\"success\"}', '2026-09-05 08:21:47'),
(5, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669676b564439476131327a\",\"type\":\"success\"}', '2026-09-07 06:18:30'),
(6, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669676b5a7a7a6170553441\",\"type\":\"success\"}', '2026-09-07 06:22:26'),
(7, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669676e644f745431343574\",\"type\":\"success\"}', '2026-09-07 08:34:41'),
(8, 'sms', 'ok', '919765412889', 'OTP SMS sent via MSG91', 200, '{\"message\":\"366969696c726761346e4859\",\"type\":\"success\"}', '2026-09-09 03:42:18'),
(9, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669696f617265644f65664d\",\"type\":\"success\"}', '2026-09-09 09:31:19'),
(10, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696a6836684d6e35736151\",\"type\":\"success\"}', '2026-09-10 03:28:08'),
(11, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696a6f7677774748453245\",\"type\":\"success\"}', '2026-09-10 09:52:23'),
(12, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696a736577725258357751\",\"type\":\"success\"}', '2026-09-10 13:35:23'),
(13, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6c7955507238794856\",\"type\":\"success\"}', '2026-09-11 06:55:47'),
(14, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6c43526a324e573042\",\"type\":\"success\"}', '2026-09-11 06:59:44'),
(15, 'sms', 'ok', '917020129093', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6c456c664978485373\",\"type\":\"success\"}', '2026-09-11 07:01:12'),
(16, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6c453442554e664577\",\"type\":\"success\"}', '2026-09-11 07:01:56'),
(17, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6d414a386278597573\",\"type\":\"success\"}', '2026-09-11 07:57:36'),
(18, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6d424e676733764f33\",\"type\":\"success\"}', '2026-09-11 07:58:40'),
(19, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6d43684b7376694d42\",\"type\":\"success\"}', '2026-09-11 07:59:08'),
(20, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6e6e376e346e6f7430\",\"type\":\"success\"}', '2026-09-11 08:44:59'),
(21, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6e746b654a706a6e6c\",\"type\":\"success\"}', '2026-09-11 08:50:11'),
(22, 'sms', 'ok', '919975040405', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6f6161714656533949\",\"type\":\"success\"}', '2026-09-11 09:31:01'),
(23, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6f75506f78666e664e\",\"type\":\"success\"}', '2026-09-11 09:51:42'),
(24, 'sms', 'ok', '917262856002', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6f7657616837684242\",\"type\":\"success\"}', '2026-09-11 09:52:50'),
(25, 'sms', 'ok', '917887435417', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6f784f524578436630\",\"type\":\"success\"}', '2026-09-11 09:54:41'),
(26, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696b6f4772567879506c55\",\"type\":\"success\"}', '2026-09-11 10:03:18'),
(27, 'sms', 'ok', '919860777105', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36696c7451336a3165657043\",\"type\":\"success\"}', '2026-09-12 15:13:55'),
(28, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669716755353667556d4d4e\",\"type\":\"success\"}', '2026-09-17 02:17:57'),
(29, 'sms', 'ok', '919373264072', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36697a6d66316e6e757a676a\",\"type\":\"success\"}', '2026-09-26 07:36:53'),
(30, 'sms', 'ok', '919373264072', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36697a6d327775534e6b7235\",\"type\":\"success\"}', '2026-09-26 08:24:23'),
(31, 'sms', 'ok', '917038398985', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36697a6d364f4c6261686a30\",\"type\":\"success\"}', '2026-09-26 08:28:41'),
(32, 'sms', 'ok', '919373264072', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36697a6f485955597249524b\",\"type\":\"success\"}', '2026-09-26 10:04:51'),
(33, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426a424d7a7678484e67\",\"type\":\"success\"}', '2026-09-28 04:58:39'),
(34, 'sms', 'ok', '917756046834', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426a446466414e434f35\",\"type\":\"success\"}', '2026-09-28 05:00:04'),
(35, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426a46494a4955595038\",\"type\":\"success\"}', '2026-09-28 05:02:35'),
(36, 'sms', 'ok', '919960471286', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c736d57644e675148\",\"type\":\"success\"}', '2026-09-28 06:49:13'),
(37, 'sms', 'ok', '917028074176', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c734b433577453877\",\"type\":\"success\"}', '2026-09-28 06:49:37'),
(38, 'sms', 'ok', '918208843746', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c734c734656594c49\",\"type\":\"success\"}', '2026-09-28 06:49:38'),
(39, 'sms', 'ok', '918010745588', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c743666316a456236\",\"type\":\"success\"}', '2026-09-28 06:50:58'),
(40, 'sms', 'ok', '918530404164', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c756179616d7a6841\",\"type\":\"success\"}', '2026-09-28 06:51:01'),
(41, 'sms', 'ok', '917666707048', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c756279656d465870\",\"type\":\"success\"}', '2026-09-28 06:51:02'),
(42, 'sms', 'ok', '919371541917', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c7541666c334d3774\",\"type\":\"success\"}', '2026-09-28 06:51:27'),
(43, 'sms', 'ok', '919404671030', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c75467a6175694263\",\"type\":\"success\"}', '2026-09-28 06:51:32'),
(44, 'sms', 'ok', '918767193286', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c764567786e523678\",\"type\":\"success\"}', '2026-09-28 06:52:31'),
(45, 'sms', 'ok', '918459507062', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c764b694a324d4435\",\"type\":\"success\"}', '2026-09-28 06:52:37'),
(46, 'sms', 'ok', '919322757325', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c7650656672335a4c\",\"type\":\"success\"}', '2026-09-28 06:52:42'),
(47, 'sms', 'ok', '917757059319', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c7632767859355438\",\"type\":\"success\"}', '2026-09-28 06:52:54'),
(48, 'sms', 'ok', '917498411101', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c763436634d356453\",\"type\":\"success\"}', '2026-09-28 06:52:56'),
(49, 'sms', 'ok', '919359633916', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c77486b3644473853\",\"type\":\"success\"}', '2026-09-28 06:53:34'),
(50, 'sms', 'ok', '917620413402', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c774943706b4a5a58\",\"type\":\"success\"}', '2026-09-28 06:53:35'),
(51, 'sms', 'ok', '919529970759', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c79624a4d36637735\",\"type\":\"success\"}', '2026-09-28 06:55:02'),
(52, 'sms', 'ok', '918275087507', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c7956497959764239\",\"type\":\"success\"}', '2026-09-28 06:55:48'),
(53, 'sms', 'ok', '917666707048', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c7a74655a614e6730\",\"type\":\"success\"}', '2026-09-28 06:56:20'),
(54, 'sms', 'ok', '917620413402', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c7a476a475354736e\",\"type\":\"success\"}', '2026-09-28 06:56:33'),
(55, 'sms', 'ok', '918308842751', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c4430736c37377965\",\"type\":\"success\"}', '2026-09-28 07:00:00'),
(56, 'sms', 'ok', '919112964631', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c44726d654469714b\",\"type\":\"success\"}', '2026-09-28 07:00:18'),
(57, 'sms', 'ok', '919657766436', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c44314f7573695734\",\"type\":\"success\"}', '2026-09-28 07:00:53'),
(58, 'sms', 'ok', '918149446518', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c464544613133466a\",\"type\":\"success\"}', '2026-09-28 07:02:31'),
(59, 'sms', 'ok', '919657766436', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c476470456b777675\",\"type\":\"success\"}', '2026-09-28 07:03:04'),
(60, 'sms', 'ok', '918275087507', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c47694f42746c5245\",\"type\":\"success\"}', '2026-09-28 07:03:09'),
(61, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426c4d6d684a7a716838\",\"type\":\"success\"}', '2026-09-28 07:09:13'),
(62, 'sms', 'ok', '918275087507', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426d426f497354716b4b\",\"type\":\"success\"}', '2026-09-28 07:58:15'),
(63, 'sms', 'ok', '919322757325', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426e747a51776a536f56\",\"type\":\"success\"}', '2026-09-28 08:50:26'),
(64, 'sms', 'ok', '918530404164', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426f70335a4671496452\",\"type\":\"success\"}', '2026-09-28 09:46:55'),
(65, 'sms', 'ok', '919657766436', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426f716542626b646f47\",\"type\":\"success\"}', '2026-09-28 09:47:05'),
(66, 'sms', 'ok', '918308842751', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426f726b6f7a506a594d\",\"type\":\"success\"}', '2026-09-28 09:48:11'),
(67, 'sms', 'ok', '919028056771', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426f724f78676a656b46\",\"type\":\"success\"}', '2026-09-28 09:48:41'),
(68, 'sms', 'ok', '917666707048', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426f776849434d6c6838\",\"type\":\"success\"}', '2026-09-28 09:53:08'),
(69, 'sms', 'ok', '917219417200', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426f7a6b476265587446\",\"type\":\"success\"}', '2026-09-28 09:56:11'),
(70, 'sms', 'ok', '917350953317', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669426f46674c795447726c\",\"type\":\"success\"}', '2026-09-28 10:02:07'),
(71, 'sms', 'ok', '917219417200', 'OTP SMS sent via MSG91', 200, '{\"message\":\"366942724a76596465326f56\",\"type\":\"success\"}', '2026-09-28 13:06:22'),
(72, 'sms', 'ok', '919423654413', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669427258436d644e727647\",\"type\":\"success\"}', '2026-09-28 13:20:29'),
(73, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"36694273366b4e4346623162\",\"type\":\"success\"}', '2026-09-28 14:28:11'),
(74, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669436f37536a414955314e\",\"type\":\"success\"}', '2026-09-29 10:29:45'),
(75, 'sms', 'ok', '917666707048', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669437572756139576c4b6c\",\"type\":\"success\"}', '2026-09-29 15:48:22'),
(76, 'sms', 'ok', '918262096140', 'OTP SMS sent via MSG91', 200, '{\"message\":\"3669447247726f456c705467\",\"type\":\"success\"}', '2026-09-30 13:03:18'),
(77, 'sms', 'ok', '919423654413', 'OTP SMS sent via MSG91', 200, '{\"message\":\"366944744a446e4854463537\",\"type\":\"success\"}', '2026-09-30 15:06:30'),
(78, 'sms', 'ok', '919404590319', 'OTP SMS sent via MSG91', 200, '{\"message\":\"366944744c416f7272455a4f\",\"type\":\"success\"}', '2026-09-30 15:08:27'),
(79, 'sms', 'ok', '919096463943', 'OTP SMS sent via MSG91', 200, '{\"message\":\"366a616866353542377a4231\",\"type\":\"success\"}', '2026-10-01 02:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `razorpay_webhook_logs`
--

CREATE TABLE `razorpay_webhook_logs` (
  `id` int(11) NOT NULL,
  `event_name` varchar(80) DEFAULT NULL,
  `payment_id` varchar(64) DEFAULT NULL,
  `order_id` varchar(64) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `result` varchar(40) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_applications`
--

CREATE TABLE `scholarship_applications` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `class` varchar(40) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `mobile` varchar(10) NOT NULL,
  `exam_fee` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `receipt_token` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Application created (IST)',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last updated (IST)',
  `submitted_by_staff_id` int(11) DEFAULT NULL,
  `submitted_by_student_id` int(11) DEFAULT NULL,
  `submitted_by_phone` varchar(20) DEFAULT NULL,
  `institution_type` varchar(32) DEFAULT 'academia' COMMENT 'academia, industry or other',
  `fee_base` decimal(10,2) DEFAULT NULL COMMENT 'Registration fee before gateway',
  `fee_platform` decimal(10,2) DEFAULT NULL COMMENT 'Payment gateway / platform fee',
  `photo` varchar(255) DEFAULT NULL COMMENT 'I-Card photo relative path',
  `coupon_id` int(11) DEFAULT NULL,
  `coupon_code` varchar(24) DEFAULT NULL,
  `whatsapp` varchar(15) DEFAULT NULL,
  `pregnancy_month` tinyint(3) UNSIGNED DEFAULT NULL,
  `registration_type` varchar(32) DEFAULT NULL,
  `wife_name` varchar(150) DEFAULT NULL,
  `husband_name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scholarship_applications`
--

INSERT INTO `scholarship_applications` (`id`, `first_name`, `middle_name`, `last_name`, `class`, `school_name`, `district`, `city`, `mobile`, `exam_fee`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `payment_date`, `receipt_token`, `created_at`, `updated_at`, `submitted_by_staff_id`, `submitted_by_student_id`, `submitted_by_phone`, `institution_type`, `fee_base`, `fee_platform`, `photo`, `coupon_id`, `coupon_code`, `whatsapp`, `pregnancy_month`, `registration_type`, `wife_name`, `husband_name`, `address`) VALUES
(1, 'Sneha', 'Sanjay', 'Vairaglar', 'faculty', 'Latur college of pharmacy Hasegaon', '', '', '9096463943', 5000.00, 'pending', 'order_TcN8CXeqeSpmop', NULL, NULL, NULL, NULL, '2026-09-15 21:34:56', '2026-09-15 21:34:56', NULL, NULL, '919096463943', 'academia', 5000.00, 200.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'DHANRAJ', '', 'WASMATE', 'm_pharm', 'Latur college of pharmacy hasegaon', '', '', '9096463943', 5000.00, 'pending', 'order_TfobJwYAS1JTsR', NULL, NULL, NULL, NULL, '2026-09-24 14:24:14', '2026-09-24 14:24:14', NULL, NULL, '919096463943', 'academia', 5000.00, 200.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Sayli', 'Satish', 'Udgirkar', 'm_pharm', 'Channabasweshwar Pharmacy college latur', '', '', '9373264072', 5000.00, 'pending', 'order_TgaPITxFR8756N', NULL, NULL, NULL, NULL, '2026-09-26 13:10:09', '2026-09-26 13:10:09', NULL, 9, '919373264072', 'academia', 5000.00, 200.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Sayli', 'Satish', 'Udgirkar', 'm_pharm', 'Channabasweshwar Pharmacy college latur', '', '', '9373264072', 5000.00, 'pending', 'order_TgaRuraduGHQx5', NULL, NULL, NULL, NULL, '2026-09-26 13:12:38', '2026-09-26 13:12:38', NULL, 9, '919373264072', 'academia', 5000.00, 200.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Sayli', 'Satish', 'Udgirkar', 'm_pharm', 'Channabasweshwar Pharmacy college latur', '', '', '9373264072', 4000.00, 'paid', 'order_TgctAOcUiPmDU9', 'pay_TgctXxkC3GT9U3', '9b0103152e16b8c9a587f629d3eaa24ad01461e63f623256ef2a0657a42b1baa', '2026-09-26 15:36:56', 'bde81281809486464ab0a8cb1775e676869b8cb8601fbbbf3d3ee8c4ac16b67d', '2026-09-26 15:35:49', '2026-09-26 15:36:56', NULL, 9, '919373264072', 'academia', 4000.00, 160.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Shoeb', 'Ayub', 'Shaikh', 'b_pharm', 'Latur college of pharmacy Hasegaon', '', '', '7028074176', 1000.00, 'paid', 'order_ThMfQCqgo29ZGG', 'pay_ThMfpSsjMW77hK', '3be54a0599dd795de6d5d6abc8434a89a4c207e1dc1100e8d8436fcdf41f99ab', '2026-09-28 12:23:39', '9cdaa80df19d1ee2bbb39410cb3ca19c6b118aace0bc66efcce72abbb1bbfe02', '2026-09-28 12:22:42', '2026-09-28 12:23:39', NULL, 12, '917028074176', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Shruti', 'Baban', 'Jagtap', 'b_pharm', 'Latur college of pharmacy hasegaon', '', '', '8010745588', 1000.00, 'paid', 'order_ThMg6GIjBybYsc', 'pay_ThMgcRF5y99An4', '078dd2c3e7544a8d8f0c903071af607e186533ee35dcdc4eb1ddecad4b3ee612', '2026-09-28 12:24:12', 'b36bbc42daf52a0b23097e63268bec585c15c92cb6295d0d0c938c7bd51cb34f', '2026-09-28 12:23:21', '2026-09-28 12:24:12', NULL, 14, '918010745588', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Kapade', 'Aishwarya', 'Chandrakant', 'b_pharm', 'Latur college of pharmacy Hasegaon', '', '', '9404671030', 1000.00, 'pending', 'order_ThMgPQkeuVXzrX', NULL, NULL, NULL, NULL, '2026-09-28 12:23:38', '2026-09-28 12:23:38', NULL, 17, '919404671030', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Payal', 'Satish', 'Lale', 'b_pharm', 'Latur collage of pharmacy hasegaon', '', '', '9371541917', 1000.00, 'pending', 'order_ThMgwtT47LsGmH', NULL, NULL, NULL, NULL, '2026-09-28 12:24:09', '2026-09-28 12:24:09', NULL, 18, '919371541917', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Yusuf', 'Jainoddin', 'Pathan', 'b_pharm', 'Latur College of Pharmacy Hasegaon', '', '', '9960471286', 1000.00, 'paid', 'order_ThMjkcPqVDLPnC', 'pay_ThMkCT4bLPGxwS', '02c51c2d4356df2b91cab039554a12cbf6f87faacbaa473b6d7c2bf9ac68cd4c', '2026-09-28 12:27:54', '36418f14406471a90fe6d0c0a4120ea2a403608fe2e253f7adb65f1fa3653e79', '2026-09-28 12:26:48', '2026-09-28 12:27:54', NULL, 11, '919960471286', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Payal', 'Satish', 'Lale', 'b_pharm', 'Latur college of pharmacy hasegaon', '', '', '8010745588', 1000.00, 'paid', 'order_ThMk0spbsj0rHf', 'pay_ThMk9tAOGPLTPY', 'a408d34fe9eb0027aa69f56346eff350b9612f6a7ca11714a28b6c93559890f9', '2026-09-28 12:27:36', '6919ab4bed095c68f657379b42147463cec9feb8e5e8e2ddb80dbe937679472a', '2026-09-28 12:27:03', '2026-09-28 12:27:36', NULL, 14, '918010745588', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Salman', 'Rajasab', 'Shaikh', 'b_pharm', 'Latur collage of pharmacy, Hasegaon', '', '', '8530404164', 1000.00, 'pending', 'order_ThMkBonPbAJw80', NULL, NULL, NULL, NULL, '2026-09-28 12:27:13', '2026-09-28 12:27:13', NULL, 16, '918530404164', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Swapnil', 'Mahadu', 'Lavte', 'b_pharm', 'Latur college of pharmacy Hasegaon', '', '', '7666707048', 1000.00, 'paid', 'order_ThMkdshNlmBpU2', NULL, NULL, NULL, NULL, '2026-09-28 12:27:39', '2026-09-29 10:37:14', NULL, 15, '917666707048', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Vaibhavi', 'Vitthal', 'Muktapure', 'b_pharm', 'Latur College Pharmacy,  Hasegaon.', '', '', '9529970759', 1000.00, 'paid', 'order_ThMl4aLXMNncOy', 'pay_ThMlFXoRLY8o2u', 'e920e1a15c9f673c1c3fa1d2c6e6e2299ba908092124650cbba9fa565f858fa3', '2026-09-28 12:28:42', '76c72c9d6eb690cd87be571a320ed4d2a9170708599567f42cded96ef819f7e6', '2026-09-28 12:28:03', '2026-09-28 12:28:42', NULL, 26, '919529970759', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Namrata', 'Pandurang', 'Dolare', 'b_pharm', 'Latur college of pharmacy,Hasegaon', '', '', '8275087507', 1000.00, 'paid', 'order_ThMlUKrrNbn3CZ', NULL, NULL, NULL, NULL, '2026-09-28 12:28:27', '2026-09-29 10:37:42', NULL, 27, '918275087507', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'Kapade', 'Aishwarya', 'Chandrakant', 'b_pharm', 'Latur college of pharmacy hasegaon', '', '', '8010745588', 1000.00, 'paid', 'order_ThMm8ijyjAgwcn', 'pay_ThMmLvVCOYu2Hv', 'f4d76d7ef20ddbb6e9cda6fc0d3045491424f6c5c5093878b7b8af88f8d8a063', '2026-09-28 12:29:38', '190c5e1488cceb30761e4a183f2e006deea76779c1f272fbd3ba82f379b92c6c', '2026-09-28 12:29:04', '2026-09-28 12:29:38', NULL, 14, '918010745588', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'ATUL', 'SANJAYRAO', 'JADHAV', 'b_pharm', 'Latur college of pharmacy, Latur', '', '', '7757059319', 1000.00, 'paid', 'order_ThMmSpHqujS5ZA', 'pay_ThMmxPZN4VueH5', 'e11b703177316f2430db70308838c9aebfea7b901a5c1fc94911b0e789edc74b', '2026-09-28 12:30:16', 'ae2593d8a59297893edf5dbea2c114dc3a20ecdfe9da840fa7e62057b4eab542', '2026-09-28 12:29:21', '2026-09-28 12:30:16', NULL, 22, '917757059319', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'SAGAR', 'APPASAHEB', 'GAJENDRA', 'b_pharm', 'LATUR COLLEGE OF PHARMACY,LATUR', '', '', '8767193286', 1000.00, 'paid', 'order_ThMmlnP80YbAyW', 'pay_ThMmwocw0XGETV', 'dc982ca5c178a18d8d7e3d90c1439a731b8db5a8e4add655b88eeb4310fced68', '2026-09-28 12:30:16', 'af7822f63b2b7ef99dc3f8cfee71fbe30ea835db8d7af1ebcde2d382d5d0d101', '2026-09-28 12:29:40', '2026-09-28 12:30:16', NULL, 19, '918767193286', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'Pravin', 'Shriram', 'Kadam', 'b_pharm', 'Latur college of pharmacy,Latur, katpur road', '', '', '7620413402', 1000.00, 'paid', 'order_ThMn6lMZpMB3JQ', 'pay_ThMoRYRacNwQrx', 'fadb0762a9c5277b7c9480077f62b3d21a0100dd90af4e3252709d0a2052c3bc', '2026-09-28 12:32:02', 'fdfd72b195f96e7337beed96dfaf4a7176321857341cc3b5e3b4ede3bc014722', '2026-09-28 12:29:59', '2026-09-28 12:32:02', NULL, 25, '917620413402', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'SHUBHAM', 'DILIP', 'ACHMARE', 'b_pharm', 'LATUR COLLEGE OF PHARMACY LATUR', '', '', '7498411101', 1000.00, 'paid', 'order_ThMnxLxusE3cQm', 'pay_ThMoHacIGS9kkl', '8782efb1e4270fbb2827ef91ee99e3b89cb3a6571863175906ad6ac31138d943', '2026-09-28 12:31:36', '8679e5a4921bc6f67e6e88e8a81b8b42316b737f5736d3bb58ab2a589a6579be', '2026-09-28 12:30:47', '2026-09-28 12:31:36', NULL, 23, '917498411101', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'Aditi', 'Rajpal', 'Chavan', 'b_pharm', 'Latur college of pharmacy latur', '', '', '9322757325', 1000.00, 'pending', 'order_ThMoTXpqrnUudA', NULL, NULL, NULL, NULL, '2026-09-28 12:31:16', '2026-09-28 12:31:17', NULL, 21, '919322757325', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'Pranav', 'Pralhad', 'Kadam', 'b_pharm', 'Latur college of pharmacy latur', '', '', '9359633916', 1000.00, 'paid', 'order_ThMopq8JAGw9NU', 'pay_ThMpHc3JwSzxWy', '87ad5356e8b816ccf79f18b5c4e36f98fe4786702587c76c51283c22a2c0bbae', '2026-09-28 12:33:17', 'f3d95484dd3fe66dac9561e6b94597be4199b9718c0e7dcded76b79ffa5c6aed', '2026-09-28 12:31:37', '2026-09-28 12:33:17', NULL, 24, '919359633916', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'Ananya', 'Anand', 'Jagatkar', 'b_pharm', 'Latur college of pharmacy, Latur', '', '', '8459507062', 1000.00, 'pending', 'order_ThMpUx4JAjCCtP', NULL, NULL, NULL, NULL, '2026-09-28 12:32:14', '2026-09-28 12:32:15', NULL, 20, '918459507062', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'HARSHADA', 'HARISHCHANDRA', 'MATE', 'b_pharm', 'Latur college of pharmacy latur', '', '', '9112964631', 1000.00, 'pending', 'order_ThMq5pNRhV46n6', NULL, NULL, NULL, NULL, '2026-09-28 12:32:48', '2026-09-28 12:32:48', NULL, 29, '919112964631', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'Vaishnavi', 'Shivaji', 'Chavan', 'b_pharm', 'Latur college of pharmacy latur', '', '', '8308842751', 1000.00, 'pending', 'order_ThMqXq9176lO0H', NULL, NULL, NULL, NULL, '2026-09-28 12:33:14', '2026-09-28 12:33:14', NULL, 28, '918308842751', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'Aditi', 'Rajpal', 'Chavan', 'b_pharm', 'Latur college of pharmacy latur', '', '', '9322757325', 1000.00, 'paid', 'order_ThPdW89LO3q88W', 'pay_ThPjj1MXm9RCzo', '6c340256bca47f013772f55dcf4c2a460110bf40391a0021638fe212eadcde94', '2026-09-28 15:29:04', '63a232e728435e818f78332212eeab4ca60bb1eb95ebbab4db4bdda08e6f9490', '2026-09-28 15:16:59', '2026-09-28 15:29:04', NULL, 21, '919322757325', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'Vinay', 'Dhondu', 'Swami', 'b_pharm', 'Latur college of pharmacy hasegaon', '', '', '9657766436', 1000.00, 'paid', 'order_ThPfOCGtJ1RKK5', 'pay_ThPfoE3nO6J5nL', '83f232f422782ff5b152547ff8d823c210af47da0d172f337cda9648a4262a44', '2026-09-28 15:19:46', '7782551147e42804d5e9e513eed764d9cd79fddea55c5654f8eb42762b3acb44', '2026-09-28 15:18:45', '2026-09-28 15:19:46', NULL, 31, '919657766436', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 'Salman', 'Rajasab', 'Shaikh', 'b_pharm', 'Latur College of Pharmacy Hasegaon', '', '', '8530404164', 1000.00, 'paid', 'order_ThPfpmlN4W3Mxl', 'pay_ThPg6XaHc68Ikd', '5898cbfee5208be1a0df6af0e1c52196c149574cdc250d1b8c50ab869c7f79cb', '2026-09-28 15:19:54', 'f2d1e0810cdd4592a956c7eeba9ec9161cf1d72dd1cdf319c98ccc7f399e0712', '2026-09-28 15:19:10', '2026-09-28 15:19:54', NULL, 16, '918530404164', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'Muskan', 'Usman', 'Shaikh', 'm_pharm', 'Channabasweshwar pharmacy college (Degree),Latur', '', '', '9028056771', 4000.00, 'paid', 'order_ThPgvQBQHIL7Ro', 'pay_ThPh6fVmimKbSR', 'e20535a4b8c6fb3612b2c1bda394cb2db26926929c83ec0a9b0005861453408b', '2026-09-28 15:20:50', '063da5deeb31ddd16a34f6135da6375ed238eb6317bf0d5efd05782654850c7b', '2026-09-28 15:20:12', '2026-09-28 15:20:50', NULL, 32, '919028056771', 'academia', 4000.00, 160.00, NULL, 2, 'STU1000', NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'Vaishnavi', 'Shivaji', 'Chavan', 'b_pharm', 'Latur college of pharmacy latur', '', '', '8308842751', 1000.00, 'paid', 'order_ThPmkRvXsJJcYu', 'pay_ThPoAz52pGuz1d', '49366e023e72c36335b9736fd4a19e220948ccd60bc7d0558123c23900668517', '2026-09-28 15:28:56', '9315cc3ea53a004adfcc20b15ed2d3911b77f529ec48dc32eb725649bfa70323', '2026-09-28 15:25:43', '2026-09-28 15:28:56', NULL, 28, '918308842751', 'academia', 1000.00, 40.00, NULL, 1, 'LCOPS', NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'Muskan', 'Muddasir', 'Bagwan', 'm_pharm', 'Channa basweshwar pharmacy college latur', '', '', '7350953317', 4000.00, 'paid', 'order_ThPv9LxymNvBDq', 'pay_ThPvKnRwph1lI7', '9e54e6a0d587d66ff8326d05e544b6bc70736482ab5b5b794db1efa36bd9096f', '2026-09-28 15:34:28', 'acff29bdbea3626dbc35ba2f6075100a7b0ea87669ebe986a65a1e360db137a4', '2026-09-28 15:33:40', '2026-09-28 15:34:28', NULL, 34, '917350953317', 'academia', 4000.00, 160.00, NULL, 2, 'STU1000', NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'Anand', 'P', 'Somani', 'academic_researcher', 'Firstcry', '', '', '9423654413', 4000.00, 'pending', 'order_ThTLOfZH0vF1xd', NULL, NULL, NULL, NULL, '2026-09-28 18:54:36', '2026-09-28 18:54:36', NULL, 35, '919423654413', 'academia', 4000.00, 160.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `created_by` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `referral_code` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `phone`, `status`, `last_login`, `login_count`, `created_by`, `created_at`, `updated_at`, `referral_code`) VALUES
(1, 'Sachin', '919096463943', 'active', NULL, 0, '919096463943', '2026-09-05 08:32:32', '2026-09-05 08:32:32', 'fkdfmc7b');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `phone`, `name`, `email`, `last_login`, `login_count`, `created_at`, `updated_at`) VALUES
(1, '919096463943', 'Sachin Padmakar Patange', 'sachinppatange@gmail.com', '2026-10-01 02:37:09', 10, '2026-09-05 07:49:40', '2026-10-01 02:37:09'),
(2, '919975040405', 'Dhanraj Nagnath Wasmate', 'dhanrajwasmate@gmail.com', '2026-09-11 09:31:16', 9, '2026-09-05 08:05:48', '2026-09-11 09:31:16'),
(3, '919860777105', NULL, NULL, '2026-09-05 08:18:26', 1, '2026-09-05 08:18:26', '2026-09-05 08:18:26'),
(4, '917756046834', 'Sneha  Vairagkar', NULL, '2026-09-28 05:00:10', 9, '2026-09-07 06:18:38', '2026-09-28 05:00:10'),
(5, '919765412889', 'Prabhakar S Swami', 'sachinppatange@gmail.com', '2026-09-09 03:42:34', 1, '2026-09-09 03:42:34', '2026-09-09 09:13:56'),
(6, '917020129093', NULL, NULL, '2026-09-11 07:01:25', 1, '2026-09-11 07:01:25', '2026-09-11 07:01:25'),
(7, '917262856002', NULL, NULL, '2026-09-11 09:53:12', 1, '2026-09-11 09:53:12', '2026-09-11 09:53:12'),
(8, '917887435417', 'Raghunath Shivaji Sakhare', NULL, '2026-09-11 09:54:56', 1, '2026-09-11 09:54:56', '2026-09-11 15:26:47'),
(9, '919373264072', 'Sayli Satish Udgirkar', NULL, '2026-09-26 10:05:05', 3, '2026-09-26 07:37:15', '2026-09-26 10:05:05'),
(10, '917038398985', NULL, NULL, '2026-09-26 08:28:47', 1, '2026-09-26 08:28:47', '2026-09-26 08:28:47'),
(11, '919960471286', 'Yusuf Jainoddin Pathan', NULL, '2026-09-28 06:49:20', 1, '2026-09-28 06:49:20', '2026-09-28 12:26:48'),
(12, '917028074176', 'Shoeb Ayub Shaikh', NULL, '2026-09-28 06:49:46', 1, '2026-09-28 06:49:46', '2026-09-28 12:22:42'),
(13, '918208843746', NULL, NULL, '2026-09-28 06:49:52', 1, '2026-09-28 06:49:52', '2026-09-28 06:49:52'),
(14, '918010745588', 'Kapade Aishwarya Chandrakant', NULL, '2026-09-28 06:51:03', 1, '2026-09-28 06:51:03', '2026-09-28 12:29:04'),
(15, '917666707048', 'Swapnil Mahadu Lavte', NULL, '2026-09-29 15:48:33', 4, '2026-09-28 06:51:14', '2026-09-29 15:48:33'),
(16, '918530404164', 'Salman Rajasab Shaikh', NULL, '2026-09-28 09:47:06', 2, '2026-09-28 06:51:18', '2026-09-28 09:47:06'),
(17, '919404671030', 'Kapade Aishwarya Chandrakant', NULL, '2026-09-28 06:51:48', 1, '2026-09-28 06:51:48', '2026-09-28 12:23:39'),
(18, '919371541917', 'Payal Satish Lale', NULL, '2026-09-28 06:51:56', 1, '2026-09-28 06:51:56', '2026-09-28 12:24:09'),
(19, '918767193286', 'SAGAR APPASAHEB GAJENDRA', NULL, '2026-09-28 06:52:37', 1, '2026-09-28 06:52:37', '2026-09-28 12:29:40'),
(20, '918459507062', 'Ananya Anand Jagatkar', NULL, '2026-09-28 06:52:54', 1, '2026-09-28 06:52:54', '2026-09-28 12:32:15'),
(21, '919322757325', 'Aditi Rajpal Chavan', NULL, '2026-09-28 08:50:35', 2, '2026-09-28 06:52:56', '2026-09-28 08:50:35'),
(22, '917757059319', 'ATUL SANJAYRAO JADHAV', NULL, '2026-09-28 06:53:05', 1, '2026-09-28 06:53:05', '2026-09-28 12:29:22'),
(23, '917498411101', 'SHUBHAM DILIP ACHMARE', NULL, '2026-09-28 06:53:20', 1, '2026-09-28 06:53:20', '2026-09-28 12:30:47'),
(24, '919359633916', 'Pranav Pralhad Kadam', NULL, '2026-09-28 06:53:45', 1, '2026-09-28 06:53:45', '2026-09-28 12:31:37'),
(25, '917620413402', 'Pravin Shriram Kadam', NULL, '2026-09-28 06:56:50', 2, '2026-09-28 06:53:57', '2026-09-28 12:29:59'),
(26, '919529970759', 'Vaibhavi Vitthal Muktapure', NULL, '2026-09-28 06:55:47', 1, '2026-09-28 06:55:47', '2026-09-28 12:28:03'),
(27, '918275087507', 'Namrata Pandurang Dolare', NULL, '2026-09-28 07:58:41', 3, '2026-09-28 06:56:28', '2026-09-28 07:58:41'),
(28, '918308842751', 'Vaishnavi Shivaji Chavan', NULL, '2026-09-28 09:48:43', 2, '2026-09-28 07:00:18', '2026-09-28 09:48:43'),
(29, '919112964631', 'HARSHADA HARISHCHANDRA MATE', NULL, '2026-09-28 07:00:36', 1, '2026-09-28 07:00:36', '2026-09-28 12:32:48'),
(30, '918149446518', NULL, NULL, '2026-09-28 07:02:49', 1, '2026-09-28 07:02:49', '2026-09-28 07:02:49'),
(31, '919657766436', 'Vinay Dhondu Swami', NULL, '2026-09-28 09:48:00', 2, '2026-09-28 07:03:26', '2026-09-28 15:18:45'),
(32, '919028056771', 'Muskan Usman Shaikh', NULL, '2026-09-28 09:49:00', 1, '2026-09-28 09:49:00', '2026-09-28 15:20:12'),
(33, '917219417200', NULL, NULL, '2026-09-28 13:06:30', 2, '2026-09-28 09:56:25', '2026-09-28 13:06:30'),
(34, '917350953317', 'Muskan Muddasir Bagwan', NULL, '2026-09-28 10:02:23', 1, '2026-09-28 10:02:23', '2026-09-28 15:33:40'),
(35, '919423654413', 'Anand P Somani', NULL, '2026-09-30 15:08:03', 2, '2026-09-28 13:20:48', '2026-09-30 15:08:03'),
(36, '918262096140', NULL, NULL, '2026-09-30 13:03:30', 1, '2026-09-30 13:03:30', '2026-09-30 13:03:30'),
(37, '919404590319', NULL, NULL, '2026-09-30 15:08:54', 1, '2026-09-30 15:08:54', '2026-09-30 15:08:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `event_checkins`
--
ALTER TABLE `event_checkins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `app_station` (`application_id`,`station`),
  ADD KEY `station` (`station`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel_created` (`channel`,`created_at`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `razorpay_webhook_logs`
--
ALTER TABLE `razorpay_webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `scholarship_applications`
--
ALTER TABLE `scholarship_applications`
  ADD UNIQUE KEY `receipt_token` (`receipt_token`),
  ADD KEY `idx_submitted_by_staff` (`submitted_by_staff_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_mobile` (`mobile`),
  ADD KEY `idx_institution_type` (`institution_type`),
  ADD KEY `idx_razorpay_order_id` (`razorpay_order_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_checkins`
--
ALTER TABLE `event_checkins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `razorpay_webhook_logs`
--
ALTER TABLE `razorpay_webhook_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

-- SVSS Dandiya Night content (applied on import; the site also refreshes these from the content pack)
UPDATE `app_settings` SET `setting_value` = 'SVSS Dandiya Night', `updated_by` = 'system' WHERE `setting_key` IN ('brand_name', 'landing_title', 'zepto_from_name');
UPDATE `app_settings` SET `setting_value` = 'Entry registration · Shri Vetaleshwar Shikshan Sanstha', `updated_by` = 'system' WHERE `setting_key` = 'landing_subtitle';
UPDATE `app_settings` SET `setting_value` = '300', `updated_by` = 'system' WHERE `setting_key` IN ('exam_fee_flat', 'exam_fee_1_4', 'exam_fee_5_10');
UPDATE `app_settings` SET `setting_value` = 'Register for Dandiya Night', `updated_by` = 'system' WHERE `setting_key` = 'landing_cta';
UPDATE `app_settings` SET `setting_value` = 'SVSS Dandiya Night\nDigital entry ticket\nPass number after payment\nEntry fee ₹300', `updated_by` = 'system' WHERE `setting_key` = 'landing_highlights';
UPDATE `app_settings` SET `setting_value` = '17 Oct 2026, 6:00 pm\nEntry only with a paid digital ticket\nPass number is issued after payment', `updated_by` = 'system' WHERE `setting_key` = 'landing_dates';
UPDATE `app_settings` SET `setting_value` = 'svss_dandiya_night_v1', `updated_by` = 'system' WHERE `setting_key` = 'content_pack';

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
