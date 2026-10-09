<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/email_otp.php';
require_once __DIR__ . '/../includes/staff_repository.php';
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/form_catalog.php';
require_once __DIR__ . '/../includes/payment_service.php';
ensure_form_catalog_schema();

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=settings.php');
    exit;
}

ensure_app_settings_schema();
seed_empty_admin_passwords();

$admin_phone = $_SESSION['admin_auth_user'];
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];
$msg_info = '';
$msg_error = '';
$tab = $_GET['tab'] ?? $_POST['tab'] ?? 'brand';
if (!in_array($tab, ['brand', 'landing', 'profile', 'payments', 'sms', 'email', 'logs'], true)) {
    $tab = 'brand';
}
$logFilter = $_GET['channel'] ?? 'all';
if (!in_array($logFilter, ['all', 'sms', 'email'], true)) {
    $logFilter = 'all';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_brand') {
        $tab = 'brand';
        $posted = $_POST;
        if (!empty($_POST['remove_logo'])) {
            $posted['brand_logo_file'] = '';
        } elseif (!empty($_FILES['brand_logo']['tmp_name'])) {
            $up = save_uploaded_image($_FILES['brand_logo'], 'logo');
            if (!$up['ok']) {
                $msg_error = $up['error'];
            } elseif (empty($up['skipped'])) {
                $posted['brand_logo_file'] = $up['path'];
            }
        }
        if ($msg_error === '') {
            $result = save_app_settings($posted, $admin_phone);
            if ($result['ok']) {
                $msg_info = 'Panel branding saved. Logo and name now show in the sidebar.';
            } else {
                $msg_error = $result['error'];
            }
        }
    }

    if ($action === 'save_profile') {
        $tab = 'profile';
        $adminId = (int) ($_SESSION['admin_auth_id'] ?? 0);
        $photoPath = null;
        if (!empty($_POST['remove_photo'])) {
            $photoPath = '';
        } elseif (!empty($_FILES['admin_photo']['tmp_name'])) {
            $up = save_uploaded_image($_FILES['admin_photo'], 'admin_' . $adminId);
            if (!$up['ok']) {
                $msg_error = $up['error'];
            } elseif (empty($up['skipped'])) {
                $photoPath = $up['path'];
            }
        }
        if ($msg_error === '') {
            $result = update_admin_profile(
                $adminId,
                $_POST['admin_name'] ?? '',
                $_POST['admin_email'] ?? '',
                $_POST['admin_mobile'] ?? '',
                $photoPath
            );
            if ($result['ok'] && !empty($result['admin'])) {
                refresh_admin_session($result['admin']);
                $admin_phone = $_SESSION['admin_auth_user'];
                $msg_info = 'Your profile was saved.';
            } else {
                $msg_error = $result['error'] ?? 'Could not save profile.';
            }
        }
    }

    if ($action === 'change_password') {
        $tab = 'profile';
        $adminId = (int) ($_SESSION['admin_auth_id'] ?? 0);
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $current = (string) ($_POST['current_password'] ?? '');
        $meNow = $adminId ? get_admin_by_id($adminId) : get_admin_by_phone($admin_phone);
        if (!$meNow) {
            $msg_error = 'Admin profile not found.';
        } elseif ($new !== $confirm) {
            $msg_error = 'New password and confirm password do not match.';
        } elseif (strlen($new) < 8) {
            $msg_error = 'New password must be at least 8 characters.';
        } elseif (!empty($meNow['password_hash']) && !password_verify($current, (string) $meNow['password_hash'])) {
            $msg_error = 'Current password is incorrect.';
        } else {
            $result = set_admin_password((int) ($meNow['id'] ?? 0), $new);
            $msg_info = $result['ok'] ? 'Password updated. Use it on the next login.' : '';
            $msg_error = $result['ok'] ? '' : $result['error'];
        }
    }

    if ($action === 'add_admin') {
        $tab = 'profile';
        $role = is_super_admin() ? ($_POST['new_admin_role'] ?? 'admin') : 'admin';
        $result = add_admin_account(
            $_POST['new_admin_name'] ?? '',
            $_POST['new_admin_mobile'] ?? '',
            $_POST['new_admin_email'] ?? '',
            $role,
            $admin_phone,
            (string) ($_POST['new_admin_password'] ?? '')
        );
        $msg_info = $result['ok'] ? 'Admin added. They can log in with mobile + password or SMS OTP.' : '';
        $msg_error = $result['ok'] ? '' : $result['error'];
    }

    if ($action === 'update_admin') {
        $tab = 'profile';
        $id = (int) ($_POST['id'] ?? 0);
        $role = is_super_admin() ? ($_POST['admin_role'] ?? 'admin') : (get_admin_by_id($id)['role'] ?? 'admin');
        $result = update_admin_account(
            $id,
            $_POST['admin_name'] ?? '',
            $_POST['admin_email'] ?? '',
            $_POST['admin_mobile'] ?? '',
            $role,
            $_POST['admin_status'] ?? 'active',
            (string) ($_POST['admin_password'] ?? '')
        );
        if ($result['ok']) {
            if ($id === (int) ($_SESSION['admin_auth_id'] ?? 0) && !empty($result['admin'])) {
                refresh_admin_session($result['admin']);
                $admin_phone = $_SESSION['admin_auth_user'];
            }
            $msg_info = 'Admin updated.';
            header('Location: settings.php?tab=profile');
            exit;
        } else {
            $msg_error = $result['error'];
        }
    }

    if ($action === 'delete_admin') {
        $tab = 'profile';
        $result = delete_admin_account((int) ($_POST['id'] ?? 0), (int) ($_SESSION['admin_auth_id'] ?? 0));
        $msg_info = $result['ok'] ? 'Admin deleted.' : '';
        $msg_error = $result['ok'] ? '' : $result['error'];
    }

    if ($action === 'toggle_admin') {
        $tab = 'profile';
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) ($_SESSION['admin_auth_id'] ?? 0)) {
            $msg_error = 'You cannot deactivate your own login.';
        } elseif (($_POST['status'] ?? '') === 'active' && count_admins('active') <= 1) {
            $msg_error = 'You cannot deactivate the last active admin.';
        } else {
            $status = ($_POST['status'] ?? '') === 'active' ? 'inactive' : 'active';
            $msg_info = ($id && set_admin_status($id, $status))
                ? ($status === 'active' ? 'Admin activated.' : 'Admin deactivated.')
                : '';
            if ($msg_info === '') {
                $msg_error = 'Could not update admin status.';
            }
        }
    }

    if ($action === 'save_razorpay') {
        $tab = 'payments';
        $posted = $_POST;
        $posted['payment_test_mode'] = !empty($_POST['payment_test_mode']) ? '1' : '0';
        $result = save_app_settings($posted, $admin_phone);
        $msg_info = $result['ok'] ? 'Razorpay and exam fee settings saved. New forms will use these values.' : '';
        $msg_error = $result['ok'] ? '' : $result['error'];
    }

    if ($action === 'save_landing') {
        $tab = 'landing';
        $posted = $_POST;
        $posted['landing_enabled'] = !empty($_POST['landing_enabled']) ? '1' : '0';
        $posted['landing_show_fees'] = !empty($_POST['landing_show_fees']) ? '1' : '0';
        $posted['icard_photo_on_form'] = !empty($_POST['icard_photo_on_form']) ? '1' : '0';
        $posted['staff_ask_on_form'] = !empty($_POST['staff_ask_on_form']) ? '1' : '0';
        $posted['coupons_on_form'] = !empty($_POST['coupons_on_form']) ? '1' : '0';
        if (!empty($_POST['remove_poster'])) {
            $posted['landing_poster_file'] = '';
        } elseif (!empty($_FILES['landing_poster']['tmp_name'])) {
            $up = save_uploaded_image($_FILES['landing_poster'], 'poster', 8 * 1024 * 1024);
            if (!$up['ok']) {
                $msg_error = $up['error'];
            } elseif (empty($up['skipped'])) {
                $posted['landing_poster_file'] = $up['path'];
            }
        }
        if ($msg_error === '') {
            $result = save_app_settings($posted, $admin_phone);
            $msg_info = $result['ok'] ? 'Landing page saved.' : '';
            $msg_error = $result['ok'] ? '' : $result['error'];
        }
    }

    if ($action === 'save_sms' || $action === 'save_email') {
        $tab = $action === 'save_email' ? 'email' : 'sms';
        $result = save_app_settings($_POST, $admin_phone);
        if ($result['ok']) {
            $msg_info = $tab === 'email' ? 'Email OTP settings saved.' : 'SMS OTP settings saved.';
        } else {
            $msg_error = $result['error'];
        }
    }

    if ($action === 'test_sms') {
        $tab = 'sms';
        $to = to_e164(trim($_POST['test_mobile'] ?? '') ?: local_10_digit($admin_phone));
        if (!$to) {
            $msg_error = 'Enter a valid 10-digit mobile number for the test SMS.';
        } else {
            $otp = generate_login_otp();
            $res = send_sms_otp($to, $otp);
            if ($res['ok']) {
                $msg_info = 'Test SMS sent to +' . $to . '. OTP used for this test: ' . $otp;
            } else {
                $msg_error = 'Test SMS failed. ' . ($res['error'] ?: 'Check the error log.');
            }
        }
    }

    if ($action === 'test_email') {
        $tab = 'email';
        $email = trim($_POST['test_email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg_error = 'Enter a valid email address for the test.';
        } else {
            $otp = generate_login_otp();
            $res = send_email_otp($email, $otp, get_admin_name());
            if ($res['ok']) {
                $msg_info = 'Test email sent to ' . $email . '. OTP used for this test: ' . $otp;
            } else {
                $msg_error = 'Test email failed. ' . ($res['error'] ?: 'Check the error log.');
            }
        }
    }

    if ($action === 'clear_logs') {
        $tab = 'logs';
        $msg_info = clear_notification_logs() ? 'Logs cleared.' : 'Could not clear logs.';
    }
}

$settings = get_app_settings();
$logs = $tab === 'logs' ? get_notification_logs($logFilter) : [];
$logCounts = notification_log_counts();
$adminId = (int) ($_SESSION['admin_auth_id'] ?? 0);
$me = $adminId ? get_admin_by_id($adminId) : get_admin_by_phone($admin_phone);
if ($me) {
    refresh_admin_session($me);
    $admin_phone = (string) $me['phone'];
}
$adminList = $tab === 'profile' ? get_all_admins() : [];
$editAdminId = (int) ($_GET['edit'] ?? 0);
if ($tab === 'profile' && ($_POST['action'] ?? '') === 'update_admin' && $msg_error) {
    $editAdminId = (int) ($_POST['id'] ?? 0);
}
$editAdmin = ($tab === 'profile' && $editAdminId) ? get_admin_by_id($editAdminId) : null;

panel_start([
    'title' => 'Settings',
    'role' => 'admin',
    'active' => $tab === 'landing' ? 'landing' : 'settings',
    'name' => get_admin_name(),
    'phone' => local_phone_display($admin_phone),
    'photo' => get_admin_photo(),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
]);

$v = static function (array $s, string $key): string {
    return htmlspecialchars((string) ($s[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<div class="hero">
  <div>
    <h2>Settings</h2>
    <p>Change the panel logo, registration landing page, admin logins, Razorpay, and OTP settings here.</p>
  </div>
  <div class="hero-pill"><?php echo htmlspecialchars(panel_brand_name()); ?></div>
</div>

<?php if ($msg_info): ?><div class="msg info"><?php echo htmlspecialchars($msg_info); ?></div><?php endif; ?>
<?php if ($msg_error): ?><div class="msg error"><?php echo htmlspecialchars($msg_error); ?></div><?php endif; ?>

<div class="settings-tabs" role="tablist">
  <a class="<?php echo $tab === 'brand' ? 'active' : ''; ?>" href="settings.php?tab=brand">Logo &amp; branding</a>
  <a class="<?php echo $tab === 'landing' ? 'active' : ''; ?>" href="settings.php?tab=landing">Event page</a>
  <a class="<?php echo $tab === 'profile' ? 'active' : ''; ?>" href="settings.php?tab=profile">Admin profile</a>
  <a class="<?php echo $tab === 'payments' ? 'active' : ''; ?>" href="settings.php?tab=payments">Razorpay / fees</a>
  <a class="<?php echo $tab === 'sms' ? 'active' : ''; ?>" href="settings.php?tab=sms">SMS OTP (MSG91)</a>
  <a class="<?php echo $tab === 'email' ? 'active' : ''; ?>" href="settings.php?tab=email">Email OTP (ZeptoMail)</a>
  <a class="<?php echo $tab === 'logs' ? 'active' : ''; ?>" href="settings.php?tab=logs">SMS / email error log</a>
</div>

<?php if ($tab === 'brand'): ?>
<form method="post" enctype="multipart/form-data" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="save_brand">
  <input type="hidden" name="tab" value="brand">
  <input type="hidden" name="brand_logo_file" value="<?php echo $v($settings, 'brand_logo_file'); ?>">
  <div class="card">
    <div class="card-head"><h3>Panel logo &amp; name</h3></div>
    <p class="hint-block">This appears in the left sidebar: logo + name. Changes show immediately after save.</p>
    <div class="brand-preview" id="brandPreview">
      <img id="previewLogo" src="<?php echo htmlspecialchars(panel_logo_src('../')); ?>" alt="">
      <div>
        <b id="previewName"><?php echo htmlspecialchars(panel_brand_name()); ?></b>
        <small id="previewTag"><?php echo htmlspecialchars(panel_tagline('admin')); ?></small>
      </div>
    </div>
    <div class="settings-grid">
      <div class="field">
        <label for="brand_name">Brand name</label>
        <input id="brand_name" name="brand_name" maxlength="60" value="<?php echo $v($settings, 'brand_name'); ?>" placeholder="Registration">
        <small>Large name in the sidebar.</small>
      </div>
      <div class="field">
        <label for="brand_tagline_admin">Admin panel label</label>
        <input id="brand_tagline_admin" name="brand_tagline_admin" maxlength="40" value="<?php echo $v($settings, 'brand_tagline_admin'); ?>" placeholder="Admin panel">
        <small>Small label under the logo.</small>
      </div>
      <div class="field">
        <label for="brand_tagline_staff">Staff panel label</label>
        <input id="brand_tagline_staff" name="brand_tagline_staff" maxlength="40" value="<?php echo $v($settings, 'brand_tagline_staff'); ?>" placeholder="Staff panel">
        <small>Label shown in the staff panel.</small>
      </div>
      <div class="field">
        <label for="brand_logo_url">Logo URL (optional)</label>
        <input id="brand_logo_url" name="brand_logo_url" value="<?php echo $v($settings, 'brand_logo_url'); ?>" placeholder="https://...">
        <small>Used when no logo file is uploaded.</small>
      </div>
    </div>
    <div class="field" style="margin-top:14px;">
      <label for="brand_logo">Upload logo</label>
      <input id="brand_logo" type="file" name="brand_logo" accept="image/png,image/jpeg,image/webp,image/gif">
      <small>PNG / JPG / WEBP, maximum 2 MB. A round logo looks best.</small>
      <label class="declaration-check" style="margin-top:10px;display:flex;gap:8px;align-items:center;">
        <input type="checkbox" name="remove_logo" value="1">
        <span>Remove uploaded logo and use the URL / default</span>
      </label>
    </div>
    <div class="settings-actions">
      <button class="btn gold" type="submit">Save branding</button>
    </div>
  </div>
</form>
<?php endif; ?>

<?php if ($tab === 'landing'): ?>
<?php
$posterSrc = landing_poster_src('../');
?>
<form method="post" enctype="multipart/form-data" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="save_landing">
  <input type="hidden" name="tab" value="landing">
  <input type="hidden" name="landing_poster_file" value="<?php echo $v($settings, 'landing_poster_file'); ?>">
  <div class="card">
    <div class="card-head"><h3>Guest photo</h3></div>
    <p class="hint-block">The entry ticket uses a QR and the participant number. Leave this off unless you also want every guest to upload a photo on the form.</p>
    <label class="declaration-check" style="margin:0 0 4px;display:flex;gap:8px;align-items:center;">
      <input type="checkbox" name="icard_photo_on_form" value="1" <?php echo ($settings['icard_photo_on_form'] ?? '0') === '1' ? 'checked' : ''; ?>>
      <span>Ask for a photo on the registration form</span>
    </label>
  </div>
  <div class="card">
    <div class="card-head"><h3>Staff on registration form</h3></div>
    <p class="hint-block">When this is on, the form asks whether someone referred the guest. Yes shows the names from Staff logins.</p>
    <label class="declaration-check" style="margin:0 0 4px;display:flex;gap:8px;align-items:center;">
      <input type="checkbox" name="staff_ask_on_form" value="1" <?php echo ($settings['staff_ask_on_form'] ?? '1') === '1' ? 'checked' : ''; ?>>
      <span>Ask who referred this guest</span>
    </label>
  </div>
  <div class="card">
    <div class="card-head"><h3>Coupon codes on the form</h3></div>
    <p class="hint-block">When on, a coupon box appears above the fee. Add codes under Coupon codes. Each code sets its own fee and how many times it can be used.</p>
    <label class="declaration-check" style="margin:0 0 4px;display:flex;gap:8px;align-items:center;">
      <input type="checkbox" name="coupons_on_form" value="1" <?php echo ($settings['coupons_on_form'] ?? '0') === '1' ? 'checked' : ''; ?>>
      <span>Show coupon code on the registration form</span>
    </label>
  </div>
  <div class="card">
    <div class="card-head">
      <h3>Event page</h3>
      <a class="btn sm" href="../index.php" target="_blank" rel="noopener">Open registration form</a>
    </div>
    <p class="hint-block">The title is used on the form, the entry ticket, and the participant cards. The first line of the date is the time shown on the ticket — include the year, for example 17 Oct 2026, 6:00 pm. The venue is printed on the ticket. One line in a box below becomes one bullet.</p>
    <p class="hint-block">Colleges are edited under <a href="colleges.php">Colleges</a>. The entry fee is on the Razorpay / fees tab. Paid guests get a participant number automatically; print the 3 × 3 inch cards from <a href="participant_cards.php">Participant cards</a>.</p>
    <label class="declaration-check" style="margin:0 0 10px;display:flex;gap:8px;align-items:center;">
      <input type="checkbox" name="landing_enabled" value="1" <?php echo ($settings['landing_enabled'] ?? '1') !== '0' ? 'checked' : ''; ?>>
      <span>Show the event page before login (off = open the login form directly)</span>
    </label>
    <label class="declaration-check" style="margin:0 0 14px;display:flex;gap:8px;align-items:center;">
      <input type="checkbox" name="landing_show_fees" value="1" <?php echo ($settings['landing_show_fees'] ?? '1') !== '0' ? 'checked' : ''; ?>>
      <span>Show the entry fee on the event page</span>
    </label>
    <div class="settings-grid">
      <div class="field">
        <label for="landing_title">Event title</label>
        <input id="landing_title" name="landing_title" maxlength="160" value="<?php echo $v($settings, 'landing_title'); ?>">
      </div>
      <div class="field">
        <label for="landing_subtitle">Line under the title</label>
        <input id="landing_subtitle" name="landing_subtitle" maxlength="240" value="<?php echo $v($settings, 'landing_subtitle'); ?>">
      </div>
      <div class="field">
        <label for="landing_cta">Register button</label>
        <input id="landing_cta" name="landing_cta" maxlength="80" value="<?php echo $v($settings, 'landing_cta'); ?>" placeholder="Register for Dandiya Night">
      </div>
      <div class="field">
        <label for="landing_whatsapp">Help / Support WhatsApp (10 digit)</label>
        <input id="landing_whatsapp" name="landing_whatsapp" maxlength="10" inputmode="numeric" value="<?php echo $v($settings, 'landing_whatsapp'); ?>" placeholder="9975040405">
        <small>Green Help / Support button on login, form, My forms and receipt. Opens WhatsApp chat.</small>
      </div>
      <div class="field">
        <label for="event_group_url">WhatsApp group link</label>
        <input id="event_group_url" name="event_group_url" value="<?php echo $v($settings, 'event_group_url'); ?>" placeholder="https://chat.whatsapp.com/…">
      </div>
      <div class="field">
        <label for="event_photos_url">Event photographs link</label>
        <input id="event_photos_url" name="event_photos_url" value="<?php echo $v($settings, 'event_photos_url'); ?>" placeholder="https://…">
      </div>
      <div class="field">
        <label for="event_feedback_url">Feedback form link</label>
        <input id="event_feedback_url" name="event_feedback_url" value="<?php echo $v($settings, 'event_feedback_url'); ?>" placeholder="https://forms.gle/…">
      </div>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_venue">Venue</label>
      <textarea id="landing_venue" name="landing_venue" rows="2"><?php echo $v($settings, 'landing_venue'); ?></textarea>
      <small>Printed on the entry ticket. Example: Latur College of Pharmacy, Hasegaon</small>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_highlights">Short points under the poster</label>
      <textarea id="landing_highlights" name="landing_highlights" rows="4"><?php echo $v($settings, 'landing_highlights'); ?></textarea>
      <small>One tag per line.</small>
    </div>
    <div class="field" style="margin-top:16px;">
      <label>Event poster</label>
      <?php if ($posterSrc !== ''): ?>
        <div class="logo-preview-wrap" style="margin:8px 0 12px;">
          <img src="<?php echo htmlspecialchars($posterSrc); ?>" alt="Poster" style="max-width:280px;width:100%;height:auto;border-radius:10px;border:1px solid #e2e8f0;">
        </div>
      <?php endif; ?>
      <input id="landing_poster" type="file" name="landing_poster" accept="image/png,image/jpeg,image/webp,image/gif">
      <small>PNG / JPG / WEBP, maximum 8 MB. Full width on mobile. Tap to enlarge.</small>
      <div class="field" style="margin-top:10px;">
        <label for="landing_poster_url">Or poster URL</label>
        <input id="landing_poster_url" name="landing_poster_url" value="<?php echo $v($settings, 'landing_poster_url'); ?>" placeholder="https://...">
        <small>Used when no poster file is uploaded.</small>
      </div>
      <label class="declaration-check" style="margin-top:10px;display:flex;gap:8px;align-items:center;">
        <input type="checkbox" name="remove_poster" value="1">
        <span>Remove uploaded poster</span>
      </label>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_about">About the event</label>
      <textarea id="landing_about" name="landing_about" rows="5"><?php echo $v($settings, 'landing_about'); ?></textarea>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_who">Who can register</label>
      <textarea id="landing_who" name="landing_who" rows="4"><?php echo $v($settings, 'landing_who'); ?></textarea>
      <small>One line each. These are the same people as the ticket types: student, faculty / staff, alumni, guest.</small>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_how">How to register?</label>
      <textarea id="landing_how" name="landing_how" rows="5"><?php echo $v($settings, 'landing_how'); ?></textarea>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_need">What the guest fills in</label>
      <textarea id="landing_need" name="landing_need" rows="4"><?php echo $v($settings, 'landing_need'); ?></textarea>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_dates">Date, time and entry notes</label>
      <textarea id="landing_dates" name="landing_dates" rows="4"><?php echo $v($settings, 'landing_dates'); ?></textarea>
      <small>The first line is the date on the ticket and the year on the participant card. Example: 17 Oct 2026, 6:00 pm</small>
    </div>
    <div class="field" style="margin-top:12px;">
      <label for="landing_helpline">Help / contact</label>
      <textarea id="landing_helpline" name="landing_helpline" rows="3"><?php echo $v($settings, 'landing_helpline'); ?></textarea>
      <small>Shown on the form footer and receipt. Leave blank if you do not want public contact lines.</small>
    </div>
    <div class="card" style="margin-top:16px;box-shadow:none;border:1px solid #e2e8f0;">
      <div class="card-head"><h3>Declaration &amp; terms (one place for all pages)</h3></div>
      <p class="hint-block">This text appears on the registration form, the payment review checkbox, the receipt, and <a href="../legal.php" target="_blank" rel="noopener">legal.php</a>. Change it here once.</p>
      <div class="field">
        <label for="legal_checkbox_text">Checkbox text</label>
        <textarea id="legal_checkbox_text" name="legal_checkbox_text" rows="3"><?php echo $v($settings, 'legal_checkbox_text'); ?></textarea>
      </div>
      <div class="field" style="margin-top:12px;">
        <label for="legal_declaration_html">Guest declaration (HTML)</label>
        <textarea id="legal_declaration_html" name="legal_declaration_html" rows="12"><?php echo $v($settings, 'legal_declaration_html'); ?></textarea>
      </div>
      <div class="field" style="margin-top:12px;">
        <label for="legal_terms_html">Rules / terms and conditions (HTML)</label>
        <textarea id="legal_terms_html" name="legal_terms_html" rows="16"><?php echo $v($settings, 'legal_terms_html'); ?></textarea>
      </div>
      <div class="settings-grid" style="margin-top:12px;">
        <div class="field">
          <label for="legal_declaration_url">Declaration link (optional)</label>
          <input id="legal_declaration_url" name="legal_declaration_url" value="<?php echo $v($settings, 'legal_declaration_url'); ?>" placeholder="Leave blank to use legal.php">
        </div>
        <div class="field">
          <label for="legal_rules_url">Rules link (optional)</label>
          <input id="legal_rules_url" name="legal_rules_url" value="<?php echo $v($settings, 'legal_rules_url'); ?>" placeholder="Leave blank to use legal.php">
        </div>
      </div>
    </div>
    <div class="settings-actions">
      <button class="btn gold" type="submit">Save event page</button>
    </div>
  </div>
</form>
<?php endif; ?>

<?php if ($tab === 'profile'): ?>
<?php
$meName = (string) ($me['name'] ?? get_admin_name());
$meEmail = (string) ($me['email'] ?? '');
$mePhone = local_10_digit((string) ($me['phone'] ?? $admin_phone));
$meRole = (string) ($me['role'] ?? get_admin_role());
$mePhoto = (string) ($me['photo'] ?? '');
$mePhotoSrc = '';
if ($mePhoto !== '' && is_file(dirname(__DIR__) . '/' . ltrim($mePhoto, '/'))) {
    $mePhotoSrc = '../' . ltrim($mePhoto, '/') . '?v=' . filemtime(dirname(__DIR__) . '/' . ltrim($mePhoto, '/'));
}
?>
<form method="post" enctype="multipart/form-data" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="save_profile">
  <input type="hidden" name="tab" value="profile">
  <div class="card">
    <div class="card-head"><h3>My admin profile</h3></div>
    <p class="hint-block">Name, photo, and mobile appear in the sidebar and top bar. Mobile is the login username.</p>
    <div class="profile-meta">
      <span>Role: <?php echo htmlspecialchars($meRole); ?></span>
      <span>Status: <?php echo htmlspecialchars((string) ($me['status'] ?? 'active')); ?></span>
      <span>Last login: <?php echo !empty($me['last_login']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $me['last_login']))) : '—'; ?></span>
      <span>Logins: <?php echo (int) ($me['login_count'] ?? 0); ?></span>
    </div>
    <div class="logo-preview-wrap">
      <?php if ($mePhotoSrc): ?>
        <img src="<?php echo htmlspecialchars($mePhotoSrc); ?>" alt="">
      <?php else: ?>
        <span class="avatar" style="width:72px;height:72px;font-size:18px;"><?php echo htmlspecialchars(panel_initials($meName)); ?></span>
      <?php endif; ?>
      <div>
        <b><?php echo htmlspecialchars($meName); ?></b>
        <small class="hint-block" style="margin:4px 0 0;">If there is no photo, initials from the name are shown.</small>
      </div>
    </div>
    <div class="settings-grid">
      <div class="field">
        <label for="admin_name">Name</label>
        <input id="admin_name" name="admin_name" required minlength="2" value="<?php echo htmlspecialchars($meName); ?>">
      </div>
      <div class="field">
        <label for="admin_email">Email</label>
        <input id="admin_email" type="email" name="admin_email" value="<?php echo htmlspecialchars($meEmail); ?>" placeholder="you@example.com">
      </div>
      <div class="field">
        <label for="admin_mobile">Mobile (login)</label>
        <input id="admin_mobile" name="admin_mobile" required maxlength="10" inputmode="numeric" value="<?php echo htmlspecialchars($mePhone); ?>">
        <small>10 digits. Password and OTP both use this number.</small>
      </div>
      <div class="field">
        <label for="admin_photo">Profile photo</label>
        <input id="admin_photo" type="file" name="admin_photo" accept="image/png,image/jpeg,image/webp,image/gif">
        <small>PNG / JPG, maximum 2 MB.</small>
      </div>
    </div>
    <label style="display:flex;gap:8px;align-items:center;margin-top:12px;font-weight:600;">
      <input type="checkbox" name="remove_photo" value="1">
      Remove photo and use initials
    </label>
    <div class="settings-actions">
      <button class="btn gold" type="submit">Save my profile</button>
    </div>
  </div>
</form>

<form method="post" class="card" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="change_password">
  <input type="hidden" name="tab" value="profile">
  <div class="card-head"><h3>Change password</h3></div>
  <p class="hint-block">For mobile + password login. Minimum 8 characters.</p>
  <div class="settings-grid">
    <div class="field">
      <label for="current_password">Current password</label>
      <input id="current_password" type="password" name="current_password" autocomplete="current-password">
    </div>
    <div class="field">
      <label for="new_password">New password</label>
      <input id="new_password" type="password" name="new_password" minlength="8" required autocomplete="new-password">
    </div>
    <div class="field">
      <label for="confirm_password">Confirm new password</label>
      <input id="confirm_password" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
    </div>
  </div>
  <div class="settings-actions">
    <button class="btn" type="submit">Update password</button>
  </div>
</form>

<?php /* All admin logins CRUD */ ?>
<form method="post" class="card" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="add_admin">
  <input type="hidden" name="tab" value="profile">
  <div class="card-head"><h3>Add admin</h3></div>
  <p class="hint-block">The new admin can log in with mobile + password or SMS OTP.</p>
  <div class="row">
    <div class="field">
      <label for="new_admin_name">Name</label>
      <input id="new_admin_name" name="new_admin_name" required minlength="2" placeholder="Full name">
    </div>
    <div class="field">
      <label for="new_admin_mobile">Mobile</label>
      <input id="new_admin_mobile" name="new_admin_mobile" required maxlength="10" inputmode="numeric" placeholder="10-digit mobile">
    </div>
    <div class="field">
      <label for="new_admin_email">Email</label>
      <input id="new_admin_email" type="email" name="new_admin_email" placeholder="optional">
    </div>
    <div class="field">
      <label for="new_admin_password">Password</label>
      <input id="new_admin_password" type="password" name="new_admin_password" minlength="8" placeholder="min 8 characters">
      <small>Leave blank to use the starter password.</small>
    </div>
    <div class="field">
      <label for="new_admin_role">Role</label>
      <select id="new_admin_role" name="new_admin_role" <?php echo is_super_admin() ? '' : 'disabled'; ?>>
        <option value="admin">Admin</option>
        <?php if (is_super_admin()): ?>
        <option value="superadmin">Superadmin</option>
        <?php endif; ?>
      </select>
    </div>
    <button class="btn gold" type="submit">Add admin</button>
  </div>
</form>

<?php if ($editAdmin): ?>
<form method="post" class="card" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="update_admin">
  <input type="hidden" name="tab" value="profile">
  <input type="hidden" name="id" value="<?php echo (int) $editAdmin['id']; ?>">
  <div class="card-head">
    <h3>Edit admin</h3>
    <a class="btn sm gray" href="settings.php?tab=profile">Cancel</a>
  </div>
  <div class="settings-grid">
    <div class="field">
      <label for="edit_admin_name">Name</label>
      <input id="edit_admin_name" name="admin_name" required minlength="2" value="<?php echo htmlspecialchars((string) $editAdmin['name']); ?>">
    </div>
    <div class="field">
      <label for="edit_admin_mobile">Mobile</label>
      <input id="edit_admin_mobile" name="admin_mobile" required maxlength="10" inputmode="numeric" value="<?php echo htmlspecialchars(local_10_digit((string) $editAdmin['phone'])); ?>">
    </div>
    <div class="field">
      <label for="edit_admin_email">Email</label>
      <input id="edit_admin_email" type="email" name="admin_email" value="<?php echo htmlspecialchars((string) ($editAdmin['email'] ?? '')); ?>">
    </div>
    <div class="field">
      <label for="edit_admin_status">Status</label>
      <select id="edit_admin_status" name="admin_status">
        <option value="active" <?php echo ($editAdmin['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo ($editAdmin['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </div>
    <div class="field">
      <label for="edit_admin_role">Role</label>
      <select id="edit_admin_role" name="admin_role" <?php echo is_super_admin() ? '' : 'disabled'; ?>>
        <option value="admin" <?php echo !admin_role_is_super($editAdmin['role'] ?? '') ? 'selected' : ''; ?>>Admin</option>
        <option value="superadmin" <?php echo admin_role_is_super($editAdmin['role'] ?? '') ? 'selected' : ''; ?>>Superadmin</option>
      </select>
    </div>
    <div class="field">
      <label for="edit_admin_password">New password (optional)</label>
      <input id="edit_admin_password" type="password" name="admin_password" minlength="8" placeholder="Leave blank to keep">
      <small>Leave blank to keep the current password.</small>
    </div>
  </div>
  <div class="settings-actions">
    <button class="btn gold" type="submit">Update admin</button>
  </div>
</form>
<?php endif; ?>

<div class="card">
  <div class="card-head"><h3>All admin logins</h3></div>
  <p class="hint-block">Use Edit to fill the form, Update to save, and Delete to remove an account. You cannot delete your own account.</p>
  <?php if (!$adminList): ?>
    <div class="empty"><b>No admins</b>Add the first admin above.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="plain">
        <thead><tr><th>Name</th><th>Mobile</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($adminList as $row): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars((string) $row['name']); ?></strong></td>
            <td>+91 <?php echo htmlspecialchars(local_10_digit((string) $row['phone'])); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['email'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) $row['role']); ?></td>
            <td><span class="badge <?php echo ($row['status'] ?? '') === 'active' ? 'on' : 'off'; ?>"><?php echo htmlspecialchars((string) $row['status']); ?></span></td>
            <td><?php echo !empty($row['last_login']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $row['last_login']))) : 'Never'; ?></td>
            <td class="actions">
              <a class="btn sm" href="settings.php?tab=profile&amp;edit=<?php echo (int) $row['id']; ?>">Edit</a>
              <?php if ((int) $row['id'] !== $adminId): ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="toggle_admin">
                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars((string) $row['status']); ?>">
                <button class="btn sm gray" type="submit"><?php echo ($row['status'] ?? '') === 'active' ? 'Stop' : 'Start'; ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Delete this admin login?');">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <button class="btn sm red" type="submit">Delete</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="app-cards">
      <?php foreach ($adminList as $row): ?>
        <div class="app-card">
          <b><?php echo htmlspecialchars((string) $row['name']); ?></b>
          <div class="app-meta">+91 <?php echo htmlspecialchars(local_10_digit((string) $row['phone'])); ?> · <?php echo htmlspecialchars((string) $row['role']); ?> · <?php echo htmlspecialchars((string) $row['status']); ?></div>
          <div class="actions" style="margin-top:8px;">
            <a class="btn sm" href="settings.php?tab=profile&amp;edit=<?php echo (int) $row['id']; ?>">Edit</a>
            <?php if ((int) $row['id'] !== $adminId): ?>
            <form method="post" onsubmit="return confirm('Delete this admin login?');">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="action" value="delete_admin">
              <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
              <button class="btn sm red" type="submit">Delete</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'payments'): ?>
<form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="save_razorpay">
  <input type="hidden" name="tab" value="payments">
  <div class="card">
    <div class="card-head"><h3>Razorpay (workshop.openform.in form)</h3></div>
    <p class="hint-block">This key is used for form payments. Copy Live / Test keys from Razorpay Dashboard → API Keys. If you save with a blank secret, the existing secret is kept.</p>
    <div class="settings-grid">
      <div class="field">
        <label for="razorpay_mode">Razorpay mode</label>
        <select id="razorpay_mode" name="razorpay_mode">
          <option value="live" <?php echo ($settings['razorpay_mode'] ?? '') === 'live' ? 'selected' : ''; ?>>Live</option>
          <option value="test" <?php echo ($settings['razorpay_mode'] ?? '') === 'test' ? 'selected' : ''; ?>>Test (rzp_test_…)</option>
        </select>
        <small>Select the same mode as the key you use in the dashboard.</small>
      </div>
      <div class="field">
        <label for="razorpay_key_id">Key ID</label>
        <input id="razorpay_key_id" name="razorpay_key_id" value="<?php echo $v($settings, 'razorpay_key_id'); ?>" placeholder="rzp_live_… or rzp_test_…">
      </div>
      <div class="field">
        <label for="razorpay_key_secret">Key Secret</label>
        <div class="secret-wrap">
          <input id="razorpay_key_secret" type="password" name="razorpay_key_secret" value="" placeholder="<?php echo ($settings['razorpay_key_secret'] ?? '') !== '' ? mask_secret($settings['razorpay_key_secret']) . '  — leave blank to keep' : 'Paste key secret'; ?>">
          <button type="button" class="btn sm gray js-toggle-secret" data-target="razorpay_key_secret">Show</button>
        </div>
        <small><?php echo ($settings['razorpay_key_secret'] ?? '') !== '' ? 'Secret saved. Leave blank to keep the existing secret.' : 'No secret saved yet.'; ?></small>
      </div>
      <div class="field">
        <label for="exam_fee_flat">Registration fee (₹)</label>
        <input id="exam_fee_flat" type="number" min="1" name="exam_fee_flat" value="<?php echo $v($settings, 'exam_fee_flat') ?: $v($settings, 'exam_fee_1_4'); ?>">
        <small>Base fee charged on the form (default ₹300).</small>
      </div>
      <div class="field">
        <label for="platform_fee_percent">Payment gateway &amp; platform fee (%)</label>
        <input id="platform_fee_percent" type="number" min="0" max="30" step="0.1" name="platform_fee_percent" value="<?php echo $v($settings, 'platform_fee_percent'); ?>">
      </div>
    </div>
    <p class="hint-block" style="margin-top:14px;"><b>Who pays the gateway fee?</b> Pick one. The preview below updates as you type.</p>
    <?php $payerNow = (($settings['platform_fee_payer'] ?? 'user') === 'admin') ? 'admin' : 'user'; ?>
    <label class="declaration-check" style="margin:0 0 8px;display:flex;gap:8px;align-items:flex-start;">
      <input type="radio" name="platform_fee_payer" value="user" <?php echo $payerNow === 'user' ? 'checked' : ''; ?>>
      <span><b>Add to the parent</b> — they pay registration fee + this %. Example: ₹300 + 4% = ₹312.</span>
    </label>
    <label class="declaration-check" style="margin:0 0 10px;display:flex;gap:8px;align-items:flex-start;">
      <input type="radio" name="platform_fee_payer" value="admin" <?php echo $payerNow === 'admin' ? 'checked' : ''; ?>>
      <span><b>Deduct from organiser</b> — parent pays only the registration fee. Razorpay’s % comes from your settlement. Example: parent pays ₹300; about ₹12 is deducted from you.</span>
    </label>
    <div class="hint-block" id="feePayerPreview" style="margin-top:0;"></div>
    <label style="display:flex;gap:8px;align-items:flex-start;margin-top:16px;font-weight:600;">
      <input type="checkbox" name="payment_test_mode" value="1" <?php echo ($settings['payment_test_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
      <span>Test payments: charge ₹1 (and add gateway % only if “Add to the parent” is selected).</span>
    </label>
    <div class="field" style="margin-top:18px;">
      <label for="razorpay_webhook_secret">Webhook secret</label>
      <div class="secret-wrap">
        <input id="razorpay_webhook_secret" type="password" name="razorpay_webhook_secret" value="" placeholder="<?php echo ($settings['razorpay_webhook_secret'] ?? '') !== '' ? mask_secret($settings['razorpay_webhook_secret']) . '  — leave blank to keep' : 'Paste webhook secret from Razorpay'; ?>">
        <button type="button" class="btn sm gray js-toggle-secret" data-target="razorpay_webhook_secret">Show</button>
      </div>
      <small><?php echo ($settings['razorpay_webhook_secret'] ?? '') !== '' ? 'Secret saved. Leave blank to keep it.' : 'Required so delayed Razorpay payments update this software automatically.'; ?></small>
    </div>
    <div class="hint-block" style="margin-top:12px;">
      <b>Webhook URL</b> (Razorpay Dashboard → Account &amp; Settings → Webhooks → Add New Webhook)<br>
      <code style="word-break:break-all;"><?php echo htmlspecialchars(razorpay_webhook_url()); ?></code><br>
      Enable events: <b>payment.captured</b>, <b>order.paid</b>, <b>payment.failed</b>.
      On live hosting this URL must be HTTPS and publicly reachable. Localhost cannot receive Razorpay webhooks unless you use a tunnel (e.g. ngrok).
    </div>
    <div class="settings-actions">
      <button class="btn gold" type="submit">Save Razorpay settings</button>
    </div>
  </div>
</form>
<?php
ensure_razorpay_webhook_schema();
$hookLogs = [];
try {
    $hookLogs = db()->query('SELECT * FROM razorpay_webhook_logs ORDER BY id DESC LIMIT 12')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $hookLogs = [];
}
?>
<div class="card">
  <div class="card-head"><h3>Recent webhook events</h3></div>
  <?php if (!$hookLogs): ?>
    <p class="hint-block">No webhook calls yet. After you add the URL in Razorpay, captured payments will appear here and pending registrations will flip to paid.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="plain">
        <thead><tr><th>Time</th><th>Event</th><th>Order</th><th>App</th><th>Result</th><th>Note</th></tr></thead>
        <tbody>
        <?php foreach ($hookLogs as $h): ?>
          <tr>
            <td><?php echo htmlspecialchars((string) $h['created_at']); ?></td>
            <td><?php echo htmlspecialchars((string) $h['event_name']); ?></td>
            <td><?php echo htmlspecialchars((string) $h['order_id']); ?></td>
            <td><?php echo $h['application_id'] ? '#' . (int) $h['application_id'] : '—'; ?></td>
            <td><?php echo htmlspecialchars((string) $h['result']); ?></td>
            <td><?php echo htmlspecialchars((string) $h['message']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'sms'): ?>
<form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="save_sms">
  <input type="hidden" name="tab" value="sms">
  <div class="card">
    <div class="card-head"><h3>MSG91 SMS OTP</h3></div>
    <p class="hint-block">Login OTP is sent by SMS to participants, staff, and admins. Copy the auth key and template ID from the MSG91 dashboard.</p>
    <div class="settings-grid">
      <div class="field">
        <label for="msg91_auth_key">MSG91 Auth Key</label>
        <div class="secret-wrap">
          <input id="msg91_auth_key" type="password" name="msg91_auth_key" value="" placeholder="<?php echo $settings['msg91_auth_key'] !== '' ? mask_secret($settings['msg91_auth_key']) . '  — leave blank to keep' : 'Paste auth key'; ?>">
          <button type="button" class="btn sm gray js-toggle-secret" data-target="msg91_auth_key">Show</button>
        </div>
        <small>Saved key ends with <b><?php echo $settings['msg91_auth_key'] !== '' ? htmlspecialchars(substr($settings['msg91_auth_key'], -4)) : 'not set'; ?></b>. Leave blank to keep the existing key.</small>
      </div>
      <div class="field">
        <label for="msg91_sender_id">Sender ID</label>
        <input id="msg91_sender_id" name="msg91_sender_id" maxlength="6" value="<?php echo $v($settings, 'msg91_sender_id'); ?>" placeholder="KAGPIF">
        <small>DLT-approved 6-character sender, e.g. KAGPIF.</small>
      </div>
      <div class="field">
        <label for="msg91_template_name">Template name</label>
        <input id="msg91_template_name" name="msg91_template_name" value="<?php echo $v($settings, 'msg91_template_name'); ?>" placeholder="kagpifotp">
        <small>Template name as shown in MSG91. For identification only.</small>
      </div>
      <div class="field">
        <label for="msg91_template_id">MSG91 Template ID</label>
        <input id="msg91_template_id" name="msg91_template_id" value="<?php echo $v($settings, 'msg91_template_id'); ?>" placeholder="Flow / template id">
        <small>ID used by the Flow API (currently in use).</small>
      </div>
      <div class="field">
        <label for="msg91_dlt_template_id">DLT Template ID</label>
        <input id="msg91_dlt_template_id" name="msg91_dlt_template_id" value="<?php echo $v($settings, 'msg91_dlt_template_id'); ?>" placeholder="TRAI / DLT id">
        <small>ID from DLT. Used if the MSG91 template ID is empty.</small>
      </div>
      <div class="field">
        <label for="otp_validity_minutes">OTP validity (minutes)</label>
        <input id="otp_validity_minutes" type="number" min="1" max="30" name="otp_validity_minutes" value="<?php echo $v($settings, 'otp_validity_minutes'); ?>">
        <small>Applies to both SMS and email OTP. 1 to 30 minutes.</small>
      </div>
    </div>
    <div class="field" style="margin-top:14px;">
      <label for="msg91_dlt_content">Approved DLT content</label>
      <textarea id="msg91_dlt_content" name="msg91_dlt_content" rows="4"><?php echo $v($settings, 'msg91_dlt_content'); ?></textarea>
      <small>Approved DLT text. ##var1## = OTP, ##var2## = minutes. For reference / checking only.</small>
    </div>
    <div class="settings-actions">
      <button class="btn gold" type="submit">Save SMS settings</button>
    </div>
  </div>
</form>

<form method="post" class="card" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="test_sms">
  <div class="card-head"><h3>Test SMS OTP</h3></div>
  <div class="row">
    <div class="field">
      <label for="test_mobile">Send test to mobile</label>
      <input id="test_mobile" name="test_mobile" maxlength="10" inputmode="numeric" value="<?php echo htmlspecialchars(local_10_digit($admin_phone)); ?>" placeholder="10-digit mobile">
    </div>
    <button class="btn" type="submit">Send test SMS</button>
  </div>
  <small>A real SMS is sent using the saved settings. The OTP is shown on screen for testing only.</small>
</form>
<?php endif; ?>

<?php if ($tab === 'email'): ?>
<form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="save_email">
  <input type="hidden" name="tab" value="email">
  <div class="card">
    <div class="card-head"><h3>Zoho ZeptoMail · Email OTP</h3></div>
    <p class="hint-block">Copy the token from Agent → SMTP/API → Send Mail Token. In the HTML use <code>{{otp}}</code> and <code>{{minutes}}</code>.</p>
    <div class="msg info" style="margin:0 0 16px; font-weight:500;">
      From email: <code>noreply@openform.in</code>.
      This address / the <code>openform.in</code> domain must be verified in the ZeptoMail Agent.
      If you get SM_111, go to Agent → <b>Domains</b> (DKIM/CNAME), then add <code>noreply@openform.in</code> under <b>Sender Address</b>. The Send Mail Token must belong to the same Agent.
    </div>
    <div class="settings-grid">
      <div class="field">
        <label for="zepto_data_center">Data center</label>
        <select id="zepto_data_center" name="zepto_data_center">
          <?php foreach (zepto_data_centers() as $code => $label): ?>
            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($settings['zepto_data_center'] ?? '') === $code ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($label); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small>Select the region of your Zoho account. Wrong DC = 401 error.</small>
      </div>
      <div class="field">
        <label for="zepto_send_method">Send method</label>
        <select id="zepto_send_method" name="zepto_send_method">
          <option value="api" <?php echo ($settings['zepto_send_method'] ?? '') === 'api' ? 'selected' : ''; ?>>API (Send Mail Token) — recommended</option>
          <option value="smtp" <?php echo ($settings['zepto_send_method'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
        </select>
        <small>API is simpler and more stable. For SMTP, the token is used as the password.</small>
      </div>
      <div class="field">
        <label for="zepto_send_mail_token">Send Mail Token</label>
        <div class="secret-wrap">
          <input id="zepto_send_mail_token" type="password" name="zepto_send_mail_token" value="" placeholder="<?php echo $settings['zepto_send_mail_token'] !== '' ? mask_secret($settings['zepto_send_mail_token']) . '  — leave blank to keep' : 'Zoho-enczapikey …'; ?>">
          <button type="button" class="btn sm gray js-toggle-secret" data-target="zepto_send_mail_token">Show</button>
        </div>
        <small><?php echo $settings['zepto_send_mail_token'] !== '' ? 'Token saved. Leave blank to keep the existing token.' : 'No token saved yet.'; ?></small>
      </div>
      <div class="field">
        <label for="zepto_from_email">From email (verified in Agent)</label>
        <input id="zepto_from_email" type="email" name="zepto_from_email" value="<?php echo $v($settings, 'zepto_from_email'); ?>" placeholder="noreply@openform.in">
        <small>Default: <code>noreply@openform.in</code>. This address must be verified in the Agent.</small>
      </div>
      <div class="field">
        <label for="zepto_from_name">From name</label>
        <input id="zepto_from_name" name="zepto_from_name" value="<?php echo $v($settings, 'zepto_from_name'); ?>" placeholder="Registration">
      </div>
      <div class="field">
        <label for="zepto_bounce_address">Bounce address</label>
        <input id="zepto_bounce_address" name="zepto_bounce_address" value="<?php echo $v($settings, 'zepto_bounce_address'); ?>" placeholder="bounce@yourdomain.com">
        <small>Bounce / return-path from the Agent. Can be left blank.</small>
      </div>
      <div class="field">
        <label for="zepto_reply_to_email">Reply-to email</label>
        <input id="zepto_reply_to_email" type="email" name="zepto_reply_to_email" value="<?php echo $v($settings, 'zepto_reply_to_email'); ?>" placeholder="hello@example.com">
      </div>
      <div class="field">
        <label for="otp_email_subject">OTP email subject</label>
        <input id="otp_email_subject" name="otp_email_subject" value="<?php echo $v($settings, 'otp_email_subject'); ?>">
      </div>
    </div>
    <div class="field" style="margin-top:14px;">
      <label for="otp_email_html">OTP email HTML</label>
      <textarea id="otp_email_html" name="otp_email_html" rows="12"><?php echo $v($settings, 'otp_email_html'); ?></textarea>
      <small>Placeholders: <code>{{otp}}</code> = OTP, <code>{{minutes}}</code> = validity.</small>
    </div>
    <div class="settings-actions">
      <button class="btn gold" type="submit">Save email settings</button>
    </div>
  </div>
</form>

<form method="post" class="card" autocomplete="off">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="test_email">
  <div class="card-head"><h3>Test email OTP</h3></div>
  <div class="row">
    <div class="field">
      <label for="test_email">Send test to email</label>
      <input id="test_email" type="email" name="test_email" placeholder="you@example.com">
    </div>
    <button class="btn" type="submit">Send test email</button>
  </div>
  <small>Email is sent with the saved HTML and subject. The OTP is shown on screen for testing only.</small>
</form>
<?php endif; ?>

<?php if ($tab === 'logs'): ?>
<div class="card">
  <div class="card-head">
    <h3>SMS / email error log</h3>
    <form method="post" onsubmit="return confirm('Clear all SMS and email logs?');">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="clear_logs">
      <button class="btn sm red" type="submit">Clear logs</button>
    </form>
  </div>
  <p class="hint-block">Last 500 sends. OTP codes are not stored in the log. Failed sends appear here.</p>
  <div class="filters">
    <a class="btn sm <?php echo $logFilter === 'all' ? '' : 'gray'; ?>" href="settings.php?tab=logs&amp;channel=all">All (<?php echo (int) $logCounts['total']; ?>)</a>
    <a class="btn sm <?php echo $logFilter === 'sms' ? '' : 'gray'; ?>" href="settings.php?tab=logs&amp;channel=sms">SMS</a>
    <a class="btn sm <?php echo $logFilter === 'email' ? '' : 'gray'; ?>" href="settings.php?tab=logs&amp;channel=email">Email</a>
  </div>
  <?php if (!$logs): ?>
    <div class="empty"><b>No log rows</b>Entries appear here after an OTP is sent.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="plain">
        <thead>
          <tr>
            <th>Time</th>
            <th>Channel</th>
            <th>Status</th>
            <th>To</th>
            <th>Message</th>
            <th>HTTP</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime((string) $row['created_at']))); ?></td>
            <td><?php echo htmlspecialchars(strtoupper((string) $row['channel'])); ?></td>
            <td><span class="badge <?php echo $row['status'] === 'ok' ? 'on' : 'failed'; ?>"><?php echo htmlspecialchars((string) $row['status']); ?></span></td>
            <td><?php echo htmlspecialchars((string) $row['recipient']); ?></td>
            <td>
              <?php echo htmlspecialchars((string) $row['message']); ?>
              <?php if (!empty($row['response'])): ?>
                <small class="log-resp"><?php echo htmlspecialchars(substr((string) $row['response'], 0, 180)); ?></small>
              <?php endif; ?>
            </td>
            <td><?php echo $row['http_code'] !== null && $row['http_code'] !== '' ? (int) $row['http_code'] : '—'; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="app-cards">
      <?php foreach ($logs as $row): ?>
        <div class="app-card">
          <b><?php echo htmlspecialchars(strtoupper((string) $row['channel'])); ?> · <?php echo htmlspecialchars((string) $row['status']); ?></b>
          <div class="app-meta">
            <?php echo htmlspecialchars(date('d M Y, h:i A', strtotime((string) $row['created_at']))); ?><br>
            <?php echo htmlspecialchars((string) $row['recipient']); ?><br>
            <?php echo htmlspecialchars((string) $row['message']); ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.js-toggle-secret').forEach((btn) => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Hide' : 'Show';
  });
});
document.getElementById('landing_whatsapp')?.addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/\D+/g, '').slice(0, 10);
});
document.getElementById('admin_mobile')?.addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/\D+/g, '').slice(0, 10);
});
document.getElementById('new_admin_mobile')?.addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/\D+/g, '').slice(0, 10);
});
document.getElementById('edit_admin_mobile')?.addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/\D+/g, '').slice(0, 10);
});
document.getElementById('brand_name')?.addEventListener('input', (e) => {
  const el = document.getElementById('previewName');
  if (el) el.textContent = e.target.value || 'Registration';
});
document.getElementById('brand_tagline_admin')?.addEventListener('input', (e) => {
  const el = document.getElementById('previewTag');
  if (el) el.textContent = e.target.value || 'Admin panel';
});
document.getElementById('brand_logo')?.addEventListener('change', (e) => {
  const file = e.target.files && e.target.files[0];
  const img = document.getElementById('previewLogo');
  if (!file || !img) return;
  img.src = URL.createObjectURL(file);
});
document.getElementById('msg91_sender_id')?.addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/[^A-Za-z0-9]/g, '').slice(0, 6).toUpperCase();
});
(function () {
  const fee = document.getElementById('exam_fee_flat');
  const pct = document.getElementById('platform_fee_percent');
  const box = document.getElementById('feePayerPreview');
  if (!fee || !pct || !box) return;
  function rupees(n) { return '₹' + (Math.round(n * 100) / 100).toFixed(2); }
  function update() {
    const base = Math.max(1, parseFloat(fee.value) || 0);
    const p = Math.max(0, Math.min(30, parseFloat(pct.value) || 0));
    const gw = Math.round(base * p) / 100;
    const payer = (document.querySelector('input[name="platform_fee_payer"]:checked') || {}).value || 'user';
    if (payer === 'admin') {
      box.innerHTML = '<b>Preview:</b> Participant pays <b>' + rupees(base) + '</b>. Gateway ' + p + '% = ' + rupees(gw) + ' is deducted from the organiser.';
    } else {
      box.innerHTML = '<b>Preview:</b> Participant pays <b>' + rupees(base + gw) + '</b> (' + rupees(base) + ' + ' + rupees(gw) + ' gateway).';
    }
  }
  ['input', 'change'].forEach((ev) => {
    fee.addEventListener(ev, update);
    pct.addEventListener(ev, update);
  });
  document.querySelectorAll('input[name="platform_fee_payer"]').forEach((el) => el.addEventListener('change', update));
  update();
})();
</script>
<?php panel_end();