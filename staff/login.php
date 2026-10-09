<?php
session_start();
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/staff_repository.php';
require_once __DIR__ . '/../includes/staff_auth.php';

if (is_staff_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$next = 'dashboard.php';
$msg_error = '';
$msg_info = '';
$debug_block = '';

if (!isset($_SESSION['staff_otp_ctx'])) {
    $_SESSION['staff_otp_ctx'] = otp_empty_ctx();
}
$ctx = &$_SESSION['staff_otp_ctx'];

function set_debug($data): void {
    global $debug_block;
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $debug_block = '<pre style="white-space:pre-wrap;background:#111;color:#eee;padding:10px;border-radius:8px;overflow:auto;">' .
            htmlspecialchars(is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
            '</pre>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp') {
        $mobile_input = trim($_POST['mobile'] ?? '');
        if (!preg_match('/^\d{10}$/', $mobile_input)) {
            $msg_error = 'Please enter a valid 10-digit phone number.';
        } else {
            $to_e164 = to_e164($mobile_input);
            $staff = $to_e164 ? get_active_staff_by_phone($to_e164) : null;
            if (!$staff) {
                $msg_error = 'This mobile is not registered as staff. Please ask admin to create your login.';
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
            $staff = get_active_staff_by_phone($verified['mobile']);
            if (!$staff) {
                $msg_error = 'This mobile is not registered as staff.';
            } else {
                session_regenerate_id(true);
                $_SESSION['staff_auth_user'] = $verified['mobile'];
                $_SESSION['staff_auth_id'] = (int) $staff['id'];
                $_SESSION['staff_auth_name'] = $staff['name'] ?? 'Staff';
                mark_staff_login($verified['mobile']);
                $ctx = otp_empty_ctx();
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}

$otp_active = otp_has_active($ctx);
$mobile_prefill = local_10_digit($ctx['mobile_e164'] ?? null);
$cooldownRemaining = 0;
if ($otp_active && !empty($ctx['last_sent_at'])) {
    $cooldownRemaining = max(0, OTP_RESEND_COOLDOWN - (time() - (int) $ctx['last_sent_at']));
}

$page_title = 'Staff Login';
$page_heading = 'Staff Login';
$page_sub = 'See forms filled through you';
$phone_label = 'Staff mobile*';
$note_text = 'Only staff created by admin can log in. Username = mobile, password = SMS OTP.';
$extra_links = '<a href="../login.php">Participant registration</a>';
$otp_length = (int) OTP_LENGTH;
$country_code = SMS_COUNTRY_CODE;

require __DIR__ . '/../includes/otp_login_view.php';
