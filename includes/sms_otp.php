<?php
/**
 * Shared MSG91 SMS OTP helpers for admin, staff and form login.
 */

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/sms_config.php';
require_once __DIR__ . '/app_settings.php';

function sms_app_log(string $message, array $context = []): void {
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        return;
    }
    $logDir = __DIR__ . '/../storage/logs';
    $logFile = $logDir . '/sms_otp.log';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

if (!function_exists('to_e164')) {
    function to_e164(string $mobile): ?string {
        $digits = preg_replace('/\D+/', '', $mobile ?? '');
        if (!$digits) {
            return null;
        }
        $cc = defined('SMS_COUNTRY_CODE') ? SMS_COUNTRY_CODE : '91';
        if (strpos($digits, $cc) === 0 && strlen($digits) >= strlen($cc) + 10) {
            return $digits;
        }
        if (strlen($digits) === 10) {
            return $cc . $digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '0') {
            return $cc . substr($digits, 1);
        }
        return null;
    }
}

function local_10_digit(?string $e164): string {
    if (!$e164) {
        return '';
    }
    $cc = defined('SMS_COUNTRY_CODE') ? SMS_COUNTRY_CODE : '91';
    if (strpos($e164, $cc) === 0) {
        $local = substr($e164, strlen($cc));
        if (strlen($local) === 10) {
            return $local;
        }
    }
    $digits = preg_replace('/\D+/', '', $e164);
    return strlen($digits) >= 10 ? substr($digits, -10) : '';
}

function generate_login_otp(): string {
    $len = defined('OTP_LENGTH') ? (int) OTP_LENGTH : 4;
    $min = (int) pow(10, $len - 1);
    $max = (int) pow(10, $len) - 1;
    return (string) random_int($min, $max);
}

function otp_empty_ctx(): array {
    return [
        'hash' => null,
        'mobile_e164' => null,
        'expires_at' => 0,
        'attempts' => 0,
        'last_sent_at' => 0,
    ];
}

function otp_has_active(array $ctx): bool {
    return !empty($ctx['mobile_e164']) && !empty($ctx['hash']) && time() < (int) ($ctx['expires_at'] ?? 0);
}

function send_sms_otp(string $to_e164, string $otp): array {
    $result = ['ok' => false, 'http_code' => 0, 'response' => null, 'error' => null];
    $s = get_app_settings();

    $authKey = trim((string) ($s['msg91_auth_key'] ?? (defined('MSG91_AUTH_KEY') ? MSG91_AUTH_KEY : '')));
    $templateId = trim((string) ($s['msg91_template_id'] ?? ''));
    if ($templateId === '') {
        $templateId = trim((string) ($s['msg91_dlt_template_id'] ?? ''));
    }
    if ($templateId === '' && defined('MSG91_OTP_TEMPLATE_ID')) {
        $templateId = trim((string) MSG91_OTP_TEMPLATE_ID);
    }
    if ($authKey === '' || $templateId === '') {
        $result['error'] = 'MSG91_CONFIG_MISSING';
        sms_app_log('MSG91 config missing', ['has_key' => $authKey !== '', 'has_template' => $templateId !== '']);
        log_notification('sms', 'error', $to_e164, 'MSG91 auth key or template ID is missing');
        return $result;
    }

    $expiryMinutes = otp_expiry_minutes();
    $otpVar = defined('MSG91_OTP_VAR') ? MSG91_OTP_VAR : 'var1';
    $minVar = defined('MSG91_MINUTES_VAR') ? MSG91_MINUTES_VAR : 'var2';
    $sender = trim((string) ($s['msg91_sender_id'] ?? (defined('MSG91_SENDER_ID') ? MSG91_SENDER_ID : 'KAGPIF')));

    $payload = [
        'template_id' => $templateId,
        'sender' => $sender !== '' ? $sender : 'KAGPIF',
        'short_url' => '0',
        'mobiles' => $to_e164,
        $otpVar => $otp,
        $minVar => (string) $expiryMinutes,
    ];

    $ch = curl_init(MSG91_FLOW_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'authkey: ' . $authKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result['http_code'] = $httpCode;
    if ($curlErr) {
        $result['error'] = 'CURL_ERROR: ' . $curlErr;
        sms_app_log('MSG91 SMS OTP curl error', ['error' => $curlErr, 'to' => $to_e164]);
        log_notification('sms', 'error', $to_e164, $result['error']);
        return $result;
    }

    $decoded = json_decode((string) $raw, true);
    $result['response'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $raw;
    $apiType = is_array($decoded) ? strtolower((string) ($decoded['type'] ?? '')) : '';
    $apiMsg = is_array($decoded) ? strtolower((string) ($decoded['message'] ?? '')) : '';

    if ($httpCode >= 200 && $httpCode < 300 && $apiType !== 'error' && $apiMsg !== 'error') {
        $result['ok'] = true;
        sms_app_log('MSG91 SMS OTP sent', ['http_code' => $httpCode, 'to' => $to_e164, 'resp' => $result['response']]);
        log_notification('sms', 'ok', $to_e164, 'OTP SMS sent via MSG91', $httpCode, $result['response']);
    } else {
        $result['error'] = is_array($decoded)
            ? ('MSG91: ' . ($decoded['message'] ?? $decoded['type'] ?? 'Unknown error'))
            : ('HTTP_' . $httpCode);
        sms_app_log('MSG91 SMS OTP failed', ['http_code' => $httpCode, 'to' => $to_e164, 'resp' => $result['response']]);
        log_notification('sms', 'error', $to_e164, $result['error'], $httpCode, $result['response']);
    }

    return $result;
}

function otp_dispatch(array &$ctx, string $to_e164): array {
    $now = time();
    $cooldown = defined('OTP_RESEND_COOLDOWN') ? (int) OTP_RESEND_COOLDOWN : 60;
    if (!empty($ctx['last_sent_at']) && ($now - (int) $ctx['last_sent_at']) < $cooldown) {
        $wait = $cooldown - ($now - (int) $ctx['last_sent_at']);
        return ['ok' => false, 'error' => "Please try again in $wait seconds.", 'debug' => null];
    }

    $otp = generate_login_otp();
    $res = send_sms_otp($to_e164, $otp);
    if (!$res['ok']) {
        return [
            'ok' => false,
            'error' => 'Failed to send OTP SMS. Please try again.',
            'debug' => [
                'http_code' => $res['http_code'],
                'error' => $res['error'],
                'response' => $res['response'],
                'hint' => 'Check MSG91 settings in Admin panel → Settings',
            ],
        ];
    }

    $ctx['hash'] = password_hash($otp, PASSWORD_DEFAULT);
    $ctx['mobile_e164'] = $to_e164;
    $ctx['expires_at'] = time() + otp_expiry_seconds();
    $ctx['attempts'] = 0;
    $ctx['last_sent_at'] = time();

    return ['ok' => true, 'error' => '', 'debug' => null];
}

function otp_read_posted_code(): string {
    if (isset($_POST['otp'])) {
        return preg_replace('/\D+/', '', (string) $_POST['otp']);
    }
    return (string) (($_POST['d1'] ?? '') . ($_POST['d2'] ?? '') . ($_POST['d3'] ?? '') . ($_POST['d4'] ?? ''));
}

function otp_verify_posted(array &$ctx, string $otpInput): array {
    $len = defined('OTP_LENGTH') ? (int) OTP_LENGTH : 4;
    if (!preg_match('/^\d{' . $len . '}$/', $otpInput)) {
        return ['ok' => false, 'error' => 'Please enter the ' . $len . '-digit OTP.'];
    }
    if (empty($ctx['hash']) || empty($ctx['mobile_e164'])) {
        return ['ok' => false, 'error' => 'Please request an OTP first.'];
    }
    if (time() > (int) $ctx['expires_at']) {
        return ['ok' => false, 'error' => 'OTP expired. Tap Resend.'];
    }

    $ctx['attempts'] = (int) ($ctx['attempts'] ?? 0) + 1;
    if ($ctx['attempts'] > 5) {
        $ctx = otp_empty_ctx();
        return ['ok' => false, 'error' => 'Too many attempts. Please request a new OTP.', 'reset' => true];
    }

    if (!password_verify($otpInput, $ctx['hash'])) {
        return ['ok' => false, 'error' => 'Login failed. Incorrect OTP.'];
    }

    return ['ok' => true, 'error' => '', 'mobile' => $ctx['mobile_e164']];
}
