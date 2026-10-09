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

if (!function_exists('landing_page_title')) {
    require_once __DIR__ . '/includes/app_settings.php';
}
if (!function_exists('form_workshop_period_label')) {
    require_once __DIR__ . '/includes/form_catalog.php';
}
$page_title = landing_page_title();
$login_showcase = true;
$send_otp_label = 'Send OTP';
$eventTitle = $page_title;
$showcase_kicker = '';
$showcase_accent = $eventTitle;
if (preg_match('/^SVSS\s+(.+)$/iu', $eventTitle, $titleParts)) {
    $showcase_kicker = 'SVSS';
    $showcase_accent = $titleParts[1];
}
$showcase_tagline = 'मोबाइल OTP ने नोंदणी करा. पेमेंट झाल्यावर पास नंबर आणि तिकीट मिळेल.';
$page_heading = 'Login';
$page_sub = $lockedStaff
    ? 'Form via ' . $lockedStaff['name'] . '. Enter your mobile number.'
    : 'Enter your mobile number to receive the OTP.';
$show_program_info = landing_is_enabled();
$program_facts = [];
if ($show_program_info) {
    $when = form_workshop_period_label();
    $venue = trim((string) strtok(form_event_venue_label(), "\r\n"));
    if ($when !== '') {
        $program_facts[] = ['When', $when];
    }
    if ($venue !== '') {
        $program_facts[] = ['Where', $venue];
    }
    if (landing_show_fees()) {
        $program_facts[] = ['Fee', landing_entry_fee()['label']];
    }
}
$phone_label = 'Mobile number *';
$note_text = '';
$extra_links = '<a href="staff/login.php">Staff Login</a>';
$staff_ref = (string) ($_SESSION['staff_ref'] ?? '');
$otp_length = (int) OTP_LENGTH;
$country_code = SMS_COUNTRY_CODE;

require __DIR__ . '/includes/otp_login_view.php';
