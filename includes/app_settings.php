<?php
/**
 * Admin-editable app settings (MSG91 + ZeptoMail) stored in the database.
 */

if (!function_exists('db')) {
    require_once __DIR__ . '/../config/wa_config.php';
}
if (!function_exists('legal_default_checkbox_text')) {
    require_once __DIR__ . '/legal_content.php';
}

function app_settings_default_email_html(): string
{
    return <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#f4f6f9;">
  <div style="background:#ffffff;border-radius:12px;padding:28px 24px;border-top:4px solid #0058F0;">
    <p style="margin:0 0 8px;font-size:12px;letter-spacing:.08em;color:#0058F0;font-weight:700;">LOGIN</p>
    <h2 style="margin:0 0 16px;color:#0058F0;font-size:22px;">Your login OTP</h2>
    <p style="margin:0 0 12px;color:#334155;font-size:15px;line-height:1.5;">Use this one-time password to log in. Do not share it with anyone.</p>
    <p style="margin:16px 0;font-size:32px;letter-spacing:8px;font-weight:800;color:#0058F0;text-align:center;">{{otp}}</p>
    <p style="margin:0;color:#64748b;font-size:13px;">Valid for {{minutes}} minutes.</p>
  </div>
</div>
HTML;
}

function app_settings_defaults(): array
{
    $auth = defined('MSG91_AUTH_KEY') ? (string) MSG91_AUTH_KEY : '';
    $sender = defined('MSG91_SENDER_ID') ? (string) MSG91_SENDER_ID : 'KAGPIF';
    $tplName = defined('MSG91_OTP_TEMPLATE_NAME') ? (string) MSG91_OTP_TEMPLATE_NAME : 'kagpifotp';
    $tplId = defined('MSG91_OTP_TEMPLATE_ID') ? (string) MSG91_OTP_TEMPLATE_ID : '';
    $expiryMin = defined('OTP_EXPIRY_SECONDS') ? max(1, (int) ceil(((int) OTP_EXPIRY_SECONDS) / 60)) : 5;

    return [
        'msg91_auth_key' => $auth,
        'msg91_sender_id' => $sender,
        'msg91_template_name' => $tplName,
        'msg91_template_id' => $tplId,
        'msg91_dlt_template_id' => '',
        'otp_validity_minutes' => (string) $expiryMin,
        'msg91_dlt_content' => 'Your OTP for account verification is ##var1##. Valid for ##var2## minutes. Do not share it. KAGPIF',
        'zepto_data_center' => 'in',
        'zepto_send_method' => 'api',
        'zepto_send_mail_token' => '',
        'zepto_from_email' => 'noreply@openform.in',
        'zepto_from_name' => 'SVSS Dandiya Night',
        'zepto_bounce_address' => '',
        'zepto_reply_to_email' => '',
        'otp_email_subject' => 'Your login OTP',
        'otp_email_html' => app_settings_default_email_html(),
        'brand_name' => 'SVSS Dandiya Night',
        'brand_tagline_admin' => 'Admin panel',
        'brand_tagline_staff' => 'Staff panel',
        'brand_logo_url' => '',
        'brand_logo_file' => 'storage/branding/logo.png',
        'razorpay_key_id' => 'rzp_live_SDwPm0ag5yJD36',
        'razorpay_key_secret' => 'Oc1ytZprLJQ8VeAQUUqqoFN1',
        'razorpay_webhook_secret' => '',
        'razorpay_mode' => 'live',
        'payment_test_mode' => '0',
        'exam_fee_1_4' => '300',
        'exam_fee_5_10' => '300',
        'exam_fee_flat' => '300',
        'platform_fee_percent' => '4',
        'platform_fee_payer' => 'user',
        'landing_enabled' => '1',
        'landing_title' => 'SVSS Dandiya Night',
        'landing_subtitle' => 'Entry registration · Shri Vetaleshwar Shikshan Sanstha',
        'landing_about' => "SVSS Dandiya Night is organised by Shri Vetaleshwar Shikshan Sanstha at Latur College of Pharmacy, Hasegaon.\nRegister with your name, college and ticket type, then pay online. A seat is confirmed only after successful payment.\nPaid guests receive a pass number and a digital entry ticket with QR. Show that ticket at the gate.",
        'landing_who' => "Students\nFaculty and staff\nAlumni\nGuests\nOne registration and one entry ticket per person",
        'landing_dates' => "17 Oct 2026, 6:00 pm\nEntry only with a paid digital ticket\nPass number is issued after payment",
        'landing_how' => "Log in with your mobile number and SMS OTP\nFill your name, college and ticket type\nPay the entry fee through official Razorpay on this website\nOpen your pass number and ticket, and show the QR at the gate",
        'landing_need' => "Full name of the guest\nCollege or organisation name\nTicket type (student, faculty / staff, alumni, or guest)",
        'landing_helpline' => "Phone / WhatsApp: +91 99750 40405\nLatur College of Pharmacy, Hasegaon, Gurunathappa Bawage Knowledge City, Tq. Ausa, Dist. Latur 413512",
        'landing_poster_file' => '',
        'landing_poster_url' => '',
        'landing_cta' => 'Register for Dandiya Night',
        'landing_venue' => "Latur College of Pharmacy, Hasegaon\nLatur, Maharashtra",
        'landing_highlights' => "SVSS Dandiya Night\nDigital entry ticket\nPass number after payment\nEntry fee ₹300",
        'landing_whatsapp' => '9975040405',
        'content_pack' => '',
        'event_group_url' => '',
        'event_photos_url' => '',
        'event_feedback_url' => '',
        'icard_photo_on_form' => '0',
        'staff_ask_on_form' => '1',
        'coupons_on_form' => '0',
        'landing_show_fees' => '1',
        'legal_checkbox_text' => legal_default_checkbox_text(),
        'legal_declaration_html' => legal_default_declaration_html(),
        'legal_terms_html' => legal_default_terms_html(),
        'legal_declaration_url' => '',
        'legal_rules_url' => '',
    ];
}

function app_settings_secret_keys(): array
{
    return ['msg91_auth_key', 'zepto_send_mail_token', 'razorpay_key_secret', 'razorpay_webhook_secret'];
}

function apply_workshop_content_pack(PDO $pdo): void
{
    $pack = 'svss_dandiya_night_v1';
    try {
        $stmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'content_pack'");
        $current = (string) ($stmt ? $stmt->fetchColumn() : '');
        if ($current === $pack) {
            return;
        }
        $d = app_settings_defaults();
        $keys = [
            'zepto_from_name', 'brand_name', 'brand_logo_file',
            'exam_fee_1_4', 'exam_fee_5_10', 'exam_fee_flat',
            'landing_enabled', 'landing_title', 'landing_subtitle', 'landing_about',
            'landing_who', 'landing_dates', 'landing_how', 'landing_need', 'landing_helpline',
            'landing_poster_file', 'landing_poster_url', 'landing_cta', 'landing_venue', 'landing_highlights',
            'landing_whatsapp', 'landing_show_fees',
            'legal_checkbox_text', 'legal_declaration_html', 'legal_terms_html',
        ];
        $up = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value, updated_by) VALUES (?, ?, 'system')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = 'system'");
        foreach ($keys as $key) {
            $up->execute([$key, (string) ($d[$key] ?? '')]);
        }
        $up->execute(['content_pack', $pack]);
        app_settings_cache_set(null);
    } catch (Throwable $e) {
        error_log('content pack: ' . $e->getMessage());
    }
}

function app_settings_repair_primary_key(PDO $pdo): void
{
    $idx = $pdo->query("SHOW INDEX FROM `app_settings` WHERE Key_name = 'PRIMARY'")->fetch(PDO::FETCH_ASSOC);
    if ($idx) {
        return;
    }
    $pdo->exec("
        CREATE TABLE `app_settings_new` (
          `setting_key` varchar(80) NOT NULL,
          `setting_value` longtext DEFAULT NULL,
          `updated_by` varchar(20) DEFAULT NULL,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        INSERT INTO `app_settings_new` (`setting_key`, `setting_value`, `updated_by`, `updated_at`)
        SELECT `setting_key`, `setting_value`, `updated_by`, `updated_at`
          FROM (
            SELECT `setting_key`, `setting_value`, `updated_by`, `updated_at`,
                   ROW_NUMBER() OVER (PARTITION BY `setting_key` ORDER BY `updated_at` DESC) AS rn
              FROM `app_settings`
          ) ranked
         WHERE rn = 1
    ");
    $pdo->exec("DROP TABLE `app_settings`");
    $pdo->exec("RENAME TABLE `app_settings_new` TO `app_settings`");
}

function ensure_app_settings_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo = db();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `app_settings` (
              `setting_key` varchar(80) NOT NULL,
              `setting_value` longtext DEFAULT NULL,
              `updated_by` varchar(20) DEFAULT NULL,
              `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Admin panel OTP / SMS / email settings'
        ");
        app_settings_repair_primary_key($pdo);
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `notification_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `channel` enum('sms','email') NOT NULL,
              `status` enum('ok','error') NOT NULL DEFAULT 'error',
              `recipient` varchar(180) DEFAULT NULL,
              `message` varchar(500) DEFAULT NULL,
              `http_code` int(11) DEFAULT NULL,
              `response` text DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_channel_created` (`channel`, `created_at`),
              KEY `idx_status_created` (`status`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='SMS and email OTP send log'
        ");
        $stmt = $pdo->prepare("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach (app_settings_defaults() as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        $fromStmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'zepto_from_email'");
        $fromNow = strtolower(trim((string) ($fromStmt ? $fromStmt->fetchColumn() : '')));
        if ($fromNow === '' || $fromNow === 'admin@agnipankh.in') {
            $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'zepto_from_email'")
                ->execute(['noreply@openform.in']);
        }
        if (function_exists('scrub_legacy_agnipankh_settings')) {
            scrub_legacy_agnipankh_settings($pdo);
        }
        apply_workshop_content_pack($pdo);
        $waNow = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'landing_whatsapp'");
        $waVal = preg_replace('/\D+/', '', (string) ($waNow ? $waNow->fetchColumn() : ''));
        if (strlen($waVal) < 10) {
            $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value, updated_by) VALUES ('landing_whatsapp', '9975040405', 'system')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute();
        }
        try {
            $pdo->exec("ALTER TABLE `admins` ADD COLUMN `photo` varchar(255) DEFAULT NULL COMMENT 'Profile photo relative path'");
        } catch (PDOException $e) {
            // column exists
        }
        try {
            $pdo->exec("ALTER TABLE `admins` ADD COLUMN `password_hash` varchar(255) DEFAULT NULL COMMENT 'Password login hash'");
        } catch (PDOException $e) {
            // column exists
        }
        $done = true;
    } catch (Throwable $e) {
        error_log('app_settings schema: ' . $e->getMessage());
    }
}

function app_settings_cache_set(?array $settings): void
{
    $GLOBALS['_app_settings_cache'] = $settings;
}

function get_app_settings(): array
{
    if (isset($GLOBALS['_app_settings_cache']) && is_array($GLOBALS['_app_settings_cache'])) {
        return $GLOBALS['_app_settings_cache'];
    }
    $settings = app_settings_defaults();
    try {
        ensure_app_settings_schema();
        $rows = db()->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        if (is_array($rows)) {
            foreach ($rows as $key => $value) {
                if (array_key_exists($key, $settings) && $value !== null) {
                    $settings[$key] = (string) $value;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('get_app_settings: ' . $e->getMessage());
    }
    app_settings_cache_set($settings);
    return $settings;
}

function get_app_setting(string $key, $fallback = '')
{
    $all = get_app_settings();
    if (!array_key_exists($key, $all) || $all[$key] === '') {
        return $fallback;
    }
    return $all[$key];
}

function otp_expiry_minutes(): int
{
    $min = (int) get_app_setting('otp_validity_minutes', 5);
    return max(1, min(30, $min));
}

function otp_expiry_seconds(): int
{
    return otp_expiry_minutes() * 60;
}

function save_app_settings(array $posted, string $updatedBy = ''): array
{
    $defaults = app_settings_defaults();
    $current = get_app_settings();
    $clean = $current;

    foreach ($defaults as $key => $default) {
        if (in_array($key, app_settings_secret_keys(), true)) {
            $incoming = trim((string) ($posted[$key] ?? ''));
            $clean[$key] = $incoming === '' ? (string) $current[$key] : $incoming;
            continue;
        }
        if (!array_key_exists($key, $posted)) {
            continue;
        }
        $value = (string) $posted[$key];
        if ($key === 'otp_validity_minutes') {
            $clean[$key] = (string) max(1, min(30, (int) $value));
            continue;
        }
        if ($key === 'msg91_sender_id') {
            $clean[$key] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value));
            $clean[$key] = substr($clean[$key], 0, 6);
            continue;
        }
        if ($key === 'zepto_data_center') {
            $allowed = array_keys(zepto_data_centers());
            $clean[$key] = in_array($value, $allowed, true) ? $value : 'in';
            continue;
        }
        if ($key === 'zepto_send_method') {
            $clean[$key] = in_array($value, ['api', 'smtp'], true) ? $value : 'api';
            continue;
        }
        if (in_array($key, ['zepto_from_email', 'zepto_reply_to_email', 'zepto_bounce_address'], true)) {
            $clean[$key] = trim($value);
            continue;
        }
        if ($key === 'brand_name') {
            $name = trim($value);
            $clean[$key] = $name !== '' ? substr($name, 0, 60) : 'Registration';
            continue;
        }
        if (in_array($key, ['brand_tagline_admin', 'brand_tagline_staff'], true)) {
            $clean[$key] = substr(trim($value), 0, 40);
            continue;
        }
        if ($key === 'brand_logo_url') {
            $url = trim($value);
            $clean[$key] = ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) ? $url : (string) $current[$key];
            continue;
        }
        if ($key === 'razorpay_mode') {
            $clean[$key] = $value === 'test' ? 'test' : 'live';
            continue;
        }
        if ($key === 'payment_test_mode') {
            $clean[$key] = $value === '1' ? '1' : '0';
            continue;
        }
        if ($key === 'exam_fee_1_4' || $key === 'exam_fee_5_10' || $key === 'exam_fee_flat') {
            $clean[$key] = (string) max(1, min(99999, (int) $value));
            continue;
        }
        if ($key === 'platform_fee_percent') {
            $clean[$key] = (string) max(0, min(30, (float) $value));
            continue;
        }
        if ($key === 'platform_fee_payer') {
            $clean[$key] = $value === 'admin' ? 'admin' : 'user';
            continue;
        }
        if ($key === 'razorpay_key_id') {
            $clean[$key] = trim($value);
            continue;
        }
        if ($key === 'landing_enabled') {
            $clean[$key] = $value === '1' ? '1' : '0';
            continue;
        }
        if ($key === 'landing_title') {
            $t = trim($value);
            $clean[$key] = $t !== '' ? substr($t, 0, 160) : (string) $defaults[$key];
            continue;
        }
        if ($key === 'landing_subtitle') {
            $clean[$key] = substr(trim($value), 0, 240);
            continue;
        }
        if (in_array($key, ['landing_about', 'landing_who', 'landing_dates', 'landing_how', 'landing_need', 'landing_helpline', 'landing_highlights', 'landing_venue'], true)) {
            $clean[$key] = substr(trim($value), 0, 8000);
            continue;
        }
        if ($key === 'landing_cta') {
            $t = trim($value);
            $clean[$key] = $t !== '' ? substr($t, 0, 80) : (string) $defaults[$key];
            continue;
        }
        if ($key === 'landing_whatsapp') {
            $d = preg_replace('/\D+/', '', $value);
            $clean[$key] = strlen($d) >= 10 ? substr($d, -10) : '';
            continue;
        }
        if (in_array($key, ['event_group_url', 'event_photos_url', 'event_feedback_url'], true)) {
            $url = trim($value);
            $clean[$key] = ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) ? substr($url, 0, 500) : (string) $current[$key];
            continue;
        }
        if ($key === 'icard_photo_on_form') {
            $clean[$key] = $value === '1' ? '1' : '0';
            continue;
        }
        if ($key === 'staff_ask_on_form') {
            $clean[$key] = $value === '1' ? '1' : '0';
            continue;
        }
        if ($key === 'coupons_on_form') {
            $clean[$key] = $value === '1' ? '1' : '0';
            continue;
        }
        if ($key === 'legal_checkbox_text') {
            $t = trim($value);
            $clean[$key] = $t !== '' ? substr($t, 0, 600) : legal_default_checkbox_text();
            continue;
        }
        if ($key === 'legal_declaration_html') {
            $html = legal_sanitize_html(trim($value));
            $clean[$key] = $html !== '' ? $html : legal_default_declaration_html();
            continue;
        }
        if ($key === 'legal_terms_html') {
            $html = legal_sanitize_html(trim($value));
            $clean[$key] = $html !== '' ? $html : legal_default_terms_html();
            continue;
        }
        if (in_array($key, ['legal_declaration_url', 'legal_rules_url'], true)) {
            $url = trim($value);
            $clean[$key] = ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) ? substr($url, 0, 500) : (string) $current[$key];
            continue;
        }
        if ($key === 'landing_poster_file') {
            $p = trim($value);
            $clean[$key] = ($p === '' || preg_match('#^storage/branding/[A-Za-z0-9._-]+$#', $p)) ? $p : (string) $current[$key];
            continue;
        }
        if ($key === 'landing_poster_url') {
            $url = trim($value);
            $clean[$key] = ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) ? $url : (string) $current[$key];
            continue;
        }
        $clean[$key] = $value;
    }

    try {
        ensure_app_settings_schema();
        $pdo = db();
        $stmt = $pdo->prepare("
            INSERT INTO app_settings (setting_key, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)
        ");
        foreach ($clean as $key => $value) {
            $stmt->execute([$key, $value, $updatedBy !== '' ? $updatedBy : null]);
        }
        app_settings_cache_set($clean);
        return ['ok' => true, 'error' => '', 'settings' => $clean];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not save settings. Please try again.', 'settings' => $current];
    }
}

function zepto_data_centers(): array
{
    return [
        'in' => 'India (zeptomail.zoho.in)',
        'com' => 'United States (zeptomail.zoho.com)',
        'eu' => 'Europe (zeptomail.zoho.eu)',
        'com.au' => 'Australia (zeptomail.zoho.com.au)',
        'jp' => 'Japan (zeptomail.zoho.jp)',
        'com.cn' => 'China (zeptomail.zoho.com.cn)',
        'ca' => 'Canada (zeptomail.zoho.ca)',
        'sa' => 'Saudi Arabia (zeptomail.zoho.sa)',
    ];
}

function zepto_api_host(string $dc): string
{
    $map = [
        'in' => 'api.zeptomail.in',
        'com' => 'api.zeptomail.com',
        'eu' => 'api.zeptomail.eu',
        'com.au' => 'api.zeptomail.com.au',
        'jp' => 'api.zeptomail.jp',
        'com.cn' => 'api.zeptomail.com.cn',
        'ca' => 'api.zeptomail.ca',
        'sa' => 'api.zeptomail.sa',
    ];
    return $map[$dc] ?? $map['in'];
}

function zepto_smtp_host(string $dc): string
{
    $map = [
        'in' => 'smtp.zeptomail.in',
        'com' => 'smtp.zeptomail.com',
        'eu' => 'smtp.zeptomail.eu',
        'com.au' => 'smtp.zeptomail.com.au',
        'jp' => 'smtp.zeptomail.jp',
        'com.cn' => 'smtp.zeptomail.com.cn',
        'ca' => 'smtp.zeptomail.ca',
        'sa' => 'smtp.zeptomail.sa',
    ];
    return $map[$dc] ?? $map['in'];
}

function render_otp_email_html(string $html, string $otp, int $minutes): string
{
    return str_replace(
        ['{{otp}}', '{{minutes}}', '{{OTP}}', '{{MINUTES}}'],
        [htmlspecialchars($otp, ENT_QUOTES, 'UTF-8'), (string) $minutes, htmlspecialchars($otp, ENT_QUOTES, 'UTF-8'), (string) $minutes],
        $html
    );
}

function log_notification(string $channel, string $status, string $recipient, string $message, ?int $httpCode = null, $response = null): void
{
    $channel = $channel === 'email' ? 'email' : 'sms';
    $status = $status === 'ok' ? 'ok' : 'error';
    $resp = '';
    if (is_array($response) || is_object($response)) {
        $resp = json_encode($response, JSON_UNESCAPED_UNICODE);
    } elseif ($response !== null) {
        $resp = (string) $response;
    }
    $resp = substr($resp, 0, 4000);
    $message = substr($message, 0, 500);
    $recipient = substr($recipient, 0, 180);

    try {
        ensure_app_settings_schema();
        $pdo = db();
        $stmt = $pdo->prepare("
            INSERT INTO notification_logs (channel, status, recipient, message, http_code, response, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$channel, $status, $recipient, $message, $httpCode, $resp]);
        $maxId = (int) $pdo->query("SELECT MAX(id) FROM notification_logs")->fetchColumn();
        if ($maxId > 500) {
            $pdo->prepare("DELETE FROM notification_logs WHERE id < ?")->execute([$maxId - 500]);
        }
    } catch (Throwable $e) {
        error_log('log_notification: ' . $e->getMessage());
    }
}

function get_notification_logs(string $channel = 'all', int $limit = 80): array
{
    try {
        ensure_app_settings_schema();
        $limit = max(10, min(200, $limit));
        if (in_array($channel, ['sms', 'email'], true)) {
            $stmt = db()->prepare("SELECT * FROM notification_logs WHERE channel = ? ORDER BY id DESC LIMIT {$limit}");
            $stmt->execute([$channel]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        return db()->query("SELECT * FROM notification_logs ORDER BY id DESC LIMIT {$limit}")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function clear_notification_logs(): bool
{
    try {
        ensure_app_settings_schema();
        db()->exec("TRUNCATE TABLE notification_logs");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function notification_log_counts(): array
{
    $out = ['total' => 0, 'error' => 0, 'ok' => 0];
    try {
        ensure_app_settings_schema();
        $row = db()->query("
            SELECT COUNT(*) total,
                   SUM(status='error') err_count,
                   SUM(status='ok') ok_count
            FROM notification_logs
        ")->fetch(PDO::FETCH_ASSOC) ?: [];
        $out['total'] = (int) ($row['total'] ?? 0);
        $out['error'] = (int) ($row['err_count'] ?? 0);
        $out['ok'] = (int) ($row['ok_count'] ?? 0);
    } catch (Throwable $e) {
        // ignore
    }
    return $out;
}

function mask_secret(?string $value): string
{
    $value = (string) $value;
    $len = strlen($value);
    if ($len === 0) {
        return '';
    }
    if ($len <= 6) {
        return str_repeat('•', $len);
    }
    return str_repeat('•', max(8, $len - 4)) . substr($value, -4);
}

function panel_brand_name(): string
{
    $name = trim((string) get_app_setting('brand_name', 'Registration'));
    if ($name === '' || strcasecmp($name, 'AGNIPANKH') === 0) {
        $title = trim((string) get_app_setting('landing_title', ''));
        if ($title !== '' && stripos($title, 'agnipankh') === false) {
            return $title;
        }
        return 'Registration';
    }
    return $name;
}

function landing_page_title(): string
{
    $title = trim((string) get_app_setting('landing_title', ''));
    if ($title === '') {
        $title = trim((string) (app_settings_defaults()['landing_title'] ?? ''));
    }
    return $title !== '' ? $title : panel_brand_name();
}

function landing_page_subtitle(): string
{
    return trim((string) get_app_setting('landing_subtitle', ''));
}

function help_whatsapp_10(): string
{
    $d = preg_replace('/\D+/', '', (string) get_app_setting('landing_whatsapp', ''));
    return strlen($d) >= 10 ? substr($d, -10) : '';
}

function help_whatsapp_url(string $prefill = ''): string
{
    $n = help_whatsapp_10();
    if ($n === '') {
        return '';
    }
    $url = 'https://wa.me/91' . $n;
    $msg = $prefill !== '' ? $prefill : 'Hello, I need help with SVSS Dandiya Night registration.';
    return $url . '?text=' . rawurlencode($msg);
}

function help_whatsapp_button(bool $float = true): string
{
    $url = help_whatsapp_url();
    if ($url === '') {
        return '';
    }
    $cls = $float ? 'help-wa help-wa-float' : 'help-wa';
    $html = '';
    if ($float) {
        $html .= '<style>.help-wa-float{position:fixed;right:14px;bottom:18px;z-index:45;display:inline-flex;align-items:center;gap:8px;background:#25d366;color:#fff!important;text-decoration:none;font-weight:800;font-family:system-ui,sans-serif;font-size:14px;padding:12px 16px;border-radius:999px;box-shadow:0 8px 24px rgba(37,211,102,.45)}@media print{.help-wa-float{display:none!important}}</style>';
    }
    $html .= '<a class="' . $cls . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Help / Support</a>';
    return $html;
}

function panel_tagline(string $role = 'admin'): string
{
    $key = $role === 'staff' ? 'brand_tagline_staff' : 'brand_tagline_admin';
    $fallback = $role === 'staff' ? 'Staff panel' : 'Admin panel';
    $label = trim((string) get_app_setting($key, $fallback));
    return $label !== '' ? $label : $fallback;
}

function panel_public_prefix(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#/(adminpanel|staff)(/|$)#', $script)) {
        return '../';
    }
    return '';
}

function panel_logo_src(?string $prefix = null): string
{
    $prefix = $prefix ?? panel_public_prefix();
    $root = dirname(__DIR__);
    $file = trim((string) get_app_setting('brand_logo_file', ''));
    if ($file !== '') {
        $abs = $root . '/' . ltrim($file, '/');
        if (is_file($abs)) {
            return $prefix . ltrim($file, '/') . '?v=' . filemtime($abs);
        }
    }
    $url = trim((string) get_app_setting('brand_logo_url', ''));
    if ($url !== '') {
        return $url;
    }
    $local = $root . '/storage/branding/logo.png';
    if (is_file($local)) {
        return $prefix . 'storage/branding/logo.png?v=' . filemtime($local);
    }
    return '';
}

function branding_storage_dir(): string
{
    $dir = dirname(__DIR__) . '/storage/branding';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function save_uploaded_image(array $file, string $basename, int $maxBytes = 2097152): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => '', 'skipped' => true];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Image upload failed. Please try again.'];
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        $mb = max(1, (int) ceil($maxBytes / 1048576));
        return ['ok' => false, 'error' => 'Image must be ' . $mb . ' MB or smaller.'];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Image upload failed.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $extMap = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($extMap[$mime])) {
        return ['ok' => false, 'error' => 'Use PNG, JPG, WEBP or GIF.'];
    }
    $rel = 'storage/branding/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) . '.' . $extMap[$mime];
    $abs = dirname(__DIR__) . '/' . $rel;
    branding_storage_dir();
    foreach (glob(dirname($abs) . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) . '.*') ?: [] as $old) {
        if (is_file($old) && $old !== $abs) {
            @unlink($old);
        }
    }
    if (!move_uploaded_file($tmp, $abs)) {
        return ['ok' => false, 'error' => 'Could not save the image on the server.'];
    }
    return ['ok' => true, 'path' => $rel, 'skipped' => false];
}

function icard_photo_on_form(): bool
{
    return get_app_setting('icard_photo_on_form', '0') === '1';
}

function staff_ask_on_form(): bool
{
    return get_app_setting('staff_ask_on_form', '1') === '1';
}

function icard_photo_dir(): string
{
    $dir = dirname(__DIR__) . '/storage/icard';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function icard_photo_src(?array $app, string $prefix = ''): string
{
    $rel = trim((string) ($app['photo'] ?? ''));
    if ($rel === '' || !preg_match('#^storage/icard/[A-Za-z0-9._-]+$#', $rel)) {
        return '';
    }
    $abs = dirname(__DIR__) . '/' . $rel;
    if (!is_file($abs)) {
        return '';
    }
    return $prefix . $rel . '?v=' . filemtime($abs);
}

function save_icard_photo(array $file, int $applicationId): array
{
    if ($applicationId < 1) {
        return ['ok' => false, 'error' => 'Invalid registration.'];
    }
    $saved = save_uploaded_image($file, 'tmp_icard_' . $applicationId, 3 * 1024 * 1024);
    if (!$saved['ok'] || !empty($saved['skipped'])) {
        return $saved;
    }
    $from = dirname(__DIR__) . '/' . $saved['path'];
    $ext = strtolower(pathinfo($saved['path'], PATHINFO_EXTENSION) ?: 'jpg');
    icard_photo_dir();
    foreach (glob(icard_photo_dir() . '/' . $applicationId . '.*') ?: [] as $old) {
        @unlink($old);
    }
    $rel = 'storage/icard/' . $applicationId . '.' . $ext;
    $to = dirname(__DIR__) . '/' . $rel;
    if (!@rename($from, $to) && !@copy($from, $to)) {
        return ['ok' => false, 'error' => 'Could not save the I-Card photo.'];
    }
    @unlink($from);
    try {
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE scholarship_applications SET photo = ? WHERE id = ?');
        $stmt->execute([$rel, $applicationId]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Photo saved but could not update the record.'];
    }
    return ['ok' => true, 'path' => $rel, 'skipped' => false];
}

function razorpay_config(): array
{
    if (!function_exists('form_fee_breakdown')) {
        require_once __DIR__ . '/form_catalog.php';
    }
    $s = get_app_settings();
    $keyId = trim((string) ($s['razorpay_key_id'] ?? ''));
    $keySecret = trim((string) ($s['razorpay_key_secret'] ?? ''));
    $testMode = (($s['payment_test_mode'] ?? '0') === '1');
    $feeLow = max(1, (int) ($s['exam_fee_flat'] ?? $s['exam_fee_1_4'] ?? 300));
    $feeHigh = $feeLow;
    $fees = [];
    foreach (array_keys(form_all_class_labels()) as $ck) {
        $fees[(string) $ck] = $testMode ? 1 : $feeLow;
    }
    $breakdown = form_fee_breakdown();
    return [
        'key_id' => $keyId,
        'key_secret' => $keySecret,
        'mode' => (($s['razorpay_mode'] ?? 'live') === 'test') ? 'test' : 'live',
        'test_mode' => $testMode,
        'fee_1_4' => $feeLow,
        'fee_5_10' => $feeHigh,
        'fee_flat' => $feeLow,
        'platform_percent' => $breakdown['percent'],
        'fees' => $fees,
        'ready' => $keyId !== '' && $keySecret !== '',
    ];
}

function landing_is_enabled(): bool
{
    return get_app_setting('landing_enabled', '1') !== '0';
}

function landing_lines(string $key): array
{
    $text = (string) get_app_setting($key, '');
    if ($text === '') {
        $text = (string) (app_settings_defaults()[$key] ?? '');
    }
    $parts = preg_split("/\r\n|\n|\r/", $text) ?: [];
    $out = [];
    foreach ($parts as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function landing_poster_src(?string $prefix = null): string
{
    $prefix = $prefix ?? panel_public_prefix();
    $root = dirname(__DIR__);
    $file = trim((string) get_app_setting('landing_poster_file', ''));
    if ($file !== '') {
        $abs = $root . '/' . ltrim($file, '/');
        if (is_file($abs)) {
            return $prefix . ltrim($file, '/') . '?v=' . filemtime($abs);
        }
    }
    $url = trim((string) get_app_setting('landing_poster_url', ''));
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    return '';
}

function form_guest_entry_url(): string
{
    return 'login.php';
}
