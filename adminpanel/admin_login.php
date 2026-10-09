<?php
// Admin SMS OTP login (mobile number is the username)

session_start();
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/staff_repository.php';
require_once __DIR__ . '/admin_auth.php';

if (is_admin_logged_in()) {
    header('Location: admin_dashboard.php');
    exit;
}

seed_empty_admin_passwords();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$next = $_GET['next'] ?? $_POST['next'] ?? 'admin_dashboard.php';
$allowedNext = ['admin_dashboard.php', 'staff_manage.php', 'settings.php'];
if (!in_array(basename($next), $allowedNext, true)) {
    $next = 'admin_dashboard.php';
}

$login_method = $_POST['mode'] ?? $_GET['mode'] ?? 'password';
if (!in_array($login_method, ['password', 'otp'], true)) {
    $login_method = 'password';
}

$msg_error = '';
$msg_info = '';
$debug_block = '';

if (!isset($_SESSION['admin_otp_ctx'])) {
    $_SESSION['admin_otp_ctx'] = otp_empty_ctx();
}
$ctx = &$_SESSION['admin_otp_ctx'];

function set_debug($data): void {
    global $debug_block;
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $debug_block = '<pre style="white-space:pre-wrap;background:#111;color:#eee;padding:10px;border-radius:8px;overflow:auto;">' .
            htmlspecialchars(is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
            '</pre>';
    }
}

function finish_admin_login(array $admin, string $dest): void {
    session_regenerate_id(true);
    refresh_admin_session($admin);
    mark_admin_login((string) $admin['phone']);
    unset($_SESSION['admin_otp_ctx']);
    header('Location: ' . $dest);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'password_login') {
        $login_method = 'password';
        $fails = (int) ($_SESSION['admin_pw_fails'] ?? 0);
        $lockUntil = (int) ($_SESSION['admin_pw_lock'] ?? 0);
        if (time() < $lockUntil) {
            $wait = $lockUntil - time();
            $msg_error = 'Too many attempts. Try again in ' . $wait . ' seconds.';
        } else {
            $result = admin_password_login($_POST['mobile'] ?? '', (string) ($_POST['password'] ?? ''));
            if ($result['ok']) {
                unset($_SESSION['admin_pw_fails'], $_SESSION['admin_pw_lock']);
                $dest = in_array(basename($next), $allowedNext, true) ? basename($next) : 'admin_dashboard.php';
                finish_admin_login($result['admin'], $dest);
            } else {
                $fails++;
                $_SESSION['admin_pw_fails'] = $fails;
                if ($fails >= 8) {
                    $_SESSION['admin_pw_lock'] = time() + 300;
                    $_SESSION['admin_pw_fails'] = 0;
                }
                $msg_error = $result['error'];
            }
        }
    }

    if ($action === 'send_otp') {
        $login_method = 'otp';
        $mobile_input = trim($_POST['mobile'] ?? '');
        if (!preg_match('/^\d{10}$/', $mobile_input)) {
            $msg_error = 'Please enter a valid 10-digit phone number.';
        } else {
            $to_e164 = to_e164($mobile_input);
            $admin = $to_e164 ? get_active_admin_by_phone($to_e164) : null;
            if (!$admin) {
                $msg_error = 'This mobile is not authorized as admin.';
            } else {
                $sent = otp_dispatch($ctx, $to_e164);
                if ($sent['ok']) {
                    $msg_info = 'OTP has been sent to your mobile via SMS.';
                } else {
                    $msg_error = $sent['error'];
                    if (!empty($sent['debug'])) {
                        set_debug($sent['debug']);
                    }
                }
            }
        }
    }

    if ($action === 'resend_otp') {
        if (empty($ctx['mobile_e164'])) {
            $msg_error = 'Please enter your phone number first.';
        } else {
            $sent = otp_dispatch($ctx, $ctx['mobile_e164']);
            if ($sent['ok']) {
                $msg_info = 'OTP has been sent to your mobile via SMS.';
            } else {
                $msg_error = $sent['error'];
                if (!empty($sent['debug'])) {
                    set_debug($sent['debug']);
                }
            }
        }
    }

    if ($action === 'change_phone') {
        $ctx = otp_empty_ctx();
        $msg_info = 'You can change your phone number now.';
    }

    if ($action === 'login') {
        $verified = otp_verify_posted($ctx, otp_read_posted_code());
        if (!$verified['ok']) {
            $msg_error = $verified['error'];
            if (!empty($verified['reset'])) {
                $ctx = otp_empty_ctx();
            }
        } else {
            $admin = get_active_admin_by_phone($verified['mobile']);
            if (!$admin) {
                $msg_error = 'This mobile is not authorized as admin.';
            } else {
                $dest = $_POST['next'] ?? 'admin_dashboard.php';
                $dest = in_array(basename($dest), $allowedNext, true) ? basename($dest) : 'admin_dashboard.php';
                finish_admin_login($admin, $dest);
            }
        }
    }
}

$otp_active = otp_has_active($ctx);
$mobile_prefill = local_10_digit($ctx['mobile_e164'] ?? null);
if (($_POST['action'] ?? '') === 'password_login' && !empty($_POST['mobile'])) {
    $mobile_prefill = substr(preg_replace('/\D+/', '', (string) $_POST['mobile']), -10);
}
$cooldownRemaining = 0;
if ($otp_active && !empty($ctx['last_sent_at'])) {
    $cooldownRemaining = max(0, OTP_RESEND_COOLDOWN - (time() - (int) $ctx['last_sent_at']));
}

$page_title = 'Admin Login';
$page_heading = 'Admin Login';
$page_sub = 'Login with mobile + password, or SMS OTP';
$phone_label = 'Admin mobile*';
$note_text = $login_method === 'password'
    ? 'Use your registered mobile and password. You can also log in with SMS OTP.'
    : 'Only authorized admin mobiles can request OTP.';
$otp_length = (int) OTP_LENGTH;
$country_code = SMS_COUNTRY_CODE;
$allow_password_login = true;
if ($otp_active) {
    $login_method = 'otp';
}

require __DIR__ . '/../includes/otp_login_view.php';
