<?php
/**
 * Shared mobile + SMS OTP login screen.
 * Expected variables: $page_title, $page_heading, $page_sub, $phone_label,
 * $note_text, $csrf, $next, $msg_info, $msg_error, $otp_active, $ctx,
 * $mobile_prefill, $cooldownRemaining, $debug_block, $otp_length, $country_code
 */
$otp_length = $otp_length ?? (defined('OTP_LENGTH') ? (int) OTP_LENGTH : 4);
$country_code = $country_code ?? (defined('SMS_COUNTRY_CODE') ? SMS_COUNTRY_CODE : '91');
$debug_block = $debug_block ?? '';
$note_text = $note_text ?? '';
$phone_label = $phone_label ?? 'Mobile number*';
$extra_links = $extra_links ?? '';
$staff_ref = $staff_ref ?? ($_SESSION['staff_ref'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo htmlspecialchars((strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/staff/') !== false || strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/adminpanel/') !== false) ? '../assets/css/theme.css?v=2' : 'assets/css/theme.css?v=2'); ?>">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
        .wrap { min-height: 100dvh; display: grid; place-items: center; padding: 16px; background: radial-gradient(1200px 500px at 50% -10%, #cfe0ff 0%, var(--bg) 55%); }
        .card { width: 100%; max-width: 460px; background: var(--card); border-radius: 18px; box-shadow: var(--shadow); padding: 24px; }
        .brand { text-align:center; margin-bottom: 12px; }
        .brand img { height: 72px; width: 72px; object-fit: contain; background:#fff; border-radius:18px; padding:8px; box-shadow:0 8px 20px rgba(0,88,240,.12); }
        h1 { margin: 0 0 8px; font-size: 22px; text-align:center; color: var(--brand); }
        .sub { text-align:center; color:var(--muted); font-size:14px; margin-bottom:8px; }
        label { display:block; margin:14px 0 8px; font-weight:600; font-size:14px; }
        .input { width:100%; padding:14px; border:1px solid var(--line); border-radius:14px; font-size:16px; background:#fff; transition:border-color .15s, box-shadow .15s; }
        .input:focus { outline:none; border-color:var(--brand-mid); box-shadow:0 0 0 4px rgba(0,88,240,.15); }
        .phone-group { position:relative; }
        .phone-prefix { position:absolute; left:10px; top:50%; transform:translateY(-50%); background:var(--brand-soft); border:1px solid var(--line); color:var(--brand-dark); padding:8px 10px; border-radius:10px; font-weight:600; font-size:14px; user-select:none; }
        .phone-input { padding-left:88px; }
        .btn { display:block; width:100%; padding:14px 16px; border:0; border-radius:14px; background:var(--brand); color:#fff; font-size:16px; font-weight:600; cursor:pointer; box-shadow:0 8px 18px rgba(0,88,240,.28); }
        .btn.secondary { background:var(--brand-mid); }
        .btn[disabled] { opacity:.6; cursor:not-allowed; box-shadow:none; }
        .btn.loading { position:relative; color:transparent; }
        .btn.loading::after { content:""; position:absolute; inset:0; margin:auto; width:18px; height:18px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .top-gap { margin-top:14px; }
        .msg { margin:10px 0 0; padding:12px; border-radius:12px; font-size:14px; }
        .msg.info { background:var(--brand-soft); color:var(--brand-dark); }
        .msg.error { background:#fee2e2; color:#7f1d1d; }
        .note { font-size:12px; color:var(--muted); margin-top:12px; text-align:center; line-height:1.4; }
        .phone-display { display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px dashed var(--line); border-radius:12px; padding:10px 12px; margin-top:4px; }
        .assist { font-size:12px; color:var(--muted); margin-top:8px; text-align:center; line-height:1.5; }
        .link { background:none; border:0; color:var(--brand); font-weight:700; cursor:pointer; padding:0; font-size:12px; }
        .extra { margin-top:16px; text-align:center; font-size:14px; }
        .extra a { display:inline-block; margin:4px 4px 0; background:var(--brand); color:#fff; font-weight:700; text-decoration:none; padding:10px 14px; border-radius:12px; }
        .program { text-align:center; background:var(--brand-soft); border-radius:14px; padding:10px 12px; margin:0 0 12px; font-size:13px; line-height:1.45; color:var(--brand-dark); }
        .program a { color:var(--brand); font-weight:700; }
        .login-tabs { display:flex; gap:8px; margin:16px 0 4px; }
        .login-tabs a { flex:1; text-align:center; text-decoration:none; font-weight:700; font-size:13px; padding:10px 8px; border-radius:12px; background:#f1f5f9; color:#475569; }
        .login-tabs a.active { background:var(--brand); color:#fff; }
        .pw-wrap { position:relative; }
        .pw-wrap .input { padding-right:84px; }
        .pw-wrap .link { position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:13px; }
        .toast{position:fixed;left:50%;bottom:16px;transform:translateX(-50%);background:#0f172a;color:#fff;padding:10px 12px;border-radius:10px;opacity:0;transition:opacity .2s;z-index:9999;}
        .toast.show{opacity:1;}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card" role="region" aria-label="<?php echo htmlspecialchars($page_heading); ?>">
        <?php
        $brandName = function_exists('panel_brand_name') ? panel_brand_name() : 'Registration';
        $brandLogo = function_exists('panel_logo_src') ? panel_logo_src() : '';
        $allow_password_login = !empty($allow_password_login);
        $login_method = $login_method ?? 'otp';
        if ($allow_password_login && !empty($otp_active)) {
            $login_method = 'otp';
        }
        if (!$allow_password_login) {
            $login_method = 'otp';
        }
        ?>
        <div class="brand">
            <?php if ($brandLogo !== ''): ?>
            <img src="<?php echo htmlspecialchars($brandLogo); ?>" alt="<?php echo htmlspecialchars($brandName); ?>">
            <?php endif; ?>
        </div>
        <h1><?php echo htmlspecialchars($page_heading); ?></h1>
        <div class="sub"><?php echo htmlspecialchars($page_sub); ?></div>
        <?php if (!empty($show_program_info)): ?>
            <div class="program">
                <?php echo htmlspecialchars(landing_page_title()); ?>
                <?php if (landing_page_subtitle() !== ''): ?>
                    <br><span style="font-weight:500;opacity:.9;"><?php echo htmlspecialchars(landing_page_subtitle()); ?></span>
                <?php endif; ?>
                <br><a href="welcome.php<?php echo $staff_ref ? ('?ref=' . urlencode((string) $staff_ref)) : ''; ?>">Read full details</a>
            </div>
        <?php endif; ?>
        <?php if ($allow_password_login): ?>
        <div class="login-tabs">
            <a class="<?php echo $login_method === 'password' ? 'active' : ''; ?>" href="?mode=password&amp;next=<?php echo urlencode((string) $next); ?>">Mobile + password</a>
            <a class="<?php echo $login_method === 'otp' ? 'active' : ''; ?>" href="?mode=otp&amp;next=<?php echo urlencode((string) $next); ?>">SMS OTP</a>
        </div>
        <?php endif; ?>
        <div aria-live="polite" aria-atomic="true">
            <?php if (!empty($msg_info)): ?><div class="msg info"><?php echo htmlspecialchars($msg_info); ?></div><?php endif; ?>
            <?php if (!empty($msg_error)): ?><div class="msg error"><?php echo htmlspecialchars($msg_error); ?></div><?php endif; ?>
        </div>

        <?php if ($allow_password_login && $login_method === 'password'): ?>
            <form method="post" id="passwordLoginForm" autocomplete="on" novalidate class="top-gap">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="password_login">
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                <label for="phone"><?php echo htmlspecialchars($phone_label); ?></label>
                <div class="phone-group">
                    <span class="phone-prefix">+<?php echo htmlspecialchars($country_code); ?></span>
                    <input id="phone" class="input phone-input" type="tel" name="mobile" maxlength="10" inputmode="numeric" pattern="\d{10}" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($mobile_prefill); ?>" required autofocus>
                </div>
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input id="password" class="input" type="password" name="password" autocomplete="current-password" placeholder="Enter password" required>
                    <button class="link" type="button" id="togglePw">Show</button>
                </div>
                <button class="btn top-gap" type="submit">Login</button>
                <?php if ($note_text): ?><div class="note"><?php echo htmlspecialchars(strip_tags($note_text)); ?></div><?php endif; ?>
            </form>
        <?php elseif (!$otp_active): ?>
            <form method="post" id="phoneForm" autocomplete="off" novalidate class="top-gap">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="send_otp">
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                <label for="phone"><?php echo htmlspecialchars($phone_label); ?></label>
                <div class="phone-group">
                    <span class="phone-prefix">+<?php echo htmlspecialchars($country_code); ?></span>
                    <input id="phone" class="input phone-input" type="tel" name="mobile" maxlength="10" inputmode="numeric" pattern="\d{10}" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($mobile_prefill); ?>" required autofocus>
                </div>
                <button class="btn top-gap" id="sendOtpBtn" type="submit" disabled>Send SMS OTP</button>
                <?php if ($note_text): ?><div class="note"><?php echo htmlspecialchars(strip_tags($note_text)); ?></div><?php endif; ?>
            </form>
        <?php else: ?>
            <div class="top-gap">
                <label><?php echo htmlspecialchars($phone_label); ?></label>
                <div class="phone-display">
                    <small>+<?php echo htmlspecialchars($country_code . ' ' . local_10_digit($ctx['mobile_e164'] ?? '')); ?></small>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="change_phone">
                        <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                        <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                        <button class="link" type="submit">Change</button>
                    </form>
                </div>
                <form method="post" id="otpLoginForm" autocomplete="one-time-code" novalidate class="top-gap">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                    <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                    <label for="otp">Enter SMS OTP</label>
                    <input id="otp" name="otp" class="input" inputmode="numeric" pattern="\d{<?php echo (int) $otp_length; ?>}" maxlength="<?php echo (int) $otp_length; ?>" autocomplete="one-time-code" placeholder="<?php echo $otp_length; ?>-digit OTP" required>
                    <div class="assist">
                        OTP SMS sent to your mobile.<br>
                        Didn't receive it?
                        <button type="button" id="resendBtn" class="link" <?php echo $cooldownRemaining > 0 ? 'disabled aria-disabled="true"' : ''; ?> aria-live="polite">
                            <?php echo $cooldownRemaining > 0 ? 'Resend in ' . sprintf('00:%02d', $cooldownRemaining) : 'Resend OTP'; ?>
                        </button>
                    </div>
                    <button class="btn secondary top-gap" type="submit" id="loginBtn">Login</button>
                </form>
                <form method="post" id="resendFormHidden" style="display:none;">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="resend_otp">
                    <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                    <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($debug_block && defined('APP_DEBUG') && APP_DEBUG): ?>
            <div style="margin-top:12px;"><?php echo $debug_block; ?></div>
        <?php endif; ?>
        <?php if ($extra_links): ?>
            <div class="extra"><?php echo $extra_links; ?></div>
        <?php endif; ?>
    </div>
</div>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
<script>
    const toastEl = document.getElementById('toast');
    function toast(msg){ if(!toastEl) return; toastEl.textContent=msg; toastEl.classList.add('show'); setTimeout(()=>toastEl.classList.remove('show'), 1800); }
    <?php if (!empty($msg_info)): ?>toast(<?php echo json_encode($msg_info); ?>);<?php endif; ?>

    const phoneInput = document.getElementById('phone');
    const sendBtn = document.getElementById('sendOtpBtn');
    if (phoneInput) {
        const toggle = () => { if (sendBtn) sendBtn.disabled = phoneInput.value.replace(/\D+/g,'').length !== 10; };
        phoneInput.addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D+/g,'').slice(0,10); toggle(); });
        toggle();
        if (sendBtn) phoneInput.form?.addEventListener('submit', () => sendBtn.classList.add('loading'));
    }
    document.getElementById('togglePw')?.addEventListener('click', (e) => {
        const input = document.getElementById('password');
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        e.currentTarget.textContent = show ? 'Hide' : 'Show';
    });

    const otp = document.getElementById('otp');
    const verifyBtn = document.getElementById('loginBtn');
    if (otp && verifyBtn) {
        otp.addEventListener('input', () => {
            const len = <?php echo (int) $otp_length; ?>;
            otp.value = otp.value.replace(/\D/g,'').slice(0,len);
            if (otp.value.length === len) {
                verifyBtn.disabled = true;
                otp.form.submit();
            }
        });
        setTimeout(()=>{ try{ otp.focus(); }catch(_){} }, 100);
    }

    const resendBtn = document.getElementById('resendBtn');
    if (resendBtn) {
        let remaining = <?php echo (int) $cooldownRemaining; ?>;
        const tick = () => {
            if (remaining > 0) {
                remaining--;
                resendBtn.textContent = 'Resend in ' + ('00:' + String(remaining).padStart(2,'0'));
                resendBtn.setAttribute('disabled','true');
                resendBtn.setAttribute('aria-disabled','true');
            } else {
                resendBtn.textContent = 'Resend OTP';
                resendBtn.removeAttribute('disabled');
                resendBtn.removeAttribute('aria-disabled');
                clearInterval(timer);
            }
        };
        if (remaining > 0) { var timer = setInterval(tick, 1000); }
        resendBtn.addEventListener('click', () => {
            if (resendBtn.hasAttribute('disabled')) return;
            document.getElementById('resendFormHidden').submit();
        });
    }
</script>
<?php if (function_exists('help_whatsapp_button')) { echo help_whatsapp_button(true); } ?>
</body>
</html>
