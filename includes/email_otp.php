<?php
/**
 * ZeptoMail transactional email OTP.
 */

require_once __DIR__ . '/app_settings.php';

function send_email_otp(string $toEmail, string $otp, string $toName = ''): array
{
    $result = ['ok' => false, 'http_code' => 0, 'response' => null, 'error' => null];
    $toEmail = trim($toEmail);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $result['error'] = 'Invalid recipient email';
        log_notification('email', 'error', $toEmail, 'Invalid recipient email');
        return $result;
    }

    $s = get_app_settings();
    $token = trim((string) ($s['zepto_send_mail_token'] ?? ''));
    $from = trim((string) ($s['zepto_from_email'] ?? ''));
    $fromName = trim((string) ($s['zepto_from_name'] ?? 'AGNIPANKH'));
    if ($token === '' || $from === '') {
        $result['error'] = 'ZEPTOMAIL_CONFIG_MISSING';
        log_notification('email', 'error', $toEmail, 'ZeptoMail token or from-email is missing');
        return $result;
    }

    $minutes = otp_expiry_minutes();
    $subject = trim((string) ($s['otp_email_subject'] ?? 'Your login OTP'));
    $html = render_otp_email_html((string) ($s['otp_email_html'] ?? ''), $otp, $minutes);
    $method = ($s['zepto_send_method'] ?? 'api') === 'smtp' ? 'smtp' : 'api';

    if ($method === 'smtp') {
        return send_email_otp_smtp($s, $toEmail, $toName, $subject, $html, $otp);
    }
    return send_email_otp_api($s, $toEmail, $toName, $subject, $html);
}

function zepto_api_error_text($decoded, string $fromEmail, int $httpCode): string
{
    $code = '';
    $detailCode = '';
    $detailMsg = '';
    $target = $fromEmail;
    $apiMsg = '';

    if (is_array($decoded)) {
        $err = isset($decoded['error']) && is_array($decoded['error']) ? $decoded['error'] : $decoded;
        $code = (string) ($err['code'] ?? $decoded['code'] ?? '');
        $apiMsg = (string) ($err['message'] ?? $decoded['message'] ?? '');
        $details = $err['details'] ?? [];
        if (is_array($details) && isset($details[0]) && is_array($details[0])) {
            $detailCode = (string) ($details[0]['code'] ?? '');
            $detailMsg = (string) ($details[0]['message'] ?? '');
            $tv = trim((string) ($details[0]['target_value'] ?? ''));
            if ($tv !== '') {
                $target = $tv;
            }
        } elseif (is_string($details) && $details !== '') {
            $detailMsg = $details;
        }
    }

    if ($code === 'TM_4001' || $detailCode === 'SM_111' || stripos($detailMsg, 'not verified') !== false) {
        return 'Sender not verified (SM_111): the From email / domain "' . $target . '" is not verified on the ZeptoMail Agent. '
            . 'Verify the domain in ZeptoMail → Agent → Domains, add this address as a sender, '
            . 'or enter an already-verified From email in Settings.';
    }

    $parts = array_filter([$code, $detailCode, $apiMsg, $detailMsg]);
    if ($parts) {
        return 'ZEPTOMAIL: ' . implode(' — ', array_unique($parts));
    }

    return 'ZEPTOMAIL: HTTP_' . $httpCode . ' send failed';
}

function zepto_auth_header(string $token): string
{
    $token = trim($token);
    if (stripos($token, 'Zoho-enczapikey') === 0) {
        return $token;
    }
    return 'Zoho-enczapikey ' . $token;
}

function send_email_otp_api(array $s, string $toEmail, string $toName, string $subject, string $html): array
{
    $result = ['ok' => false, 'http_code' => 0, 'response' => null, 'error' => null];
    $host = zepto_api_host((string) ($s['zepto_data_center'] ?? 'in'));
    $url = 'https://' . $host . '/v1.1/email';
    $fromName = trim((string) ($s['zepto_from_name'] ?? 'AGNIPANKH'));
    $payload = [
        'from' => [
            'address' => $s['zepto_from_email'],
            'name' => $fromName !== '' ? $fromName : 'AGNIPANKH',
        ],
        'to' => [[
            'email_address' => [
                'address' => $toEmail,
                'name' => $toName !== '' ? $toName : $toEmail,
            ],
        ]],
        'subject' => $subject,
        'htmlbody' => $html,
    ];
    $bounce = trim((string) ($s['zepto_bounce_address'] ?? ''));
    if ($bounce !== '') {
        $payload['bounce_address'] = $bounce;
    }
    $reply = trim((string) ($s['zepto_reply_to_email'] ?? ''));
    if ($reply !== '') {
        $payload['reply_to'] = [['address' => $reply, 'name' => $fromName]];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: ' . zepto_auth_header((string) $s['zepto_send_mail_token']),
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
        log_notification('email', 'error', $toEmail, $result['error'], $httpCode);
        return $result;
    }

    $decoded = json_decode((string) $raw, true);
    $result['response'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $raw;
    if ($httpCode >= 200 && $httpCode < 300) {
        $result['ok'] = true;
        log_notification('email', 'ok', $toEmail, 'OTP email sent via ZeptoMail API', $httpCode, $result['response']);
        return $result;
    }

    $err = zepto_api_error_text($decoded, (string) ($s['zepto_from_email'] ?? ''), $httpCode);
    $result['error'] = $err;
    log_notification('email', 'error', $toEmail, $err, $httpCode, $result['response']);
    return $result;
}

function send_email_otp_smtp(array $s, string $toEmail, string $toName, string $subject, string $html, string $otp): array
{
    $result = ['ok' => false, 'http_code' => 0, 'response' => null, 'error' => null];
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        $result['error'] = 'PHPMailer is not installed';
        log_notification('email', 'error', $toEmail, $result['error']);
        return $result;
    }
    require_once $autoload;

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = zepto_smtp_host((string) ($s['zepto_data_center'] ?? 'in'));
        $mail->SMTPAuth = true;
        $mail->Username = 'emailapikey';
        $token = trim((string) $s['zepto_send_mail_token']);
        $mail->Password = preg_replace('/^Zoho-enczapikey\s+/i', '', $token);
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $fromName = trim((string) ($s['zepto_from_name'] ?? 'AGNIPANKH'));
        $mail->setFrom((string) $s['zepto_from_email'], $fromName !== '' ? $fromName : 'AGNIPANKH');
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
        $reply = trim((string) ($s['zepto_reply_to_email'] ?? ''));
        if ($reply !== '') {
            $mail->addReplyTo($reply, $fromName);
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = 'Your OTP is ' . $otp . '. Valid for ' . otp_expiry_minutes() . ' minutes.';
        $mail->send();
        $result['ok'] = true;
        $result['http_code'] = 200;
        log_notification('email', 'ok', $toEmail, 'OTP email sent via ZeptoMail SMTP', 200);
    } catch (Throwable $e) {
        $result['error'] = 'SMTP: ' . $e->getMessage();
        log_notification('email', 'error', $toEmail, $result['error']);
    }
    return $result;
}
