<?php
// Student SMS OTP login — first login creates the student profile

session_start();
require_once __DIR__ . '/includes/sms_otp.php';
require_once __DIR__ . '/includes/student_repository.php';
require_once __DIR__ . '/includes/staff_repository.php';
require_once __DIR__ . '/includes/staff_auth.php';

capture_staff_referral();
$lockedStaff = get_locked_referral_staff();

if (is_form_user_logged_in()) {
    $u = get_form_user();
    header('Location: ' . (($u['type'] ?? '') === 'admin' ? 'index.php' : account_home_url()));
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$next = $_GET['next'] ?? $_POST['next'] ?? 'my_registrations.php';
$allowedNext = ['index.php', 'my_registrations.php', 'my_profile.php'];
if (!in_array(basename($next), $allowedNext, true)) {
    $next = 'index.php';
}

$msg_error = '';
$msg_info = '';
$debug_block = '';

if (!isset($_SESSION['student_otp_ctx'])) {
    $_SESSION['student_otp_ctx'] = otp_empty_ctx();
}
$ctx = &$_SESSION['student_otp_ctx'];

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
        if (!preg_match('/^[6-9]\d{9}$/', $mobile_input)) {
            $msg_error = 'Please enter a valid 10-digit mobile number.';
        } else {
            $to_e164 = to_e164($mobile_input);
            if (!$to_e164) {
                $msg_error = 'Please enter a valid mobile number.';
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
            try {
                $student = student_create_or_get($verified['mobile']);
            } catch (Throwable $e) {
                $msg_error = 'Could not create student profile. Please try again.';
                $student = null;
            }
            if ($student) {
                session_regenerate_id(true);
                $_SESSION['student_auth_user'] = $verified['mobile'];
                $_SESSION['student_auth_id'] = (int) $student['id'];
                $_SESSION['student_auth_name'] = $student['name'] ?: 'Student';
                mark_student_login($verified['mobile']);
                $ctx = otp_empty_ctx();
                if (basename($next) === 'my_registrations.php') {
                    $next = account_home_url();
                }
                header('Location: ' . $next);
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

$page_title = 'Participant Login';
$page_heading = 'Participant Login';
$page_sub = $lockedStaff
    ? 'Form via ' . $lockedStaff['name'] . '. Enter mobile number for SMS OTP.'
    : 'Enter your mobile number to receive SMS OTP and open the form';
$phone_label = 'Mobile number*';
$note_text = 'First login creates your profile. Username = mobile, password = SMS OTP.';
$show_program_info = false;
$extra_links = '<a href="staff/login.php">Staff Login</a>';
$staff_ref = (string) ($_SESSION['staff_ref'] ?? '');
$otp_length = (int) OTP_LENGTH;
$country_code = SMS_COUNTRY_CODE;

require __DIR__ . '/includes/otp_login_view.php';
